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

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Application\Service\AutoSyncService;
use WeprestaAcf\Application\Service\ValueHandler;
use WeprestaAcf\Application\Service\ValueProvider;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptPostRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptRelationRepositoryInterface;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Post API Controller.
 *
 * Uses Location Rules to determine which ACF groups to display for each CPT type.
 */
final class CptPostApiController extends AbstractApiController
{
    public function __construct(
        private readonly CptPostRepositoryInterface $repository,
        private readonly CptTypeRepositoryInterface $typeRepository,
        private readonly CptRelationRepositoryInterface $relationRepository,
        private readonly \WeprestaAcf\Application\Service\CptUrlService $urlService,
        ConfigurationAdapter $config,
        ContextAdapter $context,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository,
        private readonly ValueProvider $valueProvider,
        private readonly ValueHandler $valueHandler,
        private readonly LocationProviderRegistry $locationProviderRegistry,
        private readonly AutoSyncService $autoSyncService
    ) {
        parent::__construct($config, $context);
    }

    public function listByType(string $typeSlug, Request $request): JsonResponse
    {
        try {
            $type = $this->typeRepository->findBySlug($typeSlug, $this->context->getLangId(), $this->context->getShopId());
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $limit = (int) $request->query->get('limit', 50);
            $offset = (int) $request->query->get('offset', 0);
            $status = $request->query->get('status');
            $search = $request->query->get('q');

            if ($search) {
                $posts = $this->repository->findByType($type->getId(), $this->context->getLangId(), $this->context->getShopId(), 1000);
                $posts = array_filter($posts, function ($post) use ($search) {
                    return stripos($post->getTitle(), $search) !== false;
                });
                $posts = array_slice($posts, 0, 50);
                $total = \count($posts);
            } else {
                $posts = $status === 'published'
                    ? $this->repository->findPublishedByType($type->getId(), $this->context->getLangId(), $this->context->getShopId(), $limit, $offset)
                    : $this->repository->findByType($type->getId(), $this->context->getLangId(), $this->context->getShopId(), $limit, $offset);

                $total = $this->repository->countByType($type->getId(), $this->context->getShopId(), $status);
            }

            $data = array_map(function ($post) use ($type) {
                $viewUrl = $post->isPublished()
                    ? $this->urlService->getFriendlyUrl($type, $post)
                    : $this->urlService->getPreviewUrl($post, $type);

                return [
                    'id' => $post->getId(),
                    'slug' => $post->getSlug(),
                    'title' => $post->getTitle(),
                    'status' => $post->getStatus(),
                    'view_url' => $viewUrl,
                ];
            }, $posts);

            return $this->jsonSuccess(['posts' => $data, 'total' => $total]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            // Pass null for langId to fetch all translations
            $post = $this->repository->find($id, null, $this->context->getShopId());
            if (!$post) {
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }

            $type = $this->typeRepository->find($post->getTypeId());

            // Fetch relations
            $relationsData = [];
            $definedRelations = $this->relationRepository->findBySourceType($post->getTypeId());

            foreach ($definedRelations as $relation) {
                $relatedPosts = $this->repository->findRelated($relation->getId(), $post->getId());
                $relationsData[$relation->getId()] = array_map(function ($p) {
                    return [
                        'id' => $p->getId(),
                        'title' => $p->getTitle(),
                        'slug' => $p->getSlug(),
                    ];
                }, $relatedPosts);
            }

            // Fetch ACF Groups using Location Rules
            $acfGroupsData = $this->getMatchingAcfGroups($type ? $type->getSlug() : '');

            // Fetch current ACF values
            $acfValues = $this->valueProvider->getEntityFieldValuesAllLanguagesIndexedById(
                'cpt_post',
                $post->getId(),
                $this->context->getShopId()
            );

            $data = [
                'id' => $post->getId(),
                'type_id' => $post->getTypeId(),
                'slug' => $post->getSlug(),
                'title' => $post->getTitle(),
                'seo_title' => $post->getSeoTitle(),
                'seo_description' => $post->getSeoDescription(),
                'status' => $post->getStatus(),
                'translations' => $post->getTranslations(),
                'terms' => $post->getTerms(),
                'relations' => $relationsData,
                'acf_groups' => $acfGroupsData,
                'acf_values' => $acfValues,
                'view_url' => ($type && $post->isPublished())
                    ? $this->urlService->getFriendlyUrl($type, $post)
                    : ($type ? $this->urlService->getPreviewUrl($post, $type) : null),
            ];

            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(string $typeSlug, Request $request): JsonResponse
    {
        try {
            $type = $this->typeRepository->findBySlug($typeSlug, $this->context->getLangId(), $this->context->getShopId());
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (!$data || empty($data['slug']) || empty($data['title'])) {
                return $this->jsonError('Invalid data', Response::HTTP_BAD_REQUEST);
            }
            if ($this->repository->slugExists($data['slug'], $type->getId())) {
                return $this->jsonError('Slug exists', Response::HTTP_CONFLICT);
            }
            $data['id_wepresta_acf_cpt_type'] = $type->getId();
            $data['id_employee'] = $this->context->getEmployeeId() ?: null;
            $post = new \WeprestaAcf\Domain\Entity\CptPost($data);

            // Handle translations
            $translations = [];
            if (isset($data['translations']) && is_array($data['translations'])) {
                foreach ($data['translations'] as $langId => $trans) {
                    $translations[(int) $langId] = [
                        'title' => $trans['title'] ?? $data['title'],
                        'seo_title' => $trans['seo_title'] ?? ($trans['title'] ?? $data['title']),
                        'seo_description' => $trans['seo_description'] ?? '',
                    ];
                }
            } else {
                // Fallback: Replicate title for all languages
                $languages = \Language::getLanguages(true);
                foreach ($languages as $lang) {
                    $translations[(int) $lang['id_lang']] = [
                        'title' => $data['title'],
                        'seo_title' => $data['seo_title'] ?? $data['title'],
                        'seo_description' => $data['seo_description'] ?? '',
                    ];
                }
            }
            $post->setTranslations($translations);

            $id = $this->repository->save($post, $this->context->getShopId());
            if (!empty($data['terms'])) {
                $this->repository->syncTerms($id, $data['terms']);
            }
            if (isset($data['relations'])) {
                foreach ($data['relations'] as $relationId => $targetPostIds) {
                    $this->repository->syncRelated($id, (int) $relationId, (array) $targetPostIds);
                }
            }
            if (isset($data['acf'])) {
                $shopId = $this->context->getShopId();
                $this->valueHandler->saveEntityFieldValues('cpt_post', $id, $data['acf'], $shopId);
            }

            // Trigger auto-sync
            $this->autoSyncService->markDirty();

            return $this->jsonSuccess(['id' => $id], null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $post = $this->repository->find($id);
            if (!$post) {
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->jsonError('Invalid JSON', Response::HTTP_BAD_REQUEST);
            }
            if (isset($data['title'])) {
                $post->setTitle($data['title']);
            }
            if (isset($data['slug'])) {
                $post->setSlug($data['slug']);
            }
            if (isset($data['status'])) {
                $post->setStatus($data['status']);
            }

            // Update translations
            if (isset($data['translations']) && is_array($data['translations'])) {
                $post->setTranslations($data['translations']);
            }

            $this->repository->save($post);
            if (isset($data['terms'])) {
                $this->repository->syncTerms($id, $data['terms']);
            }
            if (isset($data['relations'])) {
                foreach ($data['relations'] as $relationId => $targetPostIds) {
                    $this->repository->syncRelated($id, (int) $relationId, (array) $targetPostIds);
                }
            }
            if (isset($data['acf'])) {
                $shopId = $this->context->getShopId();
                $this->valueHandler->saveEntityFieldValues('cpt_post', $id, $data['acf'], $shopId);
            }

            // Trigger auto-sync
            $this->autoSyncService->markDirty();

            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            if (!$this->repository->find($id)) {
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }
            $this->repository->delete($id);

            // Trigger auto-sync
            $this->autoSyncService->markDirty();

            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changeStatus(int $id, Request $request): JsonResponse
    {
        try {
            $post = $this->repository->find($id);
            if (!$post) {
                return $this->jsonError('Post not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (empty($data['status'])) {
                return $this->jsonError('Status required', Response::HTTP_BAD_REQUEST);
            }
            $post->setStatus($data['status']);
            $this->repository->save($post);

            // Trigger auto-sync
            $this->autoSyncService->markDirty();

            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get ACF groups that match the Location Rules for a CPT type.
     *
     * @param string $cptTypeSlug The CPT type slug
     *
     * @return array<array<string, mixed>> Matching groups with their fields
     */
    private function getMatchingAcfGroups(string $cptTypeSlug): array
    {
        if (empty($cptTypeSlug)) {
            return [];
        }

        $shopId = $this->context->getShopId();

        // Build context for location rule matching
        $context = [
            'entity_type' => 'cpt_post',
            'cpt_type_slug' => $cptTypeSlug,
        ];

        // Get all active groups
        $allGroups = $this->groupRepository->findActiveGroups($shopId);

        if (empty($allGroups)) {
            return [];
        }

        $matchingGroups = [];

        foreach ($allGroups as $group) {
            $locationRules = json_decode($group['location_rules'] ?? '[]', true) ?: [];

            // Check if group matches location rules
            if (!$this->locationProviderRegistry->matchLocation($locationRules, $context)) {
                continue;
            }

            // Exclude global scope groups
            $foOptions = json_decode($group['fo_options'] ?? '{}', true);
            if (($foOptions['valueScope'] ?? 'entity') === 'global') {
                continue;
            }

            // Get fields for this group
            $groupId = (int) $group['id_wepresta_acf_group'];
            $fields = $this->fieldRepository->findByGroup($groupId);

            $fieldsData = [];
            foreach ($fields as $field) {
                $fieldId = (int) $field['id_wepresta_acf_field'];
                $fieldData = [
                    'key' => 'field_' . $fieldId,
                    'id' => $fieldId,
                    'slug' => $field['slug'],
                    'type' => $field['type'],
                    'label' => $field['title'],
                    'title' => $field['title'],
                    'instructions' => $field['instructions'],
                    'required' => (bool) (json_decode($field['validation'] ?? '{}', true)['required'] ?? false),
                    'config' => json_decode($field['config'] ?? '{}', true),
                    'value_translatable' => (bool) ($field['value_translatable'] ?? false),
                ];

                // Handle Repeater: fetch children (subfields)
                if ($field['type'] === 'repeater') {
                    $children = $this->fieldRepository->findByParent($fieldId);
                    $fieldData['children'] = array_map(function ($child) {
                        $childConfig = json_decode($child['config'] ?? '{}', true) ?: [];
                        return [
                            'id' => (int) $child['id_wepresta_acf_field'],
                            'slug' => $child['slug'],
                            'type' => $child['type'],
                            'label' => $child['title'],
                            'title' => $child['title'],
                            'instructions' => $child['instructions'] ?? '',
                            'config' => $childConfig,
                            'translatable' => (bool) ($child['value_translatable'] ?? $child['translatable'] ?? false),
                            'value_translatable' => (bool) ($child['value_translatable'] ?? false),
                        ];
                    }, $children);
                }

                $fieldsData[] = $fieldData;
            }

            $matchingGroups[] = [
                'id' => $groupId,
                'title' => $group['title'],
                'slug' => $group['slug'],
                'description' => $group['description'] ?? '',
                'fields' => $fieldsData,
            ];
        }

        return $matchingGroups;
    }
}
