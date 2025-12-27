-- =====================================================
-- WePresta ACF - Seed Data: Test Group with All Field Types
-- Run this after install.sql to create test data
-- =====================================================

-- Insert test group
INSERT INTO `PREFIX_wepresta_acf_group` 
    (`uuid`, `title`, `slug`, `description`, `location`, `rules`, `position`, `active`, `id_shop`)
VALUES 
    (UUID(), 'Test All Field Types', 'test_all_fields', 'A group containing one field of each type for testing purposes.', 'product', '{"match":"all","conditions":[]}', 1, 1, 1);

SET @group_id = LAST_INSERT_ID();

-- Insert fields of each type
-- Position starts at 1 and increments

-- 1. Text Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'text', 'Text Field', 'test_text', 'Enter some text here', '{"placeholder":"Enter text...","maxLength":255}', '{"required":false}', 1, 1, 1);

-- 2. Textarea Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'textarea', 'Textarea Field', 'test_textarea', 'Enter longer text', '{"rows":5,"placeholder":"Enter description..."}', '{"required":false}', 2, 1, 1);

-- 3. Number Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'number', 'Number Field', 'test_number', 'Enter a number', '{"min":0,"max":1000,"step":1}', '{"required":false}', 3, 1, 0);

-- 4. Email Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'email', 'Email Field', 'test_email', 'Enter an email address', '{"placeholder":"email@example.com"}', '{"required":false}', 4, 1, 0);

-- 5. URL Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'url', 'URL Field', 'test_url', 'Enter a website URL', '{"placeholder":"https://example.com"}', '{"required":false}', 5, 1, 0);

-- 6. Boolean Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'boolean', 'Boolean Field', 'test_boolean', 'Toggle on or off', '{"onLabel":"Yes","offLabel":"No"}', '{"required":false}', 6, 1, 0);

-- 7. Select Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'select', 'Select Field', 'test_select', 'Choose an option', '{"choices":[{"value":"opt1","label":"Option 1"},{"value":"opt2","label":"Option 2"},{"value":"opt3","label":"Option 3"}],"allowEmpty":true}', '{"required":false}', 7, 1, 0);

-- 8. Checkbox Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'checkbox', 'Checkbox Field', 'test_checkbox', 'Select multiple options', '{"choices":[{"value":"check1","label":"Checkbox 1"},{"value":"check2","label":"Checkbox 2"},{"value":"check3","label":"Checkbox 3"}],"layout":"vertical"}', '{"required":false}', 8, 1, 0);

-- 9. Radio Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'radio', 'Radio Field', 'test_radio', 'Choose one option', '{"choices":[{"value":"radio1","label":"Radio 1"},{"value":"radio2","label":"Radio 2"},{"value":"radio3","label":"Radio 3"}],"layout":"vertical"}', '{"required":false}', 9, 1, 0);

-- 10. Date Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'date', 'Date Field', 'test_date', 'Select a date', '{"format":"Y-m-d"}', '{"required":false}', 10, 1, 0);

-- 11. Time Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'time', 'Time Field', 'test_time', 'Select a time', '{"format":"H:i"}', '{"required":false}', 11, 1, 0);

-- 12. DateTime Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'datetime', 'DateTime Field', 'test_datetime', 'Select date and time', '{"format":"Y-m-d H:i"}', '{"required":false}', 12, 1, 0);

-- 13. Color Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'color', 'Color Field', 'test_color', 'Pick a color', '{"defaultValue":"#3498db"}', '{"required":false}', 13, 1, 0);

-- 14. RichText Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'richtext', 'Rich Text Field', 'test_richtext', 'Enter formatted content', '{"toolbar":"full","height":200}', '{"required":false}', 14, 1, 1);

-- 15. Image Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'image', 'Image Field', 'test_image', 'Upload an image', '{"allowUpload":true,"allowUrlImport":true,"allowUrlLink":true,"enableTitle":true}', '{"required":false}', 15, 1, 0);

-- 16. File Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'file', 'File Field', 'test_file', 'Upload a file', '{"allowedFormats":["pdf","doc","docx","xls","xlsx"],"maxSizeMB":10}', '{"required":false}', 16, 1, 0);

-- 17. Video Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'video', 'Video Field', 'test_video', 'Upload or link a video', '{"allowUpload":true,"allowUrl":true,"enableTitle":true}', '{"required":false}', 17, 1, 0);

-- 18. Gallery Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'gallery', 'Gallery Field', 'test_gallery', 'Upload multiple images', '{"enableTitle":true,"maxItems":10}', '{"required":false}', 18, 1, 0);

-- 19. Files (Multiple) Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'files', 'Files Field', 'test_files', 'Upload multiple files', '{"enableTitle":true,"enableDescription":true,"maxItems":5}', '{"required":false}', 19, 1, 0);

-- 20. Star Rating Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'star_rating', 'Star Rating Field', 'test_star_rating', 'Rate from 1 to 5', '{"max":5,"allowHalf":false}', '{"required":false}', 20, 1, 0);

-- 21. List Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'list', 'List Field', 'test_list', 'Add list items', '{"minItems":0,"maxItems":10}', '{"required":false}', 21, 1, 0);

-- 22. Relation Field
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'relation', 'Relation Field', 'test_relation', 'Select related entities', '{"entityType":"product","multiple":true,"maxItems":5}', '{"required":false}', 22, 1, 0);

-- 23. Repeater Field (with sub-fields)
INSERT INTO `PREFIX_wepresta_acf_field` 
    (`uuid`, `id_wepresta_acf_group`, `type`, `title`, `slug`, `instructions`, `config`, `validation`, `position`, `active`, `translatable`)
VALUES 
    (UUID(), @group_id, 'repeater', 'Repeater Field', 'test_repeater', 'Add repeating items', '{"minRows":0,"maxRows":5,"layout":"table","subFields":[{"type":"text","title":"Item Title","slug":"item_title"},{"type":"textarea","title":"Item Description","slug":"item_desc"},{"type":"image","title":"Item Image","slug":"item_image"}]}', '{"required":false}', 23, 1, 0);

-- Done!

