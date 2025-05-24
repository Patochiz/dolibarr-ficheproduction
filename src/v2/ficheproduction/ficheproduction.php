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
 * \brief       Interface drag & drop de colisage - Drag & Drop complet
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

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));

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
                
            case 'ficheproduction_add_colis':
                echo json_encode(['success' => true, 'colis_id' => rand(1000, 9999)]);
                break;
                
            case 'ficheproduction_add_product':
                $colis_id = GETPOST('colis_id', 'int');
                $product_id = GETPOST('product_id', 'int');
                $quantite = GETPOST('quantite', 'int');
                
                echo json_encode(['success' => true, 'message' => "Produit $product_id ajouté au colis $colis_id (qté: $quantite)"]);
                break;
                
            case 'ficheproduction_save_colis':
                // TODO: Implement saving to database
                echo json_encode(['success' => true, 'message' => 'Colis sauvegardé avec succès']);
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
    // Récupération des données du formulaire avec sécurité
    $ref_chantierfp = GETPOST('ref_chantierfp', 'alpha');
    
    // Vérification que les extrafields sont chargés
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    // Mise à jour de l'extrafield
    $object->array_options['options_ref_chantierfp'] = $ref_chantierfp;
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
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
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        // Message de succès
        setEventMessages($langs->trans("RecordSaved"), null);
        // Redirection pour éviter les soumissions multiples
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

// Load external CSS file
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/ficheproduction/css/ficheproduction.css', 1).'">';

// NOUVELLE SECTION : Tableau récapitulatif des informations de commande (inspiré de la V1)
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
// FIN NOUVELLE SECTION

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
// NOUVELLE SECTION : Signature et vérification finale (inspirée de la V1)
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
// FIN NOUVELLE SECTION

// Bouton d'impression
print '<div class="tabsAction">';
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

<script>
        // Variables globales
        let products = [];
        let productGroups = [];
        let colis = [];
        let selectedColis = null;
        let draggedProduct = null;
        let draggedColisLine = null;
        let currentProductGroup = 'all';
        let currentSort = 'original';
        let isDragging = false;

        // Configuration
        const ORDER_ID = <?php echo $object->id; ?>;
        const TOKEN = '<?php echo newToken(); ?>';

        // Fonction de debug
        function debugLog(message) {
            console.log(message);
            const debugConsole = document.getElementById('debugConsole');
            if (debugConsole) {
                debugConsole.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '<br>';
                debugConsole.scrollTop = debugConsole.scrollHeight;
            }
        }

        // Fonction pour mettre à jour les totaux dans le tableau récapitulatif
        function updateSummaryTotals() {
            // Calculer le nombre total de colis
            let totalPackages = 0;
            let totalWeight = 0;
            
            colis.forEach(c => {
                totalPackages += c.multiple;
                totalWeight += c.totalWeight * c.multiple;
            });
            
            // Mettre à jour l'affichage
            const totalPackagesElement = document.getElementById('total-packages');
            const totalWeightElement = document.getElementById('total-weight');
            
            if (totalPackagesElement) {
                totalPackagesElement.textContent = totalPackages;
            }
            
            if (totalWeightElement) {
                totalWeightElement.textContent = totalWeight.toFixed(1);
            }
            
            debugLog(`Totaux mis à jour: ${totalPackages} colis, ${totalWeight.toFixed(1)} kg`);
        }

        // Fonction pour créer une vignette produit (utilisée dans inventaire et colis)
        function createProductVignette(product, isInColis = false, currentQuantity = 1) {
            // Gestion des produits libres (pas de contraintes de stock)
            if (product.isLibre) {
                const vignetteElement = document.createElement('div');
                vignetteElement.className = 'product-item libre-item';
                if (isInColis) {
                    vignetteElement.classList.add('in-colis');
                }

                const quantityInputHtml = isInColis ? `
                    <div class="quantity-input-container">
                        <span class="quantity-input-label">Qté:</span>
                        <input type="number" class="quantity-input" value="${currentQuantity}" min="1" 
                               data-product-id="${product.id}">
                    </div>
                ` : '';

                vignetteElement.innerHTML = `
                    <div class="product-header">
                        <span class="product-ref">${product.name}</span>
                        <span class="product-color libre-badge">LIBRE</span>
                    </div>
                    
                    <div class="product-dimensions">
                        Poids unitaire: ${product.weight}kg
                    </div>
                    <div class="quantity-info">
                        <span class="libre-info">📦 Élément libre</span>
                    </div>
                    ${quantityInputHtml}
                    <div class="status-indicator libre"></div>
                `;

                return vignetteElement;
            }

            // Produits normaux (existant)
            const available = product.total - product.used;
            const percentage = (product.used / product.total) * 100;
            let status = 'available';
            
            if (available === 0) status = 'exhausted';
            else if (product.used > 0) status = 'partial';

            const vignetteElement = document.createElement('div');
            vignetteElement.className = `product-item ${status}`;
            if (isInColis) {
                vignetteElement.classList.add('in-colis');
            }
            if (!isInColis) {
                vignetteElement.draggable = status !== 'exhausted';
                vignetteElement.dataset.productId = product.id;
            }

            // Ajouter input de quantité pour les vignettes dans les colis
            const quantityInputHtml = isInColis ? `
                <div class="quantity-input-container">
                    <span class="quantity-input-label">Qté:</span>
                    <input type="number" class="quantity-input" value="${currentQuantity}" min="1" 
                           data-product-id="${product.id}">
                </div>
            ` : '';

            vignetteElement.innerHTML = `
                <div class="product-header">
                    <span class="product-ref">${product.name}</span>
                    <span class="product-color">${product.color}</span>
                </div>
                
                <div class="product-dimensions">
                    L: ${product.length}mm × l: ${product.width}mm ${product.ref_ligne ? `<strong>Réf: ${product.ref_ligne}</strong>` : ''}
                </div>
                <div class="quantity-info">
                    <span class="quantity-used">${product.used}</span>
                    <span>/</span>
                    <span class="quantity-total">${product.total}</span>
                    <div class="quantity-bar">
                        <div class="quantity-progress" style="width: ${percentage}%"></div>
                    </div>
                </div>
                ${quantityInputHtml}
                <div class="status-indicator ${status === 'exhausted' ? 'error' : status === 'partial' ? 'warning' : ''}"></div>
            `;

            return vignetteElement;
        }

        // Fonction pour créer un produit libre
        function createLibreProduct(name, weight, quantity = 1) {
            const newId = Math.max(...products.map(p => p.id), 10000) + 1;
            return {
                id: newId,
                name: name,
                weight: parseFloat(weight),
                isLibre: true,
                total: 9999, // Pas de limite pour les produits libres
                used: 0
            };
        }

        // Modale Colis Libre
        function showColisLibreModal() {
            const modal = document.getElementById('colisLibreModal');
            const itemsContainer = document.getElementById('colisLibreItems');
            
            // Réinitialiser le contenu
            itemsContainer.innerHTML = '';
            addColisLibreItem(); // Ajouter un premier élément

            modal.classList.add('show');
        }

        function addColisLibreItem() {
            const container = document.getElementById('colisLibreItems');
            const itemId = Date.now();
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'colis-libre-item';
            itemDiv.dataset.itemId = itemId;
            
            itemDiv.innerHTML = `
                <div class="colis-libre-fields">
                    <input type="text" class="libre-name" placeholder="Nom de l'élément (ex: Échantillon Bleu)" required>
                    <input type="number" class="libre-weight" placeholder="Poids (kg)" step="0.1" min="0" value="0.5" required>
                    <input type="number" class="libre-quantity" placeholder="Quantité" min="1" value="1" required>
                    <button type="button" class="btn-remove-libre-item">✕</button>
                </div>
            `;
            
            // Event listener pour supprimer l'élément
            const removeBtn = itemDiv.querySelector('.btn-remove-libre-item');
            removeBtn.addEventListener('click', () => {
                itemDiv.remove();
                // S'assurer qu'il reste au moins un élément
                if (container.children.length === 0) {
                    addColisLibreItem();
                }
            });
            
            container.appendChild(itemDiv);
        }

        async function createColisLibre() {
            const items = document.querySelectorAll('.colis-libre-item');
            const libreProducts = [];
            
            // Valider et récupérer les données
            for (const item of items) {
                const name = item.querySelector('.libre-name').value.trim();
                const weight = parseFloat(item.querySelector('.libre-weight').value);
                const quantity = parseInt(item.querySelector('.libre-quantity').value);
                
                if (!name || isNaN(weight) || weight < 0 || isNaN(quantity) || quantity < 1) {
                    await showConfirm('Veuillez remplir correctement tous les champs.');
                    return false;
                }
                
                libreProducts.push({
                    name: name,
                    weight: weight,
                    quantity: quantity
                });
            }
            
            if (libreProducts.length === 0) {
                await showConfirm('Veuillez ajouter au moins un élément.');
                return false;
            }
            
            // Créer le colis libre
            const newId = Math.max(...colis.map(c => c.id), 0) + 1;
            const newNumber = Math.max(...colis.map(c => c.number), 0) + 1;
            
            const newColis = {
                id: newId,
                number: newNumber,
                products: [],
                totalWeight: 0,
                maxWeight: 25,
                status: 'ok',
                multiple: 1,
                isLibre: true // Marquer comme colis libre
            };

            // Ajouter chaque produit libre au colis
            libreProducts.forEach(libreData => {
                // Créer le produit libre et l'ajouter à la liste globale
                const libreProduct = createLibreProduct(libreData.name, libreData.weight);
                products.push(libreProduct);
                
                // Ajouter au colis
                newColis.products.push({
                    productId: libreProduct.id,
                    quantity: libreData.quantity,
                    weight: libreData.quantity * libreProduct.weight
                });
            });

            // Recalculer le poids total
            newColis.totalWeight = newColis.products.reduce((sum, p) => sum + p.weight, 0);

            colis.push(newColis);
            
            debugLog(`Colis libre créé avec ${libreProducts.length} éléments`);
            
            // Re-render et sélectionner le nouveau colis
            renderInventory();
            renderColisOverview();
            selectColis(newColis);
            updateSummaryTotals(); // Mettre à jour les totaux
            
            return true;
        }

        // Fonction de tri des produits
        function sortProducts(productsList, sortType) {
            const sorted = [...productsList];
            
            switch(sortType) {
                case 'original':
                    // Trier par line_order (ordre original de la commande)
                    return sorted.sort((a, b) => a.line_order - b.line_order);
                    
                case 'length_asc':
                    return sorted.sort((a, b) => a.length - b.length);
                    
                case 'length_desc':
                    return sorted.sort((a, b) => b.length - a.length);
                    
                case 'width_asc':
                    return sorted.sort((a, b) => a.width - b.width);
                    
                case 'width_desc':
                    return sorted.sort((a, b) => b.width - a.width);
                    
                case 'name_asc':
                    return sorted.sort((a, b) => a.name.localeCompare(b.name));
                    
                case 'name_desc':
                    return sorted.sort((a, b) => b.name.localeCompare(a.name));
                    
                default:
                    return sorted.sort((a, b) => a.line_order - b.line_order);
            }
        }

        // Modales custom
        function showConfirm(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                const messageEl = document.getElementById('confirmMessage');
                const okBtn = document.getElementById('confirmOk');
                const cancelBtn = document.getElementById('confirmCancel');

                messageEl.textContent = message;
                modal.classList.add('show');

                const cleanup = () => {
                    modal.classList.remove('show');
                    okBtn.removeEventListener('click', handleOk);
                    cancelBtn.removeEventListener('click', handleCancel);
                };

                const handleOk = () => {
                    cleanup();
                    resolve(true);
                };

                const handleCancel = () => {
                    cleanup();
                    resolve(false);
                };

                okBtn.addEventListener('click', handleOk);
                cancelBtn.addEventListener('click', handleCancel);
            });
        }

        function showPrompt(message, defaultValue = '') {
            return new Promise((resolve) => {
                const modal = document.getElementById('promptModal');
                const messageEl = document.getElementById('promptMessage');
                const inputEl = document.getElementById('promptInput');
                const okBtn = document.getElementById('promptOk');
                const cancelBtn = document.getElementById('promptCancel');

                messageEl.textContent = message;
                inputEl.value = defaultValue;
                modal.classList.add('show');
                
                // Focus sur l'input
                setTimeout(() => inputEl.focus(), 100);

                const cleanup = () => {
                    modal.classList.remove('show');
                    okBtn.removeEventListener('click', handleOk);
                    cancelBtn.removeEventListener('click', handleCancel);
                    inputEl.removeEventListener('keypress', handleKeypress);
                };

                const handleOk = () => {
                    const value = inputEl.value.trim();
                    cleanup();
                    resolve(value || null);
                };

                const handleCancel = () => {
                    cleanup();
                    resolve(null);
                };

                const handleKeypress = (e) => {
                    if (e.key === 'Enter') {
                        handleOk();
                    } else if (e.key === 'Escape') {
                        handleCancel();
                    }
                };

                okBtn.addEventListener('click', handleOk);
                cancelBtn.addEventListener('click', handleCancel);
                inputEl.addEventListener('keypress', handleKeypress);
            });
        }

        // API AJAX Functions
        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('token', TOKEN);
            formData.append('id', ORDER_ID);
            
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }

            try {
                debugLog(`API Call: ${action}`);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                debugLog(`Response: ${text.substring(0, 200)}...`);
                
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    debugLog(`JSON Parse Error: ${parseError.message}`);
                    return { success: false, error: 'Invalid JSON response' };
                }
            } catch (error) {
                debugLog('Erreur API: ' + error.message);
                return { success: false, error: error.message };
            }
        }

        async function loadData() {
            debugLog('Chargement des données (ordre commande + groupes produits)...');
            const result = await apiCall('ficheproduction_get_data');
            
            if (result && result.products) {
                // Les produits sont déjà dans l'ordre de la commande
                products = result.products;
                productGroups = result.product_groups || [];
                
                debugLog(`Chargé ${products.length} produits dans l'ordre de la commande`);
                debugLog(`Trouvé ${productGroups.length} groupes de produits`);
                
                populateProductGroupSelector();
                renderInventory();
            } else {
                debugLog('Erreur lors du chargement des données');
            }
        }

        function populateProductGroupSelector() {
            const selector = document.getElementById('productGroupSelect');
            
            // Conserver l'option "Tous les produits"
            selector.innerHTML = '<option value="all">Tous les produits</option>';
            
            // Ajouter les groupes de produits
            productGroups.forEach(group => {
                const option = document.createElement('option');
                option.value = group.key;
                option.textContent = `${group.name} - ${group.color}`;
                selector.appendChild(option);
            });
            
            debugLog(`Sélecteur rempli avec ${productGroups.length} groupes`);
        }

        // Gestion globale des zones de drop
        function activateDropZones() {
            if (!isDragging) return;
            
            debugLog('🎯 Activation des zones de drop');
            
            // Activer toutes les lignes du tableau colis
            const allColisRows = document.querySelectorAll('#colisTableBody tr');
            allColisRows.forEach(row => {
                if (row.dataset.colisId || row.classList.contains('colis-group-header') || row.classList.contains('colis-group-item')) {
                    row.classList.add('drop-active');
                }
            });
            
            // Activer la zone de détail du colis sélectionné
            const colisContent = document.getElementById('colisContent');
            if (colisContent && selectedColis) {
                colisContent.classList.add('drop-zone-active');
            }
        }

        function deactivateDropZones() {
            debugLog('🔴 Désactivation des zones de drop');
            
            // Désactiver toutes les zones de drop
            const dropActiveElements = document.querySelectorAll('.drop-active');
            dropActiveElements.forEach(el => el.classList.remove('drop-active'));
            
            const dropZoneActive = document.querySelectorAll('.drop-zone-active');
            dropZoneActive.forEach(el => el.classList.remove('drop-zone-active'));
        }

        // Fonctions principales définies en premier
        function addNewColis() {
            debugLog('Ajout nouveau colis');
            const newId = Math.max(...colis.map(c => c.id), 0) + 1;
            const newNumber = Math.max(...colis.map(c => c.number), 0) + 1;
            
            const newColis = {
                id: newId,
                number: newNumber,
                products: [],
                totalWeight: 0,
                maxWeight: 25,
                status: 'ok',
                multiple: 1,
                isLibre: false
            };

            colis.push(newColis);
            renderColisOverview();
            selectColis(newColis);
            updateSummaryTotals(); // Mettre à jour les totaux
        }

        async function deleteColis(colisId) {
            debugLog(`Tentative suppression colis ID: ${colisId}`);
            
            const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer ce colis ?');
            if (!confirmed) {
                debugLog('Suppression annulée par utilisateur');
                return;
            }

            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) {
                debugLog('ERREUR: Colis non trouvé');
                await showConfirm('Erreur: Colis non trouvé');
                return;
            }
            
            debugLog(`Suppression colis: ${JSON.stringify(coliData)}`);
            
            // Remettre tous les produits dans l'inventaire (sauf les produits libres)
            coliData.products.forEach(p => {
                const product = products.find(prod => prod.id === p.productId);
                if (product && !product.isLibre) {
                    const quantityToRestore = p.quantity * coliData.multiple;
                    product.used -= quantityToRestore;
                    debugLog(`Remise en stock extrafield "nombre": ${product.ref} +${quantityToRestore}`);
                }
            });

            // Supprimer les produits libres de la liste globale
            if (coliData.isLibre) {
                coliData.products.forEach(p => {
                    const productIndex = products.findIndex(prod => prod.id === p.productId && prod.isLibre);
                    if (productIndex > -1) {
                        products.splice(productIndex, 1);
                        debugLog(`Produit libre supprimé: ${p.productId}`);
                    }
                });
            }

            // Supprimer le colis
            const colisIndex = colis.findIndex(c => c.id === colisId);
            if (colisIndex > -1) {
                colis.splice(colisIndex, 1);
                debugLog('Colis supprimé de la liste');
            }
            
            // Déselectionner si c'était le colis sélectionné
            if (selectedColis && selectedColis.id === colisId) {
                selectedColis = null;
                debugLog('Colis désélectionné');
            }

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
            updateSummaryTotals(); // Mettre à jour les totaux
            
            debugLog('Interface mise à jour après suppression');
        }

        async function showDuplicateDialog(colisId) {
            debugLog(`Ouverture dialogue duplication pour colis ID: ${colisId}`);
            
            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) {
                debugLog('ERREUR: Colis non trouvé pour duplication');
                await showConfirm('Erreur: Colis non trouvé');
                return;
            }

            const currentMultiple = coliData.multiple || 1;
            const message = `Combien de fois créer ce colis identique ?\n\nActuellement: ${currentMultiple} colis`;
            const newMultiple = await showPrompt(message, currentMultiple.toString());
            
            debugLog(`Nouvelle valeur saisie: ${newMultiple}`);
            
            if (newMultiple !== null && !isNaN(newMultiple) && parseInt(newMultiple) > 0) {
                updateColisMultiple(colisId, parseInt(newMultiple));
            } else if (newMultiple !== null) {
                await showConfirm('Veuillez saisir un nombre entier positif');
            }
        }

        async function updateColisMultiple(colisId, multiple) {
            debugLog(`Mise à jour multiple colis ${colisId}: ${multiple}`);
            
            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) {
                debugLog('ERREUR: Colis non trouvé');
                return;
            }

            const oldMultiple = coliData.multiple;
            const newMultiple = parseInt(multiple);
            
            if (isNaN(newMultiple) || newMultiple < 1) {
                await showConfirm('Le nombre de colis doit être un entier positif');
                return;
            }

            // Calculer la différence pour ajuster les quantités utilisées
            const multipleDiff = newMultiple - oldMultiple;
            debugLog(`Différence multiple: ${multipleDiff}`);
            
            // Mettre à jour les quantités utilisées pour chaque produit (sauf libres)
            for (const p of coliData.products) {
                const product = products.find(prod => prod.id === p.productId);
                if (product && !product.isLibre) {
                    product.used += p.quantity * multipleDiff;
                    
                    // Vérifier qu'on ne dépasse pas le total disponible (extrafield nombre)
                    if (product.used > product.total) {
                        await showConfirm(`Attention: ${product.ref} - Quantité dépassée! Utilisé: ${product.used}, Total (extrafield nombre): ${product.total}`);
                        // Revenir à l'ancienne valeur
                        product.used -= p.quantity * multipleDiff;
                        return;
                    }
                    debugLog(`Mise à jour stock ${product.ref}: ${product.used}/${product.total} (extrafield nombre)`);
                }
            }

            coliData.multiple = newMultiple;
            
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id === colisId) {
                renderColisDetail();
            }
            updateSummaryTotals(); // Mettre à jour les totaux
        }

        function removeProductFromColis(colisId, productId) {
            debugLog(`Suppression produit ${productId} du colis ${colisId}`);
            
            const coliData = colis.find(c => c.id === colisId);
            const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
            
            if (!coliData || !productInColis) {
                debugLog('ERREUR: Colis ou produit non trouvé dans le colis');
                return;
            }

            // Remettre les quantités dans l'inventaire (tenir compte des multiples) sauf pour les produits libres
            const product = products.find(p => p.id === productId);
            if (product && !product.isLibre) {
                product.used -= productInColis.quantity * coliData.multiple;
                debugLog(`Remise en stock extrafield "nombre": ${product.ref} +${productInColis.quantity * coliData.multiple}`);
            }

            // Supprimer le produit du colis
            const productIndex = coliData.products.findIndex(p => p.productId === productId);
            if (productIndex > -1) {
                coliData.products.splice(productIndex, 1);
            }
            
            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
            updateSummaryTotals(); // Mettre à jour les totaux
        }

        function updateProductQuantity(colisId, productId, newQuantity) {
            debugLog(`Mise à jour quantité: Colis ${colisId}, Produit ${productId}, Nouvelle quantité: ${newQuantity}`);
            
            const coliData = colis.find(c => c.id === colisId);
            const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
            const product = products.find(p => p.id === productId);
            
            if (!productInColis || !product || !coliData) {
                debugLog('ERREUR: Données non trouvées');
                return;
            }

            const oldQuantity = productInColis.quantity;
            const quantityDiff = parseInt(newQuantity) - oldQuantity;

            // Pour les produits libres, pas de vérification de stock
            if (product.isLibre) {
                productInColis.quantity = parseInt(newQuantity);
                productInColis.weight = productInColis.quantity * product.weight;
                
                // Recalculer le poids total
                coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);
                
                debugLog(`Quantité mise à jour pour produit libre ${product.name}: ${productInColis.quantity}`);
                
                // Re-render
                renderInventory();
                renderColisOverview();
                renderColisDetail();
                updateSummaryTotals(); // Mettre à jour les totaux
                return;
            }

            // Vérifier la disponibilité (tenir compte des multiples) pour les produits normaux
            const totalQuantityNeeded = quantityDiff * coliData.multiple;
            const available = product.total - product.used;
            
            if (totalQuantityNeeded > available) {
                alert(`Quantité insuffisante ! Disponible (extrafield nombre): ${available}, Besoin: ${totalQuantityNeeded}`);
                // Remettre l'ancienne valeur dans l'input
                const input = document.querySelector(`input[data-product-id="${productId}"]`);
                if (input) input.value = oldQuantity;
                return;
            }

            // Mettre à jour les quantités
            productInColis.quantity = parseInt(newQuantity);
            productInColis.weight = productInColis.quantity * product.weight;
            product.used += totalQuantityNeeded;

            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            debugLog(`Quantité mise à jour ${product.ref}: ${product.used}/${product.total} (extrafield nombre)`);

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
            updateSummaryTotals(); // Mettre à jour les totaux
        }

        function renderInventory() {
            const container = document.getElementById('inventoryList');
            container.innerHTML = '';

            // Filtrer les produits selon le groupe sélectionné (exclure les produits libres)
            let filteredProducts = products.filter(p => !p.isLibre);
            if (currentProductGroup !== 'all') {
                const selectedGroup = productGroups.find(g => g.key === currentProductGroup);
                if (selectedGroup) {
                    filteredProducts = filteredProducts.filter(product => selectedGroup.products.includes(product.id));
                    debugLog(`Filtrage par groupe "${currentProductGroup}": ${filteredProducts.length} produits`);
                }
            }

            // Trier les produits selon le critère sélectionné
            const sortedProducts = sortProducts(filteredProducts, currentSort);
            debugLog(`Tri appliqué: ${currentSort} - ${sortedProducts.length} produits`);

            sortedProducts.forEach(product => {
                const productElement = createProductVignette(product, false);

                // Événements drag & drop
                productElement.addEventListener('dragstart', function(e) {
                    const available = product.total - product.used;
                    if (available === 0) {
                        e.preventDefault();
                        return;
                    }
                    
                    isDragging = true;
                    draggedProduct = product;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'copy';
                    debugLog(`🚀 Drag start: ${product.ref} (ordre ligne: ${product.line_order})`);
                    
                    // Activer les zones de drop après un délai pour laisser le temps au dragstart de s'exécuter
                    setTimeout(() => {
                        activateDropZones();
                    }, 50);
                });

                productElement.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    isDragging = false;
                    draggedProduct = null;
                    debugLog(`🛑 Drag end: ${product.ref}`);
                    
                    // Désactiver les zones de drop
                    deactivateDropZones();
                });

                container.appendChild(productElement);
            });
        }

        function renderColisOverview() {
            const tbody = document.getElementById('colisTableBody');
            tbody.innerHTML = '';

            if (colis.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Aucun colis créé. Cliquez sur "Nouveau Colis" pour commencer.</td></tr>';
                return;
            }

            colis.forEach(c => {
                const weightPercentage = (c.totalWeight / c.maxWeight) * 100;
                let statusIcon = '✅';
                let statusClass = '';
                if (weightPercentage > 90) {
                    statusIcon = '⚠️';
                    statusClass = 'warning';
                } else if (weightPercentage > 100) {
                    statusIcon = '❌';
                    statusClass = 'error';
                }

                // Ligne d'en-tête pour le colis - NOUVEAU FORMAT
                const headerRow = document.createElement('tr');
                headerRow.className = 'colis-group-header';
                if (c.isLibre) {
                    headerRow.classList.add('colis-libre');
                }
                headerRow.dataset.colisId = c.id;
                if (selectedColis && selectedColis.id === c.id) {
                    headerRow.classList.add('selected');
                }

                // Calcul des textes pour l'affichage gauche/droite
                const totalColis = c.multiple; // Nombre total de colis identiques
                const leftText = totalColis > 1 ? `${totalColis} colis` : '1 colis';
                const colisType = c.isLibre ? 'LIBRE' : c.number;
                const rightText = `Colis ${colisType} (${c.products.length} produit${c.products.length > 1 ? 's' : ''}) - ${c.totalWeight.toFixed(1)} Kg ${statusIcon}`;

                headerRow.innerHTML = `
                    <td colspan="6">
                        <div class="colis-header-content">
                            <span class="colis-header-left">${c.isLibre ? '📦' : '📦'} ${leftText}</span>
                            <span class="colis-header-right">${rightText}</span>
                        </div>
                    </td>
                `;

                // Event listener pour sélectionner le colis
                headerRow.addEventListener('click', () => {
                    selectColis(c);
                });

                // Setup drop zone pour l'en-tête du colis (seulement pour colis normaux)
                if (!c.isLibre) {
                    setupDropZone(headerRow, c.id);
                }
                tbody.appendChild(headerRow);

                // Lignes pour chaque produit dans le colis
                if (c.products.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.className = 'colis-group-item';
                    if (c.isLibre) {
                        emptyRow.classList.add('colis-libre');
                    }
                    emptyRow.dataset.colisId = c.id;
                    emptyRow.innerHTML = `
                        <td></td>
                        <td colspan="5" style="font-style: italic; color: #999; padding: 10px;">
                            Colis vide - ${c.isLibre ? 'Colis libre sans éléments' : 'Glissez des produits ici'}
                        </td>
                    `;
                    
                    if (!c.isLibre) {
                        setupDropZone(emptyRow, c.id);
                    }
                    tbody.appendChild(emptyRow);
                } else {
                    c.products.forEach((productInColis, index) => {
                        const product = products.find(p => p.id === productInColis.productId);
                        if (!product) return;

                        const productRow = document.createElement('tr');
                        productRow.className = 'colis-group-item';
                        if (c.isLibre) {
                            productRow.classList.add('colis-libre');
                        }
                        productRow.dataset.colisId = c.id;
                        productRow.dataset.productId = product.id;

                        // Affichage différent pour les produits libres
                        const dimensionsDisplay = product.isLibre ? 
                            `Poids unit.: ${product.weight}kg` : 
                            `${product.length}×${product.width}`;

                        const colorDisplay = product.isLibre ? 
                            'LIBRE' : 
                            product.color;

                        productRow.innerHTML = `
                            <td></td>
                            <td>
                                <div class="product-label">
                                    <span>${product.name}</span>
                                    <span class="product-color-badge ${product.isLibre ? 'libre-badge' : ''}">${colorDisplay}</span>
                                </div>
                                ${product.ref_ligne ? `<div style="font-size: 10px; color: #888; font-style: italic;">Réf: ${product.ref_ligne}</div>` : ''}
                            </td>
                            <td style="font-weight: bold; text-align: right; vertical-align: top;">
                                ${productInColis.quantity}
                                ${c.multiple > 1 ? `<div style="font-size: 10px; color: #666;">×${c.multiple} = ${productInColis.quantity * c.multiple}</div>` : ''}
                            </td>
                            <td style="font-weight: bold; text-align: left; vertical-align: top;">
                                ${dimensionsDisplay}
                                <div style="font-size: 10px; color: #666;">${productInColis.weight.toFixed(1)}kg</div>
                            </td>
                            <td class="${statusClass}" style="text-align: center;">
                                ${statusIcon}
                            </td>
                            <td>
                                <button class="btn-small btn-edit" title="Modifier quantité" 
                                        data-colis-id="${c.id}" data-product-id="${product.id}">📝</button>
                                <button class="btn-small btn-delete" title="Supprimer" 
                                        data-colis-id="${c.id}" data-product-id="${product.id}">🗑️</button>
                                ${index === 0 ? `<button class="btn-small btn-duplicate" title="Dupliquer colis" 
                                                        data-colis-id="${c.id}">×${c.multiple}</button>` : ''}
                            </td>
                        `;

                        // Event listeners pour les boutons
                        const editBtn = productRow.querySelector('.btn-edit');
                        const deleteBtn = productRow.querySelector('.btn-delete');
                        const duplicateBtn = productRow.querySelector('.btn-duplicate');

                        if (editBtn) {
                            editBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                const stockInfo = product.isLibre ? '' : `\n(Stock disponible extrafield "nombre": ${product.total - product.used})`;
                                const newQuantity = await showPrompt(
                                    `Nouvelle quantité pour ${product.name} :${stockInfo}`,
                                    productInColis.quantity.toString()
                                );
                                if (newQuantity !== null && !isNaN(newQuantity) && parseInt(newQuantity) > 0) {
                                    updateProductQuantity(c.id, product.id, parseInt(newQuantity));
                                }
                            });
                        }

                        if (deleteBtn) {
                            deleteBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                const confirmed = await showConfirm(
                                    `Supprimer ${product.name} du colis ${c.isLibre ? 'libre' : c.number} ?`
                                );
                                if (confirmed) {
                                    removeProductFromColis(c.id, product.id);
                                }
                            });
                        }

                        if (duplicateBtn) {
                            duplicateBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                await showDuplicateDialog(c.id);
                            });
                        }

                        if (!c.isLibre) {
                            setupDropZone(productRow, c.id);
                        }
                        tbody.appendChild(productRow);
                    });
                }
            });
        }

        function setupDropZone(element, colisId) {
            element.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                if (isDragging && draggedProduct) {
                    debugLog(`🎯 Dragover sur colis ${colisId}`);
                }
            });

            element.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (draggedProduct && isDragging) {
                    debugLog(`📍 Drop sur colis ${colisId} - Produit: ${draggedProduct.ref} (ordre: ${draggedProduct.line_order})`);
                    addProductToColis(colisId, draggedProduct.id, 1);
                } else {
                    debugLog(`❌ Drop échoué - draggedProduct: ${!!draggedProduct}, isDragging: ${isDragging}`);
                }
            });
        }

        function selectColis(coliData) {
            debugLog(`Sélection colis ${coliData.id}`);
            selectedColis = coliData;
            renderColisOverview();
            renderColisDetail();
        }

        function renderColisDetail() {
            const container = document.getElementById('colisDetail');
            
            if (!selectedColis) {
                container.innerHTML = '<div class="empty-state">Sélectionnez un colis pour voir les détails</div>';
                return;
            }

            const weightPercentage = (selectedColis.totalWeight / selectedColis.maxWeight) * 100;
            let weightStatus = 'ok';
            if (weightPercentage > 90) weightStatus = 'danger';
            else if (weightPercentage > 70) weightStatus = 'warning';

            const multipleSection = selectedColis.multiple > 1 ? 
                `<div class="duplicate-controls">
                    <span>📦 Ce colis sera créé</span>
                    <input type="number" value="${selectedColis.multiple}" min="1" max="100" 
                           class="duplicate-input" id="multipleInput">
                    <span>fois identique(s)</span>
                    <span style="margin-left: 10px; font-weight: bold;">
                        Total: ${(selectedColis.totalWeight * selectedColis.multiple).toFixed(1)} kg
                    </span>
                </div>` : '';

            const colisTypeText = selectedColis.isLibre ? 'Colis Libre' : `Colis ${selectedColis.number}`;
            const colisTypeIcon = selectedColis.isLibre ? '📦🆓' : '📦';

            container.innerHTML = `
                <div class="colis-detail-header">
                    <h3 class="colis-detail-title">${colisTypeIcon} ${colisTypeText}</h3>
                    <button class="btn-delete-colis" id="deleteColisBtn">🗑️ Supprimer</button>
                </div>

                ${multipleSection}

                <div class="constraints-section">
                    <div class="constraint-item">
                        <div class="constraint-label">Poids:</div>
                        <div class="constraint-values">
                            ${selectedColis.totalWeight.toFixed(1)} / ${selectedColis.maxWeight} kg
                        </div>
                        <div class="constraint-bar">
                            <div class="constraint-progress ${weightStatus}" style="width: ${Math.min(weightPercentage, 100)}%"></div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 10px; font-weight: bold;">Produits dans ce colis:</div>
                <div class="colis-content" id="colisContent" style="border: 2px dashed #ddd; border-radius: 8px; min-height: 150px; padding: 15px; position: relative;">
                    <div class="drop-hint" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #999; font-style: italic; pointer-events: none;">
                        ${selectedColis.products.length === 0 ? (selectedColis.isLibre ? 'Colis libre vide' : 'Glissez un produit ici pour l\'ajouter') : ''}
                    </div>
                </div>
            `;

            // Ajouter les vignettes dans la zone de contenu
            const colisContent = document.getElementById('colisContent');
            if (selectedColis.products.length > 0) {
                selectedColis.products.forEach((p, index) => {
                    const product = products.find(prod => prod.id === p.productId);
                    if (!product) return;

                    // Créer une vignette identique à l'inventaire avec input quantité
                    const vignette = createProductVignette(product, true, p.quantity);
                    
                    // Ajouter bouton supprimer
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'btn-remove-line';
                    removeBtn.textContent = '✕';
                    removeBtn.dataset.productId = p.productId;
                    removeBtn.style.position = 'absolute';
                    removeBtn.style.top = '5px';
                    removeBtn.style.left = '5px';
                    vignette.style.position = 'relative';
                    vignette.appendChild(removeBtn);

                    colisContent.appendChild(vignette);
                });
            }

            // Event listeners pour les boutons et inputs
            
            // Bouton supprimer colis
            const deleteBtn = document.getElementById('deleteColisBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    debugLog(`Bouton supprimer colis cliqué pour colis ${selectedColis.id}`);
                    await deleteColis(selectedColis.id);
                });
            }

            // Input pour les multiples
            const multipleInput = document.getElementById('multipleInput');
            if (multipleInput) {
                multipleInput.addEventListener('change', async (e) => {
                    await updateColisMultiple(selectedColis.id, e.target.value);
                });
            }

            // Boutons supprimer ligne (sur les vignettes)
            const removeLineBtns = container.querySelectorAll('.btn-remove-line');
            removeLineBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const productId = parseInt(e.target.dataset.productId);
                    debugLog(`Bouton supprimer ligne cliqué pour produit ${productId}`);
                    removeProductFromColis(selectedColis.id, productId);
                });
            });

            // Inputs quantité (sur les vignettes)
            const quantityInputs = container.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('change', async (e) => {
                    const productId = parseInt(e.target.dataset.productId);
                    updateProductQuantity(selectedColis.id, productId, e.target.value);
                });
            });

            // Setup drop zone pour le contenu du colis (seulement pour colis normaux)
            if (colisContent && !selectedColis.isLibre) {
                setupDropZone(colisContent, selectedColis.id);
            }
        }

        function addProductToColis(colisId, productId, quantity) {
            debugLog(`🔧 Ajout produit ${productId} (qté: ${quantity}) au colis ${colisId}`);
            
            const coliData = colis.find(c => c.id === colisId);
            const product = products.find(p => p.id === productId);
            
            if (!coliData || !product) {
                debugLog('ERREUR: Colis ou produit non trouvé');
                return;
            }

            // Ne pas permettre d'ajouter des produits normaux aux colis libres
            if (coliData.isLibre) {
                alert('Impossible d\'ajouter des produits de la commande à un colis libre.');
                return;
            }

            // Vérifier la disponibilité (basée sur extrafield "nombre")
            const available = product.total - product.used;
            if (available < quantity) {
                alert(`Quantité insuffisante ! Disponible (extrafield "nombre"): ${available}, Demandé: ${quantity}`);
                return;
            }

            // Vérifier si le produit est déjà dans le colis
            const existingProduct = coliData.products.find(p => p.productId === productId);
            
            if (existingProduct) {
                existingProduct.quantity += quantity;
                existingProduct.weight = existingProduct.quantity * product.weight;
                debugLog(`✅ Quantité mise à jour pour ${product.ref}: ${existingProduct.quantity}`);
            } else {
                coliData.products.push({
                    productId: productId,
                    quantity: quantity,
                    weight: quantity * product.weight
                });
                debugLog(`✅ Nouveau produit ajouté: ${product.ref}`);
            }

            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            // Mettre à jour les quantités utilisées (tenir compte des multiples)
            product.used += quantity * coliData.multiple;
            debugLog(`📊 Stock mis à jour ${product.ref}: ${product.used}/${product.total} (extrafield nombre)`);

            // Re-render
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id === colisId) {
                renderColisDetail();
            }
            updateSummaryTotals(); // Mettre à jour les totaux
        }

        function setupEventListeners() {
            debugLog('Configuration des event listeners');
            
            // Recherche
            const searchBox = document.getElementById('searchBox');
            if (searchBox) {
                searchBox.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const productItems = document.querySelectorAll('.product-item');
                    
                    productItems.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        item.style.display = text.includes(searchTerm) ? 'block' : 'none';
                    });
                });
            }

            // Sélecteur de groupe de produits
            const productGroupSelect = document.getElementById('productGroupSelect');
            if (productGroupSelect) {
                productGroupSelect.addEventListener('change', function(e) {
                    currentProductGroup = e.target.value;
                    debugLog(`Changement groupe produit: ${currentProductGroup}`);
                    renderInventory();
                });
            }

            // Sélecteur de tri
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function(e) {
                    currentSort = e.target.value;
                    debugLog(`Changement tri: ${currentSort}`);
                    renderInventory();
                });
            }

            // Bouton Nouveau Colis
            const addNewColisBtn = document.getElementById('addNewColisBtn');
            if (addNewColisBtn) {
                addNewColisBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('Bouton nouveau colis cliqué');
                    addNewColis();
                });
            }

            // Bouton Nouveau Colis Libre
            const addNewColisLibreBtn = document.getElementById('addNewColisLibreBtn');
            if (addNewColisLibreBtn) {
                addNewColisLibreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('Bouton nouveau colis libre cliqué');
                    showColisLibreModal();
                });
            }

            // Event listeners pour la modale colis libre
            const colisLibreOk = document.getElementById('colisLibreOk');
            const colisLibreCancel = document.getElementById('colisLibreCancel');
            const addColisLibreItemBtn = document.getElementById('addColisLibreItem');

            if (colisLibreOk) {
                colisLibreOk.addEventListener('click', async () => {
                    const success = await createColisLibre();
                    if (success) {
                        document.getElementById('colisLibreModal').classList.remove('show');
                    }
                });
            }

            if (colisLibreCancel) {
                colisLibreCancel.addEventListener('click', () => {
                    document.getElementById('colisLibreModal').classList.remove('show');
                });
            }

            if (addColisLibreItemBtn) {
                addColisLibreItemBtn.addEventListener('click', addColisLibreItem);
            }

            // Affichage/masquage de la console de debug (double-clic sur le titre)
            const header = document.querySelector('.header h1');
            if (header) {
                header.addEventListener('dblclick', function() {
                    const debugConsole = document.getElementById('debugConsole');
                    if (debugConsole) {
                        debugConsole.style.display = debugConsole.style.display === 'none' ? 'block' : 'none';
                    }
                });
            }
            
            debugLog('Event listeners configurés');
        }

        // Script pour la fonction d'impression
        function preparePrint() {
            // Sauvegarde l'état actuel de la page
            var originalTitle = document.title;
            
            // Modifie le titre pour l'impression
            document.title = 'Fiche de Production - <?php echo $object->ref; ?>';
            
            // Lance l'impression
            window.print();
            
            // Restaure le titre original après l'impression
            setTimeout(function() {
                document.title = originalTitle;
            }, 1000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('DOM chargé, initialisation...');
            debugLog('🆕 NOUVEAU : Fonctionnalité Colis Libre ajoutée !');
            debugLog('📋 NOUVEAU : Tableau récapitulatif des informations de commande ajouté !');
            
            renderInventory();
            renderColisOverview();
            setupEventListeners();
            loadData();
            updateSummaryTotals(); // Initialiser les totaux
            
            debugLog('Initialisation terminée');
            debugLog('Double-cliquez sur le titre pour afficher/masquer cette console');
        });
</script>

<?php
print '</div>'; // End fichecenter
print dol_get_fiche_end();

llxFooter();
$db->close();
?>