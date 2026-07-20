<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;

class RecordBackupCommand extends Command
{
    protected $signature = 'pgspot:record-backup {database} {--storage= : Percorso backup foto} {--type=manual : Tipo backup (manual|cron)}';

    protected $description = 'Registra nel database l\'ultimo backup completato';

    public function handle(): int
    {
        SystemHealthService::recordBackup(
            $this->argument('database'),
            $this->option('type'),
            $this->option('storage') ?: null,
        );

        $this->info('Backup registrato.');

        return self::SUCCESS;
    }
}
