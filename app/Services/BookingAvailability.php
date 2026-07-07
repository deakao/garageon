<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantHoliday;
use App\Models\TenantOperatingHour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Disponibilidade e criação de agendamentos da loja.
 *
 * Fonte única de verdade compartilhada entre a agenda pública (landing),
 * o dashboard e o atendente virtual (tools de IA). Antes vivia como closures
 * inacessíveis em routes/web.php.
 */
class BookingAvailability
{
    /**
     * Monta a grade de serviços x dias x horários livres para os próximos dias.
     *
     * @return array<string, mixed>
     */
    public function forTenant(Tenant $tenant, int $windowDays = 30): array
    {
        $now = now();
        $startsAt = $now->copy()->startOfDay();
        $endsAt = $now->copy()->addDays($windowDays)->endOfDay();
        $operatingHours = $tenant->operatingHours()->get()->keyBy('day_of_week');
        $holidays = $tenant->holidays()->get();
        $appointments = $tenant->appointments()
            ->whereBetween('scheduled_at', [$startsAt, $endsAt])
            ->whereNotIn('status', ['cancelled', 'canceled'])
            ->get(['scheduled_at', 'ends_at']);
        $services = $tenant->services()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'generated_at' => $now->toIso8601String(),
            'timezone' => config('app.timezone'),
            'services' => $services->mapWithKeys(fn (Service $service): array => [
                $service->id => $this->serviceAvailability($service, $now, $windowDays, $operatingHours, $holidays, $appointments),
            ])->all(),
        ];
    }

    /**
     * Verifica se um horário específico ainda está livre para o serviço.
     */
    public function slotIsAvailable(Tenant $tenant, Service $service, string $date, string $time): bool
    {
        $serviceAvailability = $this->forTenant($tenant)['services'][$service->id] ?? null;

        if (! $serviceAvailability) {
            return false;
        }

        foreach ($serviceAvailability['days'] as $day) {
            if ($day['date'] !== $date) {
                continue;
            }

            return collect($day['times'])->contains(fn (array $slot) => $slot['value'] === $time);
        }

        return false;
    }

    /**
     * Cria o agendamento após validar o slot. Retorna null se o horário caiu.
     *
     * @param  array<string, mixed>  $attributes  source, notes, vehicle_id, status
     */
    public function book(Tenant $tenant, Service $service, Customer $customer, string $date, string $time, array $attributes = []): ?Appointment
    {
        if (! $this->slotIsAvailable($tenant, $service, $date, $time)) {
            return null;
        }

        $scheduledAt = Carbon::parse($date.' '.$time);

        return Appointment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'vehicle_id' => $attributes['vehicle_id'] ?? null,
            'source' => $attributes['source'] ?? 'manual',
            'status' => $attributes['status'] ?? 'scheduled',
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes($service->duration_minutes),
            'notes' => $attributes['notes'] ?? null,
        ]);
    }

    /**
     * @param  Collection<int, TenantOperatingHour>  $operatingHours
     * @param  Collection<int, TenantHoliday>  $holidays
     * @param  Collection<int, Appointment>  $appointments
     * @return array<string, mixed>
     */
    private function serviceAvailability(Service $service, Carbon $now, int $windowDays, Collection $operatingHours, Collection $holidays, Collection $appointments): array
    {
        $days = [];

        for ($offset = 0; $offset < $windowDays; $offset++) {
            $date = $now->copy()->startOfDay()->addDays($offset);
            $holiday = $holidays->first(fn (TenantHoliday $holiday) => $holiday->repeats_yearly
                ? $holiday->date->format('m-d') === $date->format('m-d')
                : $holiday->date->isSameDay($date));

            if ($holiday) {
                continue;
            }

            $dayHour = $operatingHours->get($date->dayOfWeek);
            $isClosed = $dayHour?->is_closed ?? $date->dayOfWeek === Carbon::SUNDAY;

            if ($isClosed) {
                continue;
            }

            $opensAt = $dayHour?->opens_at ? substr((string) $dayHour->opens_at, 0, 5) : '08:00';
            $closesAt = $dayHour?->closes_at ? substr((string) $dayHour->closes_at, 0, 5) : '18:00';
            $slot = Carbon::parse($date->toDateString().' '.$opensAt);
            $close = Carbon::parse($date->toDateString().' '.$closesAt);
            $times = [];

            while ($slot->copy()->addMinutes($service->duration_minutes)->lessThanOrEqualTo($close)) {
                $slotEnd = $slot->copy()->addMinutes($service->duration_minutes);
                $tooSoon = $slot->lessThan($now->copy()->addMinutes(30));
                $hasConflict = $appointments->contains(fn (Appointment $appointment) => $slot->lessThan($appointment->ends_at) && $slotEnd->greaterThan($appointment->scheduled_at));

                if (! $tooSoon && ! $hasConflict) {
                    $times[] = [
                        'value' => $slot->format('H:i'),
                        'label' => $slot->format('H\hi'),
                    ];
                }

                $slot->addMinutes(30);
            }

            if ($times !== []) {
                $days[] = [
                    'date' => $date->toDateString(),
                    'day' => $date->format('d'),
                    'weekday' => Str::upper($date->translatedFormat('D')),
                    'month_label' => Str::ucfirst($date->translatedFormat('F Y')),
                    'date_label' => Str::ucfirst($date->translatedFormat('l, d/m')),
                    'times' => $times,
                ];
            }
        }

        return [
            'id' => $service->id,
            'name' => $service->name,
            'category' => $service->category,
            'duration' => $service->duration_minutes,
            'price' => 'R$ '.number_format((float) $service->price, 2, ',', '.'),
            'days' => $days,
        ];
    }
}
