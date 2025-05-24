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
                                }
                                
                                // Only add products with quantity > 0
                                if ($quantity > 0) {
                                    $productData = array(
                                        'id' => $productIndex++,
                                        'ref' => $product->ref,
                                        'name' => $product->label,
                                        'color' => $color,
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
            height: fit-content;
            min-height: 700px;
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
            min-height: 700px;
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
            z-index: 1000;
        }

        .product-item.exhausted {
            opacity: 0.6;
            background: #ffebee;
            border-color: #ef5350;
        }

        .product-item.in-colis {
            width: 100% !important;
            margin-bottom: 10px;
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

        .product-dimensions {
            font-size: 11px;
            color: #666;
            margin: 4px 0;
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

        .quantity-input-container {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
        }

        .quantity-input-label {
            font-size: 12px;
            font-weight: bold;
            color: #666;
        }

        .quantity-input {
            width: 60px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 12px;
        }

        /* Zone Constructeur */
        .constructor-zone {
            width: 60%;
            display: flex;
            flex-direction: column;
            min-height: fit-content;
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
            min-height: fit-content;
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
            background: #e8f5e8 !important;
            border-left: 4px solid #4CAF50 !important;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { background: #e8f5e8; }
            50% { background: #c8e6c9; }
            100% { background: #e8f5e8; }
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
            transition: all 0.3s ease;
        }

        .colis-content.drop-zone-active {
            border-color: #4CAF50 !important;
            background: #e8f5e8 !important;
            transform: scale(1.01);
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
            opacity: 1;
            transition: opacity 0.3s;
        }

        .colis-content.drop-zone-active .drop-hint {
            opacity: 0;
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

        /* Debug styles */
        .debug-console {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-width: 300px;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            z-index: 9999;
        }

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

<script>
        // Variables globales
        let products = [];
        let productGroups = [];
        let colis = [];
        let selectedColis = null;
        let draggedProduct = null;
        let draggedColisLine = null;
        let currentProductGroup = 'all';
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

        // Fonction pour créer une vignette produit (utilisée dans inventaire et colis)
        function createProductVignette(product, isInColis = false, currentQuantity = 1) {
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
                    <span class="product-ref">${product.ref}</span>
                    <span class="product-color">${product.color}</span>
                </div>
                <div class="product-name">${product.name}</div>
                <div class="product-dimensions">
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
                ${quantityInputHtml}
                <div class="status-indicator ${status === 'exhausted' ? 'error' : status === 'partial' ? 'warning' : ''}"></div>
            `;

            return vignetteElement;
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
            
            debugLog(`Suppression colis: ${JSON.stringify(coliData)}`);
            
            // Remettre tous les produits dans l'inventaire
            coliData.products.forEach(p => {
                const product = products.find(prod => prod.id === p.productId);
                if (product) {
                    const quantityToRestore = p.quantity * coliData.multiple;
                    product.used -= quantityToRestore;
                    debugLog(`Remise en stock extrafield "nombre": ${product.ref} +${quantityToRestore}`);
                }
            });

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
            
            // Mettre à jour les quantités utilisées pour chaque produit
            for (const p of coliData.products) {
                const product = products.find(prod => prod.id === p.productId);
                if (product) {
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
        }

        function removeProductFromColis(colisId, productId) {
            debugLog(`Suppression produit ${productId} du colis ${colisId}`);
            
            const coliData = colis.find(c => c.id === colisId);
            const productInColis = coliData ? coliData.products.find(p => p.productId === productId) : null;
            
            if (!coliData || !productInColis) {
                debugLog('ERREUR: Colis ou produit non trouvé dans le colis');
                return;
            }

            // Remettre les quantités dans l'inventaire (tenir compte des multiples)
            const product = products.find(p => p.id === productId);
            if (product) {
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

            // Vérifier la disponibilité (tenir compte des multiples)
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
        }

        function renderInventory() {
            const container = document.getElementById('inventoryList');
            container.innerHTML = '';

            // Filtrer les produits selon le groupe sélectionné
            let filteredProducts = products;
            if (currentProductGroup !== 'all') {
                const selectedGroup = productGroups.find(g => g.key === currentProductGroup);
                if (selectedGroup) {
                    filteredProducts = products.filter(product => selectedGroup.products.includes(product.id));
                    debugLog(`Filtrage par groupe "${currentProductGroup}": ${filteredProducts.length} produits`);
                }
            }

            // Les produits sont déjà dans l'ordre de la commande, pas besoin de trier
            filteredProducts.forEach(product => {
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

                // Setup drop zone pour l'en-tête du colis
                setupDropZone(headerRow, c.id);
                tbody.appendChild(headerRow);

                // Lignes pour chaque produit dans le colis
                if (c.products.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.className = 'colis-group-item';
                    emptyRow.dataset.colisId = c.id;
                    emptyRow.innerHTML = `
                        <td></td>
                        <td colspan="5" style="font-style: italic; color: #999; padding: 10px;">
                            Colis vide - Glissez des produits ici
                        </td>
                    `;
                    
                    setupDropZone(emptyRow, c.id);
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
                                    `Nouvelle quantité pour ${product.ref} :\n(Stock disponible extrafield "nombre": ${product.total - product.used})`,
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

                        setupDropZone(productRow, c.id);
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

                <div style="margin-bottom: 10px; font-weight: bold;">Produits dans ce colis:</div>
                <div class="colis-content" id="colisContent" style="border: 2px dashed #ddd; border-radius: 8px; min-height: 150px; padding: 15px; position: relative;">
                    <div class="drop-hint" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #999; font-style: italic; pointer-events: none;">
                        ${selectedColis.products.length === 0 ? 'Glissez un produit ici pour l\'ajouter' : ''}
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
                    await updateProductQuantity(selectedColis.id, productId, e.target.value);
                });
            });

            // Setup drop zone pour le contenu du colis
            if (colisContent) {
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

            // Bouton Nouveau Colis
            const addNewColisBtn = document.getElementById('addNewColisBtn');
            if (addNewColisBtn) {
                addNewColisBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    debugLog('Bouton nouveau colis cliqué');
                    addNewColis();
                });
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

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('DOM chargé, initialisation...');
            debugLog('🔧 CONFIGURATION: Interface épurée + vignettes 100% + inputs quantité');
            
            renderInventory();
            renderColisOverview();
            setupEventListeners();
            loadData();
            
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
