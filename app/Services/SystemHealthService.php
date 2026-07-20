<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Contribution;
use App\Models\Poi;
use App\Models\User;
use App\Support\SafeMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemHealthService
{
    public function snapshot(): array
    {
        $storagePath = storage_path();
        $publicStorage = public_path('storage');

        return [
            'application' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
                'debug' => (bool) config('app.debug'),
                'locale' => config('app.locale'),
            ],
            'runtime' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'mail' => [
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'resend_configured' => filled(config('services.resend.key')),
            ],
            'database' => [
                'connected' => $this->databaseConnected(),
                'driver' => config('database.default'),
            ],
            'filesystem' => [
                'storage_writable' => is_writable($storagePath),
                'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
                'public_storage_linked' => is_link($publicStorage) || is_dir($publicStorage),
                'storage_free_mb' => $this->diskFreeMb($storagePath),
                'app_free_mb' => $this->diskFreeMb(base_path()),
            ],
            'queue' => [
                'connection' => config('queue.default'),
                'failed_jobs' => $this->failedJobsCount(),
            ],
            'logs' => [
                'laravel_log_mb' => $this->logFileSizeMb(),
            ],
            'stats' => [
                'users' => User::query()->count(),
                'users_unverified' => User::query()->whereNull('email_verified_at')->count(),
                'pois_published' => Poi::query()->published()->count(),
                'contributions_pending' => Contribution::query()->pending()->count(),
            ],
            'backup' => AppSetting::getValue('system.last_backup'),
            'last_mail_error' => SafeMail::lastError(),
            'health_url' => url('/up'),
        ];
    }

    private function databaseConnected(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function diskFreeMb(string $path): ?float
    {
        $free = @disk_free_space($path);

        return $free !== false ? round($free / 1024 / 1024, 1) : null;
    }

    private function logFileSizeMb(): ?float
    {
        $log = storage_path('logs/laravel.log');

        if (! File::exists($log)) {
            return null;
        }

        return round(File::size($log) / 1024 / 1024, 2);
    }

    public static function recordBackup(string $databasePath, string $type = 'manual', ?string $storagePath = null): void
    {
        AppSetting::setValue('system.last_backup', [
            'at' => now()->toIso8601String(),
            'type' => $type,
            'database' => $databasePath,
            'storage' => $storagePath,
        ]);
    }
}
