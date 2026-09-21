<?php

namespace App\Console\Commands;

use App\Repositories\SitemapRepository;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate public/sitemap.xml for search engines';

    public function handle(SitemapRepository $sitemapRepository): int
    {
        $path = $sitemapRepository->generateSitemap();

        $this->components->info("Sitemap generated: {$path}");

        return self::SUCCESS;
    }
}
