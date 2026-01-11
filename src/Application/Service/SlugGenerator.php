<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

final class SlugGenerator
{
    public function generate(string $input): string
    {
        $slug = mb_strtolower($input, 'UTF-8');
        $slug = preg_replace('/[àáâãäåāăą]/u', 'a', $slug) ?? $slug;
        $slug = preg_replace('/[èéêëēėę]/u', 'e', $slug) ?? $slug;
        $slug = preg_replace('/[ìíîïīį]/u', 'i', $slug) ?? $slug;
        $slug = preg_replace('/[òóôõöøōő]/u', 'o', $slug) ?? $slug;
        $slug = preg_replace('/[ùúûüūůűų]/u', 'u', $slug) ?? $slug;
        $slug = preg_replace('/[ýÿ]/u', 'y', $slug) ?? $slug;
        $slug = preg_replace('/[ñń]/u', 'n', $slug) ?? $slug;
        $slug = preg_replace('/[çćč]/u', 'c', $slug) ?? $slug;
        $slug = preg_replace('/[ß]/u', 'ss', $slug) ?? $slug;
        $slug = preg_replace('/[æ]/u', 'ae', $slug) ?? $slug;
        $slug = preg_replace('/[œ]/u', 'oe', $slug) ?? $slug;
        $slug = preg_replace('/[^a-z0-9\-_\s]/u', '', $slug) ?? $slug;
        $slug = preg_replace('/[\s\-]+/', '_', $slug) ?? $slug;
        $slug = preg_replace('/^_+|_+$/', '', $slug) ?? $slug;

        return substr($slug, 0, 255) ?: 'field';
    }

    public function generateUnique(string $input, callable $existsChecker, ?int $excludeId = null): string
    {
        $baseSlug = $this->generate($input);

        if (! $existsChecker($baseSlug, $excludeId)) {
            return $baseSlug;
        }
        $counter = 2;

        while ($counter < 100) {
            $testSlug = $baseSlug . '_' . $counter;

            if (! $existsChecker($testSlug, $excludeId)) {
                return $testSlug;
            }
            ++$counter;
        }

        return $baseSlug . '_' . time();
    }
}
