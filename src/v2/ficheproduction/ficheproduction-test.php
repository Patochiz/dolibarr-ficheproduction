<?php
/* Copyright (C) 2025 SuperAdmin
 * VERSION DE TEST avec affichage d'erreurs activé
 */

// ACTIVATION DEBUG - À RETIRER EN PRODUCTION
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- DÉBUT TEST FICHEPRODUCTION -->\n";

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
    echo "<!-- Dolibarr main.inc.php chargé -->\n";
}
if (!$res) {
    die("Include of main fails");
}

echo "<!-- Tentative de chargement des modules -->\n";

// Load required files
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

echo "<!-- Classes Dolibarr chargées -->\n";

// Load FicheProduction modules
try {
    require_once __DIR__ . '/includes/ficheproduction-permissions.php';
    echo "<!-- permissions.php chargé -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR permissions.php: " . $e->getMessage() . " -->\n";
}

try {
    require_once __DIR__ . '/includes/ficheproduction-ajax.php';
    echo "<!-- ajax.php chargé -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR ajax.php: " . $e->getMessage() . " -->\n";
}

try {
    require_once __DIR__ . '/includes/ficheproduction-actions.php';
    echo "<!-- actions.php chargé -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR actions.php: " . $e->getMessage() . " -->\n";
}

try {
    require_once __DIR__ . '/includes/ficheproduction-header.php';
    echo "<!-- header.php chargé -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR header.php: " . $e->getMessage() . " -->\n";
}

try {
    // Utiliser la version corrigée
    require_once __DIR__ . '/includes/ficheproduction-display-fixed.php';
    echo "<!-- display-fixed.php chargé -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR display-fixed.php: " . $e->getMessage() . " -->\n";
}

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));
$langs->load('ficheproduction@ficheproduction');

echo "<!-- Translations chargées -->\n";

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');

echo "<!-- Paramètres: id=$id, ref=$ref, action=$action -->\n";

// Handle AJAX actions first
if (!empty($action) && strpos($action, 'ficheproduction_') === 0) {
    echo "<!-- Action AJAX détectée: $action -->\n";
    handleFicheProductionAjax($action, $id, $db, $user);
    exit;
}

echo "<!-- Vérification des permissions -->\n";

// Check permissions and load object
try {
    $object = checkPermissionsAndLoadOrder($id, $ref, $user, $db);
    echo "<!-- Commande chargée: " . $object->ref . " -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR chargement commande: " . $e->getMessage() . " -->\n";
    die("Erreur lors du chargement de la commande: " . $e->getMessage());
}

echo "<!-- Traitement des actions -->\n";

// Handle form actions
try {
    handleFicheProductionActions($action, $object, $user, $langs);
    echo "<!-- Actions traitées -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR actions: " . $e->getMessage() . " -->\n";
}

echo "<!-- Préparation header -->\n";

// Prepare page header
try {
    $userCanEdit = prepareFicheProductionHeader($object, $langs);
    echo "<!-- Header préparé, userCanEdit=" . ($userCanEdit ? 'true' : 'false') . " -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR header: " . $e->getMessage() . " -->\n";
}

echo "<!-- Début affichage contenu -->\n";

// Display main content
try {
    displayFicheProductionContent($object, $form, $langs, $userCanEdit, $user, $db);
    echo "<!-- Contenu affiché -->\n";
} catch (Exception $e) {
    echo "<!-- ERREUR affichage: " . $e->getMessage() . " -->\n";
    echo "<div style='background:red; color:white; padding:20px; margin:20px;'>";
    echo "<h2>Erreur d'affichage:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<!-- Chargement JavaScript -->\n";

// Include JavaScript modules in correct order
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-core.js', 1).'"></script>';
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-utils.js', 1).'"></script>';
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-ajax.js', 1).'"></script>';
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-inventory.js', 1).'"></script>';
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-colis.js', 1).'"></script>';
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-dragdrop.js', 1).'"></script>';
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-ui.js', 1).'"></script>';
echo '<script src="'.dol_buildpath('/ficheproduction/js/ficheproduction-libre.js', 1).'"></script>';

echo '<script>';
echo 'console.log("[DEBUG] Initialisation FicheProduction...");';
echo 'document.addEventListener("DOMContentLoaded", function() {';
echo '    console.log("[DEBUG] DOM chargé, ID commande: '.$object->id.'");';
echo '    if (typeof initializeFicheProduction === "function") {';
echo '        try {';
echo '            initializeFicheProduction('.$object->id.', "'.newToken().'");';
echo '            console.log("[DEBUG] Initialisation réussie");';
echo '        } catch(e) {';
echo '            console.error("[DEBUG] Erreur initialisation:", e);';
echo '        }';
echo '    } else {';
echo '        console.error("[DEBUG] Fonction initializeFicheProduction non trouvée");';
echo '    }';
echo '});';
echo '</script>';

echo "<!-- FIN TEST FICHEPRODUCTION -->\n";

// Page footer
llxFooter();
?>