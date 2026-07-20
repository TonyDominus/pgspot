<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\Poi;
use App\Support\SiteFeatures;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            $this->entry(route('home'), now()->toAtomString(), 'daily', '1.0'),
            $this->entry(route('poi.index'), now()->toAtomString(), 'daily', '0.9'),
            $this->entry(route('routes'), now()->toAtomString(), 'weekly', '0.8'),
        ];

        if (SiteFeatures::eventsPublicEnabled()) {
            $urls[] = $this->entry(route('events.index'), now()->toAtomString(), 'weekly', '0.7');
        }

        foreach (['privacy', 'termini', 'cookie', 'contatti'] as $page) {
            $urls[] = $this->entry(route('legal.show', $page), now()->toAtomString(), 'monthly', '0.3');
        }

        Poi::query()->published()->orderByDesc('updated_at')->each(function (Poi $poi) use (&$urls) {
            $urls[] = $this->entry(
                route('poi.show', $poi->slug),
                $poi->updated_at?->toAtomString() ?? now()->toAtomString(),
                'weekly',
                '0.8',
            );
        });

        Itinerary::query()->published()->orderByDesc('updated_at')->each(function (Itinerary $itinerary) use (&$urls) {
            $urls[] = $this->entry(
                route('itineraries.show', $itinerary->slug),
                $itinerary->updated_at?->toAtomString() ?? now()->toAtomString(),
                'monthly',
                '0.7',
            );
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', $urls)
            .'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function entry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return '<url>'
            .'<loc>'.htmlspecialchars($loc, ENT_XML1).'</loc>'
            .'<lastmod>'.htmlspecialchars($lastmod, ENT_XML1).'</lastmod>'
            .'<changefreq>'.$changefreq.'</changefreq>'
            .'<priority>'.$priority.'</priority>'
            .'</url>';
    }
}
