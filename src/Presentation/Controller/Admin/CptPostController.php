<?php

declare(strict_types=1);

namespace WeprestaAcf\Presentation\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WeprestaAcf\Application\Service\CptPostService;
use WeprestaAcf\Application\Service\CptTypeService;
use WeprestaAcf\Application\Service\CptTaxonomyService;
use WeprestaAcf\Application\Service\ValueHandler;
use WeprestaAcf\Application\Service\ValueProvider;
use WeprestaAcf\Domain\Repository\CptGroupRepositoryInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * CPT Post Controller - CRUD for CPT posts with ACF fields
 */
final class CptPostController extends FrameworkBundleAdminController
{
    private CptPostService $postService;
    private CptTypeService $typeService;
    private CptTaxonomyService $taxonomyService;
    private ValueHandler $valueHandler;
    private ValueProvider $valueProvider;

    public function __construct(
        CptPostService $postService,
        CptTypeService $typeService,
        CptTaxonomyService $taxonomyService,
        ValueHandler $valueHandler,
        ValueProvider $valueProvider
    ) {
        $this->postService = $postService;
        $this->typeService = $typeService;
        $this->taxonomyService = $taxonomyService;
        $this->valueHandler = $valueHandler;
        $this->valueProvider = $valueProvider;
    }

    /**
     * List posts by type
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
     * Create new post
     */
    #[AdminSecurity("is_granted('create', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function create(string $typeSlug, Request $request): Response
    {
        $type = $this->typeService->getTypeWithGroups($typeSlug);

        if (!$type) {
            $this->addFlash('error', $this->trans('CPT Type not found', 'Modules.Weprestaacf.Admin'));
            return $this->redirectToRoute('wepresta_acf_builder');
        }

        if ($request->isMethod('POST')) {
            return $this->handleSave(null, $type, $request);
        }

        // Get taxonomies
        $taxonomies = $this->taxonomyService->getTaxonomiesByType($type->getId());

        return $this->render('@Modules/wepresta_acf/views/templates/admin/cpt-post-edit.html.twig', [
            'layoutTitle' => $this->trans('New', 'Modules.Weprestaacf.Admin') . ' - ' . $type->getName(),
            'type' => $this->serializeType($type),
            'post' => null,
            'taxonomies' => $this->serializeTaxonomies($taxonomies),
            'acfValues' => [],
        ]);
    }

    /**
     * Edit existing post
     */
    #[AdminSecurity("is_granted('update', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function edit(int $id, Request $request): Response
    {
        $post = $this->postService->getPostById($id);

        if (!$post) {
            $this->addFlash('error', $this->trans('Post not found', 'Modules.Weprestaacf.Admin'));
            return $this->redirectToRoute('wepresta_acf_builder');
        }

        $type = $this->typeService->getTypeWithGroups($post->getTypeId());

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
        $acfValues = $this->valueProvider->getEntityFieldValues('cpt_post', $id, (int) $this->get('prestashop.adapter.legacy.context')->getContext()->shop->id);

        return $this->render('@Modules/wepresta_acf/views/templates/admin/cpt-post-edit.html.twig', [
            'layoutTitle' => $this->trans('Edit', 'Modules.Weprestaacf.Admin') . ' - ' . $post->getTitle(),
            'type' => $this->serializeType($type),
            'post' => $this->serializePost($post),
            'taxonomies' => $this->serializeTaxonomies($taxonomies),
            'acfValues' => $acfValues,
        ]);
    }

    /**
     * Delete post
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

    private function serializeType($type): array
    {
        return [
            'id' => $type->getId(),
            'slug' => $type->getSlug(),
            'name' => $type->getName(),
            'acf_groups' => $type->getAcfGroups(),
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
