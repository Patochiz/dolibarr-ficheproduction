<?php
/**
 * \file        includes/ficheproduction-display.php
 * \ingroup     ficheproduction
 * \brief       Gestion de l'affichage principal pour FicheProduction
 */

// Prevent direct access
if (!defined('DOL_VERSION')) {
    print "Error: This module requires Dolibarr framework.\n";
    exit;
}

/**
 * Display main FicheProduction content
 * 
 * @param Commande $object     Order object
 * @param Form     $form       Form object
 * @param Translate $langs     Language object
 * @param bool     $userCanEdit User can edit flag
 * @param User     $user       Current user
 * @param DoliDB   $db         Database connection
 */
function displayFicheProductionContent($object, $form, $langs, $userCanEdit, $user, $db) 
{
    // Count products in order
    $product_count = countProductsInOrder($object, $db);
    
    // Display summary section
    displaySummarySection($object, $langs, $userCanEdit);
    
    // Display main interface
    displayMainInterface($object, $product_count);
    
    // Display signature section
    displaySignatureSection($langs);
    
    // Display action buttons
    displayActionButtons($userCanEdit, $langs);
    
    // Display modals
    displayModals();
    
    // Close fichecenter div
    print '</div>';
}

/**
 * Count products in order
 */
function countProductsInOrder($object, $db) 
{
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
    return $product_count;
}

/**
 * Display summary section
 */
function displaySummarySection($object, $langs, $userCanEdit) 
{
    global $form;
    
    print '<div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom: 20px;">';

    // Left column - Summary table
    print '<div style="flex:1; min-width:300px;">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    // Order reference
    print '<tr><td class="titlefield">'.$langs->trans("OrderReference").':</td><td>'.$object->ref.'</td></tr>';

    // Customer
    print '<tr><td>'.$langs->trans("CustomerName").':</td><td>'.$object->thirdparty->getNomUrl(1).'</td></tr>';

    // Project reference
    displayProjectReference($object, $langs, $userCanEdit);

    // Comments
    displayComments($object, $langs, $userCanEdit);

    // Total packages (calculated dynamically)
    print '<tr>';
    print '<td>'.$langs->trans("TotalPackages").':</td>';
    print '<td><span id="total-packages">0</span></td>';
    print '</tr>';

    // Total weight
    $poids_total = !empty($object->array_options['options_poids_total']) ? $object->array_options['options_poids_total'] : 0;
    print '<tr>';
    print '<td>'.$langs->trans("TotalWeight").':</td>';
    print '<td><span id="total-weight">'.$poids_total.'</span> kg</td>';
    print '</tr>';

    print '</table>';
    print '</div>'; // End left column

    // Right column - Delivery information
    displayDeliveryInfo($object, $langs, $db);
    
    print '</div>'; // End flexbox layout
}

/**
 * Display project reference with edit capability
 */
function displayProjectReference($object, $langs, $userCanEdit) 
{
    $ref_chantier = !empty($object->array_options['options_ref_chantier']) ? $object->array_options['options_ref_chantier'] : '';
    $ref_chantierfp = !empty($object->array_options['options_ref_chantierfp']) ? $object->array_options['options_ref_chantierfp'] : $ref_chantier;
    $action = GETPOST('action', 'alpha');

    print '<tr>';
    print '<td>'.$langs->trans("ProjectReference").':</td>';
    print '<td>';

    if ($action == 'edit_ref_chantierfp') {
        // Edit form
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="update_ref_chantierfp">';
        print '<input type="text" name="ref_chantierfp" size="40" value="'.$ref_chantierfp.'">';
        print ' <input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("Cancel").'</a>';
        print '</form>';
    } else {
        // Normal display with edit icon
        print $ref_chantierfp;
        if ($userCanEdit) {
            print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_ref_chantierfp">'.img_edit($langs->trans("Edit")).'</a>';
        }
    }

    print '</td>';
    print '</tr>';
}

/**
 * Display comments with edit capability
 */
function displayComments($object, $langs, $userCanEdit) 
{
    $commentaires_fp = !empty($object->array_options['options_commentaires_fp']) ? $object->array_options['options_commentaires_fp'] : '';
    $action = GETPOST('action', 'alpha');

    print '<tr>';
    print '<td>'.$langs->trans("Comments").':</td>';
    print '<td>';

    if ($action == 'edit_commentaires_fp') {
        // Edit form with WYSIWYG editor
        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="update_commentaires_fp">';
        
        // Use Dolibarr editor
        $doleditor = new DolEditor('commentaires_fp', $commentaires_fp, '', 200, 'dolibarr_notes', '', false, true, true, ROWS_5, '90%');
        print $doleditor->Create(1);
        
        print '<br><input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("Cancel").'</a>';
        print '</form>';
    } else {
        // Normal display with edit icon
        print $commentaires_fp; // HTML content will display correctly
        if ($userCanEdit) {
            print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_commentaires_fp">'.img_edit($langs->trans("Edit")).'</a>';
        }
    }

    print '</td>';
    print '</tr>';
}

/**
 * Display delivery information
 */
function displayDeliveryInfo($object, $langs, $db) 
{
    print '<div style="flex:0 0 300px;">';
    print '<div class="delivery-info-box" style="padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; height:100%;">';
    print '<h4>' . $langs->trans("DeliveryInformation") . '</h4>';

    // Get delivery information
    $deliveryInfo = getDeliveryInformation($object, $langs, $db);
    
    // Display information
    print '<p><strong>' . $langs->trans("Contact") . ':</strong> ' . $deliveryInfo['contact'] . '</p>';
    print '<p><strong>' . $langs->trans("Address") . ':</strong> ' . nl2br($deliveryInfo['address']) . '</p>';
    
    if (!empty($deliveryInfo['phone'])) {
        print '<p><strong>' . $langs->trans("Phone") . ':</strong> ' . $deliveryInfo['phone'] . '</p>';
    }
    
    if (!empty($deliveryInfo['email'])) {
        print '<p><strong>' . $langs->trans("Email") . ':</strong> ' . $deliveryInfo['email'] . '</p>';
    }
    
    if (!empty($deliveryInfo['note'])) {
        print '<p><strong>' . $langs->trans("NotePublic") . ':</strong> ' . nl2br($deliveryInfo['note']) . '</p>';
    }

    print '</div>'; // End info box
    print '</div>'; // End right column
}

/**
 * Get delivery information
 */
function getDeliveryInformation($object, $langs, $db) 
{
    $deliveryInfo = [
        'contact' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'note' => ''
    ];

    // Get associated contacts
    $contacts = $object->liste_contact(-1, 'external', 0, 'SHIPPING');
    
    if (is_array($contacts) && count($contacts) > 0) {
        foreach ($contacts as $contact) {
            $contactstatic = new Contact($db);
            if ($contactstatic->fetch($contact['id']) > 0) {
                $deliveryInfo['contact'] = $contactstatic->getFullName($langs);
                $deliveryInfo['address'] = $contactstatic->address;
                $deliveryInfo['address'] .= "\n" . $contactstatic->zip . " " . $contactstatic->town;
                $deliveryInfo['address'] .= !empty($contactstatic->country) ? "\n" . $contactstatic->country : "";
                
                // Get all available phone numbers
                $phone_array = array();
                if (!empty($contactstatic->phone_pro)) $phone_array[] = $langs->trans("Pro") . ": " . $contactstatic->phone_pro;
                if (!empty($contactstatic->phone_perso)) $phone_array[] = $langs->trans("Personal") . ": " . $contactstatic->phone_perso;
                if (!empty($contactstatic->phone_mobile)) $phone_array[] = $langs->trans("Mobile") . ": " . $contactstatic->phone_mobile;
                
                $deliveryInfo['phone'] = implode(" / ", $phone_array);
                $deliveryInfo['email'] = $contactstatic->email;
                $deliveryInfo['note'] = $contactstatic->note_public;
            }
            break; // Take only the first delivery contact
        }
    }

    // If no specific contact, use delivery address from order
    if (empty($deliveryInfo['contact']) && !empty($object->array_options['options_adresse_livraison'])) {
        $deliveryInfo['address'] = $object->array_options['options_adresse_livraison'];
    }

    // If still no address, use customer's address
    if (empty($deliveryInfo['address'])) {
        $deliveryInfo['contact'] = $object->thirdparty->name;
        $deliveryInfo['address'] = $object->thirdparty->address;
        $deliveryInfo['address'] .= "\n" . $object->thirdparty->zip . " " . $object->thirdparty->town;
        $deliveryInfo['address'] .= !empty($object->thirdparty->country) ? "\n" . $object->thirdparty->country : "";
        
        // For company, get different available phones
        $phone_array = array();
        if (!empty($object->thirdparty->phone)) $phone_array[] = $langs->trans("Pro") . ": " . $object->thirdparty->phone;
        if (!empty($object->thirdparty->fax)) $phone_array[] = $langs->trans("Fax") . ": " . $object->thirdparty->fax;
        
        $deliveryInfo['phone'] = implode(" / ", $phone_array);
        $deliveryInfo['email'] = $object->thirdparty->email;
        $deliveryInfo['note'] = $object->thirdparty->note_public;
    }
    
    return $deliveryInfo;
}

/**
 * Display main interface
 */
function displayMainInterface($object, $product_count) 
{
    print '<div class="header">';
    print '<h1>🚀 Gestionnaire de Colisage v2.0</h1>';
    print '<div class="subtitle">Interface drag & drop pour colis mixtes - Commande '.$object->ref.' ('.$product_count.' produits commandés)</div>';
    print '</div>';

    print '<div class="colisage-container">';
    
    // Inventory zone
    print '<div class="inventory-zone">';
    print '<div class="inventory-header">📦 Inventaire Produits (ordre de la commande)</div>';
    
    print '<div class="inventory-controls">';
    print '<input type="text" class="search-box" placeholder="🔍 Rechercher un produit..." id="searchBox">';
    print '<div class="sort-controls">';
    print '<select id="productGroupSelect" class="sort-select">';
    print '<option value="all">Tous les produits</option>';
    print '</select>';
    print '<select id="sortSelect" class="sort-select">';
    print '<option value="original">📋 Ordre commande</option>';
    print '<option value="length_asc">📏 Longueur ↑</option>';
    print '<option value="length_desc">📏 Longueur ↓</option>';
    print '<option value="width_asc">📐 Largeur ↑</option>';
    print '<option value="width_desc">📐 Largeur ↓</option>';
    print '<option value="name_asc">🔤 Nom A→Z</option>';
    print '<option value="name_desc">🔤 Nom Z→A</option>';
    print '</select>';
    print '</div>';
    print '</div>';
    
    print '<div class="inventory-list" id="inventoryList">';
    print '<!-- Généré par JavaScript -->';
    print '</div>';
    print '</div>';

    // Constructor zone
    print '<div class="constructor-zone">';
    print '<div class="constructor-header">';
    print '<div class="constructor-title">🏗️ Constructeur de Colis</div>';
    print '<div class="constructor-buttons">';
    print '<button class="btn-add-colis" id="addNewColisBtn">+ Nouveau Colis</button>';
    print '<button class="btn-add-colis-libre" id="addNewColisLibreBtn">📦 Colis Libre</button>';
    print '</div>';
    print '</div>';
    
    print '<div class="colis-overview" id="colisOverview">';
    print '<table class="colis-table" id="colisTable">';
    print '<thead>';
    print '<tr>';
    print '<th>Colis</th>';
    print '<th>Libellé + Couleur</th>';
    print '<th>Nombre</th>';
    print '<th>Long×Larg</th>';
    print '<th>Statut</th>';
    print '<th>Actions</th>';
    print '</tr>';
    print '</thead>';
    print '<tbody id="colisTableBody">';
    print '<!-- Généré par JavaScript -->';
    print '</tbody>';
    print '</table>';
    print '</div>';
    
    print '<div class="colis-detail" id="colisDetail">';
    print '<div class="empty-state">';
    print 'Sélectionnez un colis pour voir les détails<br>';
    print 'ou créez un nouveau colis pour commencer';
    print '</div>';
    print '</div>';
    print '</div>';
    
    print '</div>'; // End colisage-container
}

/**
 * Display signature section
 */
function displaySignatureSection($langs) 
{
    print '<div class="colisage-signature" style="margin-top:30px; padding-top:20px; border-top:1px solid #ddd;">';
    print '<h3>' . $langs->trans("FinalChecks") . '</h3>';
    print '<table class="colisage-check-table" style="width:100%; border-collapse:collapse;">';
    print '<tr>';

    // Left column - FINAL PACKAGING
    print '<td style="width:50%; padding:15px; vertical-align:top; border:1px solid #ddd;">';
    print '<p><strong>' . $langs->trans("FinalPackaging") . '</strong></p>';
    print '<p style="margin:10px 0;">______' . $langs->trans("Pallets") . ' ' . $langs->trans("Being") . ' ______' . $langs->trans("Packages") . '</p>';
    print '<p style="margin:10px 0;">______' . $langs->trans("Bundles") . ' ' . $langs->trans("Being") . ' ______' . $langs->trans("Packages") . '</p>';
    print '<p style="margin:10px 0;">      ' . $langs->trans("BulkPackages") . ' ______' . $langs->trans("Packages") . '</p>';
    print '<p style="margin:15px 0;"><strong>' . $langs->trans("TotalNumberOfPackages") . '</strong> : ______' . $langs->trans("Packages") . '</p>';
    print '</td>';

    // Right column - Verifications and signatures
    print '<td style="width:50%; padding:15px; vertical-align:top; border:1px solid #ddd;">';
    print '<p>' . $langs->trans("ProductionSheetVerificationBy") . ' : __________</p>';
    print '<p style="height:20px;"></p>'; // Space for signature
    print '<p>' . $langs->trans("FinalCounting") . ' ' . $langs->trans("AndReturnDateBy") . ' : __________</p>';
    print '<p style="height:20px;"></p>'; // Space for signature
    print '<p>' . $langs->trans("CoilsIDUsed") . ' : __________</p>';
    print '<p style="height:20px;"></p>'; // Space for signature
    print '</td>';

    print '</tr>';
    print '</table>';
    print '</div>';
}

/**
 * Display action buttons
 */
function displayActionButtons($userCanEdit, $langs) 
{
    print '<div class="tabsAction">';
    if ($userCanEdit) {
        print '<a class="butAction" href="javascript:saveColisage();" id="saveColisageBtn">💾 ' . $langs->trans("Save") . '</a>';
    }
    print '<a class="butAction" href="javascript:preparePrint();">' . $langs->trans("PrintButton") . '</a>';
    print '</div>';
}

/**
 * Display all modals
 */
function displayModals() 
{
    // Debug console
    print '<div class="debug-console" id="debugConsole"></div>';

    // Confirmation modal
    print '<div class="modal-overlay" id="confirmModal">';
    print '<div class="modal-content">';
    print '<div class="modal-header">Confirmation</div>';
    print '<div class="modal-message" id="confirmMessage"></div>';
    print '<div class="modal-buttons">';
    print '<button class="modal-btn secondary" id="confirmCancel">Annuler</button>';
    print '<button class="modal-btn danger" id="confirmOk">Confirmer</button>';
    print '</div>';
    print '</div>';
    print '</div>';

    // Prompt modal
    print '<div class="modal-overlay" id="promptModal">';
    print '<div class="modal-content">';
    print '<div class="modal-header">Saisie</div>';
    print '<div class="modal-message" id="promptMessage"></div>';
    print '<input type="text" class="modal-input" id="promptInput" placeholder="Saisir la valeur...">';
    print '<div class="modal-buttons">';
    print '<button class="modal-btn secondary" id="promptCancel">Annuler</button>';
    print '<button class="modal-btn primary" id="promptOk">Valider</button>';
    print '</div>';
    print '</div>';
    print '</div>';

    // Free package modal
    print '<div class="modal-overlay" id="colisLibreModal">';
    print '<div class="modal-content modal-large">';
    print '<div class="modal-header">📦 Création Colis Libre</div>';
    print '<div class="modal-message">Ajout d\'éléments libres (échantillons, catalogues, etc.)</div>';
    
    print '<div class="colis-libre-form">';
    print '<h4>Contenu du colis libre :</h4>';
    print '<div id="colisLibreItems">';
    print '<!-- Items générés par JavaScript -->';
    print '</div>';
    print '<button type="button" class="btn-add-item" id="addColisLibreItem">+ Ajouter un élément</button>';
    print '</div>';
    
    print '<div class="modal-buttons">';
    print '<button class="modal-btn secondary" id="colisLibreCancel">Annuler</button>';
    print '<button class="modal-btn primary" id="colisLibreOk">Créer le colis</button>';
    print '</div>';
    print '</div>';
    print '</div>';

    // Save progress modal
    print '<div class="modal-overlay" id="saveModal">';
    print '<div class="modal-content">';
    print '<div class="modal-header">💾 Sauvegarde en cours...</div>';
    print '<div class="modal-message">';
    print '<div class="save-progress">';
    print '<div class="progress-bar">';
    print '<div class="progress-fill" id="saveProgressFill"></div>';
    print '</div>';
    print '<div id="saveStatusMessage">Préparation des données...</div>';
    print '</div>';
    print '</div>';
    print '</div>';
    print '</div>';
}
?>