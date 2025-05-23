<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * Minimal version for debugging - Very basic page to test if Dolibarr loading works
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Minimal Test Page</h1>";
echo "<p>If you see this, PHP is working...</p>";

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
    echo "<p style='color: red;'>❌ CRITICAL ERROR: Could not load Dolibarr main.inc.php</p>";
    echo "<p>Tried these paths:</p>";
    echo "<ul>";
    echo "<li>" . ($_SERVER["CONTEXT_DOCUMENT_ROOT"] ?? 'not set') . "/main.inc.php</li>";
    echo "<li>../main.inc.php</li>";
    echo "<li>../../main.inc.php</li>";
    echo "<li>../../../main.inc.php</li>";
    echo "</ul>";
    echo "<p>Please check your Dolibarr installation.</p>";
    exit;
}

echo "<p style='color: green;'>✅ Dolibarr main.inc.php loaded successfully!</p>";

// Check basic Dolibarr objects
if (isset($db)) {
    echo "<p style='color: green;'>✅ Database connection available</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection not available</p>";
}

if (isset($user)) {
    echo "<p style='color: green;'>✅ User object available (ID: {$user->id}, Login: {$user->login})</p>";
} else {
    echo "<p style='color: red;'>❌ User object not available</p>";
}

if (isset($conf)) {
    echo "<p style='color: green;'>✅ Configuration object available</p>";
} else {
    echo "<p style='color: red;'>❌ Configuration object not available</p>";
}

// Load translation files
$langs->loadLangs(array('orders', 'products', 'companies', 'ficheproduction@ficheproduction'));
echo "<p style='color: green;'>✅ Translation files loaded</p>";

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

echo "<h2>Parameters:</h2>";
echo "<ul>";
echo "<li>ID: " . ($id ? $id : 'not set') . "</li>";
echo "<li>Ref: " . ($ref ? $ref : 'not set') . "</li>";
echo "</ul>";

// Check module activation
if (function_exists('isModEnabled') && isModEnabled('ficheproduction')) {
    echo "<p style='color: green;'>✅ Module ficheproduction is enabled</p>";
} else {
    echo "<p style='color: red;'>❌ Module ficheproduction is NOT enabled</p>";
    echo "<p>Please activate the module in Configuration > Modules</p>";
}

// Check permissions
if (isset($user->rights->ficheproduction)) {
    if ($user->rights->ficheproduction->read) {
        echo "<p style='color: green;'>✅ User has ficheproduction read permission</p>";
    } else {
        echo "<p style='color: red;'>❌ User does NOT have ficheproduction read permission</p>";
    }
} else {
    echo "<p style='color: red;'>❌ User has NO ficheproduction rights object</p>";
}

if (isset($user->rights->commande) && $user->rights->commande->lire) {
    echo "<p style='color: green;'>✅ User has commande read permission</p>";
} else {
    echo "<p style='color: red;'>❌ User does NOT have commande read permission</p>";
}

// Try to load order if ID provided
if ($id > 0) {
    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    
    $object = new Commande($db);
    $result = $object->fetch($id, $ref);
    
    if ($result > 0) {
        echo "<p style='color: green;'>✅ Order loaded successfully: {$object->ref} (ID: {$object->id})</p>";
        echo "<p>Customer: {$object->thirdparty->name}</p>";
        echo "<p>Number of lines: " . count($object->lines) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Could not load order with ID: $id</p>";
    }
}

// Test basic HTML output
echo "<h2>Basic Dolibarr Header Test</h2>";

try {
    $form = new Form($db);
    
    llxHeader('', 'Test Page', '', '');
    
    echo "<div class='fichecenter'>";
    echo "<h1>🎉 SUCCESS!</h1>";
    echo "<p>If you see this styled content, Dolibarr header loading works!</p>";
    echo "<div class='info'>This is a Dolibarr info box</div>";
    echo "</div>";
    
    llxFooter();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading Dolibarr header: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>