<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Poi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SmokeTestService
{
    /** @return array{ok: bool, passed: int, warnings: int, failed: int, checks: list<array{key: string, label: string, status: string, message: string}>} */
    public function run(): array
    {
        $checks = [
            $this->checkCondition('env_production', 'Ambiente production', config('app.env') === 'production', 'APP_ENV='.config('app.env'), 'warn'),
            $this->checkBool('debug_off', 'Debug disattivato', ! config('app.debug'), 'APP_DEBUG=true — disattiva in produzione'),
            $this->checkCondition('https_url', 'APP_URL HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'Usa APP_URL=https://pgspot.it', 'warn'),
            $this->checkBool('database', 'Database connesso', $this->databaseConnected(), 'Connessione MySQL fallita'),
            $this->checkBool('storage', 'storage/ scrivibile', is_writable(storage_path()), 'Permessi storage'),
            $this->checkBool('resend', 'Resend configurato', filled(config('services.resend.key')), 'RESEND_API_KEY nel .env'),
            $this->checkHttp('page_home', 'Homepage (/)', url('/'), 200),
            $this->checkHttp('page_up', 'Health check (/up)', url('/up'), 200),
            $this->checkHttpContains('page_sitemap', 'Sitemap XML', url('/sitemap.xml'), 200, '<urlset'),
            $this->checkHttpContains('page_robots', 'Robots.txt', url('/robots.txt'), 200, 'Sitemap:'),
            $this->checkHttp('page_login', 'Login', url('/login'), 200),
            $this->checkHttp('page_lista', 'Lista POI', url('/lista'), 200),
            $this->checkCondition(
                'pois_published',
                'POI pubblicati presenti',
                Poi::query()->published()->exists(),
                'Nessun POI pubblicato — ok per soft launch, verifica contenuti',
                'warn',
            ),
            $this->checkCondition(
                'legal_privacy',
                'Privacy policy compilata',
                filled($this->legalBody('legal.privacy')),
                'Testo privacy vuoto in Admin → Impostazioni',
                'warn',
            ),
            $this->checkCondition(
                'ga_configured',
                'Google Analytics ID',
                filled(AppSetting::getValue('site.analytics', [])['ga_id'] ?? null),
                'Measurement ID non impostato in Impostazioni',
                'warn',
            ),
            $this->checkBackupRecent(),
        ];

        $failed = collect($checks)->where('status', 'fail')->count();
        $warnings = collect($checks)->where('status', 'warn')->count();
        $passed = collect($checks)->where('status', 'pass')->count();

        $result = [
            'at' => now()->toIso8601String(),
            'ok' => $failed === 0,
            'passed' => $passed,
            'warnings' => $warnings,
            'failed' => $failed,
            'checks' => $checks,
        ];

        AppSetting::setValue('system.last_smoke_test', $result);

        return $result;
    }

    public static function lastResult(): ?array
    {
        $value = AppSetting::getValue('system.last_smoke_test');

        return is_array($value) ? $value : null;
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function check(string $key, string $label, bool $condition, string $failMessage, string $failStatus = 'fail'): array
    {
        if ($condition) {
            return $this->pass($key, $label);
        }

        return [
            'key' => $key,
            'label' => $label,
            'status' => $failStatus,
            'message' => $failMessage,
        ];
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function checkBool(string $key, string $label, bool $ok, string $failMessage): array
    {
        return $ok ? $this->pass($key, $label) : $this->fail($key, $label, $failMessage);
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function checkCondition(string $key, string $label, bool $ok, string $warnMessage, string $status = 'warn'): array
    {
        return $ok ? $this->pass($key, $label) : $this->check($key, $label, false, $warnMessage, $status);
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function checkHttp(string $key, string $label, string $url, int $expectedStatus): array
    {
        try {
            $response = Http::timeout(15)->get($url);

            if ($response->status() === $expectedStatus) {
                return $this->pass($key, $label, "HTTP {$response->status()}");
            }

            return $this->fail($key, $label, "HTTP {$response->status()} su {$url}");
        } catch (\Throwable $e) {
            return $this->fail($key, $label, $e->getMessage());
        }
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function checkHttpContains(string $key, string $label, string $url, int $expectedStatus, string $needle): array
    {
        try {
            $response = Http::timeout(15)->get($url);

            if ($response->status() !== $expectedStatus) {
                return $this->fail($key, $label, "HTTP {$response->status()} su {$url}");
            }

            if (! str_contains($response->body(), $needle)) {
                return $this->fail($key, $label, "Risposta inattesa (manca «{$needle}»)");
            }

            return $this->pass($key, $label, 'OK');
        } catch (\Throwable $e) {
            return $this->fail($key, $label, $e->getMessage());
        }
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function checkBackupRecent(): array
    {
        $backup = AppSetting::getValue('system.last_backup');

        if (! is_array($backup) || empty($backup['at'])) {
            return $this->check('backup_recent', 'Backup recente', false, 'Nessun backup registrato — configura cron', 'warn');
        }

        $at = \Carbon\Carbon::parse($backup['at']);

        if ($at->lt(now()->subDays(8))) {
            return $this->check('backup_recent', 'Backup recente', false, 'Ultimo backup oltre 8 giorni fa', 'warn');
        }

        return $this->pass('backup_recent', 'Backup recente', 'Ultimo: '.$at->timezone('Europe/Rome')->format('d/m/Y H:i'));
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

    /** @return array{key: string, label: string, status: string, message: string} */
    private function pass(string $key, string $label, string $message = 'OK'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => 'pass',
            'message' => $message,
        ];
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function fail(string $key, string $label, string $message): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => 'fail',
            'message' => $message,
        ];
    }

    private function legalBody(string $key): string
    {
        $raw = AppSetting::getValue($key);

        return is_array($raw) ? trim((string) ($raw['body'] ?? '')) : trim((string) ($raw ?? ''));
    }
}
