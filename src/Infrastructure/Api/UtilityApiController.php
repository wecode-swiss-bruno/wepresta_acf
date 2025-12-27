<?php

declare(strict_types=1);

namespace WeprestaAcf\Infrastructure\Api;

use WeprestaAcf\Application\Service\FieldTypeRegistry;
use WeprestaAcf\Application\Service\SlugGenerator;
use WeprestaAcf\Domain\Repository\AcfGroupRepositoryInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Db;
use Module;

class UtilityApiController extends FrameworkBundleAdminController
{
    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        private readonly SlugGenerator $slugGenerator,
        private readonly AcfGroupRepositoryInterface $groupRepository,
    ) {}

    public function fieldTypes(): JsonResponse
    {
        try {
            return $this->json(['success' => true, 'data' => $this->fieldTypeRegistry->toArray()]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function fieldTypesGrouped(): JsonResponse
    {
        try {
            $grouped = $this->fieldTypeRegistry->getAllGroupedByCategory();
            $result = [];
            foreach ($grouped as $category => $types) {
                $result[$category] = [];
                foreach ($types as $type => $fieldType) {
                    $result[$category][$type] = [
                        'type' => $type, 'label' => $fieldType->getLabel(),
                        'icon' => $fieldType->getIcon(), 'supportsTranslation' => $fieldType->supportsTranslation(),
                    ];
                }
            }
            return $this->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function slugify(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $text = $data['text'] ?? '';

            if (empty($text)) {
                return $this->json(['success' => false, 'error' => 'Text is required'], 400);
            }

            $slug = $this->slugGenerator->generate($text);

            return $this->json(['success' => true, 'data' => ['slug' => $slug]]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Force upgrade the module by resetting version and triggering upgrade.
     */
    #[AdminSecurity("is_granted('modify', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function forceUpgrade(): JsonResponse
    {
        try {
            $module = Module::getInstanceByName('wepresta_acf');
            if (!$module) {
                return $this->json(['success' => false, 'error' => 'Module not found'], Response::HTTP_NOT_FOUND);
            }

            // Get current version from database
            $currentVersion = $module->version ?? '1.0.0';
            $targetVersion = $module::VERSION;

            if ($currentVersion === $targetVersion) {
                return $this->json([
                    'success' => true,
                    'skipped' => true,
                    'message' => "Module is already at version {$targetVersion}",
                    'current_version' => $currentVersion,
                    'target_version' => $targetVersion,
                ]);
            }

            // Force upgrade by resetting version in database
            $db = Db::getInstance();
            $db->execute('UPDATE `' . _DB_PREFIX_ . 'module` SET `version` = "1.0.0" WHERE `name` = "wepresta_acf"');

            // Trigger upgrade
            $result = $module->runUpgradeModule();

            if ($result) {
                return $this->json([
                    'success' => true,
                    'message' => 'Upgrade completed successfully',
                    'previous_version' => $currentVersion,
                    'new_version' => $module->version ?? $targetVersion,
                ]);
            }

            $errors = !empty($module->_errors) ? $module->_errors : ['Unknown upgrade error'];
            return $this->json([
                'success' => false,
                'error' => implode(', ', $errors),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Execute seed script to create test group with all field types.
     */
    #[AdminSecurity("is_granted('create', 'AdminWeprestaAcfBuilder')", redirectRoute: 'admin_dashboard')]
    public function seed(Request $request): JsonResponse
    {
        try {
            $db = Db::getInstance();
            $prefix = _DB_PREFIX_;

            // Check if group already exists
            $existing = $db->getValue("SELECT id_wepresta_acf_group FROM {$prefix}wepresta_acf_group WHERE slug = 'test_all_fields'");
            if ($existing) {
                return $this->json([
                    'success' => true,
                    'skipped' => true,
                    'message' => 'Test group already exists',
                    'data' => ['group_id' => (int) $existing]
                ]);
            }

            // Generate UUID
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Insert group
            $groupData = [
                'uuid' => pSQL($uuid),
                'title' => pSQL('Test All Field Types'),
                'slug' => pSQL('test_all_fields'),
                'description' => pSQL('A group containing one field of each type for testing.'),
                'location_rules' => '{"and":[{"==":[{"var":"entity_type"},"product"]}]}',
                'placement_tab' => pSQL('description'),
                'priority' => 10,
                'active' => 1,
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ];

            $db->insert('wepresta_acf_group', $groupData);
            $groupId = (int) $db->Insert_ID();

            if (!$groupId) {
                return $this->json(['success' => false, 'error' => 'Failed to create group'], 500);
            }

            // Link group to shop 1
            $db->insert('wepresta_acf_group_shop', [
                'id_wepresta_acf_group' => $groupId,
                'id_shop' => 1,
            ]);

            // Define all field types with their configs
            $fields = [
                ['type' => 'text', 'title' => 'Text Field', 'slug' => 'test_text', 'instructions' => 'Enter some text', 'config' => '{"placeholder":"Enter text...","maxLength":255}', 'translatable' => 1],
                ['type' => 'textarea', 'title' => 'Textarea Field', 'slug' => 'test_textarea', 'instructions' => 'Enter longer text', 'config' => '{"rows":5}', 'translatable' => 1],
                ['type' => 'number', 'title' => 'Number Field', 'slug' => 'test_number', 'instructions' => 'Enter a number', 'config' => '{"min":0,"max":1000,"step":1}', 'translatable' => 0],
                ['type' => 'email', 'title' => 'Email Field', 'slug' => 'test_email', 'instructions' => 'Enter an email', 'config' => '{"placeholder":"email@example.com"}', 'translatable' => 0],
                ['type' => 'url', 'title' => 'URL Field', 'slug' => 'test_url', 'instructions' => 'Enter a URL', 'config' => '{"placeholder":"https://example.com"}', 'translatable' => 0],
                ['type' => 'boolean', 'title' => 'Boolean Field', 'slug' => 'test_boolean', 'instructions' => 'Toggle on/off', 'config' => '{"onLabel":"Yes","offLabel":"No"}', 'translatable' => 0],
                ['type' => 'select', 'title' => 'Select Field', 'slug' => 'test_select', 'instructions' => 'Choose an option', 'config' => '{"choices":[{"value":"opt1","label":"Option 1"},{"value":"opt2","label":"Option 2"},{"value":"opt3","label":"Option 3"}]}', 'translatable' => 0],
                ['type' => 'checkbox', 'title' => 'Checkbox Field', 'slug' => 'test_checkbox', 'instructions' => 'Select multiple', 'config' => '{"choices":[{"value":"c1","label":"Check 1"},{"value":"c2","label":"Check 2"},{"value":"c3","label":"Check 3"}],"layout":"vertical"}', 'translatable' => 0],
                ['type' => 'radio', 'title' => 'Radio Field', 'slug' => 'test_radio', 'instructions' => 'Choose one', 'config' => '{"choices":[{"value":"r1","label":"Radio 1"},{"value":"r2","label":"Radio 2"},{"value":"r3","label":"Radio 3"}],"layout":"vertical"}', 'translatable' => 0],
                ['type' => 'date', 'title' => 'Date Field', 'slug' => 'test_date', 'instructions' => 'Select a date', 'config' => '{"format":"Y-m-d"}', 'translatable' => 0],
                ['type' => 'time', 'title' => 'Time Field', 'slug' => 'test_time', 'instructions' => 'Select a time', 'config' => '{"format":"H:i"}', 'translatable' => 0],
                ['type' => 'datetime', 'title' => 'DateTime Field', 'slug' => 'test_datetime', 'instructions' => 'Select date & time', 'config' => '{"format":"Y-m-d H:i"}', 'translatable' => 0],
                ['type' => 'color', 'title' => 'Color Field', 'slug' => 'test_color', 'instructions' => 'Pick a color', 'config' => '{"defaultValue":"#3498db"}', 'translatable' => 0],
                ['type' => 'richtext', 'title' => 'Rich Text Field', 'slug' => 'test_richtext', 'instructions' => 'Enter formatted content', 'config' => '{"toolbar":"full","height":200}', 'translatable' => 1],
                ['type' => 'image', 'title' => 'Image Field', 'slug' => 'test_image', 'instructions' => 'Upload an image', 'config' => '{"allowUpload":true,"allowUrlImport":true,"allowUrlLink":true,"enableTitle":true}', 'translatable' => 0],
                ['type' => 'file', 'title' => 'File Field', 'slug' => 'test_file', 'instructions' => 'Upload a file', 'config' => '{"allowedFormats":["pdf","doc","docx"],"maxSizeMB":10}', 'translatable' => 0],
                ['type' => 'video', 'title' => 'Video Field', 'slug' => 'test_video', 'instructions' => 'Upload or link a video', 'config' => '{"allowUpload":true,"allowUrl":true,"enableTitle":true}', 'translatable' => 0],
                ['type' => 'gallery', 'title' => 'Gallery Field', 'slug' => 'test_gallery', 'instructions' => 'Upload multiple images', 'config' => '{"enableTitle":true,"maxItems":10}', 'translatable' => 0],
                ['type' => 'files', 'title' => 'Files Field', 'slug' => 'test_files', 'instructions' => 'Upload multiple files', 'config' => '{"enableTitle":true,"enableDescription":true}', 'translatable' => 0],
                ['type' => 'star_rating', 'title' => 'Star Rating', 'slug' => 'test_star_rating', 'instructions' => 'Rate 1-5', 'config' => '{"max":5,"allowHalf":false}', 'translatable' => 0],
                ['type' => 'list', 'title' => 'List Field', 'slug' => 'test_list', 'instructions' => 'Add list items', 'config' => '{"minItems":0,"maxItems":10}', 'translatable' => 0],
                ['type' => 'relation', 'title' => 'Relation Field', 'slug' => 'test_relation', 'instructions' => 'Select related products', 'config' => '{"entityType":"product","multiple":true,"maxItems":5}', 'translatable' => 0],
                ['type' => 'repeater', 'title' => 'Repeater Field', 'slug' => 'test_repeater', 'instructions' => 'Add repeating rows', 'config' => '{"minRows":0,"maxRows":5,"layout":"table","subFields":[{"type":"text","title":"Title","slug":"rep_title"},{"type":"textarea","title":"Desc","slug":"rep_desc"}]}', 'translatable' => 0],
            ];

            $position = 1;
            $inserted = 0;
            $now = date('Y-m-d H:i:s');

            foreach ($fields as $field) {
                $fieldUuid = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $fieldData = [
                    'uuid' => pSQL($fieldUuid),
                    'id_wepresta_acf_group' => $groupId,
                    'type' => pSQL($field['type']),
                    'title' => pSQL($field['title']),
                    'slug' => pSQL($field['slug']),
                    'instructions' => pSQL($field['instructions']),
                    'config' => $field['config'],
                    'validation' => '{"required":false}',
                    'position' => $position++,
                    'active' => 1,
                    'translatable' => (int) $field['translatable'],
                    'date_add' => $now,
                    'date_upd' => $now,
                ];

                if ($db->insert('wepresta_acf_field', $fieldData)) {
                    $inserted++;
                }
            }

            return $this->json([
                'success' => true,
                'message' => 'Seed completed successfully',
                'data' => [
                    'group_id' => $groupId,
                    'fields_inserted' => $inserted,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

