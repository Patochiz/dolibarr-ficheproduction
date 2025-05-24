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
 * \brief       Page de gestion du colisage avec interface drag & drop moderne
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

// Load translations
$langs->loadLangs(array('orders', 'products', 'companies'));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Initialize objects
$object = new Commande($db);
$extrafields = new ExtraFields($db);

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
    
    // IMPORTANT: Load the thirdparty object
    $object->fetch_thirdparty();
} else {
    header('Location: '.dol_buildpath('/commande/list.php', 1));
    exit;
}

// Prepare objects for display
$form = new Form($db);
$head = commande_prepare_head($object);

// Start page
llxHeader('', $langs->trans('Order').' - '.$object->ref, '');

print dol_get_fiche_head($head, 'ficheproduction', $langs->trans('CustomerOrder'), -1, 'order');

// Object banner
$linkback = '<a href="'.dol_buildpath('/commande/list.php', 1).'?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
$morehtmlref = '<div class="refidno">';
$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
// Check if thirdparty exists before calling getNomUrl
if (is_object($object->thirdparty)) {
    $morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
} else {
    $morehtmlref .= '<br>Client non défini';
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
?>

<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .header {
            background: #2c5f7a;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header .subtitle {
            opacity: 0.8;
            font-size: 14px;
        }

        .colisage-container {
            display: flex;
            height: 700px;
            background: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* Zone Inventaire */
        .inventory-zone {
            width: 40%;
            border-right: 2px solid #e0e0e0;
            background: #fafafa;
            display: flex;
            flex-direction: column;
        }

        .inventory-header {
            background: #4CAF50;
            color: white;
            padding: 15px;
            font-weight: bold;
        }

        .inventory-controls {
            padding: 15px;
            background: white;
            border-bottom: 1px solid #ddd;
        }

        .search-box {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .sort-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .sort-select {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .inventory-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .product-item {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: grab;
            transition: all 0.3s ease;
            position: relative;
        }

        .product-item:hover {
            border-color: #2196F3;
            box-shadow: 0 2px 8px rgba(33,150,243,0.2);
            transform: translateX(5px);
        }

        .product-item.dragging {
            opacity: 0.5;
            transform: rotate(3deg) scale(0.95);
            cursor: grabbing;
        }

        .product-item.exhausted {
            opacity: 0.6;
            background: #ffebee;
            border-color: #ef5350;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .product-ref {
            font-weight: bold;
            color: #1976D2;
        }

        .product-color {
            background: #e3f2fd;
            color: #1976D2;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .product-name {
            color: #333;
            margin-bottom: 8px;
        }

        .quantity-info {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-used {
            font-weight: bold;
            color: #f44336;
        }

        .quantity-total {
            font-weight: bold;
            color: #4CAF50;
        }

        .quantity-bar {
            flex: 1;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-left: 10px;
        }

        .quantity-progress {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50 0%, #FF9800 50%, #f44336 100%);
            transition: width 0.3s;
        }

        /* Zone Constructeur */
        .constructor-zone {
            width: 60%;
            display: flex;
            flex-direction: column;
        }

        .constructor-header {
            background: #FF9800;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .constructor-title {
            font-weight: bold;
        }

        .btn-add-colis {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-add-colis:hover {
            background: rgba(255,255,255,0.3);
        }

        .colis-overview {
            padding: 15px;
            background: #f9f9f9;
            min-height: 200px;
            max-height: 300px;
            overflow-y: auto;
        }

        .colis-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .colis-table th {
            background: #FF9800;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
        }

        .colis-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }

        .colis-table tr {
            cursor: pointer;
        }

        .colis-table tr:hover {
            background: #fff3e0;
        }

        .colis-table tr.selected {
            background: #fff3e0;
            border-left: 4px solid #FF9800;
        }

        .colis-table tr.drop-active {
            background: #e8f5e8;
            border-left: 4px solid #4CAF50;
        }

        .colis-table .colis-number {
            font-weight: bold;
            color: #FF9800;
        }

        .colis-table .colis-multiple {
            background: #e3f2fd;
            color: #1976D2;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .colis-table .colis-group-header {
            background: #fff3e0;
            font-weight: bold;
            border-left: 4px solid #FF9800;
        }

        .colis-table .colis-group-item {
            background: #fafafa;
            border-left: 2px solid #FFE0B2;
        }

        .colis-table .colis-group-item:hover {
            background: #f5f5f5;
        }

        .colis-table .product-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .colis-table .product-color-badge {
            background: #e3f2fd;
            color: #1976D2;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: bold;
        }

        .colis-table .dimensions {
            font-family: monospace;
            color: #666;
            font-size: 12px;
        }

        .colis-table .btn-small {
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 3px;
            border: none;
            cursor: pointer;
            margin: 1px;
        }

        .colis-table .btn-edit {
            background: #2196F3;
            color: white;
        }

        .colis-table .btn-delete {
            background: #f44336;
            color: white;
        }

        .colis-table .btn-duplicate {
            background: #FF9800;
            color: white;
        }

        .duplicate-controls {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
            padding: 10px;
            background: #e3f2fd;
            border-radius: 6px;
        }

        .duplicate-input {
            width: 60px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .colis-detail {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .colis-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .colis-detail-title {
            color: #333;
            font-size: 18px;
        }

        .btn-delete-colis {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-delete-colis:hover {
            background: #d32f2f;
        }

        .constraints-section {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .constraint-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .constraint-label {
            width: 80px;
            font-weight: bold;
        }

        .constraint-values {
            flex: 1;
            margin-right: 10px;
        }

        .constraint-bar {
            width: 100px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .constraint-progress {
            height: 100%;
            background: #4CAF50;
            transition: width 0.3s;
        }

        .constraint-progress.warning {
            background: #FF9800;
        }

        .constraint-progress.danger {
            background: #f44336;
        }

        .colis-content {
            border: 2px dashed #ddd;
            border-radius: 8px;
            min-height: 200px;
            padding: 15px;
            position: relative;
        }

        .colis-content.drop-zone-active {
            border-color: #4CAF50;
            background: #e8f5e8;
        }

        .colis-line {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: grab;
            transition: all 0.2s;
        }

        .colis-line:hover {
            border-color: #2196F3;
            box-shadow: 0 2px 4px rgba(33,150,243,0.2);
        }

        .colis-line.dragging {
            opacity: 0.5;
            transform: rotate(2deg);
            cursor: grabbing;
        }

        .colis-line .drag-handle {
            color: #999;
            cursor: grab;
            font-size: 16px;
        }

        .line-product {
            flex: 1;
            font-weight: bold;
        }

        .line-quantity {
            width: 60px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .line-weight {
            color: #666;
            font-size: 12px;
        }

        .btn-remove-line {
            background: #f44336;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-remove-line:hover {
            background: #d32f2f;
        }

        .drop-hint {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #999;
            font-style: italic;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 40px;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Status indicators */
        .status-indicator {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4CAF50;
        }

        .status-indicator.warning { background: #FF9800; }
        .status-indicator.error { background: #f44336; }

        /* Modales custom */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
        }

        .modal-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .modal-message {
            margin-bottom: 20px;
            color: #666;
            line-height: 1.4;
        }

        .modal-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .modal-btn.primary {
            background: #2196F3;
            color: white;
        }

        .modal-btn.primary:hover {
            background: #1976D2;
        }

        .modal-btn.danger {
            background: #f44336;
            color: white;
        }

        .modal-btn.danger:hover {
            background: #d32f2f;
        }

        .modal-btn.secondary {
            background: #e0e0e0;
            color: #333;
        }

        .modal-btn.secondary:hover {
            background: #d0d0d0;
        }
</style>

<div class="header">
    <h1>🚀 Prototype - Gestionnaire de Colisage v2.0</h1>
    <div class="subtitle">Interface drag & drop pour colis mixtes - Commande <?php echo $object->ref; ?> (<?php echo count($object->lines); ?> produits commandés)</div>
</div>

<div class="colisage-container">
    <!-- Zone Inventaire -->
    <div class="inventory-zone">
        <div class="inventory-header">
            📦 Inventaire Produits
        </div>
        
        <div class="inventory-controls">
            <input type="text" class="search-box" placeholder="🔍 Rechercher un produit..." id="searchBox">
            <div class="sort-controls">
                <select id="filterSelect" class="sort-select">
                    <option value="all">Tous les produits</option>
                    <option value="available">Disponibles</option>
                    <option value="partial">Partiellement utilisés</option>
                    <option value="exhausted">Épuisés</option>
                </select>
                <select id="sortSelect" class="sort-select">
                    <option value="ref">Trier par Référence</option>
                    <option value="name">Trier par Nom</option>
                    <option value="length">Trier par Longueur</option>
                    <option value="width">Trier par Largeur</option>
                    <option value="color">Trier par Couleur</option>
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
            <button class="btn-add-colis" id="addNewColisBtn">+ Nouveau Colis</button>
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

<?php
print '</div>'; // End fichecenter
print dol_get_fiche_end();

// Load products from the actual order with extrafields from order lines
$products_from_order = array();
if (is_array($object->lines) && count($object->lines) > 0) {
    foreach ($object->lines as $line) {
        if ($line->fk_product > 0) {
            $product = new Product($db);
            $product->fetch($line->fk_product);
            
            // Only include products (type 0), not services (type 1)
            if ($product->type != 0) {
                continue; // Skip services
            }
            
            // Get dimensions from order line extrafields (not from product)
            $length = 1000; // default
            $width = 100;   // default
            $color = 'Standard'; // default
            
            // Try to get length from extrafields (common variations)
            if (isset($line->array_options['options_length']) && !empty($line->array_options['options_length'])) {
                $length = floatval($line->array_options['options_length']);
            } elseif (isset($line->array_options['options_longueur']) && !empty($line->array_options['options_longueur'])) {
                $length = floatval($line->array_options['options_longueur']);
            } elseif (isset($line->array_options['options_long']) && !empty($line->array_options['options_long'])) {
                $length = floatval($line->array_options['options_long']);
            }
            
            // Try to get width from extrafields (common variations)
            if (isset($line->array_options['options_width']) && !empty($line->array_options['options_width'])) {
                $width = floatval($line->array_options['options_width']);
            } elseif (isset($line->array_options['options_largeur']) && !empty($line->array_options['options_largeur'])) {
                $width = floatval($line->array_options['options_largeur']);
            } elseif (isset($line->array_options['options_larg']) && !empty($line->array_options['options_larg'])) {
                $width = floatval($line->array_options['options_larg']);
            }
            
            // Try to get color from extrafields (common variations)
            if (isset($line->array_options['options_color']) && !empty($line->array_options['options_color'])) {
                $color = $line->array_options['options_color'];
            } elseif (isset($line->array_options['options_couleur']) && !empty($line->array_options['options_couleur'])) {
                $color = $line->array_options['options_couleur'];
            } elseif (!empty($product->color)) {
                $color = $product->color;
            }
            
            $products_from_order[] = array(
                'id' => $product->id,
                'ref' => $product->ref,
                'name' => $product->label,
                'color' => $color,
                'used' => 0,
                'total' => $line->qty,
                'weight' => (!empty($product->weight) ? $product->weight : 1.0),
                'length' => $length,
                'width' => $width,
                'line_id' => $line->rowid
            );
        }
    }
}

// Convert PHP array to JavaScript
$products_json = json_encode($products_from_order);

// DEBUG: Show available extrafields for first product line (not service)
$first_product_line = null;
foreach ($object->lines as $line) {
    if ($line->fk_product > 0) {
        $temp_product = new Product($db);
        $temp_product->fetch($line->fk_product);
        if ($temp_product->type == 0) { // Only products, not services
            $first_product_line = $line;
            break;
        }
    }
}

if ($first_product_line && !empty($first_product_line->array_options)) {
    print '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; font-size: 12px;">';
    print '<strong>🔍 DEBUG - Extrafields disponibles dans la première ligne produit :</strong><br>';
    foreach ($first_product_line->array_options as $key => $value) {
        print "• <code>$key</code> = " . (is_null($value) ? 'null' : $value) . "<br>";
    }
    print '</div>';
}
?>

<script type="text/javascript">
        // Variables globales
        let products = <?php echo $products_json; ?>;

        // Default products if none in order
        if (products.length === 0) {
            products = [
                { id: 1, ref: 'PROD-A1', name: 'Profilé Aluminium Standard', color: 'Naturel', used: 15, total: 50, weight: 2.5, length: 6000, width: 40 },
                { id: 2, ref: 'PROD-A2', name: 'Profilé Aluminium Renforcé', color: 'Naturel', used: 8, total: 25, weight: 3.2, length: 6000, width: 60 },
                { id: 3, ref: 'PROD-B1', name: 'Panneau Composite', color: 'Blanc', used: 12, total: 30, weight: 1.8, length: 3000, width: 1500 },
                { id: 4, ref: 'PROD-B2', name: 'Panneau Composite', color: 'Gris', used: 5, total: 20, weight: 1.8, length: 2000, width: 1200 },
                { id: 5, ref: 'PROD-C1', name: 'Visserie Inox M6x20', color: 'Acier', used: 0, total: 100, weight: 0.1, length: 20, width: 6 },
                { id: 6, ref: 'PROD-D1', name: 'Joint Étanchéité EPDM', color: 'Noir', used: 20, total: 20, weight: 0.3, length: 10000, width: 15 }
            ];
        }

        let colis = [
            { 
                id: 1, 
                number: 1, 
                products: [
                    { productId: products[0] ? products[0].id : 1, quantity: 5, weight: 12.5 },
                    { productId: products[1] ? products[1].id : 3, quantity: 3, weight: 5.4 }
                ],
                totalWeight: 17.9,
                maxWeight: 25,
                status: 'ok',
                multiple: 1
            },
            { 
                id: 2, 
                number: 2, 
                products: [
                    { productId: products[1] ? products[1].id : 2, quantity: 2, weight: 6.4 }
                ],
                totalWeight: 6.4,
                maxWeight: 25,
                status: 'ok',
                multiple: 1
            }
        ];

        let selectedColis = null;
        let draggedProduct = null;
        let draggedColisLine = null;
        let currentSort = 'ref';
        let currentFilter = 'all';

        // Fonction de debug
        function debugLog(message) {
            console.log(message);
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

        // Fonctions principales
        function addNewColis() {
            debugLog('Ajout nouveau colis');
            const newId = Math.max(...colis.map(c => c.id)) + 1;
            const newNumber = Math.max(...colis.map(c => c.number)) + 1;
            
            const newColis = {
                id: newId,
                number: newNumber,
                products: [],
                totalWeight: 0,
                maxWeight: 25,
                status: 'ok',
                multiple: 1
            };

            colis.push(newColis);
            renderColisOverview();
            selectColis(newColis);
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
            
            // Remettre tous les produits dans l'inventaire
            coliData.products.forEach(p => {
                const product = products.find(prod => prod.id === p.productId);
                if (product) {
                    const quantityToRestore = p.quantity * coliData.multiple;
                    product.used -= quantityToRestore;
                }
            });

            // Supprimer le colis
            const colisIndex = colis.findIndex(c => c.id === colisId);
            if (colisIndex > -1) {
                colis.splice(colisIndex, 1);
            }
            
            // Déselectionner si c'était le colis sélectionné
            if (selectedColis && selectedColis.id === colisId) {
                selectedColis = null;
            }

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
        }

        async function showDuplicateDialog(colisId) {
            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) {
                await showConfirm('Erreur: Colis non trouvé');
                return;
            }

            const currentMultiple = coliData.multiple || 1;
            const message = `Combien de fois créer ce colis identique ?\n\nActuellement: ${currentMultiple} colis`;
            const newMultiple = await showPrompt(message, currentMultiple.toString());
            
            if (newMultiple !== null && !isNaN(newMultiple) && parseInt(newMultiple) > 0) {
                updateColisMultiple(colisId, parseInt(newMultiple));
            } else if (newMultiple !== null) {
                await showConfirm('Veuillez saisir un nombre entier positif');
            }
        }

        async function updateColisMultiple(colisId, multiple) {
            const coliData = colis.find(c => c.id === colisId);
            if (!coliData) return;

            const oldMultiple = coliData.multiple;
            const newMultiple = parseInt(multiple);
            
            if (isNaN(newMultiple) || newMultiple < 1) {
                await showConfirm('Le nombre de colis doit être un entier positif');
                return;
            }

            // Calculer la différence pour ajuster les quantités utilisées
            const multipleDiff = newMultiple - oldMultiple;
            
            // Mettre à jour les quantités utilisées pour chaque produit
            for (const p of coliData.products) {
                const product = products.find(prod => prod.id === p.productId);
                if (product) {
                    product.used += p.quantity * multipleDiff;
                    
                    // Vérifier qu'on ne dépasse pas le total disponible
                    if (product.used > product.total) {
                        await showConfirm(`Attention: ${product.ref} - Quantité dépassée! Utilisé: ${product.used}, Total: ${product.total}`);
                        // Revenir à l'ancienne valeur
                        product.used -= p.quantity * multipleDiff;
                        return;
                    }
                }
            }

            coliData.multiple = newMultiple;
            
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id === colisId) {
                renderColisDetail();
            }
        }

        function removeProductFromColis(colisId, productId) {
            const coliData = colis.find(c => c.id === colisId);
            const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
            
            if (!coliData || !productInColis) return;

            // Remettre les quantités dans l'inventaire (tenir compte des multiples)
            const product = products.find(p => p.id === productId);
            if (product) {
                product.used -= productInColis.quantity * coliData.multiple;
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
        }

        function updateProductQuantity(colisId, productId, newQuantity) {
            const coliData = colis.find(c => c.id === colisId);
            const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
            const product = products.find(p => p.id === productId);
            
            if (!productInColis || !product || !coliData) return;

            const oldQuantity = productInColis.quantity;
            const quantityDiff = parseInt(newQuantity) - oldQuantity;

            // Vérifier la disponibilité (tenir compte des multiples)
            const totalQuantityNeeded = quantityDiff * coliData.multiple;
            const available = product.total - product.used;
            
            if (totalQuantityNeeded > available) {
                alert(`Quantité insuffisante ! Disponible: ${available}, Besoin: ${totalQuantityNeeded}`);
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

            // Re-render
            renderInventory();
            renderColisOverview();
            renderColisDetail();
        }

        function renderInventory() {
            const container = document.getElementById('inventoryList');
            container.innerHTML = '';

            // Trier les produits selon le critère sélectionné
            const sortedProducts = [...products].sort((a, b) => {
                switch(currentSort) {
                    case 'ref': return a.ref.localeCompare(b.ref);
                    case 'name': return a.name.localeCompare(b.name);
                    case 'length': return b.length - a.length;
                    case 'width': return b.width - a.width;
                    case 'color': return a.color.localeCompare(b.color);
                    default: return 0;
                }
            });

            // Filtrer les produits
            const filteredProducts = sortedProducts.filter(product => {
                const available = product.total - product.used;
                switch(currentFilter) {
                    case 'available': return available > 0 && product.used === 0;
                    case 'partial': return available > 0 && product.used > 0;
                    case 'exhausted': return available === 0;
                    default: return true;
                }
            });

            filteredProducts.forEach(product => {
                const available = product.total - product.used;
                const percentage = (product.used / product.total) * 100;
                let status = 'available';
                
                if (available === 0) status = 'exhausted';
                else if (product.used > 0) status = 'partial';

                const productElement = document.createElement('div');
                productElement.className = `product-item ${status}`;
                productElement.draggable = status !== 'exhausted';
                productElement.dataset.productId = product.id;

                productElement.innerHTML = `
                    <div class="product-header">
                        <span class="product-ref">${product.ref}</span>
                        <span class="product-color">${product.color}</span>
                    </div>
                    <div class="product-name">${product.name}</div>
                    <div style="font-size: 11px; color: #666; margin: 4px 0;">
                        L: ${product.length}mm × l: ${product.width}mm
                    </div>
                    <div class="quantity-info">
                        <span class="quantity-used">${product.used}</span>
                        <span>/</span>
                        <span class="quantity-total">${product.total}</span>
                        <div class="quantity-bar">
                            <div class="quantity-progress" style="width: ${percentage}%"></div>
                        </div>
                    </div>
                    <div class="status-indicator ${status === 'exhausted' ? 'error' : status === 'partial' ? 'warning' : ''}"></div>
                `;

                // Événements drag & drop
                productElement.addEventListener('dragstart', function(e) {
                    draggedProduct = product;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'copy';
                });

                productElement.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    draggedProduct = null;
                });

                container.appendChild(productElement);
            });
        }

        function renderColisOverview() {
            const tbody = document.getElementById('colisTableBody');
            tbody.innerHTML = '';

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

                const multipleDisplay = c.multiple > 1 ? ` (×${c.multiple})` : '';

                // Ligne d'en-tête pour le colis
                const headerRow = document.createElement('tr');
                headerRow.className = 'colis-group-header';
                headerRow.dataset.colisId = c.id;
                if (selectedColis && selectedColis.id === c.id) {
                    headerRow.classList.add('selected');
                }

                headerRow.innerHTML = `
                    <td colspan="6">
                        <strong>📦 Colis ${c.number}${multipleDisplay}</strong>
                        <span style="margin-left: 15px; color: #666;">
                            ${c.products.length} produit${c.products.length > 1 ? 's' : ''} • 
                            ${c.totalWeight.toFixed(1)} kg • 
                            ${statusIcon}
                        </span>
                    </td>
                `;

                // Event listener pour sélectionner le colis
                headerRow.addEventListener('click', () => {
                    selectColis(c);
                });

                tbody.appendChild(headerRow);

                // Lignes pour chaque produit dans le colis
                if (c.products.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.className = 'colis-group-item';
                    emptyRow.innerHTML = `
                        <td></td>
                        <td colspan="5" style="font-style: italic; color: #999; padding: 10px;">
                            Colis vide - Glissez des produits ici
                        </td>
                    `;
                    
                    // Drop zone pour colis vide
                    emptyRow.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        this.style.background = '#e8f5e8';
                    });

                    emptyRow.addEventListener('dragleave', function(e) {
                        this.style.background = '';
                    });

                    emptyRow.addEventListener('drop', async function(e) {
                        e.preventDefault();
                        this.style.background = '';
                        if (draggedProduct) {
                            await addProductToColis(c.id, draggedProduct.id, 1);
                        }
                    });

                    tbody.appendChild(emptyRow);
                } else {
                    c.products.forEach((productInColis, index) => {
                        const product = products.find(p => p.id === productInColis.productId);
                        if (!product) return;

                        const productRow = document.createElement('tr');
                        productRow.className = 'colis-group-item';
                        productRow.dataset.colisId = c.id;
                        productRow.dataset.productId = product.id;

                        productRow.innerHTML = `
                            <td></td>
                            <td>
                                <div class="product-label">
                                    <span>${product.name}</span>
                                    <span class="product-color-badge">${product.color}</span>
                                </div>
                                <div style="font-size: 11px; color: #666;">${product.ref}</div>
                            </td>
                            <td style="font-weight: bold; text-align: right; vertical-align: top;">
                                ${productInColis.quantity}
                                ${c.multiple > 1 ? `<div style="font-size: 10px; color: #666;">×${c.multiple} = ${productInColis.quantity * c.multiple}</div>` : ''}
                            </td>
                            <td style="font-weight: bold; text-align: left; vertical-align: top;">
                                ${product.length}×${product.width}
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
                                const newQuantity = await showPrompt(
                                    `Nouvelle quantité pour ${product.ref} :`,
                                    productInColis.quantity.toString()
                                );
                                if (newQuantity !== null && !isNaN(newQuantity) && parseInt(newQuantity) > 0) {
                                    await updateProductQuantity(c.id, product.id, parseInt(newQuantity));
                                }
                            });
                        }

                        if (deleteBtn) {
                            deleteBtn.addEventListener('click', async (e) => {
                                e.stopPropagation();
                                const confirmed = await showConfirm(
                                    `Supprimer ${product.ref} du colis ${c.number} ?`
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

                        // Drop zone sur les lignes de produits
                        productRow.addEventListener('dragover', function(e) {
                            e.preventDefault();
                            this.style.background = '#e8f5e8';
                        });

                        productRow.addEventListener('dragleave', function(e) {
                            this.style.background = '';
                        });

                        productRow.addEventListener('drop', async function(e) {
                            e.preventDefault();
                            this.style.background = '';
                            if (draggedProduct) {
                                await addProductToColis(c.id, draggedProduct.id, 1);
                            }
                        });

                        tbody.appendChild(productRow);
                    });
                }
            });
        }

        function selectColis(coliData) {
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

            container.innerHTML = `
                <div class="colis-detail-header">
                    <h3 class="colis-detail-title">📦 Colis ${selectedColis.number}</h3>
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

                <div class="colis-content" id="colisContent">
                    ${selectedColis.products.map((p, index) => {
                        const product = products.find(prod => prod.id === p.productId);
                        return `
                            <div class="colis-line" draggable="true" data-line-index="${index}">
                                <span class="drag-handle">⋮⋮</span>
                                <span class="line-product">${product.ref} - ${product.name}</span>
                                <input type="number" class="line-quantity" value="${p.quantity}" min="1" 
                                       data-product-id="${p.productId}">
                                <span class="line-weight">${p.weight.toFixed(1)} kg</span>
                                <button class="btn-remove-line" data-product-id="${p.productId}">✕</button>
                            </div>
                        `;
                    }).join('')}
                    <div class="drop-hint">Glissez un produit ici pour l'ajouter</div>
                </div>
            `;

            // Event listeners pour les boutons et inputs
            
            // Bouton supprimer colis
            const deleteBtn = document.getElementById('deleteColisBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
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

            // Boutons supprimer ligne
            const removeLineBtns = container.querySelectorAll('.btn-remove-line');
            removeLineBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const productId = parseInt(e.target.dataset.productId);
                    removeProductFromColis(selectedColis.id, productId);
                });
            });

            // Inputs quantité
            const quantityInputs = container.querySelectorAll('.line-quantity');
            quantityInputs.forEach(input => {
                input.addEventListener('change', async (e) => {
                    const productId = parseInt(e.target.dataset.productId);
                    await updateProductQuantity(selectedColis.id, productId, e.target.value);
                });
            });

            // Setup drop zone pour le contenu du colis
            const colisContent = document.getElementById('colisContent');
            if (colisContent) {
                colisContent.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('drop-zone-active');
                });

                colisContent.addEventListener('dragleave', function(e) {
                    this.classList.remove('drop-zone-active');
                });

                colisContent.addEventListener('drop', async function(e) {
                    e.preventDefault();
                    this.classList.remove('drop-zone-active');
                    if (draggedProduct) {
                        await addProductToColis(selectedColis.id, draggedProduct.id, 1);
                    } else if (draggedColisLine !== null) {
                        // Réorganisation des produits dans le colis
                        reorderProductInColis(draggedColisLine.colisId, draggedColisLine.fromIndex, getDropIndex(e));
                    }
                });

                // Setup drag & drop pour réorganiser les produits
                const colisLines = container.querySelectorAll('.colis-line');
                colisLines.forEach((line, index) => {
                    line.addEventListener('dragstart', function(e) {
                        draggedColisLine = {
                            colisId: selectedColis.id,
                            fromIndex: index
                        };
                        this.classList.add('dragging');
                        e.dataTransfer.effectAllowed = 'move';
                    });

                    line.addEventListener('dragend', function(e) {
                        this.classList.remove('dragging');
                        draggedColisLine = null;
                    });
                });
            }
        }

        function addProductToColis(colisId, productId, quantity) {
            const coliData = colis.find(c => c.id === colisId);
            const product = products.find(p => p.id === productId);
            
            if (!coliData || !product) return;

            // Vérifier la disponibilité
            const available = product.total - product.used;
            if (available < quantity) {
                alert(`Quantité insuffisante ! Disponible: ${available}, Demandé: ${quantity}`);
                return;
            }

            // Vérifier si le produit est déjà dans le colis
            const existingProduct = coliData.products.find(p => p.productId === productId);
            
            if (existingProduct) {
                existingProduct.quantity += quantity;
                existingProduct.weight = existingProduct.quantity * product.weight;
            } else {
                coliData.products.push({
                    productId: productId,
                    quantity: quantity,
                    weight: quantity * product.weight
                });
            }

            // Recalculer le poids total
            coliData.totalWeight = coliData.products.reduce((sum, p) => sum + p.weight, 0);

            // Mettre à jour les quantités utilisées (tenir compte des multiples)
            product.used += quantity * coliData.multiple;

            // Re-render
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id === colisId) {
                renderColisDetail();
            }
        }

        function reorderProductInColis(colisId, fromIndex, toIndex) {
            const coliData = colis.find(c => c.id === colisId);
            if (!coliData || fromIndex === toIndex) return;

            // Réorganiser les produits
            const product = coliData.products.splice(fromIndex, 1)[0];
            coliData.products.splice(toIndex, 0, product);

            // Re-render
            renderColisDetail();
        }

        function getDropIndex(event) {
            const colisContent = document.getElementById('colisContent');
            const lines = Array.from(colisContent.querySelectorAll('.colis-line'));
            const mouseY = event.clientY;

            for (let i = 0; i < lines.length; i++) {
                const rect = lines[i].getBoundingClientRect();
                if (mouseY < rect.top + rect.height / 2) {
                    return i;
                }
            }
            return lines.length;
        }

        function setupEventListeners() {
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

            // Filtre
            const filterSelect = document.getElementById('filterSelect');
            if (filterSelect) {
                filterSelect.addEventListener('change', function(e) {
                    currentFilter = e.target.value;
                    renderInventory();
                });
            }

            // Tri
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function(e) {
                    currentSort = e.target.value;
                    renderInventory();
                });
            }

            // Bouton Nouveau Colis
            const addNewColisBtn = document.getElementById('addNewColisBtn');
            if (addNewColisBtn) {
                addNewColisBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    addNewColis();
                });
            }
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            renderInventory();
            renderColisOverview();
            setupEventListeners();
        });
</script>

<?php
llxFooter();
$db->close();
?>