<?php

namespace App\Console\Commands;

use App\Services\SmokeTestService;
use Illuminate\Console\Command;

class SmokeTestCommand extends Command
{
    protected $signature = 'pgspot:smoke-test';

    protected $description = 'Esegue smoke test go-live (HTTP, DB, SEO, config)';

    public function handle(SmokeTestService $smoke): int
    {
        $this->info('Smoke test PG Spot...');

        $result = $smoke->run();

        foreach ($result['checks'] as $check) {
            $icon = match ($check['status']) {
                'pass' => '<fg=green>✓</>',
                'warn' => '<fg=yellow>!</>',
                default => '<fg=red>✗</>',
            };
            $this->line(" {$icon} {$check['label']}: {$check['message']}");
        }

        $this->newLine();
        $this->line("Passati: {$result['passed']} | Avvisi: {$result['warnings']} | Falliti: {$result['failed']}");

        if ($result['failed'] > 0) {
            $this->error('Smoke test fallito.');

            return self::FAILURE;
        }

        $this->info($result['warnings'] > 0 ? 'Smoke test ok con avvisi.' : 'Smoke test ok.');

        return self::SUCCESS;
    }
}
