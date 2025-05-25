<?php
/**
 * \file        includes/ficheproduction-header.php
 * \ingroup     ficheproduction
 * \brief       Gestion de l'en-tête et préparation de la page pour FicheProduction
 */

// Prevent direct access
if (!defined('DOL_VERSION')) {
    print "Error: This module requires Dolibarr framework.\n";
    exit;
}

/**
 * Prepare FicheProduction page header
 * 
 * @param Commande $object Order object
 * @param Translate $langs Language object
 * @return bool            True if user can edit
 */
function prepareFicheProductionHeader($object, $langs) 
{
    global $user;
    
    // Set userCanEdit - check if user has right to edit orders
    $userCanEdit = $user->rights->commande->creer ?? false;
    
    // Prepare objects for display
    $head = commande_prepare_head($object);

    // Start page
    llxHeader('', $langs->trans('Order').' - '.$object->ref, '');

    print dol_get_fiche_head($head, 'ficheproduction', $langs->trans('CustomerOrder'), -1, 'order');

    // Object banner
    displayObjectBanner($object, $langs);
    
    return $userCanEdit;
}

/**
 * Display object banner
 * 
 * @param Commande $object Order object
 * @param Translate $langs Language object
 */
function displayObjectBanner($object, $langs) 
{
    global $form;
    
    if (!$form) {
        $form = new Form($GLOBALS['db']);
    }
    
    $linkback = '<a href="'.dol_buildpath('/commande/list.php', 1).'?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    $morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    
    if (is_object($object->thirdparty)) {
        $morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
    } else {
        $morehtmlref .= '<br>Client non défini';
    }
    
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    
    // Load external CSS file
    print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/ficheproduction/css/ficheproduction.css', 1).'">';
}
?>