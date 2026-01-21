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

namespace WeprestaAcf\Application\Provider\EntityField;

use WeprestaAcf\Wedev\Extension\EntityFields\EntityFieldProviderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Entity Field Provider for CPT Posts
 * Registers 'cpt_post' as an entity type that can use ACF fields
 */
final class CptPostEntityFieldProvider implements EntityFieldProviderInterface
{
    public function getEntityType(): string
    {
        return 'cpt_post';
    }

    public function getEntityLabel(int $langId): string
    {
        return 'CPT Post';
    }

    public function getDisplayHooks(): array
    {
        // Hook pour afficher les champs dans le formulaire d'édition
        return ['displayAdminCptPostExtra'];
    }

    public function getActionHooks(): array
    {
        // Hooks de sauvegarde (gérés par le CptPostController)
        return [];
    }

    public function buildContext(int $entityId): array
    {
        // Construire le contexte pour les Location Rules
        try {
            $postRepository = \WeprestaAcf\Application\Service\AcfServiceContainer::get(
                'WeprestaAcf\Domain\Repository\CptPostRepositoryInterface'
            );

            if (!$postRepository) {
                return [
                    'entity_type' => 'cpt_post',
                    'entity_id' => $entityId,
                ];
            }

            $post = $postRepository->find($entityId);

            if (!$post) {
                return [
                    'entity_type' => 'cpt_post',
                    'entity_id' => $entityId,
                ];
            }

            // Get CPT type for slug
            $typeRepository = \WeprestaAcf\Application\Service\AcfServiceContainer::get(
                'WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface'
            );

            $cptTypeSlug = '';
            if ($typeRepository) {
                $type = $typeRepository->find($post->getTypeId());
                if ($type) {
                    $cptTypeSlug = $type->getSlug();
                }
            }

            return [
                'entity_type' => 'cpt_post',
                'entity_id' => $entityId,
                'cpt_type_id' => $post->getTypeId(),
                'cpt_type_slug' => $cptTypeSlug,
                'cpt_status' => $post->getStatus(),
                'cpt_terms' => $post->getTerms(),
            ];
        } catch (\Exception $e) {
            return [
                'entity_type' => 'cpt_post',
                'entity_id' => $entityId,
            ];
        }
    }

    public function getEntityIds(): array
    {
        // Retourne tous les IDs des posts CPT (pour indexation, recherche, etc.)
        try {
            $postRepository = \WeprestaAcf\Application\Service\AcfServiceContainer::get(
                'WeprestaAcf\Domain\Repository\CptPostRepositoryInterface'
            );

            if (!$postRepository) {
                return [];
            }

            // Récupérer tous les types CPT
            $typeRepository = \WeprestaAcf\Application\Service\AcfServiceContainer::get(
                'WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface'
            );

            if (!$typeRepository) {
                return [];
            }

            $types = $typeRepository->findAll();
            $allIds = [];

            foreach ($types as $type) {
                $posts = $postRepository->findByType($type->getId(), null, null, 1000, 0);
                foreach ($posts as $post) {
                    $allIds[] = $post->getId();
                }
            }

            return $allIds;
        } catch (\Exception $e) {
            return [];
        }
    }
}
