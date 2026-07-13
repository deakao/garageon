<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ServiceImportTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_tenant_user_can_import_csv_and_xlsx_services(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $csv = "\xEF\xBB\xBF".implode(';', self::HEADERS)."\n"
            .'Lavagem Premium;Lavagem técnica completa;90;149,90;15;30;Lavagem;sim';

        $this->actingAs($user)
            ->post(route('settings.services.import'), [
                'file' => UploadedFile::fake()->createWithContent('servicos.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', '1 serviço importado e pronto para agendamento.');

        $xlsxPath = tempnam(storage_path('app'), 'services-test-');
        $writer = new Writer;
        $writer->openToFile($xlsxPath);
        $writer->addRow(Row::fromValues(self::HEADERS));
        $writer->addRow(Row::fromValues([
            'Vitrificação 9H',
            'Proteção cerâmica de pintura.',
            480,
            1890,
            100,
            180,
            'Proteção',
            '1',
        ]));
        $writer->close();

        $this->post(route('settings.services.import'), [
            'file' => UploadedFile::fake()->createWithContent('servicos.xlsx', file_get_contents($xlsxPath)),
        ])->assertRedirect()->assertSessionHasNoErrors();

        unlink($xlsxPath);

        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'name' => 'Lavagem Premium',
            'price' => 149.90,
            'category' => 'Lavagem',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('services', [
            'tenant_id' => $tenant->id,
            'name' => 'Vitrificação 9H',
            'category' => 'Proteção',
        ]);
        $this->assertDatabaseHas('tenant_service_categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Proteção',
        ]);
    }

    public function test_invalid_row_prevents_partial_import(): void
    {
        [$tenant, $user] = $this->createTenantUser();

        $csv = implode(';', self::HEADERS)."\n"
            ."Lavagem Premium;;90;149,90;15;30;Lavagem;sim\n"
            .'Serviço inválido;;5;abc;0;;Lavagem;sim';

        $this->actingAs($user)
            ->post(route('settings.services.import'), [
                'file' => UploadedFile::fake()->createWithContent('servicos.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(null, null, 'import');

        $this->assertDatabaseMissing('services', ['tenant_id' => $tenant->id]);
    }

    public function test_tenant_user_can_download_both_examples(): void
    {
        [, $user] = $this->createTenantUser();

        foreach (['csv', 'xlsx'] as $format) {
            $this->actingAs($user)
                ->get(route('settings.services.example', $format))
                ->assertOk()
                ->assertDownload("exemplo-servicos.{$format}");
        }
    }

    /**
     * @return array{Tenant, User}
     */
    private function createTenantUser(): array
    {
        $tenant = Tenant::create([
            'name' => 'Carbon Studio Detail',
            'slug' => 'carbon-studio',
        ]);

        $user = User::factory()->create(['is_platform_admin' => false]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return [$tenant, $user];
    }
}
