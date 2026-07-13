<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Options as CsvReaderOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Options as CsvWriterOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ServiceImportController extends Controller
{
    private const HEADERS = [
        'nome',
        'descricao',
        'duracao_minutos',
        'preco',
        'pontos_fidelidade',
        'ciclo_dias',
        'categoria',
        'ativo',
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('import', [
            'file' => ['required', 'file', 'extensions:csv,xlsx', 'max:5120'],
        ], [
            'file.required' => 'Escolha uma planilha para importar.',
            'file.extensions' => 'Envie um arquivo no formato CSV ou XLSX.',
            'file.max' => 'A planilha deve ter no máximo 5 MB.',
        ]);

        $file = $validated['file'];
        $extension = Str::lower($file->getClientOriginalExtension());
        $reader = null;

        try {
            $reader = $extension === 'csv'
                ? new CsvReader(new CsvReaderOptions(FIELD_DELIMITER: $this->csvDelimiter($file->getRealPath())))
                : new XlsxReader;
            $reader->open($file->getRealPath());
            $rows = $this->readRows($reader);
        } catch (Throwable) {
            return back()->withErrors([
                'file' => 'Não consegui ler essa planilha. Baixe um exemplo e confira o formato antes de tentar novamente.',
            ], 'import');
        } finally {
            $reader?->close();
        }

        if ($rows === []) {
            return back()->withErrors(['file' => 'A planilha não possui serviços para importar.'], 'import');
        }

        if (count($rows) > 1000) {
            return back()->withErrors(['file' => 'Importe no máximo 1.000 serviços por planilha.'], 'import');
        }

        $services = [];
        $errors = [];

        foreach ($rows as $line => $row) {
            $data = [
                'name' => $row[0],
                'description' => $row[1] ?: null,
                'duration_minutes' => $row[2],
                'price' => $this->decimal($row[3]),
                'loyalty_points' => $row[4] === '' ? 0 : $row[4],
                'lifecycle_days' => $row[5] === '' ? null : $row[5],
                'category' => $row[6],
                'is_active' => $this->boolean($row[7]),
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
                'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
                'loyalty_points' => ['integer', 'min:0', 'max:999999'],
                'lifecycle_days' => ['nullable', 'integer', 'min:1', 'max:999'],
                'category' => ['required', 'string', 'max:80'],
                'is_active' => ['required', 'boolean'],
            ], [], [
                'name' => 'nome',
                'description' => 'descrição',
                'duration_minutes' => 'duração em minutos',
                'price' => 'preço',
                'loyalty_points' => 'pontos de fidelidade',
                'lifecycle_days' => 'ciclo em dias',
                'category' => 'categoria',
                'is_active' => 'ativo',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $index => $message) {
                    $errors["linha_{$line}_{$index}"] = "Linha {$line}: {$message}";
                }

                continue;
            }

            $services[] = $validator->validated();
        }

        if ($errors !== []) {
            return back()->withErrors($errors, 'import');
        }

        $tenant = $request->user()->tenants()->firstOrFail();

        DB::transaction(function () use ($tenant, $services): void {
            foreach ($services as $service) {
                $tenant->serviceCategories()->firstOrCreate(
                    ['name' => $service['category']],
                    ['slug' => Str::slug($service['category'])],
                );

                $tenant->services()->create([
                    ...$service,
                    'slug' => Str::slug($service['name']).'-'.Str::lower(Str::random(5)),
                ]);
            }
        });

        $message = count($services) === 1
            ? '1 serviço importado e pronto para agendamento.'
            : count($services).' serviços importados e prontos para agendamento.';

        return back()->with('status', $message);
    }

    public function example(string $format): BinaryFileResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $path = tempnam(storage_path('app'), 'servicos-exemplo-');
        $writer = $format === 'csv'
            ? new CsvWriter(new CsvWriterOptions(FIELD_DELIMITER: ';'))
            : new XlsxWriter;

        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(self::HEADERS));
        $writer->addRow(Row::fromValues([
            'Lavagem Premium',
            'Lavagem técnica completa com acabamento.',
            90,
            '149,90',
            15,
            30,
            'Lavagem',
            'sim',
        ]));
        $writer->close();

        return response()->download($path, "exemplo-servicos.{$format}")->deleteFileAfterSend(true);
    }

    private function readRows(CsvReader|XlsxReader $reader): array
    {
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                $values = array_map(static fn (mixed $value): string => trim((string) $value), $row->toArray());

                if ($index === 1) {
                    if ($values !== self::HEADERS) {
                        throw new \UnexpectedValueException('Invalid spreadsheet headers.');
                    }

                    continue;
                }

                $values = array_pad(array_slice($values, 0, count(self::HEADERS)), count(self::HEADERS), '');

                if (array_filter($values, static fn (string $value): bool => $value !== '') !== []) {
                    $rows[$index] = $values;
                }
            }

            break;
        }

        return $rows;
    }

    private function csvDelimiter(string $path): string
    {
        $stream = fopen($path, 'r');
        $firstLine = $stream ? (string) fgets($stream) : '';

        if ($stream) {
            fclose($stream);
        }

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    private function decimal(string $value): string
    {
        return str_contains($value, ',')
            ? str_replace(',', '.', str_replace('.', '', $value))
            : $value;
    }

    private function boolean(string $value): bool|string
    {
        return match (Str::lower($value)) {
            '1', 'sim', 'true', 'ativo' => true,
            '0', 'nao', 'não', 'false', 'inativo' => false,
            default => $value,
        };
    }
}
