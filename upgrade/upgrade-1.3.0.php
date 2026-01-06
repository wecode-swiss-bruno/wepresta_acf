<?php
/**
 * WePresta ACF - Upgrade to 1.3.0
 * 
 * This upgrade adds the display hook feature to groups.
 * Existing groups are migrated with a default display hook based on their location rules.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Upgrade module to version 1.3.0
 * 
 * @param WeprestaAcf $module
 * @return bool
 */
function upgrade_module_1_3_0($module)
{
    try {
        $db = Db::getInstance();
        
        // Get all existing groups
        $groups = $db->executeS('
            SELECT id_wepresta_acf_group, location_rules, fo_options
            FROM `' . _DB_PREFIX_ . 'wepresta_acf_group`
        ');

        if (!$groups) {
            // No groups to migrate
            return true;
        }

        $updated = 0;
        
        foreach ($groups as $group) {
            $groupId = (int) $group['id_wepresta_acf_group'];
            $locationRules = json_decode($group['location_rules'] ?? '[]', true);
            $foOptions = json_decode($group['fo_options'] ?? '{}', true);

            // Skip if displayHook already set
            if (!empty($foOptions['displayHook'])) {
                continue;
            }

            // Determine entity type from location rules
            $entityType = getEntityTypeFromLocationRules($locationRules);

            // Set default display hook based on entity type
            $defaultHook = getDefaultHookForEntity($entityType);
            
            if ($defaultHook) {
                $foOptions['displayHook'] = $defaultHook;
                
                $result = $db->update(
                    'wepresta_acf_group',
                    ['fo_options' => pSQL(json_encode($foOptions), true)],
                    'id_wepresta_acf_group = ' . $groupId
                );

                if ($result) {
                    $updated++;
                }
            }
        }

        $module->log("Upgrade 1.3.0: Migrated {$updated} groups with default display hooks");
        
        return true;

    } catch (Exception $e) {
        $module->log('Upgrade 1.3.0 failed: ' . $e->getMessage(), 3);
        return false;
    }
}

/**
 * Extract entity type from location rules
 * 
 * @param array $locationRules
 * @return string Entity type (product, category, etc.) or empty string
 */
function getEntityTypeFromLocationRules(array $locationRules): string
{
    if (empty($locationRules)) {
        return '';
    }

    // Parse first "==" rule to get entity type
    foreach ($locationRules as $rule) {
        if (isset($rule['==']) && is_array($rule['==']) && count($rule['==']) >= 2) {
            return (string) $rule['=='][1];
        }
    }

    return '';
}

/**
 * Get default display hook for an entity type
 * 
 * @param string $entityType
 * @return string Hook name or empty string
 */
function getDefaultHookForEntity(string $entityType): string
{
    $defaults = [
        'product' => 'displayProductAdditionalInfo',
        'category' => 'displayCategoryHeader',
    ];

    return $defaults[$entityType] ?? '';
}

