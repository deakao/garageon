<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\AttendantUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendantUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_limit_comes_from_plan_and_falls_back_without_plan(): void
    {
        $usage = app(AttendantUsage::class);

        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'monthly_price' => 197, 'ai_daily_message_limit' => 200]);
        $withPlan = Tenant::create(['name' => 'A', 'slug' => 'a', 'plan_id' => $plan->id])->load('plan');
        $noPlan = Tenant::create(['name' => 'B', 'slug' => 'b']);

        $this->assertSame(200, $usage->limitFor($withPlan));
        $this->assertSame(50, $usage->limitFor($noPlan));
    }

    public function test_counts_and_blocks_when_limit_reached(): void
    {
        $usage = app(AttendantUsage::class);
        $plan = Plan::create(['name' => 'Mini', 'slug' => 'mini', 'monthly_price' => 10, 'ai_daily_message_limit' => 2]);
        $tenant = Tenant::create(['name' => 'A', 'slug' => 'a', 'plan_id' => $plan->id])->load('plan');

        $this->assertFalse($usage->hasReachedLimit($tenant));
        $usage->record($tenant);
        $this->assertSame(1, $usage->usedToday($tenant));
        $this->assertSame(1, $usage->remainingToday($tenant));

        $usage->record($tenant);
        $this->assertTrue($usage->hasReachedLimit($tenant));
        $this->assertSame(0, $usage->remainingToday($tenant));
    }

    public function test_counter_resets_next_day(): void
    {
        $usage = app(AttendantUsage::class);
        $plan = Plan::create(['name' => 'Mini', 'slug' => 'mini', 'monthly_price' => 10, 'ai_daily_message_limit' => 2]);
        $tenant = Tenant::create(['name' => 'A', 'slug' => 'a', 'plan_id' => $plan->id])->load('plan');

        Carbon::setTestNow('2026-07-06 23:00:00');
        $usage->record($tenant);
        $usage->record($tenant);
        $this->assertTrue($usage->hasReachedLimit($tenant));

        Carbon::setTestNow('2026-07-07 00:05:00');
        $this->assertSame(0, $usage->usedToday($tenant));
        $this->assertFalse($usage->hasReachedLimit($tenant));

        Carbon::setTestNow();
    }
}
