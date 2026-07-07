<?php

namespace App\Repositories;

use App\Models\Event;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapRepository
{
    /** @var list<string> */
    private const PUBLIC_STATUSES = ['publish', 'selesai'];

    /** @var list<string> */
    private const LEGAL_SLUGS = ['terms', 'privacy', 'pdp'];

    public function generateSitemap(): string
    {
        $sitemap = Sitemap::create();

        $this->addUrl($sitemap, route('home'), 1.0, Url::CHANGE_FREQUENCY_WEEKLY);
        $this->addUrl($sitemap, route('event.public.index'), 0.9, Url::CHANGE_FREQUENCY_DAILY);
        $this->addUrl($sitemap, route('worldcup.index'), 0.7, Url::CHANGE_FREQUENCY_DAILY);

        foreach (self::LEGAL_SLUGS as $slug) {
            $this->addUrl($sitemap, route('legal.show', $slug), 0.5, Url::CHANGE_FREQUENCY_MONTHLY);
        }

        Event::query()
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->orderByDesc('updated_at')
            ->get(['id', 'updated_at'])
            ->each(function (Event $event) use ($sitemap): void {
                $this->addUrl(
                    $sitemap,
                    route('event.public.show', $event->id),
                    0.8,
                    Url::CHANGE_FREQUENCY_WEEKLY,
                    $event->updated_at,
                );
            });

        $path = public_path('sitemap.xml');
        $sitemap->writeToFile($path);

        return $path;
    }

    private function addUrl(
        Sitemap $sitemap,
        string $url,
        float $priority,
        string $changeFrequency,
        ?\DateTimeInterface $lastModified = null,
    ): void {
        $tag = Url::create($url)
            ->setPriority($priority)
            ->setChangeFrequency($changeFrequency);

        if ($lastModified !== null) {
            $tag->setLastModificationDate($lastModified);
        }

        $sitemap->add($tag);
    }
}
