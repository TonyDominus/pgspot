<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
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

        $raw = AppSetting::getValue($meta['key'], ['body' => $this->defaultContent($page)]);
        $content = is_array($raw) ? ($raw['body'] ?? '') : (string) $raw;

        return Inertia::render('Legal/Show', [
            'title' => $meta['title'],
            'content' => $content,
        ]);
    }

    private function defaultContent(string $page): string
    {
        return match ($page) {
            'privacy' => 'Informativa privacy di PG Spot. Aggiorna questo testo dal pannello superadmin.',
            'termini' => 'Termini di utilizzo della piattaforma PG Spot.',
            'cookie' => 'Questo sito utilizza cookie tecnici necessari al funzionamento.',
            'contatti' => 'Per informazioni: info@pgspot.it',
            default => '',
        };
    }
}
