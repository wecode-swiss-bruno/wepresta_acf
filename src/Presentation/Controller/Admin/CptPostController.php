<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use Context;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Provider\LocationProviderRegistry;
use WeprestaAcf\Application\Service\CptPostService;
use WeprestaAcf\Application\Service\CptTaxonomyService;
use WeprestaAcf\Application\Service\CptTypeService;
use WeprestaAcf\Application\Service\ValueHandler;
use WeprestaAcf\Application\Service\ValueProvider;
use WeprestaAcf\Domain\Repository\AcfFieldRepositoryInterface;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Post Controller - CRUD for CPT posts with ACF fields.
 *
 * Uses Location Rules to determine which ACF groups to display for each CPT type.
 */
final class CptPostController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly CptPostService $postService,
        private readonly CptTypeService $typeService,
        private readonly CptTaxonomyService $taxonomyService,
        private readonly ValueHandler $valueHandler,
        private readonly ValueProvider $valueProvider,
        private readonly LocationProviderRegistry $locationProviderRegistry,
        private readonly AcfGroupRepositoryInterface $groupRepository,
        private readonly AcfFieldRepositoryInterface $fieldRepository
    ) {
    }

    /**
     * List posts by type.
     */
    #[AdminSecurity("is_granted('read', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function list(string $typeSlug, Request $request): Response
    {
        $type = $this->typeService->getTypeBySlug($typeSlug);

        if (!$type) {
            $this->addFlash('error', $this->trans('CPT Type not found', 'Modules.Weprestaacf.Admin'));

            return $this->redirectToRoute('wepresta_acf_builder');
        }

        $limit = 50;
        $offset = (int) $request->query->get('offset', 0);

        $posts = $this->postService->getPostsByType($type->getId(), $limit, $offset);
        $total = $this->postService->countPostsByType($type->getId());

        return $this->render('@Modules/wepresta_acf/views/templates/admin/cpt-post-list.html.twig', [
            'layoutTitle' => $type->getName() . ' - ' . $this->trans('Posts', 'Modules.Weprestaacf.Admin'),
            'type' => [
                'id' => $type->getId(),
                'slug' => $type->getSlug(),
                'name' => $type->getName(),
            ],
            'posts' => $posts,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Create new post.
     */
    #[AdminSecurity("is_granted('create', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function create(string $typeSlug, Request $request): Response
    {
        $type = $this->typeService->getTypeBySlug($typeSlug);

        if (!$type) {
            $this->addFlash('error', $this->trans('CPT Type not found', 'Modules.Weprestaacf.Admin'));

            return $this->redirectToRoute('wepresta_acf_builder');
        }

        if ($request->isMethod('POST')) {
            return $this->handleSave(null, $type, $request);
        }

        // Get taxonomies
        $taxonomies = $this->taxonomyService->getTaxonomiesByType($type->getId());

        // Get ACF groups via Location Rules
        $acfGroups = $this->getMatchingAcfGroups($type->getSlug());

        // Get context data for proper field rendering
        $context = Context::getContext();
        $languages = \Language::getLanguages(false, $context->shop->id);
        $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
        $currentShopId = (int) $context->shop->id;
        $link = $context->link;
        $adminApiUrl = $link->getAdminLink('WeprestaAcfApi');

        return $this->render('@Modules/wepresta_acf/views/templates/admin/cpt-post-edit.html.twig', [
            'layoutTitle' => $this->trans('New', 'Modules.Weprestaacf.Admin') . ' - ' . $type->getName(),
            'type' => $this->serializeType($type, $acfGroups),
            'post' => null,
            'taxonomies' => $this->serializeTaxonomies($taxonomies),
            'acfValues' => [],
            'languages' => $languages,
            'defaultLanguageId' => $defaultLangId,
            'shopId' => $currentShopId,
            'apiUrl' => $adminApiUrl,
        ]);
    }

    /**
     * Edit existing post.
     */
    #[AdminSecurity("is_granted('update', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function edit(int $id, Request $request): Response
    {
        $post = $this->postService->getPostById($id);

        if (!$post) {
            $this->addFlash('error', $this->trans('Post not found', 'Modules.Weprestaacf.Admin'));

            return $this->redirectToRoute('wepresta_acf_builder');
        }

        $type = $this->typeService->getTypeBySlug($post->getTypeId());

        // Try by ID if slug lookup failed
        if (!$type) {
            $type = $this->typeService->getTypeById($post->getTypeId());
        }

        if (!$type) {
            $this->addFlash('error', $this->trans('CPT Type not found', 'Modules.Weprestaacf.Admin'));

            return $this->redirectToRoute('wepresta_acf_builder');
        }

        if ($request->isMethod('POST')) {
            return $this->handleSave($id, $type, $request);
        }

        // Get taxonomies
        $taxonomies = $this->taxonomyService->getTaxonomiesByType($type->getId());

        // Get ACF values
        $shopId = (int) $this->get('prestashop.adapter.legacy.context')->getContext()->shop->id;
        $acfValues = $this->valueProvider->getEntityFieldValues('cpt_post', $id, $shopId);

        // Get ACF groups via Location Rules
        $acfGroups = $this->getMatchingAcfGroups($type->getSlug());

        // Get context data for proper field rendering (Harmonization with Product/Entity pages)
        $context = Context::getContext();
        $languages = \Language::getLanguages(false, $context->shop->id);
        $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT');
        $currentShopId = (int) $context->shop->id;
        // Construct API URL similarly to EntityFieldService
        $link = $context->link;
        $apiUrl = $link->getAdminLink('WeprestaAcfApi', true, ['route' => 'wepresta_acf_api_relation_resolve']);
        // Clean up API URL to base if needed, or pass specific endpoints. 
        // For relation field, we usually need base API url or specific resolving route.
        // EntityFieldService passes `acf_api_base_url` which is .../module/wepresta_acf/api
        // Let's standarize on passing the resolve route for now as per Relation field needs, 
        // or better, pass the same base URL structure if possible.
        // Looking at EntityFieldService, it uses $link->getAdminLink('WeprestaAcfApi').
        $adminApiUrl = $link->getAdminLink('WeprestaAcfApi');

        return $this->render('@Modules/wepresta_acf/views/templates/admin/cpt-post-edit.html.twig', [
            'layoutTitle' => $this->trans('Edit', 'Modules.Weprestaacf.Admin') . ' - ' . $post->getTitle(),
            'type' => $this->serializeType($type, $acfGroups),
            'post' => $this->serializePost($post),
            'taxonomies' => $this->serializeTaxonomies($taxonomies),
            'acfValues' => $acfValues,
            'languages' => $languages,
            'defaultLanguageId' => $defaultLangId,
            'shopId' => $currentShopId,
            'apiUrl' => $adminApiUrl,
        ]);
    }

    /**
     * Delete post.
     */
    #[AdminSecurity("is_granted('delete', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function delete(int $id): Response
    {
        $post = $this->postService->getPostById($id);

        if (!$post) {
            $this->addFlash('error', $this->trans('Post not found', 'Modules.Weprestaacf.Admin'));

            return $this->redirectToRoute('wepresta_acf_builder');
        }

        $typeSlug = $this->typeService->getTypeById($post->getTypeId())?->getSlug();

        $this->postService->deletePost($id);

        $this->addFlash('success', $this->trans('Post deleted successfully', 'Modules.Weprestaacf.Admin'));

        return $this->redirectToRoute('wepresta_acf_cpt_posts_list', ['typeSlug' => $typeSlug]);
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
        $shopId = (int) Context::getContext()->shop->id;

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

            $matchingGroups[] = [
                'id_wepresta_acf_group' => $groupId,
                'id' => $groupId,
                'title' => $group['title'],
                'slug' => $group['slug'],
                'description' => $group['description'] ?? '',
                'fields' => $fields,
            ];
        }

        return $matchingGroups;
    }

    private function handleSave(?int $id, $type, Request $request): Response
    {
        $data = [
            'id_wepresta_acf_cpt_type' => $type->getId(),
            'title' => $request->request->get('title'),
            'slug' => $request->request->get('slug'),
            'status' => $request->request->get('status', 'draft'),
            'seo_title' => $request->request->get('seo_title'),
            'seo_description' => $request->request->get('seo_description'),
        ];

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->postService->generateUniqueSlug($data['title'], $type->getId(), $id);
        }

        // Create or update post
        if ($id) {
            $this->postService->updatePost($id, $data);
        } else {
            $id = $this->postService->createPost($data);
        }

        // Sync terms
        $terms = $request->request->all('terms') ?: [];
        $termIds = [];
        foreach ($terms as $taxonomyId => $selectedTerms) {
            $termIds = array_merge($termIds, is_array($selectedTerms) ? $selectedTerms : [$selectedTerms]);
        }
        $this->postService->syncTerms($id, $termIds);

        // Save ACF values
        $acfValues = $request->request->all('acf') ?: [];
        $shopId = (int) $this->get('prestashop.adapter.legacy.context')->getContext()->shop->id;
        $this->valueHandler->saveEntityFieldValues('cpt_post', $id, $acfValues, $shopId);

        $this->addFlash('success', $this->trans('Post saved successfully', 'Modules.Weprestaacf.Admin'));

        return $this->redirectToRoute('wepresta_acf_cpt_posts_edit', [
            'typeSlug' => $type->getSlug(),
            'id' => $id,
        ]);
    }

    /**
     * @param array<array<string, mixed>> $acfGroups Groups from Location Rules
     */
    private function serializeType($type, array $acfGroups = []): array
    {
        return [
            'id' => $type->getId(),
            'slug' => $type->getSlug(),
            'name' => $type->getName(),
            'acf_groups' => $acfGroups,
        ];
    }

    private function serializePost($post): array
    {
        return [
            'id' => $post->getId(),
            'slug' => $post->getSlug(),
            'title' => $post->getTitle(),
            'status' => $post->getStatus(),
            'seo_title' => $post->getSeoTitle(),
            'seo_description' => $post->getSeoDescription(),
            'terms' => $post->getTerms(),
            'date_add' => $post->getDateAdd()?->format('Y-m-d H:i:s'),
            'date_upd' => $post->getDateUpd()?->format('Y-m-d H:i:s'),
        ];
    }

    private function serializeTaxonomies(array $taxonomies): array
    {
        return array_map(function ($taxonomy) {
            $terms = $this->taxonomyService->getTermsByTaxonomy($taxonomy->getId());

            return [
                'id' => $taxonomy->getId(),
                'slug' => $taxonomy->getSlug(),
                'name' => $taxonomy->getName(),
                'terms' => array_map(function ($term) {
                    return [
                        'id' => $term->getId(),
                        'name' => $term->getName(),
                        'slug' => $term->getSlug(),
                    ];
                }, $terms),
            ];
        }, $taxonomies);
    }
}
