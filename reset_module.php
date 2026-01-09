<?php
/**
 * Script to reset WePresta ACF module
 * Run with: php reset_module.php
 */

require_once __DIR__ . '/config/config.inc.php';

echo "🔄 Starting WePresta ACF module reset...\n";

// Check if module exists
$module = Module::getInstanceByName('wepresta_acf');
if (!$module) {
    echo "❌ Module wepresta_acf not found!\n";
    exit(1);
}

echo "📦 Module found, version: " . $module->version . "\n";

// Step 1: Disable module
echo "🚫 Disabling module...\n";
if (!$module->disable()) {
    echo "❌ Failed to disable module\n";
    exit(1);
}
echo "✅ Module disabled\n";

// Step 2: Uninstall module (this will drop tables)
echo "🗑️ Uninstalling module...\n";
if (!$module->uninstall()) {
    echo "❌ Failed to uninstall module\n";
    exit(1);
}
echo "✅ Module uninstalled\n";

// Step 3: Install module
echo "📥 Installing module...\n";
if (!$module->install()) {
    echo "❌ Failed to install module\n";
    exit(1);
}
echo "✅ Module installed\n";

// Step 4: Enable module
echo "✅ Enabling module...\n";
if (!$module->enable()) {
    echo "❌ Failed to enable module\n";
    exit(1);
}
echo "✅ Module enabled\n";

echo "🎉 Module reset completed successfully!\n";
echo "📊 Check your database to confirm tables were recreated.\n";