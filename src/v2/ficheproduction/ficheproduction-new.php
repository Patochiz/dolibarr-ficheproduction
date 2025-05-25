<?php
/* Copyright (C) 2025 SuperAdmin
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file        ficheproduction.php (version allégée)
 * \ingroup     ficheproduction  
 * \brief       Interface drag & drop de colisage - Fichier principal restructuré
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Load required files
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Load FicheProduction modules
require_once __DIR__ . '/includes/ficheproduction-permissions.php';
require_once __DIR__ . '/includes/ficheproduction-ajax.php';
require_once __DIR__ . '/includes/ficheproduction-actions.php';
require_once __DIR__ . '/includes/ficheproduction-header.php';
require_once __DIR__ . '/includes/ficheproduction-display.php';

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));
$langs->load('ficheproduction@ficheproduction');

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');

// Handle AJAX actions first (delegated to ajax module)
if (!empty($action) && strpos($action, 'ficheproduction_') === 0) {
    handleFicheProductionAjax($action, $id, $db, $user);
    exit;
}

// Check permissions and load object (delegated to permissions module)
$object = checkPermissionsAndLoadOrder($id, $ref, $user, $db);

// Handle form actions (delegated to actions module)
handleFicheProductionActions($action, $object, $user, $langs);

// Prepare page header (delegated to header module)
$userCanEdit = prepareFicheProductionHeader($object, $langs);

// Display main content (delegated to display module)
displayFicheProductionContent($object, $form, $langs, $userCanEdit, $user, $db);

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
echo 'document.addEventListener("DOMContentLoaded", function() {';
echo '    initializeFicheProduction('.$object->id.', "'.newToken().'");';
echo '});';
echo '</script>';

// Page footer
llxFooter();
?>