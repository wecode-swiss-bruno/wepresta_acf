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
