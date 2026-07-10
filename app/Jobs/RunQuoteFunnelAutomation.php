<?php

namespace App\Jobs;

use App\Models\Quote;
use App\Models\QuoteFunnelAutomation;
use App\Services\QuoteFunnelAutomationRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunQuoteFunnelAutomation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public readonly Quote $quote,
        public readonly QuoteFunnelAutomation $automation,
    ) {}

    public function handle(QuoteFunnelAutomationRunner $runner): void
    {
        $runner->run($this->quote, $this->automation);
    }
}
