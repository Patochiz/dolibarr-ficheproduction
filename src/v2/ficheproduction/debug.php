<?php
/* Copyright (C) 2025 SuperAdmin
 * 
 * Debug script for Fiche de Production v2.0
 * Use this to diagnose issues when the main page is blank
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Debug Fiche de Production v2.0</h1>";
echo "<hr>";

// Test 1: Basic PHP
echo "<h2>✅ Test 1: PHP Basic</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Script path: " . __FILE__ . "<br>";
echo "Current directory: " . getcwd() . "<br>";
echo "<hr>";

// Test 2: Dolibarr main.inc.php
echo "<h2>🔍 Test 2: Dolibarr Loading</h2>";

$res = 0;
$main_inc_paths = array(
    $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php",
    "../main.inc.php",
    "../../main.inc.php",
    "../../../main.inc.php"
);

foreach ($main_inc_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Found main.inc.php at: $path<br>";
        try {
            $res = @include $path;
            if ($res) {
                echo "✅ Successfully loaded main.inc.php<br>";
                break;
            }
        } catch (Exception $e) {
            echo "❌ Error loading main.inc.php: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Not found: $path<br>";
    }
}

if (!$res) {
    echo "<strong>❌ CRITICAL: Could not load main.inc.php</strong><br>";
    echo "Please check your Dolibarr installation path.<br>";
    exit;
}

echo "<hr>";

// Test 3: Dolibarr environment
echo "<h2>🔍 Test 3: Dolibarr Environment</h2>";

if (defined('DOL_DOCUMENT_ROOT')) {
    echo "✅ DOL_DOCUMENT_ROOT: " . DOL_DOCUMENT_ROOT . "<br>";
} else {
    echo "❌ DOL_DOCUMENT_ROOT not defined<br>";
}

if (isset($db)) {
    echo "✅ Database connection available<br>";
    echo "Database type: " . $db->type . "<br>";
} else {
    echo "❌ Database connection not available<br>";
}

if (isset($user)) {
    echo "✅ User object available<br>";
    echo "User ID: " . $user->id . "<br>";
    echo "User login: " . $user->login . "<br>";
} else {
    echo "❌ User object not available<br>";
}

if (isset($conf)) {
    echo "✅ Configuration object available<br>";
} else {
    echo "❌ Configuration object not available<br>";
}

echo "<hr>";

// Test 4: Module files
echo "<h2>🔍 Test 4: Module Files</h2>";

$module_files = array(
    'Main PHP' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/ficheproduction.php',
    'CSS' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/css/ficheproduction.css',
    'JS' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/js/ficheproduction.js',
    'Session Class' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductionsession.class.php',
    'Colis Class' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php',
    'Actions Class' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/actions_ficheproduction.class.php',
    'Module Descriptor' => DOL_DOCUMENT_ROOT.'/custom/ficheproduction/core/modules/modficheproduction.class.php'
);

foreach ($module_files as $name => $file) {
    if (file_exists($file)) {
        echo "✅ $name: Found<br>";
        if (is_readable($file)) {
            echo "&nbsp;&nbsp;&nbsp;✅ Readable<br>";
        } else {
            echo "&nbsp;&nbsp;&nbsp;❌ Not readable<br>";
        }
    } else {
        echo "❌ $name: Not found ($file)<br>";
    }
}

echo "<hr>";

// Test 5: Module activation
echo "<h2>🔍 Test 5: Module Activation</h2>";

if (function_exists('isModEnabled')) {
    if (isModEnabled('ficheproduction')) {
        echo "✅ Module ficheproduction is enabled<br>";
    } else {
        echo "❌ Module ficheproduction is NOT enabled<br>";
    }
} else {
    echo "❌ isModEnabled function not available<br>";
}

// Check in database
if (isset($db)) {
    $sql = "SELECT name, value FROM ".MAIN_DB_PREFIX."const WHERE name LIKE '%FICHEPRODUCTION%'";
    $resql = $db->query($sql);
    if ($resql) {
        echo "<strong>Module constants in database:</strong><br>";
        while ($obj = $db->fetch_object($resql)) {
            echo "&nbsp;&nbsp;&nbsp;{$obj->name} = {$obj->value}<br>";
        }
    }
}

echo "<hr>";

// Test 6: Permissions
echo "<h2>🔍 Test 6: User Permissions</h2>";

if (isset($user)) {
    if (isset($user->rights->ficheproduction)) {
        echo "✅ User has ficheproduction rights object<br>";
        
        if ($user->rights->ficheproduction->read) {
            echo "&nbsp;&nbsp;&nbsp;✅ Read permission<br>";
        } else {
            echo "&nbsp;&nbsp;&nbsp;❌ No read permission<br>";
        }
        
        if ($user->rights->ficheproduction->write) {
            echo "&nbsp;&nbsp;&nbsp;✅ Write permission<br>";
        } else {
            echo "&nbsp;&nbsp;&nbsp;❌ No write permission<br>";
        }
        
        if ($user->rights->ficheproduction->delete) {
            echo "&nbsp;&nbsp;&nbsp;✅ Delete permission<br>";
        } else {
            echo "&nbsp;&nbsp;&nbsp;❌ No delete permission<br>";
        }
    } else {
        echo "❌ User has NO ficheproduction rights object<br>";
    }
    
    // Check commande permissions
    if (isset($user->rights->commande)) {
        if ($user->rights->commande->lire) {
            echo "✅ User has commande read permission<br>";
        } else {
            echo "❌ User has NO commande read permission<br>";
        }
    } else {
        echo "❌ User has NO commande rights object<br>";
    }
}

echo "<hr>";

// Test 7: Database tables
echo "<h2>🔍 Test 7: Database Tables</h2>";

if (isset($db)) {
    $tables = array(
        'llx_ficheproduction_session',
        'llx_ficheproduction_colis', 
        'llx_ficheproduction_colis_line'
    );
    
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            echo "✅ Table $table exists<br>";
        } else {
            echo "❌ Table $table does NOT exist<br>";
        }
    }
}

echo "<hr>";

// Test 8: GET Parameters
echo "<h2>🔍 Test 8: GET Parameters</h2>";

echo "Current URL: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "GET parameters:<br>";
foreach ($_GET as $key => $value) {
    echo "&nbsp;&nbsp;&nbsp;$key = $value<br>";
}

if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    echo "<strong>Testing order ID: $order_id</strong><br>";
    
    if (isset($db)) {
        $sql = "SELECT rowid, ref, fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE rowid = $order_id";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            echo "✅ Order found: {$obj->ref} (ID: {$obj->rowid}, Society: {$obj->fk_soc})<br>";
        } else {
            echo "❌ Order with ID $order_id not found<br>";
        }
    }
} else {
    echo "❌ No order ID in URL<br>";
}

echo "<hr>";

// Test 9: Try to include main module file
echo "<h2>🔍 Test 9: Include Main Module File</h2>";

try {
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/ficheproduction/ficheproduction.php')) {
        echo "✅ Main file exists, trying to analyze...<br>";
        
        // Read first lines to check for syntax errors
        $content = file_get_contents(DOL_DOCUMENT_ROOT.'/custom/ficheproduction/ficheproduction.php', false, null, 0, 1000);
        if (strpos($content, '<?php') === 0) {
            echo "✅ File starts with PHP tag<br>";
        } else {
            echo "❌ File does not start with PHP tag<br>";
        }
        
        // Check syntax (basic)
        $syntax_check = exec('php -l '.DOL_DOCUMENT_ROOT.'/custom/ficheproduction/ficheproduction.php 2>&1', $output, $return_var);
        if ($return_var === 0) {
            echo "✅ PHP syntax check passed<br>";
        } else {
            echo "❌ PHP syntax error:<br>";
            foreach ($output as $line) {
                echo "&nbsp;&nbsp;&nbsp;$line<br>";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking main file: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 10: Direct access test
echo "<h2>🔍 Test 10: Direct Access Test</h2>";

if (isset($_GET['id'])) {
    $test_url = '/custom/ficheproduction/ficheproduction.php?id=' . $_GET['id'];
    echo "<strong>Test URL:</strong> <a href='$test_url' target='_blank'>$test_url</a><br>";
} else {
    echo "❌ Cannot create test URL without order ID<br>";
    echo "<strong>Try this URL format:</strong> /custom/ficheproduction/debug.php?id=1<br>";
}

echo "<hr>";

// Summary
echo "<h2>📋 Summary</h2>";
echo "If you see this page, basic PHP and Dolibarr loading works.<br>";
echo "<strong>Next steps:</strong><br>";
echo "1. Check if all files exist and are readable<br>";
echo "2. Verify module is activated in Dolibarr<br>";
echo "3. Check user permissions<br>";
echo "4. Verify database tables exist<br>";
echo "5. Test direct URL access<br>";
echo "<br>";
echo "<strong>Common issues:</strong><br>";
echo "• Module not activated: Go to Configuration > Modules<br>";
echo "• Permission denied: Check user rights in Users/Groups<br>";
echo "• File not found: Verify file paths and permissions<br>";
echo "• Database error: Check Dolibarr logs<br>";

echo "<hr>";
echo "<small>Debug script completed at " . date('Y-m-d H:i:s') . "</small>";
?>