<?php
/**
 * CPT Routes Testing Script
 * 
 * Usage: php test_cpt_routes.php
 * 
 * This script tests if the CPT URL rewriting is working correctly.
 * It checks:
 * 1. Module installation
 * 2. CPT Types exist
 * 3. Routes are registered
 * 4. URLs are generated correctly
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

require_once __DIR__ . '/../../../config/config.inc.php';
require_once __DIR__ . '/autoload.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    CPT URL Routes Testing                                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Module installed
echo "[1/6] Checking if module is installed...\n";
$module = Module::getInstanceByName('wepresta_acf');
if ($module && $module->isInstalled('wepresta_acf')) {
    echo "✅ Module installed\n\n";
} else {
    die("❌ Module not installed!\n\n");
}

// Test 2: CPT Types exist
echo "[2/6] Checking CPT Types...\n";
try {
    $typeService = \WeprestaAcf\Application\Service\AcfServiceContainer::getTypeService();
    $types = $typeService->getActiveTypes();
    
    if (count($types) > 0) {
        echo "✅ Found " . count($types) . " active CPT Type(s):\n";
        foreach ($types as $type) {
            echo "   - " . $type->getName() . " (slug: " . $type->getSlug() . ")\n";
        }
        echo "\n";
    } else {
        echo "⚠️  No active CPT Types found. Create one first!\n";
        echo "   Suggestion: Run php modules/wepresta_acf/demo_cpt_blog.php\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Error loading CPT Types: " . $e->getMessage() . "\n\n";
}

// Test 3: Routes are registered
echo "[3/6] Checking registered routes...\n";
try {
    $routes = $module->hookModuleRoutes();
    
    if (is_array($routes) && count($routes) > 0) {
        echo "✅ " . count($routes) . " routes registered:\n";
        foreach ($routes as $key => $route) {
            echo "   - " . $key . " → " . $route['rule'] . "\n";
        }
        echo "\n";
    } else {
        echo "⚠️  No routes registered. Create CPT Types first!\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Error loading routes: " . $e->getMessage() . "\n\n";
}

// Test 4: URL rewriting enabled
echo "[4/6] Checking URL rewriting...\n";
$urlRewriting = (int) Configuration::get('PS_REWRITING_SETTINGS');
if ($urlRewriting === 1) {
    echo "✅ URL rewriting is ENABLED\n\n";
} else {
    echo "⚠️  URL rewriting is DISABLED\n";
    echo "   Enable it: Admin > Preferences > SEO & URLs > Enable friendly URLs\n\n";
}

// Test 5: Generate sample URLs
echo "[5/6] Testing URL generation...\n";
try {
    if (count($types) > 0) {
        $type = $types[0];
        $urlService = \WeprestaAcf\Application\Service\AcfServiceContainer::get(
            'WeprestaAcf\Application\Service\CptUrlService'
        );
        
        if ($urlService) {
            $archiveUrl = $urlService->getArchiveUrl($type);
            echo "✅ Archive URL generated:\n";
            echo "   " . $archiveUrl . "\n";
            
            // Try to get a post
            $postRepo = \WeprestaAcf\Application\Service\AcfServiceContainer::get(
                'WeprestaAcf\Domain\Repository\CptPostRepositoryInterface'
            );
            $posts = $postRepo->findPublishedByType($type->getId(), null, null, 1);
            
            if (count($posts) > 0) {
                $post = $posts[0];
                $postUrl = $urlService->getPostUrl($post, $type);
                echo "\n✅ Single post URL generated:\n";
                echo "   " . $postUrl . "\n";
            }
            
            // Try to get a term
            $taxonomyService = \WeprestaAcf\Application\Service\AcfServiceContainer::getTaxonomyService();
            $taxonomies = $taxonomyService->getTaxonomiesByType($type->getId());
            
            if (count($taxonomies) > 0) {
                $taxonomy = $taxonomies[0];
                $terms = $taxonomyService->getTermsByTaxonomy($taxonomy->getId());
                
                if (count($terms) > 0) {
                    $term = $terms[0];
                    $termUrl = $urlService->getTermUrl($term, $type);
                    echo "\n✅ Taxonomy URL generated:\n";
                    echo "   " . $termUrl . "\n";
                }
            }
            
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "⚠️  Could not generate sample URLs: " . $e->getMessage() . "\n\n";
}

// Test 6: Check .htaccess
echo "[6/6] Checking .htaccess...\n";
$htaccess = _PS_ROOT_DIR_ . '/.htaccess';
if (file_exists($htaccess)) {
    echo "✅ .htaccess file exists\n\n";
} else {
    echo "⚠️  .htaccess file not found\n";
    echo "   Location: " . _PS_ROOT_DIR_ . "/.htaccess\n";
    echo "   URL rewriting will not work without it!\n\n";
}

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    Summary                                                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

if (count($types) > 0 && $urlRewriting === 1 && file_exists($htaccess)) {
    echo "\n✅ All systems operational!\n";
    echo "\nYou can now test:\n";
    foreach ($types as $type) {
        $prefix = $type->getUrlPrefix();
        echo "   - https://yoursite.com/" . $prefix . "/\n";
        echo "   - https://yoursite.com/" . $prefix . "/{post-slug}\n";
        echo "   - https://yoursite.com/" . $prefix . "/{taxonomy-slug}/{term-slug}\n";
    }
    echo "\n";
} else {
    echo "\n⚠️  Setup not complete. Please fix the issues above.\n\n";
}
