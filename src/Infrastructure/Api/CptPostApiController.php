<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
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
        private readonly LocationProviderRegistry $locationProviderRegistry
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
                return [
                    'id' => $post->getId(),
                    'slug' => $post->getSlug(),
                    'title' => $post->getTitle(),
                    'status' => $post->getStatus(),
                    'view_url' => $this->urlService->getPostUrl($post, $type),
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
            $post = $this->repository->find($id, $this->context->getLangId(), $this->context->getShopId());
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
            $acfValues = $this->valueProvider->getEntityFieldValues(
                'cpt_post',
                $post->getId(),
                $this->context->getShopId(),
                $this->context->getLangId()
            );

            $data = [
                'id' => $post->getId(),
                'type_id' => $post->getTypeId(),
                'slug' => $post->getSlug(),
                'title' => $post->getTitle(),
                'seo_title' => $post->getSeoTitle(),
                'seo_description' => $post->getSeoDescription(),
                'status' => $post->getStatus(),
                'terms' => $post->getTerms(),
                'relations' => $relationsData,
                'acf_groups' => $acfGroupsData,
                'acf_values' => $acfValues,
                'view_url' => $type ? $this->urlService->getPostUrl($post, $type) : null,
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
            $languages = \Language::getLanguages(true);
            $translations = [];
            foreach ($languages as $lang) {
                $translations[(int) $lang['id_lang']] = [
                    'title' => $data['title'],
                    'seo_title' => $data['seo_title'] ?? $data['title'],
                    'seo_description' => $data['seo_description'] ?? '',
                ];
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

            // Update translations if title changed
            if (isset($data['title'])) {
                $languages = \Language::getLanguages(true);
                $translations = [];
                foreach ($languages as $lang) {
                    $translations[(int) $lang['id_lang']] = [
                        'title' => $data['title'],
                        'seo_title' => $data['seo_title'] ?? $data['title'],
                        'seo_description' => $data['seo_description'] ?? '',
                    ];
                }
                $post->setTranslations($translations);
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
                $fieldsData[] = [
                    'key' => 'field_' . $field['id_wepresta_acf_field'],
                    'id' => $field['id_wepresta_acf_field'],
                    'slug' => $field['slug'],
                    'type' => $field['type'],
                    'label' => $field['title'],
                    'title' => $field['title'],
                    'instructions' => $field['instructions'],
                    'required' => (bool) (json_decode($field['validation'] ?? '{}', true)['required'] ?? false),
                    'config' => json_decode($field['config'] ?? '{}', true),
                    'value_translatable' => (bool) ($field['value_translatable'] ?? false),
                ];
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
