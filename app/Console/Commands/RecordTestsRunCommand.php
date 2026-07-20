<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use Illuminate\Console\Command;

class RecordTestsRunCommand extends Command
{
    protected $signature = 'pgspot:record-tests {--passed= : Test passati} {--total= : Test totali}';

    protected $description = 'Registra l\'ultima esecuzione della suite PHPUnit nel monitoraggio';

    public function handle(): int
    {
        AppSetting::setValue('system.last_tests_run', [
            'at' => now()->toIso8601String(),
            'passed' => $this->option('passed') ? (int) $this->option('passed') : null,
            'total' => $this->option('total') ? (int) $this->option('total') : null,
        ]);

        $this->info('Ultima esecuzione test registrata.');

        return self::SUCCESS;
    }
}
