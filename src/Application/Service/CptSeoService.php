<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    WePresta <mail@wepresta.shop>
 * @copyright Since 2024 WePresta
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Entity\CptPost;
use WeprestaAcf\Domain\Entity\CptType;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * SEO Service for CPT
 * Generates meta tags, Open Graph, Schema.org
 */
final class CptSeoService
{
    /**
     * Generate meta tags for post
     */
    public function generateMetaTags(CptPost $post, CptType $type): array
    {
        $title = $post->getSeoTitle() ?: $post->getTitle();
        $description = $post->getSeoDescription() ?: $this->truncate($post->getTitle(), 160);

        $tags = [
            'title' => $this->parsePattern($type->getSeoConfig()['title_pattern'] ?? '{title}', $post),
            'description' => $description,
            'og:type' => 'article',
            'og:title' => $title,
            'og:description' => $description,
            'og:site_name' => \Configuration::get('PS_SHOP_NAME'),
            'twitter:card' => 'summary',
            'twitter:title' => $title,
            'twitter:description' => $description,
        ];

        return $tags;
    }

    /**
     * Generate Schema.org Article markup
     */
    public function generateSchemaOrg(CptPost $post, CptType $type): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->getTitle(),
            'datePublished' => $post->getDateAdd()?->format('c'),
            'dateModified' => $post->getDateUpd()?->format('c'),
            'author' => [
                '@type' => 'Person',
                'name' => \Configuration::get('PS_SHOP_NAME'),
            ],
        ];
    }

    /**
     * Parse pattern with post data
     */
    private function parsePattern(string $pattern, CptPost $post): string
    {
        $replacements = [
            '{title}' => $post->getTitle(),
            '{slug}' => $post->getSlug(),
            '{shop_name}' => \Configuration::get('PS_SHOP_NAME'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    /**
     * Truncate text
     */
    private function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 3) . '...';
    }
}
