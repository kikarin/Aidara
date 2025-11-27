<?php

namespace App\Repositories;

use App\Traits\RepositoryTrait;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapRepository
{
    use RepositoryTrait;

    public function __construct()
    {
    }

    public function generateSitemap()
    {
        $sitemap   = Sitemap::create();
        $baseUrl   = url('');
        $endpoints = [
        ];
        foreach ($endpoints as $endpoint) {
            $sitemap->add(
                Url::create($baseUrl.$endpoint)
                    ->setLastModificationDate(now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(0.8)
            );
        }
        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
