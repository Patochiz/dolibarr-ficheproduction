<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * Quick fix script to grant permissions and create missing tables
 * Run this if you have permission or table issues
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
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

// Check if user is admin
if (!$user->admin) {
    echo "<h1>❌ Access Denied</h1>";
    echo "<p>Only administrators can run this script.</p>";
    exit;
}

echo "<h1>🔧 Quick Fix for Fiche de Production v2.0</h1>";
echo "<hr>";

$action = GETPOST('action', 'alpha');

if ($action == 'fix_permissions') {
    echo "<h2>🔐 Fixing Permissions</h2>";
    
    // Get all users
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE statut = 1";
    $resql = $db->query($sql);
    
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $user_id = $obj->rowid;
            
            // Grant ficheproduction read permission
            $sql_perm = "INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($user_id, 43657101)";
            $db->query($sql_perm);
            
            // Grant ficheproduction write permission  
            $sql_perm = "INSERT IGNORE INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($user_id, 43657102)";
            $db->query($sql_perm);
            
            echo "✅ Permissions granted to user ID: $user_id<br>";
        }
    }
    
    echo "<p style='color: green;'><strong>✅ Permissions fixed!</strong></p>";
    
} elseif ($action == 'create_tables') {
    echo "<h2>🗄️ Creating Database Tables</h2>";
    
    // Create session table
    $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."ficheproduction_session(
        rowid integer AUTO_INCREMENT PRIMARY KEY,
        ref varchar(128) NOT NULL,
        fk_soc integer NOT NULL,
        fk_commande integer NOT NULL,
        ref_chantier varchar(255) DEFAULT NULL,
        commentaires text DEFAULT NULL,
        date_creation datetime NOT NULL,
        tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        fk_user_creat integer NOT NULL,
        fk_user_modif integer,
        status smallint DEFAULT 1 NOT NULL,
        active integer DEFAULT 1 NOT NULL
    ) ENGINE=innodb";
    
    if ($db->query($sql)) {
        echo "✅ Table llx_ficheproduction_session created<br>";
    } else {
        echo "❌ Error creating session table: " . $db->lasterror() . "<br>";
    }
    
    // Create colis table
    $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."ficheproduction_colis(
        rowid integer AUTO_INCREMENT PRIMARY KEY,
        fk_session integer NOT NULL,
        numero_colis integer NOT NULL,
        poids_max decimal(10,3) DEFAULT 25.000 NOT NULL,
        poids_total decimal(10,3) DEFAULT 0.000 NOT NULL,
        multiple_colis integer DEFAULT 1 NOT NULL,
        status varchar(32) DEFAULT 'ok' NOT NULL,
        date_creation datetime NOT NULL,
        tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        fk_user_creat integer NOT NULL,
        fk_user_modif integer,
        active integer DEFAULT 1 NOT NULL
    ) ENGINE=innodb";
    
    if ($db->query($sql)) {
        echo "✅ Table llx_ficheproduction_colis created<br>";
    } else {
        echo "❌ Error creating colis table: " . $db->lasterror() . "<br>";
    }
    
    // Create colis line table
    $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."ficheproduction_colis_line(
        rowid integer AUTO_INCREMENT PRIMARY KEY,
        fk_colis integer NOT NULL,
        fk_product integer NOT NULL,
        quantite integer NOT NULL,
        poids_unitaire decimal(10,3) NOT NULL,
        poids_total decimal(10,3) NOT NULL,
        rang integer DEFAULT 0 NOT NULL,
        date_creation datetime NOT NULL,
        tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        fk_user_creat integer NOT NULL,
        fk_user_modif integer
    ) ENGINE=innodb";
    
    if ($db->query($sql)) {
        echo "✅ Table llx_ficheproduction_colis_line created<br>";
    } else {
        echo "❌ Error creating colis line table: " . $db->lasterror() . "<br>";
    }
    
    echo "<p style='color: green;'><strong>✅ Database tables created!</strong></p>";
    
} elseif ($action == 'activate_module') {
    echo "<h2>🔌 Activating Module</h2>";
    
    // Activate module
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, note, visible, entity) VALUES 
            ('MAIN_MODULE_FICHEPRODUCTION', '1', 'chaine', 'Module Fiche de Production v2.0 activated', '0', '0')
            ON DUPLICATE KEY UPDATE value = '1'";
    
    if ($db->query($sql)) {
        echo "✅ Module activated in database<br>";
    } else {
        echo "❌ Error activating module: " . $db->lasterror() . "<br>";
    }
    
    // Add configuration constants
    $sql = "INSERT IGNORE INTO ".MAIN_DB_PREFIX."const (name, value, type, note, visible, entity) VALUES 
            ('FICHEPRODUCTION_POIDS_MAX_COLIS', '25', 'chaine', 'Poids maximum par défaut', '1', '0'),
            ('FICHEPRODUCTION_AUTO_CREATE_SESSION', '1', 'chaine', 'Création automatique session', '1', '0')";
    
    if ($db->query($sql)) {
        echo "✅ Configuration constants added<br>";
    } else {
        echo "❌ Error adding constants: " . $db->lasterror() . "<br>";
    }
    
    echo "<p style='color: green;'><strong>✅ Module activated!</strong></p>";
    
} else {
    echo "<h2>🚀 Quick Fix Options</h2>";
    echo "<p>Choose what you want to fix:</p>";
    echo "<ul>";
    echo "<li><a href='?action=activate_module' style='color: blue; font-weight: bold;'>1. Activate Module</a> - Enable the ficheproduction module</li>";
    echo "<li><a href='?action=create_tables' style='color: blue; font-weight: bold;'>2. Create Database Tables</a> - Create missing tables</li>";
    echo "<li><a href='?action=fix_permissions' style='color: blue; font-weight: bold;'>3. Fix User Permissions</a> - Grant permissions to all users</li>";
    echo "</ul>";
    
    echo "<h3>⚠️ Current Status</h3>";
    
    // Check module status
    if (isModEnabled('ficheproduction')) {
        echo "✅ Module is enabled<br>";
    } else {
        echo "❌ Module is NOT enabled<br>";
    }
    
    // Check tables
    $tables = array('llx_ficheproduction_session', 'llx_ficheproduction_colis', 'llx_ficheproduction_colis_line');
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            echo "✅ Table $table exists<br>";
        } else {
            echo "❌ Table $table missing<br>";
        }
    }
    
    // Check permissions
    if (isset($user->rights->ficheproduction) && $user->rights->ficheproduction->read) {
        echo "✅ Current user has permissions<br>";
    } else {
        echo "❌ Current user missing permissions<br>";
    }
    
    echo "<hr>";
    echo "<h3>🔧 Manual Steps</h3>";
    echo "<p>If quick fix doesn't work, try these manual steps:</p>";
    echo "<ol>";
    echo "<li><strong>Module activation:</strong> Configuration > Modules > Search 'Fiche de Production' > Activate</li>";
    echo "<li><strong>User permissions:</strong> Users & Groups > Users > Edit user > Permissions tab > Fiche de Production section</li>";
    echo "<li><strong>File permissions:</strong> Check that files in /custom/ficheproduction/ are readable by web server</li>";
    echo "<li><strong>Check logs:</strong> Look in Dolibarr error logs for PHP errors</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><a href='test_permissions.php'>🔍 Test Permissions</a> | <a href='debug.php'>🐛 Full Debug</a> | <a href='ficheproduction_minimal.php'>🧪 Minimal Test</a></p>";

?>