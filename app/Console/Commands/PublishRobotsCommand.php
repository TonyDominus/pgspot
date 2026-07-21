<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PublishRobotsCommand extends Command
{
    protected $signature = 'pgspot:publish-robots';

    protected $description = 'Genera public/robots.txt (Nginx lo serve come file statico)';

    public function handle(): int
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /profile',
            'Disallow: /contribuisci',
            'Disallow: /verify-email',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        $path = public_path('robots.txt');
        file_put_contents($path, implode("\n", $lines)."\n");

        $this->info("Scritto {$path}");

        return self::SUCCESS;
    }
}
