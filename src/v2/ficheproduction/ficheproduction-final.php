<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file        ficheproduction.php
 * \ingroup     ficheproduction
 * \brief       Interface drag & drop de colisage - Drag & Drop complet avec sauvegarde
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

// Load FicheProduction classes
require_once dol_buildpath('/ficheproduction/class/ficheproductionmanager.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductionsession.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolis.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolisline.class.php');

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));
// Load custom module translations
$langs->load('ficheproduction@ficheproduction');

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');

// Handle AJAX actions first
if (!empty($action) && strpos($action, 'ficheproduction_') === 0) {
    header('Content-Type: application/json');
    
    try {
        $object = new Commande($db);
        if ($id > 0) {
            $object->fetch($id);
        }
        
        switch ($action) {
            case 'ficheproduction_get_data':
                $data = array('products' => array(), 'colis' => array(), 'product_groups' => array());
                
                // Get products from order lines in the order of the command
                if ($object->id > 0) {
                    if (empty($object->lines)) {
                        $object->fetch_lines();
                    }
                    
                    $productIndex = 1;
                    $productGroups = array();
                    
                    foreach ($object->lines as $lineIndex => $line) {
                        if ($line->fk_product > 0) {
                            $product = new Product($db);
                            if ($product->fetch($line->fk_product) > 0 && $product->type == 0) {
                                
                                // Get quantity from extrafield "nombre" instead of qty
                                $quantity = 0;
                                if (isset($line->array_options['options_nombre']) && !empty($line->array_options['options_nombre'])) {
                                    $quantity = intval($line->array_options['options_nombre']);
                                } else {
                                    // Fallback to standard qty if nombre is not set
                                    $quantity = intval($line->qty);
                                }
                                
                                // Get dimensions from line extrafields
                                $length = 1000; // default
                                $width = 100;   // default
                                $color = 'Standard'; // default
                                $ref_ligne = ''; // default
                                
                                if (isset($line->array_options) && is_array($line->array_options)) {
                                    // Length variations
                                    if (isset($line->array_options['options_length']) && !empty($line->array_options['options_length'])) {
                                        $length = floatval($line->array_options['options_length']);
                                    } elseif (isset($line->array_options['options_longueur']) && !empty($line->array_options['options_longueur'])) {
                                        $length = floatval($line->array_options['options_longueur']);
                                    } elseif (isset($line->array_options['options_long']) && !empty($line->array_options['options_long'])) {
                                        $length = floatval($line->array_options['options_long']);
                                    }
                                    
                                    // Width variations
                                    if (isset($line->array_options['options_width']) && !empty($line->array_options['options_width'])) {
                                        $width = floatval($line->array_options['options_width']);
                                    } elseif (isset($line->array_options['options_largeur']) && !empty($line->array_options['options_largeur'])) {
                                        $width = floatval($line->array_options['options_largeur']);
                                    } elseif (isset($line->array_options['options_larg']) && !empty($line->array_options['options_larg'])) {
                                        $width = floatval($line->array_options['options_larg']);
                                    }
                                    
                                    // Color variations
                                    if (isset($line->array_options['options_color']) && !empty($line->array_options['options_color'])) {
                                        $color = $line->array_options['options_color'];
                                    } elseif (isset($line->array_options['options_couleur']) && !empty($line->array_options['options_couleur'])) {
                                        $color = $line->array_options['options_couleur'];
                                    }
                                    
                                    // Ref ligne from extrafield
                                    if (isset($line->array_options['options_ref_ligne']) && !empty($line->array_options['options_ref_ligne'])) {
                                        $ref_ligne = $line->array_options['options_ref_ligne'];
                                    }
                                }
                                
                                // Only add products with quantity > 0
                                if ($quantity > 0) {
                                    $productData = array(
                                        'id' => $productIndex++,
                                        'ref' => $product->ref,
                                        'name' => $product->label,
                                        'color' => $color,
                                        'ref_ligne' => $ref_ligne,
                                        'weight' => (!empty($product->weight) ? $product->weight : 1.0),
                                        'length' => $length,
                                        'width' => $width,
                                        'total' => $quantity, // Using extrafield "nombre" as total available
                                        'used' => 0,
                                        'line_id' => $line->id, // Store line ID for future reference
                                        'line_order' => $lineIndex // Keep original order from command
                                    );
                                    
                                    $data['products'][] = $productData;
                                    
                                    // Create product groups for the selector (name + color)
                                    $groupKey = $product->label . ' - ' . $color;
                                    if (!isset($productGroups[$groupKey])) {
                                        $productGroups[$groupKey] = array(
                                            'key' => $groupKey,
                                            'name' => $product->label,
                                            'color' => $color,
                                            'products' => array()
                                        );
                                    }
                                    $productGroups[$groupKey]['products'][] = $productData['id'];
                                }
                            }
                        }
                    }
                    
                    // Convert product groups to array
                    $data['product_groups'] = array_values($productGroups);
                }
                
                echo json_encode($data);
                break;
                
            case 'ficheproduction_load_saved_data':
                $manager = new FicheProductionManager($db);
                $result = $manager->loadColisageData($object->id);
                echo json_encode($result);
                break;
                
            case 'ficheproduction_save_colis':
                // Get JSON data from POST
                $colisData = GETPOST('colis_data', 'alpha');
                if (empty($colisData)) {
                    echo json_encode(['success' => false, 'error' => 'Aucune donnée de colis reçue']);
                    break;
                }
                
                // Decode JSON data
                $decodedData = json_decode($colisData, true);
                if (!$decodedData || !is_array($decodedData)) {
                    echo json_encode(['success' => false, 'error' => 'Données de colis invalides']);
                    break;
                }
                
                // Use FicheProductionManager to save data
                $manager = new FicheProductionManager($db);
                $result = $manager->saveColisageData($object->id, $object->socid, $decodedData, $user);
                
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Initialize objects
$object = new Commande($db);
$form = new Form($db);

// Check permissions
if (!$user->rights->commande->lire) {
    accessforbidden();
}

// Load object
if ($id > 0 || !empty($ref)) {
    $result = $object->fetch($id, $ref);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }
    
    // Load the thirdparty object
    if (method_exists($object, 'fetch_thirdparty')) {
        $object->fetch_thirdparty();
    }
    
    // Load lines and extrafields
    $object->fetch_lines();
    $object->fetch_optionals();
} else {
    header('Location: '.dol_buildpath('/commande/list.php', 1));
    exit;
}

// Set userCanEdit - check if user has right to edit orders
$userCanEdit = $user->rights->commande->creer ?? false;

/*
 * ACTIONS
 */

// Action pour mettre à jour la référence chantier
if ($action == 'update_ref_chantierfp') {
    $ref_chantierfp = GETPOST('ref_chantierfp', 'alpha');
    
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    $object->array_options['options_ref_chantierfp'] = $ref_chantierfp;
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        setEventMessages($langs->trans("RecordSaved"), null);
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}

// Action pour mettre à jour les commentaires
if ($action == 'update_commentaires_fp') {
    $commentaires_fp = GETPOST('commentaires_fp', 'restricthtml');
    
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    $object->array_options['options_commentaires_fp'] = $commentaires_fp;
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        setEventMessages($langs->trans("RecordSaved"), null);
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}

// Prepare objects for display
$head = commande_prepare_head($object);

// Start page
llxHeader('', $langs->trans('Order').' - '.$object->ref, '');

print dol_get_fiche_head($head, 'ficheproduction', $langs->trans('CustomerOrder'), -1, 'order');

// Object banner
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

// Count products in order
$product_count = 0;
if (!empty($object->lines)) {
    foreach ($object->lines as $line) {
        if ($line->fk_product > 0) {
            $temp_product = new Product($db);
            $temp_product->fetch($line->fk_product);
            if ($temp_product->type == 0) { // Only products, not services
                $product_count++;
            }
        }
    }
}

// Load external CSS files
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/ficheproduction/css/ficheproduction.css', 1).'">';
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/ficheproduction/css/ficheproduction-save.css', 1).'">';

// SECTION : Tableau récapitulatif des informations de commande
print '<div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom: 20px;">';

// Colonne gauche - Tableau récapitulatif
print '<div style="flex:1; min-width:300px;">';
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
print '<td><span id="total-packages">0</span></td>';
print '</tr>';

// Poids total (extrafield poids_total)
$poids_total = !empty($object->array_options['options_poids_total']) ? $object->array_options['options_poids_total'] : 0;
print '<tr>';
print '<td>'.$langs->trans("TotalWeight").':</td>';
print '<td><span id="total-weight">'.$poids_total.'</span> kg</td>';
print '</tr>';

print '</table>';
print '</div>'; // Fermeture div colonne gauche

// Colonne droite - Adresse de livraison
print '<div style="flex:0 0 300px;">';
print '<div class="delivery-info-box" style="padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; height:100%;">';
print '<h4>' . $langs->trans("DeliveryInformation") . '</h4>';

// Récupération des infos de livraison
$contact_livraison = "";
$address_livraison = "";
$phone_livraison = "";
$email_livraison = "";
$note_public_livraison = "";

// Récupérer les contacts associés à la commande
$contacts = $object->liste_contact(-1, 'external', 0, 'SHIPPING');
if (is_array($contacts) && count($contacts) > 0) {
    foreach ($contacts as $contact) {
        // Récupérer les détails du contact de livraison
        $contactstatic = new Contact($db);
        if ($contactstatic->fetch($contact['id']) > 0) {
            $contact_livraison = $contactstatic->getFullName($langs);
            $address_livraison = $contactstatic->address;
            $address_livraison .= "\n" . $contactstatic->zip . " " . $contactstatic->town;
            $address_livraison .= !empty($contactstatic->country) ? "\n" . $contactstatic->country : "";
            
            // Récupérer tous les numéros de téléphone disponibles
            $phone_array = array();
            if (!empty($contactstatic->phone_pro)) $phone_array[] = $langs->trans("Pro") . ": " . $contactstatic->phone_pro;
            if (!empty($contactstatic->phone_perso)) $phone_array[] = $langs->trans("Personal") . ": " . $contactstatic->phone_perso;
            if (!empty($contactstatic->phone_mobile)) $phone_array[] = $langs->trans("Mobile") . ": " . $contactstatic->phone_mobile;
            
            $phone_livraison = implode(" / ", $phone_array);
            $email_livraison = $contactstatic->email;
            
            // Récupérer la note publique du contact
            $note_public_livraison = $contactstatic->note_public;
        }
        break; // On ne prend que le premier contact de livraison
    }
}

// Si pas de contact spécifique, on prend l'adresse de livraison de la commande
if (empty($contact_livraison) && !empty($object->array_options['options_adresse_livraison'])) {
    $address_livraison = $object->array_options['options_adresse_livraison'];
}

// Si toujours pas d'adresse, on prend celle du client
if (empty($address_livraison)) {
    $contact_livraison = $object->thirdparty->name;
    $address_livraison = $object->thirdparty->address;
    $address_livraison .= "\n" . $object->thirdparty->zip . " " . $object->thirdparty->town;
    $address_livraison .= !empty($object->thirdparty->country) ? "\n" . $object->thirdparty->country : "";
    
    // Pour la société, on récupère les différents téléphones disponibles
    $phone_array = array();
    if (!empty($object->thirdparty->phone)) $phone_array[] = $langs->trans("Pro") . ": " . $object->thirdparty->phone;
    if (!empty($object->thirdparty->fax)) $phone_array[] = $langs->trans("Fax") . ": " . $object->thirdparty->fax;
    
    $phone_livraison = implode(" / ", $phone_array);
    $email_livraison = $object->thirdparty->email;
    
    // Récupérer la note publique de la société
    $note_public_livraison = $object->thirdparty->note_public;
}

// Affichage des informations
print '<p><strong>' . $langs->trans("Contact") . ':</strong> ' . $contact_livraison . '</p>';
print '<p><strong>' . $langs->trans("Address") . ':</strong> ' . nl2br($address_livraison) . '</p>';
if (!empty($phone_livraison)) {
    print '<p><strong>' . $langs->trans("Phone") . ':</strong> ' . $phone_livraison . '</p>';
}
if (!empty($email_livraison)) {
    print '<p><strong>' . $langs->trans("Email") . ':</strong> ' . $email_livraison . '</p>';
}
if (!empty($note_public_livraison)) {
    print '<p><strong>' . $langs->trans("NotePublic") . ':</strong> ' . nl2br($note_public_livraison) . '</p>';
}

print '</div>'; // Fin box
print '</div>'; // Fermeture div colonne droite

print '</div>'; // Fermeture div layout flexbox

?>

<div class="header">
    <h1>🚀 Gestionnaire de Colisage v2.0</h1>
    <div class="subtitle">Interface drag & drop pour colis mixtes - Commande <?php echo $object->ref; ?> (<?php echo $product_count; ?> produits commandés)</div>
</div>

<div class="colisage-container">
    <!-- Zone Inventaire -->
    <div class="inventory-zone">
        <div class="inventory-header">
            📦 Inventaire Produits (ordre de la commande)
        </div>
        
        <div class="inventory-controls">
            <input type="text" class="search-box" placeholder="🔍 Rechercher un produit..." id="searchBox">
            <div class="sort-controls">
                <select id="productGroupSelect" class="sort-select">
                    <option value="all">Tous les produits</option>
                    <!-- Options générées par JavaScript -->
                </select>
                <select id="sortSelect" class="sort-select">
                    <option value="original">📋 Ordre commande</option>
                    <option value="length_asc">📏 Longueur ↑</option>
                    <option value="length_desc">📏 Longueur ↓</option>
                    <option value="width_asc">📐 Largeur ↑</option>
                    <option value="width_desc">📐 Largeur ↓</option>
                    <option value="name_asc">🔤 Nom A→Z</option>
                    <option value="name_desc">🔤 Nom Z→A</option>
                </select>
            </div>
        </div>
        
        <div class="inventory-list" id="inventoryList">
            <!-- Généré par JavaScript -->
        </div>
    </div>

    <!-- Zone Constructeur -->
    <div class="constructor-zone">
        <div class="constructor-header">
            <div class="constructor-title">🏗️ Constructeur de Colis</div>
            <div class="constructor-buttons">
                <button class="btn-add-colis" id="addNewColisBtn">+ Nouveau Colis</button>
                <button class="btn-add-colis-libre" id="addNewColisLibreBtn">📦 Colis Libre</button>
            </div>
        </div>
        
        <div class="colis-overview" id="colisOverview">
            <table class="colis-table" id="colisTable">
                <thead>
                    <tr>
                        <th>Colis</th>
                        <th>Libellé + Couleur</th>
                        <th>Nombre</th>
                        <th>Long×Larg</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="colisTableBody">
                    <!-- Généré par JavaScript -->
                </tbody>
            </table>
        </div>
        
        <div class="colis-detail" id="colisDetail">
            <div class="empty-state">
                Sélectionnez un colis pour voir les détails<br>
                ou créez un nouveau colis pour commencer
            </div>
        </div>
    </div>
</div>

<?php
// SECTION : Signature et vérification finale
print '<div class="colisage-signature" style="margin-top:30px; padding-top:20px; border-top:1px solid #ddd;">';
print '<h3>' . $langs->trans("FinalChecks") . '</h3>';
print '<table class="colisage-check-table" style="width:100%; border-collapse:collapse;">';
print '<tr>';

// Colonne de gauche - COLISAGE FINAL
print '<td style="width:50%; padding:15px; vertical-align:top; border:1px solid #ddd;">';
print '<p><strong>' . $langs->trans("FinalPackaging") . '</strong></p>';
print '<p style="margin:10px 0;">______' . $langs->trans("Pallets") . ' ' . $langs->trans("Being") . ' ______' . $langs->trans("Packages") . '</p>';
print '<p style="margin:10px 0;">______' . $langs->trans("Bundles") . ' ' . $langs->trans("Being") . ' ______' . $langs->trans("Packages") . '</p>';
print '<p style="margin:10px 0;">      ' . $langs->trans("BulkPackages") . ' ______' . $langs->trans("Packages") . '</p>';
print '<p style="margin:15px 0;"><strong>' . $langs->trans("TotalNumberOfPackages") . '</strong> : ______' . $langs->trans("Packages") . '</p>';
print '</td>';

// Colonne de droite - Vérifications et signatures
print '<td style="width:50%; padding:15px; vertical-align:top; border:1px solid #ddd;">';
print '<p>' . $langs->trans("ProductionSheetVerificationBy") . ' : __________</p>';
print '<p style="height:20px;"></p>'; // Espace pour signature
print '<p>' . $langs->trans("FinalCounting") . ' ' . $langs->trans("AndReturnDateBy") . ' : __________</p>';
print '<p style="height:20px;"></p>'; // Espace pour signature
print '<p>' . $langs->trans("CoilsIDUsed") . ' : __________</p>';
print '<p style="height:20px;"></p>'; // Espace pour signature
print '</td>';

print '</tr>';
print '</table>';
print '</div>';

// Boutons d'action
print '<div class="tabsAction">';
if ($userCanEdit) {
    print '<a class="butAction" href="javascript:saveColisage();" id="saveColisageBtn">💾 ' . $langs->trans("Save") . '</a>';
}
print '<a class="butAction" href="javascript:preparePrint();">' . $langs->trans("PrintButton") . '</a>';
print '</div>';
?>

<!-- Console de debug -->
<div class="debug-console" id="debugConsole"></div>

<!-- Modales custom -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-content">
        <div class="modal-header">Confirmation</div>
        <div class="modal-message" id="confirmMessage"></div>
        <div class="modal-buttons">
            <button class="modal-btn secondary" id="confirmCancel">Annuler</button>
            <button class="modal-btn danger" id="confirmOk">Confirmer</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="promptModal">
    <div class="modal-content">
        <div class="modal-header">Saisie</div>
        <div class="modal-message" id="promptMessage"></div>
        <input type="text" class="modal-input" id="promptInput" placeholder="Saisir la valeur...">
        <div class="modal-buttons">
            <button class="modal-btn secondary" id="promptCancel">Annuler</button>
            <button class="modal-btn primary" id="promptOk">Valider</button>
        </div>
    </div>
</div>

<!-- Modale Colis Libre -->
<div class="modal-overlay" id="colisLibreModal">
    <div class="modal-content modal-large">
        <div class="modal-header">📦 Création Colis Libre</div>
        <div class="modal-message">Ajout d'éléments libres (échantillons, catalogues, etc.)</div>
        
        <div class="colis-libre-form">
            <h4>Contenu du colis libre :</h4>
            <div id="colisLibreItems">
                <!-- Items générés par JavaScript -->
            </div>
            <button type="button" class="btn-add-item" id="addColisLibreItem">+ Ajouter un élément</button>
        </div>
        
        <div class="modal-buttons">
            <button class="modal-btn secondary" id="colisLibreCancel">Annuler</button>
            <button class="modal-btn primary" id="colisLibreOk">Créer le colis</button>
        </div>
    </div>
</div>

<!-- Modale de sauvegarde -->
<div class="modal-overlay" id="saveModal">
    <div class="modal-content">
        <div class="modal-header">💾 Sauvegarde en cours...</div>
        <div class="modal-message">
            <div class="save-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="saveProgressFill"></div>
                </div>
                <div id="saveStatusMessage">Préparation des données...</div>
            </div>
        </div>
    </div>
</div>

<!-- Include JavaScript unifié -->
<script src="<?php echo dol_buildpath('/ficheproduction/js/ficheproduction.js', 1); ?>"></script>

<script>
// Initialisation avec les paramètres PHP
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser l'application avec les paramètres de la commande
    initializeFicheProduction(<?php echo $object->id; ?>, '<?php echo newToken(); ?>');
});
</script>

<?php
print '</div>'; // End fichecenter
print dol_get_fiche_end();

llxFooter();
$db->close();
?>
