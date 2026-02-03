<?php
/**
 * Verification script for Entity feature
 * Run with: php verify-entities.php
 */

echo "=== Entity Feature Verification ===\n\n";

$errors = [];
$checks = 0;

// 1. Check Entity model exists and has correct structure
$checks++;
$entityModel = file_get_contents(__DIR__ . '/src/Models/Entity.php');
if (strpos($entityModel, 'class Entity extends Model') !== false) {
    echo "✅ Entity model exists\n";
} else {
    $errors[] = "Entity model missing or malformed";
}

// 2. Check Entity has accounts relationship
$checks++;
if (strpos($entityModel, 'public function accounts()') !== false && 
    strpos($entityModel, 'hasMany') !== false) {
    echo "✅ Entity->accounts() relationship exists\n";
} else {
    $errors[] = "Entity->accounts() relationship missing";
}

// 3. Check Account model has entity relationship
$checks++;
$accountModel = file_get_contents(__DIR__ . '/src/Models/Account.php');
if (strpos($accountModel, 'public function entity()') !== false && 
    strpos($accountModel, 'belongsTo') !== false) {
    echo "✅ Account->entity() relationship exists\n";
} else {
    $errors[] = "Account->entity() relationship missing";
}

// 4. Check Account fillable includes entity_id
$checks++;
if (strpos($accountModel, "'entity_id'") !== false) {
    echo "✅ Account fillable includes entity_id\n";
} else {
    $errors[] = "Account fillable missing entity_id";
}

// 5. Check EntitiesController exists
$checks++;
$controller = file_get_contents(__DIR__ . '/src/Http/Controllers/EntitiesController.php');
if (strpos($controller, 'class EntitiesController') !== false) {
    echo "✅ EntitiesController exists\n";
} else {
    $errors[] = "EntitiesController missing";
}

// 6. Check EntitiesController has required methods
$checks++;
$methods = ['index', 'store', 'update', 'destroy'];
$missingMethods = [];
foreach ($methods as $method) {
    if (strpos($controller, "public function $method") === false) {
        $missingMethods[] = $method;
    }
}
if (empty($missingMethods)) {
    echo "✅ EntitiesController has all CRUD methods\n";
} else {
    $errors[] = "EntitiesController missing methods: " . implode(', ', $missingMethods);
}

// 7. Check migration exists
$checks++;
$migrationFile = glob(__DIR__ . '/database/migrations/*create_entities_table.php');
if (!empty($migrationFile)) {
    $migration = file_get_contents($migrationFile[0]);
    if (strpos($migration, 'mixpost_entities') !== false && 
        strpos($migration, 'entity_id') !== false) {
        echo "✅ Migration file exists and contains entity table + FK\n";
    } else {
        $errors[] = "Migration missing table or FK";
    }
} else {
    $errors[] = "Migration file not found";
}

// 8. Check routes exist
$checks++;
$routes = file_get_contents(__DIR__ . '/routes/web.php');
if (strpos($routes, "Route::prefix('entities')") !== false &&
    strpos($routes, 'EntitiesController') !== false) {
    echo "✅ Entity routes registered\n";
} else {
    $errors[] = "Entity routes not registered";
}

// 9. Check EntityResource exists
$checks++;
if (file_exists(__DIR__ . '/src/Http/Resources/EntityResource.php')) {
    echo "✅ EntityResource exists\n";
} else {
    $errors[] = "EntityResource missing";
}

// 10. Check Vue components exist
$checks++;
$vueFiles = [
    '/resources/js/Pages/Entities.vue',
    '/resources/js/Components/Entity/EntityBadge.vue',
    '/resources/js/Components/Entity/EntitySelect.vue',
];
$missingVue = [];
foreach ($vueFiles as $file) {
    if (!file_exists(__DIR__ . $file)) {
        $missingVue[] = $file;
    }
}
if (empty($missingVue)) {
    echo "✅ All Vue components exist\n";
} else {
    $errors[] = "Missing Vue files: " . implode(', ', $missingVue);
}

// 11. Check Sidebar includes Entities link
$checks++;
$sidebar = file_get_contents(__DIR__ . '/resources/js/Components/Sidebar/Sidebar.vue');
if (strpos($sidebar, 'mixpost.entities.index') !== false) {
    echo "✅ Sidebar has Entities navigation link\n";
} else {
    $errors[] = "Sidebar missing Entities link";
}

// 12. Check AccountsController loads entities
$checks++;
$accountsController = file_get_contents(__DIR__ . '/src/Http/Controllers/AccountsController.php');
if (strpos($accountsController, "Entity::") !== false && 
    strpos($accountsController, "'entities'") !== false) {
    echo "✅ AccountsController passes entities to view\n";
} else {
    $errors[] = "AccountsController not loading entities";
}

// Summary
echo "\n=== Summary ===\n";
echo "Checks passed: " . ($checks - count($errors)) . "/$checks\n";

if (empty($errors)) {
    echo "\n🎉 ALL CHECKS PASSED! Entity feature is properly implemented.\n";
    exit(0);
} else {
    echo "\n❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}
