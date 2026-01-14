<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use Context;
use Db;
use DbQuery;
use Exception;
use Image;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Relation API Controller - Search functionality for relation fields.
 */
final class RelationApiController extends AbstractApiController
{
    /**
     * Search for products or categories.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $entityType = $request->query->get('type', 'product');
        $limit = min((int) $request->query->get('limit', 10), 50);
        $excludeId = (int) $request->query->get('exclude', 0);
        $activeOnly = $request->query->getBoolean('active', true);
        $inStockOnly = $request->query->getBoolean('in_stock', false);
        $categories = $request->query->get('categories', '');

        if (\strlen($query) < 2) {
            return $this->jsonSuccess([]);
        }

        $langId = (int) $request->query->get('id_lang', $this->context->getLangId());
        $shopId = (int) $request->query->get('id_shop', $this->context->getShopId());

        try {
            if ($entityType === 'category') {
                $results = $this->searchCategories($query, $langId, $limit, $excludeId, $activeOnly);
            } else {
                $results = $this->searchProducts($query, $langId, $shopId, $limit, $excludeId, $activeOnly, $inStockOnly, $categories);
            }

            return $this->jsonSuccess($results);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchProducts(
        string $query,
        int $langId,
        int $shopId,
        int $limit,
        int $excludeId,
        bool $activeOnly,
        bool $inStockOnly,
        string $categories
    ): array {
        $db = Db::getInstance();
        $escapedQuery = pSQL($query);

        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name, p.reference, i.id_image')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $langId . ' AND pl.id_shop = ' . $shopId)
            ->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $shopId)
            ->leftJoin('image_shop', 'i', 'p.id_product = i.id_product AND i.id_shop = ' . $shopId . ' AND i.cover = 1')
            ->where("(pl.name LIKE '%" . $escapedQuery . "%' OR p.reference LIKE '%" . $escapedQuery . "%' OR p.id_product = '" . $escapedQuery . "')")
            ->orderBy('pl.name ASC')
            ->limit($limit);

        if ($excludeId > 0) {
            $sql->where('p.id_product != ' . $excludeId);
        }

        if ($activeOnly) {
            $sql->where('ps.active = 1');
        }

        if ($inStockOnly) {
            $sql->leftJoin('stock_available', 'sa', 'p.id_product = sa.id_product AND sa.id_shop = ' . $shopId . ' AND sa.id_product_attribute = 0');
            $sql->where('sa.quantity > 0');
        }

        if (!empty($categories)) {
            $categoryIds = array_map('intval', explode(',', $categories));

            if (!empty($categoryIds)) {
                $sql->innerJoin('category_product', 'cp', 'p.id_product = cp.id_product');
                $sql->where('cp.id_category IN (' . implode(',', $categoryIds) . ')');
            }
        }

        $results = $db->executeS($sql);

        if (!$results) {
            return [];
        }

        $items = [];

        foreach ($results as $row) {
            $imageUrl = null;

            if (!empty($row['id_image'])) {
                $imageUrl = _PS_BASE_URL_ . _THEME_PROD_DIR_ . Image::getImgFolderStatic($row['id_image']) . $row['id_image'] . '-small_default.jpg';
            }

            $items[] = [
                'id' => (int) $row['id_product'],
                'name' => $row['name'],
                'reference' => $row['reference'] ?? '',
                'image' => $imageUrl,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchCategories(
        string $query,
        int $langId,
        int $limit,
        int $excludeId,
        bool $activeOnly
    ): array {
        $db = Db::getInstance();
        $escapedQuery = pSQL($query);

        $sql = new DbQuery();
        $sql->select('c.id_category, cl.name')
            ->from('category', 'c')
            ->innerJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND cl.id_lang = ' . $langId)
            ->where("cl.name LIKE '%" . $escapedQuery . "%'")
            ->where('c.id_category != 1') // Exclude root category
            ->where('c.id_category != 2') // Exclude home category
            ->orderBy('cl.name ASC')
            ->limit($limit);

        if ($excludeId > 0) {
            $sql->where('c.id_category != ' . $excludeId);
        }

        if ($activeOnly) {
            $sql->where('c.active = 1');
        }

        $results = $db->executeS($sql);

        if (!$results) {
            return [];
        }

        $items = [];

        foreach ($results as $row) {
            $items[] = [
                'id' => (int) $row['id_category'],
                'name' => $row['name'],
            ];
        }

        return $items;
    }
    /**
     * Resolve entities by IDs.
     */
    public function resolve(Request $request): JsonResponse
    {
        $ids = trim((string) $request->query->get('ids', ''));
        $entityType = $request->query->get('type', 'product');

        if (empty($ids)) {
            return $this->jsonSuccess([]);
        }

        $idList = array_map('intval', explode(',', $ids));
        $idList = array_filter($idList);

        if (empty($idList)) {
            return $this->jsonSuccess([]);
        }

        $langId = $this->context->getLangId();
        $shopId = $this->context->getShopId();

        try {
            if ($entityType === 'category') {
                $results = $this->resolveCategories($idList, $langId);
            } else {
                $results = $this->resolveProducts($idList, $langId, $shopId);
            }

            return $this->jsonSuccess($results);
        } catch (Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    /**
     * @param int[] $ids
     * @return array<int, array<string, mixed>>
     */
    private function resolveProducts(array $ids, int $langId, int $shopId): array
    {
        $db = Db::getInstance();
        $idsString = implode(',', $ids);

        $sql = new DbQuery();
        $sql->select('p.id_product, pl.name, p.reference, i.id_image')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $langId . ' AND pl.id_shop = ' . $shopId)
            ->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $shopId)
            ->leftJoin('image_shop', 'i', 'p.id_product = i.id_product AND i.id_shop = ' . $shopId . ' AND i.cover = 1')
            ->where('p.id_product IN (' . $idsString . ')');

        $results = $db->executeS($sql);

        if (!$results) {
            return [];
        }

        $items = [];

        foreach ($results as $row) {
            $imageUrl = null;

            if (!empty($row['id_image'])) {
                $imageUrl = _PS_BASE_URL_ . _THEME_PROD_DIR_ . Image::getImgFolderStatic($row['id_image']) . $row['id_image'] . '-small_default.jpg';
            }

            $items[] = [
                'id' => (int) $row['id_product'],
                'name' => $row['name'],
                'reference' => $row['reference'] ?? '',
                'image' => $imageUrl,
            ];
        }

        // Preserve order based on input IDs
        $orderedItems = [];
        foreach ($ids as $id) {
            foreach ($items as $item) {
                if ($item['id'] === $id) {
                    $orderedItems[] = $item;
                    break;
                }
            }
        }

        return $orderedItems;
    }

    /**
     * @param int[] $ids
     * @return array<int, array<string, mixed>>
     */
    private function resolveCategories(array $ids, int $langId): array
    {
        $db = Db::getInstance();
        $idsString = implode(',', $ids);

        $sql = new DbQuery();
        $sql->select('c.id_category, cl.name')
            ->from('category', 'c')
            ->innerJoin('category_lang', 'cl', 'c.id_category = cl.id_category AND cl.id_lang = ' . $langId)
            ->where('c.id_category IN (' . $idsString . ')');

        $results = $db->executeS($sql);

        if (!$results) {
            return [];
        }

        $items = [];

        foreach ($results as $row) {
            $items[] = [
                'id' => (int) $row['id_category'],
                'name' => $row['name'],
            ];
        }

        // Preserve order
        $orderedItems = [];
        foreach ($ids as $id) {
            foreach ($items as $item) {
                if ($item['id'] === $id) {
                    $orderedItems[] = $item;
                    break;
                }
            }
        }

        return $orderedItems;
    }
}

