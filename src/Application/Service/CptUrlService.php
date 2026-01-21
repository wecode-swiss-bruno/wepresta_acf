<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Service;

use WeprestaAcf\Domain\Entity\CptPost;
use WeprestaAcf\Domain\Entity\CptTerm;
use WeprestaAcf\Domain\Entity\CptType;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * URL Service for CPT
 * Generates front-office URLs
 */
final class CptUrlService
{
    /**
     * Get archive URL for type
     */
    public function getArchiveUrl(CptType $type): string
    {
        $prefix = $type->getUrlPrefix();
        $link = \Context::getContext()->link;

        return $link->getModuleLink(
            'wepresta_acf',
            'cptarchive',
            ['type' => $type->getSlug()]
        );
    }

    /**
     * Get single post URL
     */
    public function getPostUrl(CptPost $post, CptType $type): string
    {
        $link = \Context::getContext()->link;

        return $link->getModuleLink(
            'wepresta_acf',
            'cptsingle',
            [
                'type' => $type->getSlug(),
                'slug' => $post->getSlug(),
            ]
        );
    }

    /**
     * Get taxonomy term URL
     */
    public function getTermUrl(CptTerm $term, CptType $type): string
    {
        $link = \Context::getContext()->link;

        return $link->getModuleLink(
            'wepresta_acf',
            'cpttaxonomy',
            [
                'type' => $type->getSlug(),
                'taxonomy' => $term->getTaxonomyId(),
                'term' => $term->getSlug(),
            ]
        );
    }

    /**
     * Get friendly URL (if mod_rewrite enabled)
     */
    public function getFriendlyUrl(CptType $type, ?CptPost $post = null, ?CptTerm $term = null): string
    {
        $prefix = $type->getUrlPrefix();

        if ($post) {
            return '/' . $prefix . '/' . $post->getSlug();
        }

        if ($term) {
            return '/' . $prefix . '/' . $term->getSlug();
        }

        return '/' . $prefix;
    }
    /**
     * Get preview token for post
     */
    public function getPreviewToken(CptPost $post): string
    {
        return hash_hmac('sha256', (string) $post->getId(), _COOKIE_KEY_);
    }

    /**
     * Get preview URL for post
     */
    public function getPreviewUrl(CptPost $post, CptType $type): string
    {
        $token = $this->getPreviewToken($post);
        $url = $this->getFriendlyUrl($type, $post);

        // Append token
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        return $url . $separator . 'preview_token=' . $token;
    }
}
