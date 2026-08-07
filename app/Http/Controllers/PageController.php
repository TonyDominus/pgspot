<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Support\LegalDefaults;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function legal(string $page): Response
    {
        $pages = [
            'privacy' => ['title' => 'Privacy Policy', 'key' => 'legal.privacy'],
            'termini' => ['title' => 'Termini di utilizzo', 'key' => 'legal.terms'],
            'cookie' => ['title' => 'Cookie Policy', 'key' => 'legal.cookies'],
            'contatti' => ['title' => 'Contatti', 'key' => 'legal.contact'],
        ];

        abort_unless(isset($pages[$page]), 404);

        $meta = $pages[$page];
        $content = $this->resolveContent($meta['key'], $page);

        return Inertia::render('Legal/Show', [
            'title' => $meta['title'],
            'content' => $content,
        ]);
    }

    private function resolveContent(string $settingKey, string $page): string
    {
        $raw = AppSetting::getValue($settingKey);
        $body = is_array($raw) ? trim((string) ($raw['body'] ?? '')) : trim((string) ($raw ?? ''));

        if ($body !== '') {
            return $body;
        }

        return LegalDefaults::body($page);
    }
}
