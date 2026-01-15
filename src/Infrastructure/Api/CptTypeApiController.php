<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Domain\Repository\CptTypeRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Wedev\Core\Adapter\ConfigurationAdapter;
use WeprestaAcf\Wedev\Core\Adapter\ContextAdapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class CptTypeApiController extends AbstractApiController
{
    private CptTypeRepositoryInterface $repository;
    private AcfGroupRepositoryInterface $groupRepository;
    private AcfFieldRepositoryInterface $fieldRepository;
    private \WeprestaAcf\Application\Service\CptUrlService $urlService;

    public function __construct(
        CptTypeRepositoryInterface $repository,
        \WeprestaAcf\Application\Service\CptUrlService $urlService,
        ConfigurationAdapter $config,
        ContextAdapter $context,
        AcfGroupRepositoryInterface $groupRepository,
        AcfFieldRepositoryInterface $fieldRepository
    ) {
        parent::__construct($config, $context);
        $this->repository = $repository;
        $this->urlService = $urlService;
        $this->groupRepository = $groupRepository;
        $this->fieldRepository = $fieldRepository;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $langId = (int) $this->context->getLangId();
            $types = $this->repository->findAll();
            $data = array_map(function ($type) use ($langId) {
                return [
                    'id' => $type->getId(),
                    'slug' => $type->getSlug(),
                    'name' => $type->getName($langId),
                    'url_prefix' => $type->getUrlPrefix(),
                    'has_archive' => $type->hasArchive(),
                    'active' => $type->isActive(),
                    'view_url' => $type->hasArchive() ? $this->urlService->getFriendlyUrl($type) : null,
                ];
            }, $types);
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            // In the builder, we ALWAYS want ALL translations
            $type = $this->repository->findFull($id, null, $this->context->getShopId());
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $data = [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName(),
                'description' => $type->getDescription(),
                'config' => $type->getConfig(),
                'url_prefix' => $type->getUrlPrefix(),
                'has_archive' => $type->hasArchive(),
                'seo_config' => $type->getSeoConfig(),
                'active' => $type->isActive(),
                'acf_groups' => $type->getAcfGroups(),
                'acf_groups_full' => $this->getHydratedAcfGroups($type->getAcfGroups()),
                'taxonomies' => $type->getTaxonomies(),
                'translations' => $type->getTranslations(),
                'view_url' => $type->hasArchive() ? $this->urlService->getFriendlyUrl($type) : null,
            ];
            return $this->jsonSuccess($data);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data || empty($data['slug']) || empty($data['name'])) {
                return $this->jsonError('Invalid data', Response::HTTP_BAD_REQUEST);
            }
            if ($this->repository->slugExists($data['slug'])) {
                return $this->jsonError('Slug exists', Response::HTTP_CONFLICT);
            }
            $type = new \WeprestaAcf\Domain\Entity\CptType($data);
            $id = $this->repository->save($type, $this->context->getShopId());
            if (!empty($data['acf_groups'])) {
                $this->repository->syncGroups($id, $data['acf_groups']);
            }
            if (!empty($data['taxonomies'])) {
                $this->repository->syncTaxonomies($id, $data['taxonomies']);
            }
            return $this->jsonSuccess(['id' => $id], null, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $type = $this->repository->find($id);
            if (!$type) {
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->jsonError('Invalid JSON', Response::HTTP_BAD_REQUEST);
            }
            if (isset($data['name']))
                $type->setName($data['name']);
            if (isset($data['description']))
                $type->setDescription($data['description']);
            if (isset($data['slug']))
                $type->setSlug($data['slug']);
            if (isset($data['url_prefix']))
                $type->setUrlPrefix($data['url_prefix']);
            if (isset($data['has_archive']))
                $type->setHasArchive((bool) $data['has_archive']);
            if (isset($data['active']))
                $type->setActive((bool) $data['active']);
            if (isset($data['translations']) && is_array($data['translations'])) {
                $type->setTranslations($data['translations']);
            }
            $this->repository->save($type);
            if (isset($data['acf_groups'])) {
                $this->repository->syncGroups($id, $data['acf_groups']);
            }
            if (isset($data['taxonomies'])) {
                $this->repository->syncTaxonomies($id, $data['taxonomies']);
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
                return $this->jsonError('Type not found', Response::HTTP_NOT_FOUND);
            }
            $this->repository->delete($id);
            return $this->jsonSuccess(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getHydratedAcfGroups(?array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }

        $hydratedGroups = [];
        foreach ($groupIds as $id) {
            $groupId = (int) ($id['id_wepresta_acf_group'] ?? $id);
            if (!$groupId)
                continue;

            $group = $this->groupRepository->findById($groupId);
            if (!$group || empty($group['active']))
                continue;

            $fields = $this->fieldRepository->findByGroup($groupId);
            $fieldsData = [];
            foreach ($fields as $field) {
                $fieldId = (int) $field['id_wepresta_acf_field'];
                $translations = $this->fieldRepository->getFieldTranslations($fieldId);

                // Get current language iso code to find translation
                $currentLangId = $this->context->getLangId();
                // We need iso code because getFieldTranslations returns keyed by iso code
                $languages = \Language::getLanguages(true);
                $currentIso = 'en';
                foreach ($languages as $l) {
                    if ((int) $l['id_lang'] === $currentLangId) {
                        $currentIso = $l['iso_code'];
                        break;
                    }
                }

                $label = $field['title'];
                $instructions = $field['instructions'];
                if (isset($translations[$currentIso])) {
                    $label = !empty($translations[$currentIso]['title']) ? $translations[$currentIso]['title'] : $label;
                    $instructions = !empty($translations[$currentIso]['instructions']) ? $translations[$currentIso]['instructions'] : $instructions;
                }

                $fieldsData[] = [
                    'key' => 'field_' . $fieldId,
                    'id' => $fieldId,
                    'slug' => $field['slug'],
                    'type' => $field['type'],
                    'label' => $label,
                    'instructions' => $instructions,
                    'required' => (bool) (json_decode($field['validation'] ?? '{}', true)['required'] ?? false),
                    'config' => json_decode($field['config'] ?? '{}', true),
                    'value_translatable' => (bool) ($field['value_translatable'] ?? false),
                    'children' => $this->getFieldChildren($fieldId, $currentIso),
                ];
            }

            $hydratedGroups[] = [
                'id' => $groupId,
                'title' => $group['title'],
                'fields' => $fieldsData
            ];
        }

        return $hydratedGroups;
    }

    private function getFieldChildren(int $parentId, string $langIso): array
    {
        $children = $this->fieldRepository->findByParent($parentId);
        $childrenData = [];
        foreach ($children as $field) {
            $fieldId = (int) $field['id_wepresta_acf_field'];
            $translations = $this->fieldRepository->getFieldTranslations($fieldId);

            $label = $field['title'];
            $instructions = $field['instructions'];
            if (isset($translations[$langIso])) {
                $label = !empty($translations[$langIso]['title']) ? $translations[$langIso]['title'] : $label;
                $instructions = !empty($translations[$langIso]['instructions']) ? $translations[$langIso]['instructions'] : $instructions;
            }

            $childrenData[] = [
                'key' => 'field_' . $fieldId,
                'id' => $fieldId,
                'slug' => $field['slug'],
                'type' => $field['type'],
                'title' => $label,
                'label' => $label,
                'instructions' => $instructions,
                'required' => (bool) (json_decode($field['validation'] ?? '{}', true)['required'] ?? false),
                'config' => json_decode($field['config'] ?? '{}', true),
                'value_translatable' => (bool) ($field['value_translatable'] ?? false),
            ];
        }
        return $childrenData;
    }
}
