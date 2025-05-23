<?php
/**
 * Page principale du module Fiche de Production
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files
$langs->loadLangs(array("orders", "companies", "ficheproduction@ficheproduction"));

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Initialize technical objects
$object = new Commande($db);
$form = new Form($db);
$formmail = new FormMail($db);
$extrafields = new ExtraFields($db);

// Load object
if ($id > 0) {
    $result = $object->fetch($id);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }
    
    $result = $object->fetch_thirdparty();
    if ($result < 0) {
        dol_print_error($db, $object->error);
        exit;
    }
    
    // Chargement des extrafields
    $object->fetch_optionals();
}

// Security check using the standard Dolibarr function
$result = restrictedArea($user, 'commande', $id);

// Set userCanEdit - check if user has right to edit orders
$userCanEdit = $user->rights->commande->creer ?? false;

/*
 * ACTIONS
 */

// Action pour mettre à jour la référence chantier
if ($action == 'update_ref_chantierfp') {
    // Récupération des données du formulaire avec sécurité
    $ref_chantierfp = GETPOST('ref_chantierfp', 'alpha');
    
    // Vérification que les extrafields sont chargés
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    // Mise à jour de l'extrafield
    $object->array_options['options_ref_chantierfp'] = $ref_chantierfp;
    
    // Debug
    dol_syslog("ficheproduction.php: update_ref_chantierfp - valeur: ".$ref_chantierfp, LOG_DEBUG);
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
        dol_syslog("ficheproduction.php: Error saving extrafields: ".$object->error, LOG_ERR);
    } else {
        // Message de succès
        setEventMessages($langs->trans("RecordSaved"), null);
        // Redirection pour éviter les soumissions multiples
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}

// Action pour mettre à jour les commentaires
if ($action == 'update_commentaires_fp') {
    // Récupération des données du formulaire avec sécurité
    $commentaires_fp = GETPOST('commentaires_fp', 'restricthtml');
    
    // Vérification que les extrafields sont chargés
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    // Mise à jour de l'extrafield
    $object->array_options['options_commentaires_fp'] = $commentaires_fp;
    
    // Debug
    dol_syslog("ficheproduction.php: update_commentaires_fp - longueur: ".strlen($commentaires_fp), LOG_DEBUG);
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
        dol_syslog("ficheproduction.php: Error saving extrafields: ".$object->error, LOG_ERR);
    } else {
        // Message de succès
        setEventMessages($langs->trans("RecordSaved"), null);
        // Redirection pour éviter les soumissions multiples
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}

/*
 * VIEW
 */

// Page header & tabs
$title = $langs->trans("Order").' '.$object->ref.' - '.$langs->trans("FicheProduction");
llxHeader('', $title);

// Affichage des onglets
$head = commande_prepare_head($object);
print dol_get_fiche_head($head, 'ficheproduction', $langs->trans("CustomerOrder"), -1, 'order');

// Affichage des informations de la commande dans une card
print '<div class="fichecenter">';

// Tableau récapitulatif
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Référence commande
print '<tr><td class="titlefield">'.$langs->trans("OrderReference").':</td><td>'.$object->ref.'</td></tr>';

// Client
print '<tr><td>'.$langs->trans("CustomerName").':</td><td>'.$object->thirdparty->getNomUrl(1).'</td></tr>';

// Référence chantier (extrafield ref_chantierfp)
$ref_chantier = !empty($object->array_options['options_ref_chantier']) ? $object->array_options['options_ref_chantier'] : '';
$ref_chantierfp = !empty($object->array_options['options_ref_chantierfp']) ? $object->array_options['options_ref_chantierfp'] : $ref_chantier;

print '<tr>';
print '<td>'.$langs->trans("ProjectReference").':</td>';
print '<td>';

if ($action == 'edit_ref_chantierfp') {
    // Formulaire d'édition
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update_ref_chantierfp">';
    print '<input type="text" name="ref_chantierfp" size="40" value="'.$ref_chantierfp.'">';
    print ' <input type="submit" class="button" value="'.$langs->trans("Save").'">';
    print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("Cancel").'</a>';
    print '</form>';
} else {
    // Affichage normal avec icône d'édition
    print $ref_chantierfp;
    if ($userCanEdit) {
        print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_ref_chantierfp">'.img_edit($langs->trans("Edit")).'</a>';
    }
}

print '</td>';
print '</tr>';

// Commentaires (extrafield commentaires_fp)
$commentaires_fp = !empty($object->array_options['options_commentaires_fp']) ? $object->array_options['options_commentaires_fp'] : '';

print '<tr>';
print '<td>'.$langs->trans("Comments").':</td>';
print '<td>';

if ($action == 'edit_commentaires_fp') {
    // Formulaire d'édition avec éditeur WYSIWYG
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update_commentaires_fp">';
    
    // Utilisation de l'éditeur Dolibarr
    $doleditor = new DolEditor('commentaires_fp', $commentaires_fp, '', 200, 'dolibarr_notes', '', false, true, true, ROWS_5, '90%');
    print $doleditor->Create(1);
    
    print '<br><input type="submit" class="button" value="'.$langs->trans("Save").'">';
    print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("Cancel").'</a>';
    print '</form>';
} else {
    // Affichage normal avec icône d'édition
    print $commentaires_fp; // Le contenu HTML sera affiché correctement
    if ($userCanEdit) {
        print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_commentaires_fp">'.img_edit($langs->trans("Edit")).'</a>';
    }
}

print '</td>';
print '</tr>';

// Total colis (sera calculé dynamiquement via JavaScript)
print '<tr>';
print '<td>'.$langs->trans("TotalPackages").':</td>';
print '<td><span id="total_packages">0</span></td>';
print '</tr>';

// Poids total (extrafield poids_total)
$poids_total = !empty($object->array_options['options_poids_total']) ? $object->array_options['options_poids_total'] : 0;
print '<tr>';
print '<td>'.$langs->trans("TotalWeight").':</td>';
print '<td>'.$poids_total.' kg</td>';
print '</tr>';

print '</table>';
print '</div>';

// Affichage des objets pour le debug
if ($conf->global->MAIN_DEBUG_ENABLED) {
    print '<div class="fichecenter"><br>';
    print '<div class="underbanner clearboth"></div>';
    print '<div class="fichehalfleft">';
    print '<div class="div-table-responsive-no-min">';
    print '<table class="border centpercent tableforfield">';
    print '<tr><td colspan="2" class="titlefieldcreate">Debug Extrafields</td></tr>';
    print '<tr><td>object->array_options exists</td><td>' . (isset($object->array_options) ? 'Yes' : 'No') . '</td></tr>';
    if (isset($object->array_options) && is_array($object->array_options)) {
        foreach ($object->array_options as $key => $value) {
            print '<tr><td>' . $key . '</td><td>' . (is_array($value) ? print_r($value, true) : $value) . '</td></tr>';
        }
    }
    print '</table>';
    print '</div>';
    print '</div>';
    print '</div>';
}

// Message temporaire - sera remplacé plus tard par les tableaux de produits
print '<div style="padding: 20px; background-color: #f8f8f8; border: 1px solid #ddd; margin: 20px 0; border-radius: 4px;">';
print '<h3 style="color: #444;">'.$langs->trans("Module installed successfully").'</h3>';
print '<p>'.$langs->trans("The first step has been implemented: summary table with editable fields. The next step will be to implement the product tables and jspreadsheet integration.").'</p>';
print '</div>';

// Fermeture des onglets et pied de page
print dol_get_fiche_end();
llxFooter();
$db->close();