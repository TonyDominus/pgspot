<?php

namespace Tests\Feature;

use App\Enums\PoiStatus;
use App\Models\AppSetting;
use App\Models\Poi;
use App\Services\SmokeTestService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmokeTestServiceTest extends TestCase
{
    public function test_smoke_test_records_results(): void
    {
        config([
            'app.url' => 'https://pgspot.test',
            'app.env' => 'production',
            'app.debug' => false,
            'services.resend.key' => 're_test_key',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'robots.txt')) {
                return Http::response("Sitemap: https://pgspot.test/sitemap.xml\n", 200);
            }

            if (str_contains($url, 'sitemap.xml')) {
                return Http::response('<?xml version="1.0"?><urlset></urlset>', 200);
            }

            return Http::response('ok', 200);
        });

        Poi::query()->create([
            'name' => 'Test',
            'slug' => 'test-smoke',
            'latitude' => 43.11,
            'longitude' => 12.39,
            'status' => PoiStatus::Published,
        ]);

        AppSetting::setValue('legal.privacy', ['body' => 'Privacy test']);
        AppSetting::setValue('site.analytics', ['ga_id' => 'G-TEST12345']);
        AppSetting::setValue('system.last_backup', ['at' => now()->toIso8601String()]);
        AppSetting::setValue('legal.privacy', ['body' => 'Privacy policy di test per smoke test.']);

        $result = app(SmokeTestService::class)->run();

        $this->assertArrayHasKey('checks', $result);
        $this->assertSame(0, $result['failed'], json_encode($result['checks']));
        $this->assertNotNull(SmokeTestService::lastResult());
    }
}
