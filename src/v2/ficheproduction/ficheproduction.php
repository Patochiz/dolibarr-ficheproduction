<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        ficheproduction.php
 * \ingroup     ficheproduction
 * \brief       Page de gestion du colisage avec interface drag & drop moderne
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
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
// Try main.inc.php using relative path
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductionsession.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/lib/ficheproduction.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('orders', 'products', 'companies', 'ficheproduction@ficheproduction'));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new Commande($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->commande->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('ordercard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch extrafields
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once

// Check permissions
if (!$user->rights->commande->lire) {
    accessforbidden();
}
if (!isModEnabled('ficheproduction')) {
    accessforbidden('Module not enabled');
}
if (!$user->rights->ficheproduction->read) {
    accessforbidden();
}

/*
 * Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $error = 0;

    // Actions to build doc
    $upload_dir = $conf->commande->dir_output;
    $permissiontoadd = $user->rights->commande->creer;
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}

/*
 * View
 */

$form = new Form($db);

// CSS et JS files
$arrayofcss = array(
    '/custom/ficheproduction/css/ficheproduction.css'
);
$arrayofjs = array(
    '/custom/ficheproduction/js/ficheproduction.js'
);

llxHeader('', $langs->trans('ProductionSheet').' - '.$object->ref, '', '', 0, 0, $arrayofjs, $arrayofcss);

if ($id > 0 || !empty($ref)) {
    $result = $object->fetch($id, $ref);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }

    $head = commande_prepare_head($object);
    print dol_get_fiche_head($head, 'ficheproduction', $langs->trans('CustomerOrder'), -1, 'order');

    // Object card
    $linkback = '<a href="'.dol_buildpath('/commande/list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    // Ref customer
    $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    $morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
    // Project
    if (isModEnabled('project')) {
        $langs->load("projects");
        $morehtmlref .= '<br>';
        if (0) {
            $morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
            if ($action != 'classify') {
                $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
            }
            $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
        } else {
            if (!empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref .= $proj->getNomUrl(1);
                if ($proj->title) {
                    $morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
                }
            }
        }
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';

    // Main content area with drag & drop interface
    print '<div class="colisage-interface-container">';
    
    // Header with order info
    print '<div class="colisage-header">';
    print '<h2 class="colisage-title">';
    print '<i class="fas fa-boxes"></i> '.$langs->trans('ColisageManager').' v2.0';
    print '</h2>';
    print '<div class="colisage-subtitle">';
    print $langs->trans('OrderReference').': <strong>'.$object->ref.'</strong> - ';
    print $langs->trans('CustomerName').': <strong>'.$object->thirdparty->name.'</strong>';
    print '</div>';
    print '</div>';

    // Main colisage interface
    print '<div class="colisage-container">';
    
    // Zone Inventaire (left)
    print '<div class="inventory-zone">';
    print '<div class="inventory-header">';
    print '<i class="fas fa-warehouse"></i> '.$langs->trans('ProductInventory');
    print '</div>';
    
    print '<div class="inventory-controls">';
    print '<input type="text" class="search-box" placeholder="'.$langs->trans('SearchProduct').'..." id="searchBox">';
    print '<div class="sort-controls">';
    print '<select id="filterSelect" class="sort-select">';
    print '<option value="all">'.$langs->trans('AllProducts').'</option>';
    print '<option value="available">'.$langs->trans('AvailableProducts').'</option>';
    print '<option value="partial">'.$langs->trans('PartiallyUsed').'</option>';
    print '<option value="exhausted">'.$langs->trans('ExhaustedProducts').'</option>';
    print '</select>';
    print '<select id="sortSelect" class="sort-select">';
    print '<option value="ref">'.$langs->trans('SortByRef').'</option>';
    print '<option value="name">'.$langs->trans('SortByName').'</option>';
    print '<option value="length">'.$langs->trans('SortByLength').'</option>';
    print '<option value="width">'.$langs->trans('SortByWidth').'</option>';
    print '<option value="color">'.$langs->trans('SortByColor').'</option>';
    print '</select>';
    print '</div>';
    print '</div>';
    
    print '<div class="inventory-list" id="inventoryList">';
    // Products will be loaded via JavaScript
    print '</div>';
    print '</div>';

    // Zone Constructeur (right)
    print '<div class="constructor-zone">';
    print '<div class="constructor-header">';
    print '<div class="constructor-title">';
    print '<i class="fas fa-tools"></i> '.$langs->trans('ColisageConstructor');
    print '</div>';
    print '<button class="btn-add-colis" id="addNewColisBtn">';
    print '<i class="fas fa-plus"></i> '.$langs->trans('NewColis');
    print '</button>';
    print '</div>';
    
    print '<div class="colis-overview" id="colisOverview">';
    print '<table class="colis-table" id="colisTable">';
    print '<thead>';
    print '<tr>';
    print '<th>'.$langs->trans('ColisNumber').'</th>';
    print '<th>'.$langs->trans('ProductName').' + '.$langs->trans('ProductColor').'</th>';
    print '<th>'.$langs->trans('ProductQuantity').'</th>';
    print '<th>'.$langs->trans('ProductLength').'×'.$langs->trans('ProductWidth').'</th>';
    print '<th>'.$langs->trans('ColisStatus').'</th>';
    print '<th>'.$langs->trans('Action').'</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody id="colisTableBody">';
    // Colis will be loaded via JavaScript
    print '</tbody>';
    print '</table>';
    print '</div>';
    
    print '<div class="colis-detail" id="colisDetail">';
    print '<div class="empty-state">';
    print $langs->trans('SelectColis').'<br>';
    print $langs->trans('CreateNewColis');
    print '</div>';
    print '</div>';
    print '</div>';
    
    print '</div>'; // End colisage-container
    print '</div>'; // End colisage-interface-container

    print '</div>'; // End fichecenter

    print dol_get_fiche_end();

    // Hidden data for JavaScript
    print '<script type="text/javascript">';
    print 'var COLISAGE_CONFIG = {';
    print '  orderId: '.$object->id.',';
    print '  ajaxUrl: "'.dol_buildpath('/custom/ficheproduction/ficheproduction.php', 1).'",';
    print '  token: "'.newToken().'", ';
    print '  translations: {';
    print '    "confirm": "'.$langs->trans('Confirm').'", ';
    print '    "cancel": "'.$langs->trans('Cancel').'", ';
    print '    "save": "'.$langs->trans('Save').'", ';
    print '    "delete": "'.$langs->trans('Delete').'", ';
    print '    "edit": "'.$langs->trans('Edit').'", ';
    print '    "add": "'.$langs->trans('Add').'", ';
    print '    "confirmDeleteColis": "'.$langs->trans('ConfirmDeleteColis').'", ';
    print '    "confirmDeleteProduct": "'.$langs->trans('ConfirmDeleteProduct').'", ';
    print '    "insufficientQuantity": "'.$langs->trans('InsufficientQuantity').'", ';
    print '    "weightExceeded": "'.$langs->trans('WeightExceeded').'", ';
    print '    "sessionSaved": "'.$langs->trans('SessionSaved').'", ';
    print '    "colisCreated": "'.$langs->trans('ColisCreated').'", ';
    print '    "colisDeleted": "'.$langs->trans('ColisDeleted').'", ';
    print '    "productAdded": "'.$langs->trans('ProductAdded').'", ';
    print '    "productRemoved": "'.$langs->trans('ProductRemoved').'", ';
    print '    "quantityUpdated": "'.$langs->trans('QuantityUpdated').'", ';
    print '    "emptyState": "'.$langs->trans('SelectColis').'", ';
    print '    "createNewColis": "'.$langs->trans('CreateNewColis').'", ';
    print '    "dragProductHere": "'.$langs->trans('DragProductHere').'", ';
    print '    "emptyColis": "'.$langs->trans('EmptyColis').'"';
    print '  }';
    print '};';
    print '</script>';

} else {
    print $langs->trans('ErrorOrderNotFound');
}

// Initialize the colisage interface
print '<script type="text/javascript">';
print 'document.addEventListener("DOMContentLoaded", function() {';
print '  if (typeof ColisageManager !== "undefined") {';
print '    window.colisageManager = new ColisageManager(COLISAGE_CONFIG);';
print '    window.colisageManager.init();';
print '  } else {';
print '    console.error("ColisageManager not loaded");';
print '  }';
print '});';
print '</script>';

// End of page
llxFooter();
$db->close();