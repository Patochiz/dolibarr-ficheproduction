<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * Test permissions and rights for Fiche de Production v2.0
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; 
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1; 
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

if (!$res) {
    die("Include of main fails");
}

// Load required classes and check permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

// Get parameters
$id = GETPOST('id', 'int');

// Check permissions like the main script
echo "<h1>🔐 Permission Check for Fiche de Production v2.0</h1>";

echo "<h2>1. User Information</h2>";
echo "User ID: {$user->id}<br>";
echo "User login: {$user->login}<br>";
echo "User admin: " . ($user->admin ? 'Yes' : 'No') . "<br>";
echo "User entity: {$user->entity}<br>";

echo "<h2>2. Module Checks</h2>";

// Check if module is enabled
if (!isModEnabled('ficheproduction')) {
    echo "<div style='color: red; font-weight: bold;'>❌ Module 'ficheproduction' is NOT enabled</div>";
    echo "<p>To fix this:</p>";
    echo "<ol>";
    echo "<li>Go to Configuration > Modules</li>";
    echo "<li>Search for 'Fiche de Production'</li>";
    echo "<li>Click Activate</li>";
    echo "</ol>";
} else {
    echo "<div style='color: green; font-weight: bold;'>✅ Module 'ficheproduction' is enabled</div>";
}

echo "<h2>3. Permission Checks</h2>";

// Check commande permissions
if (!$user->rights->commande->lire) {
    echo "<div style='color: red; font-weight: bold;'>❌ User does NOT have 'commande->lire' permission</div>";
    echo "<p>This user cannot read orders. Contact your administrator.</p>";
} else {
    echo "<div style='color: green; font-weight: bold;'>✅ User has 'commande->lire' permission</div>";
}

// Check ficheproduction permissions
if (!isset($user->rights->ficheproduction)) {
    echo "<div style='color: red; font-weight: bold;'>❌ User has NO 'ficheproduction' rights object</div>";
    echo "<p>The module may not be properly activated or user permissions not set.</p>";
} else {
    if (!$user->rights->ficheproduction->read) {
        echo "<div style='color: red; font-weight: bold;'>❌ User does NOT have 'ficheproduction->read' permission</div>";
        echo "<p>To fix this:</p>";
        echo "<ol>";
        echo "<li>Go to Users & Groups > Users</li>";
        echo "<li>Edit this user: {$user->login}</li>";
        echo "<li>Go to 'Permissions' tab</li>";
        echo "<li>Find 'Fiche de Production' section</li>";
        echo "<li>Check 'Read' permission</li>";
        echo "<li>Save</li>";
        echo "</ol>";
    } else {
        echo "<div style='color: green; font-weight: bold;'>✅ User has 'ficheproduction->read' permission</div>";
    }
    
    if ($user->rights->ficheproduction->write) {
        echo "<div style='color: green;'>✅ User has 'ficheproduction->write' permission</div>";
    } else {
        echo "<div style='color: orange;'>⚠️ User does NOT have 'ficheproduction->write' permission</div>";
    }
    
    if ($user->rights->ficheproduction->delete) {
        echo "<div style='color: green;'>✅ User has 'ficheproduction->delete' permission</div>";
    } else {
        echo "<div style='color: orange;'>⚠️ User does NOT have 'ficheproduction->delete' permission</div>";
    }
}

echo "<h2>4. Order Test</h2>";

if ($id > 0) {
    $object = new Commande($db);
    $result = $object->fetch($id);
    
    if ($result <= 0) {
        echo "<div style='color: red;'>❌ Could not load order with ID: $id</div>";
        echo "<p>Please check if the order exists and you have access to it.</p>";
    } else {
        echo "<div style='color: green;'>✅ Order loaded successfully</div>";
        echo "<ul>";
        echo "<li>Ref: {$object->ref}</li>";
        echo "<li>ID: {$object->id}</li>";
        echo "<li>Customer: {$object->thirdparty->name}</li>";
        echo "<li>Status: {$object->statut}</li>";
        echo "</ul>";
    }
} else {
    echo "<div style='color: orange;'>⚠️ No order ID provided in URL</div>";
    echo "<p>Add ?id=XXX to the URL to test with a specific order.</p>";
}

echo "<h2>5. File Access Test</h2>";

$files_to_check = array(
    'Main script' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/ficheproduction.php',
    'CSS file' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/css/ficheproduction.css',
    'JS file' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/js/ficheproduction.js'
);

foreach ($files_to_check as $name => $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<div style='color: green;'>✅ $name: Exists and readable</div>";
        } else {
            echo "<div style='color: red;'>❌ $name: Exists but NOT readable</div>";
        }
    } else {
        echo "<div style='color: red;'>❌ $name: Does NOT exist</div>";
    }
}

echo "<h2>6. Database Tables Test</h2>";

$tables = array(
    'llx_ficheproduction_session',
    'llx_ficheproduction_colis',
    'llx_ficheproduction_colis_line'
);

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        echo "<div style='color: green;'>✅ Table $table: Exists</div>";
    } else {
        echo "<div style='color: red;'>❌ Table $table: Does NOT exist</div>";
    }
}

echo "<h2>7. Simulation Test</h2>";

// Simulate the permission checks from the main script
if (!$user->rights->commande->lire) {
    echo "<div style='color: red; font-weight: bold;'>🚫 BLOCKED: accessforbidden() would be called - No commande read permission</div>";
} elseif (!isModEnabled('ficheproduction')) {
    echo "<div style='color: red; font-weight: bold;'>🚫 BLOCKED: accessforbidden('Module not enabled') would be called</div>";
} elseif (!$user->rights->ficheproduction->read) {
    echo "<div style='color: red; font-weight: bold;'>🚫 BLOCKED: accessforbidden() would be called - No ficheproduction read permission</div>";
} else {
    echo "<div style='color: green; font-weight: bold;'>✅ PASSED: All permission checks would pass</div>";
    echo "<p>The main ficheproduction.php script should work for this user.</p>";
}

echo "<h2>8. Next Steps</h2>";

if (isModEnabled('ficheproduction') && 
    $user->rights->commande->lire && 
    isset($user->rights->ficheproduction) && 
    $user->rights->ficheproduction->read) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #f0fff0;'>";
    echo "<strong>✅ Everything looks good!</strong><br>";
    echo "The ficheproduction.php page should work. If you're still seeing a blank page, check:";
    echo "<ul>";
    echo "<li>PHP error logs in Dolibarr</li>";
    echo "<li>Browser console for JavaScript errors</li>";
    echo "<li>Web server error logs</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff0f0;'>";
    echo "<strong>❌ Issues found!</strong><br>";
    echo "Please fix the issues above before trying to access the ficheproduction page.";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Test URLs:</strong></p>";
echo "<ul>";
echo "<li><a href='debug.php?id=1'>Full Debug (ID=1)</a></li>";
echo "<li><a href='ficheproduction_minimal.php?id=1'>Minimal Test (ID=1)</a></li>";
if ($id > 0) {
    echo "<li><a href='ficheproduction.php?id=$id'>Main Script (ID=$id)</a></li>";
}
echo "</ul>";

?>