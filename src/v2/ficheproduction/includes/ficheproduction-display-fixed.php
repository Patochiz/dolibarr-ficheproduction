<?php
/**
 * \file        includes/ficheproduction-display.php
 * \ingroup     ficheproduction
 * \brief       Gestion de l'affichage principal pour FicheProduction (VERSION CORRIGÉE)
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
    displaySummarySection($object, $langs, $userCanEdit, $db);
    
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
                if ($temp_product->fetch($line->fk_product) > 0 && $temp_product->type == 0) {
                    $product_count++;
                }
            }
        }
    }
    return $product_count;
}

/**
 * Display summary section (VERSION CORRIGÉE)
 */
function displaySummarySection($object, $langs, $userCanEdit, $db) 
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
    print '<tr><td>'.$langs->trans("CustomerName").':</td><td>';
    if (is_object($object->thirdparty)) {
        print $object->thirdparty->getNomUrl(1);
    } else {
        print 'Client non défini';
    }
    print '</td></tr>';

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

    // Right column - Delivery information (CORRECTION ICI)
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
        print '<input type="text" name="ref_chantierfp" size="40" value="'.dol_escape_htmltag($ref_chantierfp).'">';
        print ' <input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("Cancel").'</a>';
        print '</form>';
    } else {
        // Normal display with edit icon
        print dol_escape_htmltag($ref_chantierfp);
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
 * Display delivery information (VERSION CORRIGÉE)
 */
function displayDeliveryInfo($object, $langs, $db) 
{
    print '<div style="flex:0 0 300px;">';
    print '<div class="delivery-info-box" style="padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; height:100%;">';
    print '<h4>' . $langs->trans("DeliveryInformation") . '</h4>';

    // Get delivery information (CORRECTION: passer $db en paramètre)
    $deliveryInfo = getDeliveryInformation($object, $langs, $db);
    
    // Display information
    if (!empty($deliveryInfo['contact'])) {
        print '<p><strong>' . $langs->trans("Contact") . ':</strong> ' . dol_escape_htmltag($deliveryInfo['contact']) . '</p>';
    }
    
    if (!empty($deliveryInfo['address'])) {
        print '<p><strong>' . $langs->trans("Address") . ':</strong> ' . nl2br(dol_escape_htmltag($deliveryInfo['address'])) . '</p>';
    }
    
    if (!empty($deliveryInfo['phone'])) {
        print '<p><strong>' . $langs->trans("Phone") . ':</strong> ' . dol_escape_htmltag($deliveryInfo['phone']) . '</p>';
    }
    
    if (!empty($deliveryInfo['email'])) {
        print '<p><strong>' . $langs->trans("Email") . ':</strong> ' . dol_escape_htmltag($deliveryInfo['email']) . '</p>';
    }
    
    if (!empty($deliveryInfo['note'])) {
        print '<p><strong>' . $langs->trans("NotePublic") . ':</strong> ' . nl2br(dol_escape_htmltag($deliveryInfo['note'])) . '</p>';
    }
    
    // Si aucune information, afficher un message
    if (empty($deliveryInfo['contact']) && empty($deliveryInfo['address'])) {
        print '<p style="color: #666; font-style: italic;">Aucune information de livraison disponible</p>';
    }

    print '</div>'; // End info box
    print '</div>'; // End right column
}

/**
 * Get delivery information (VERSION CORRIGÉE)
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

    try {
        // Vérifier que l'objet commande est valide
        if (!is_object($object) || empty($object->id)) {
            return $deliveryInfo;
        }

        // Get associated contacts
        $contacts = $object->liste_contact(-1, 'external', 0, 'SHIPPING');
        
        if (is_array($contacts) && count($contacts) > 0) {
            foreach ($contacts as $contact) {
                if (!empty($contact['id'])) {
                    $contactstatic = new Contact($db);
                    if ($contactstatic->fetch($contact['id']) > 0) {
                        $deliveryInfo['contact'] = $contactstatic->getFullName($langs);
                        
                        $address_parts = [];
                        if (!empty($contactstatic->address)) $address_parts[] = $contactstatic->address;
                        if (!empty($contactstatic->zip) || !empty($contactstatic->town)) {
                            $city_line = trim($contactstatic->zip . ' ' . $contactstatic->town);
                            if ($city_line) $address_parts[] = $city_line;
                        }
                        if (!empty($contactstatic->country)) $address_parts[] = $contactstatic->country;
                        
                        $deliveryInfo['address'] = implode("\n", $address_parts);
                        
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
        }

        // If no specific contact, use delivery address from order
        if (empty($deliveryInfo['contact']) && !empty($object->array_options['options_adresse_livraison'])) {
            $deliveryInfo['address'] = $object->array_options['options_adresse_livraison'];
        }

        // If still no address, use customer's address
        if (empty($deliveryInfo['address']) && is_object($object->thirdparty)) {
            $deliveryInfo['contact'] = $object->thirdparty->name;
            
            $address_parts = [];
            if (!empty($object->thirdparty->address)) $address_parts[] = $object->thirdparty->address;
            if (!empty($object->thirdparty->zip) || !empty($object->thirdparty->town)) {
                $city_line = trim($object->thirdparty->zip . ' ' . $object->thirdparty->town);
                if ($city_line) $address_parts[] = $city_line;
            }
            if (!empty($object->thirdparty->country)) $address_parts[] = $object->thirdparty->country;
            
            $deliveryInfo['address'] = implode("\n", $address_parts);
            
            // For company, get different available phones
            $phone_array = array();
            if (!empty($object->thirdparty->phone)) $phone_array[] = $langs->trans("Pro") . ": " . $object->thirdparty->phone;
            if (!empty($object->thirdparty->fax)) $phone_array[] = $langs->trans("Fax") . ": " . $object->thirdparty->fax;
            
            $deliveryInfo['phone'] = implode(" / ", $phone_array);
            $deliveryInfo['email'] = $object->thirdparty->email;
            $deliveryInfo['note'] = $object->thirdparty->note_public;
        }
        
    } catch (Exception $e) {
        // En cas d'erreur, log et retourner des données vides
        error_log("Erreur getDeliveryInformation: " . $e->getMessage());
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
    print '<div class="subtitle">Interface drag & drop pour colis mixtes - Commande '.dol_escape_htmltag($object->ref).' ('.$product_count.' produits commandés)</div>';
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
    print '<div class="debug-console" id="debugConsole" style="display:none; position:fixed; bottom:10px; right:10px; width:400px; height:200px; background:#000; color:#0f0; font-family:monospace; font-size:11px; padding:10px; border:1px solid #333; z-index:9999; overflow-y:auto;"></div>';

    // Confirmation modal
    print '<div class="modal-overlay" id="confirmModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;">';
    print '<div class="modal-content" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; max-width:500px; width:90%;">';
    print '<div class="modal-header" style="font-weight:bold; margin-bottom:15px; font-size:18px;">Confirmation</div>';
    print '<div class="modal-message" id="confirmMessage" style="margin-bottom:20px;"></div>';
    print '<div class="modal-buttons" style="text-align:right;">';
    print '<button class="modal-btn secondary" id="confirmCancel" style="margin-right:10px; padding:8px 15px; border:1px solid #ccc; background:#f5f5f5; cursor:pointer;">Annuler</button>';
    print '<button class="modal-btn danger" id="confirmOk" style="padding:8px 15px; border:none; background:#d32f2f; color:white; cursor:pointer;">Confirmer</button>';
    print '</div>';
    print '</div>';
    print '</div>';

    // Prompt modal
    print '<div class="modal-overlay" id="promptModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;">';
    print '<div class="modal-content" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; max-width:500px; width:90%;">';
    print '<div class="modal-header" style="font-weight:bold; margin-bottom:15px; font-size:18px;">Saisie</div>';
    print '<div class="modal-message" id="promptMessage" style="margin-bottom:15px;"></div>';
    print '<input type="text" class="modal-input" id="promptInput" placeholder="Saisir la valeur..." style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-bottom:20px;">';
    print '<div class="modal-buttons" style="text-align:right;">';
    print '<button class="modal-btn secondary" id="promptCancel" style="margin-right:10px; padding:8px 15px; border:1px solid #ccc; background:#f5f5f5; cursor:pointer;">Annuler</button>';
    print '<button class="modal-btn primary" id="promptOk" style="padding:8px 15px; border:none; background:#1976d2; color:white; cursor:pointer;">Valider</button>';
    print '</div>';
    print '</div>';
    print '</div>';

    // Free package modal
    print '<div class="modal-overlay" id="colisLibreModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;">';
    print '<div class="modal-content modal-large" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; max-width:700px; width:90%;">';
    print '<div class="modal-header" style="font-weight:bold; margin-bottom:15px; font-size:18px;">📦 Création Colis Libre</div>';
    print '<div class="modal-message" style="margin-bottom:15px;">Ajout d\'éléments libres (échantillons, catalogues, etc.)</div>';
    
    print '<div class="colis-libre-form">';
    print '<h4>Contenu du colis libre :</h4>';
    print '<div id="colisLibreItems">';
    print '<!-- Items générés par JavaScript -->';
    print '</div>';
    print '<button type="button" class="btn-add-item" id="addColisLibreItem" style="margin-top:10px; padding:8px 15px; border:1px solid #4caf50; background:#4caf50; color:white; cursor:pointer;">+ Ajouter un élément</button>';
    print '</div>';
    
    print '<div class="modal-buttons" style="text-align:right; margin-top:20px;">';
    print '<button class="modal-btn secondary" id="colisLibreCancel" style="margin-right:10px; padding:8px 15px; border:1px solid #ccc; background:#f5f5f5; cursor:pointer;">Annuler</button>';
    print '<button class="modal-btn primary" id="colisLibreOk" style="padding:8px 15px; border:none; background:#1976d2; color:white; cursor:pointer;">Créer le colis</button>';
    print '</div>';
    print '</div>';
    print '</div>';

    // Save progress modal
    print '<div class="modal-overlay" id="saveModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10000;">';
    print '<div class="modal-content" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; max-width:400px; width:90%;">';
    print '<div class="modal-header" style="font-weight:bold; margin-bottom:15px; font-size:18px;">💾 Sauvegarde en cours...</div>';
    print '<div class="modal-message">';
    print '<div class="save-progress">';
    print '<div class="progress-bar" style="width:100%; height:20px; background:#f0f0f0; border-radius:10px; overflow:hidden; margin-bottom:10px;">';
    print '<div class="progress-fill" id="saveProgressFill" style="height:100%; background:#4caf50; width:0%; transition:width 0.3s ease;"></div>';
    print '</div>';
    print '<div id="saveStatusMessage">Préparation des données...</div>';
    print '</div>';
    print '</div>';
    print '</div>';
    print '</div>';
    
    // Ajouter CSS pour les modales
    print '<style>';
    print '.modal-overlay.show { display: block !important; }';
    print '.modal-btn:hover { opacity: 0.8; }';
    print '.debug-console.show { display: block !important; }';
    print '</style>';
}
?>