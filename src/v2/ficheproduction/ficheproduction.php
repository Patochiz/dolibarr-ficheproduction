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
 * \brief       Interface drag & drop de colisage - Reproduction exacte de la maquette
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
    // Set JSON header
    header('Content-Type: application/json');
    
    // Initialize objects for AJAX
    $object = new Commande($db);
    if ($id > 0) {
        $object->fetch($id);
    }
    
    switch ($action) {
        case 'ficheproduction_get_data':
            handleGetData($db, $object);
            break;
        case 'ficheproduction_add_colis':
            handleAddColis($db, $object, $user);
            break;
        case 'ficheproduction_delete_colis':
            handleDeleteColis($db, $user);
            break;
        case 'ficheproduction_add_product':
            handleAddProduct($db, $user);
            break;
        case 'ficheproduction_remove_product':
            handleRemoveProduct($db, $user);
            break;
        case 'ficheproduction_update_quantity':
            handleUpdateQuantity($db, $user);
            break;
        case 'ficheproduction_update_multiple':
            handleUpdateMultiple($db, $user);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
    exit;
}

// AJAX Handler Functions
function handleGetData($db, $object) {
    $data = array(
        'products' => array(),
        'colis' => array(),
        'session' => null
    );
    
    try {
        // Get products from order lines
        if ($object->id > 0) {
            // Fetch order lines if not already loaded
            if (empty($object->lines)) {
                $object->fetch_lines();
            }
            
            foreach ($object->lines as $line) {
                if ($line->fk_product > 0) {
                    $product = new Product($db);
                    $result = $product->fetch($line->fk_product);
                    
                    if ($result > 0 && $product->type == 0) { // Only products, not services
                        // Get dimensions from line extrafields
                        $length = 1000; // default
                        $width = 100;   // default
                        $color = 'Standard'; // default
                        
                        // Try to get dimensions from extrafields
                        if (isset($line->array_options)) {
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
                        
                        $data['products'][] = array(
                            'id' => $product->id,
                            'ref' => $product->ref,
                            'label' => $product->label,
                            'weight' => (!empty($product->weight) ? $product->weight : 1.0),
                            'length' => $length,
                            'width' => $width,
                            'color' => $color,
                            'total' => $line->qty,
                            'used' => 0 // TODO: Calculate from existing colis
                        );
                    }
                }
            }
        }
        
        // TODO: Load existing colis from database when implemented
        
        echo json_encode($data);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleAddColis($db, $object, $user) {
    try {
        // For now, just return success
        // TODO: Implement actual database insertion
        echo json_encode([
            'success' => true, 
            'colis_id' => rand(1000, 9999) // Temporary ID
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDeleteColis($db, $user) {
    try {
        // TODO: Implement actual database deletion
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleAddProduct($db, $user) {
    try {
        // TODO: Implement actual database insertion
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleRemoveProduct($db, $user) {
    try {
        // TODO: Implement actual database deletion
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleUpdateQuantity($db, $user) {
    try {
        // TODO: Implement actual database update
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleUpdateMultiple($db, $user) {
    try {
        // TODO: Implement actual database update
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

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
    
    // Load the thirdparty object
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
if (is_object($object->thirdparty)) {
    $morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
} else {
    $morehtmlref .= '<br>Client non défini';
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';

// DEBUG: Show available extrafields for debugging
$first_product_line = null;
if (!empty($object->lines)) {
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
}

if ($first_product_line && !empty($first_product_line->array_options)) {
    print '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; font-size: 12px;">';
    print '<strong>🔍 DEBUG - Extrafields disponibles dans la première ligne produit :</strong><br>';
    foreach ($first_product_line->array_options as $key => $value) {
        print "• <code>$key</code> = " . (is_null($value) ? 'null' : htmlspecialchars($value)) . "<br>";
    }
    print '</div>';
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
    <h1>🚀 Gestionnaire de Colisage v2.0</h1>
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
            <div class="empty-state">Chargement des produits...</div>
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
                    <tr><td colspan="6" class="empty-state">Aucun colis créé. Cliquez sur "Nouveau Colis" pour commencer.</td></tr>
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

<script type="text/javascript">
// Variables globales
let products = [];
let colis = [];
let selectedColis = null;
let draggedProduct = null;
let draggedColisLine = null;
let currentSort = 'ref';
let currentFilter = 'all';

// Configuration
const ORDER_ID = <?php echo $object->id; ?>;
const TOKEN = '<?php echo newToken(); ?>';

// Fonction de debug
function debugLog(message) {
    console.log('🔧 ' + message);
    const debugConsole = document.getElementById('debugConsole');
    if (debugConsole) {
        debugConsole.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '<br>';
        debugConsole.scrollTop = debugConsole.scrollHeight;
    }
}

// Modales custom identiques à la maquette
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
        debugLog(`Response text: ${text.substring(0, 200)}...`);
        
        try {
            return JSON.parse(text);
        } catch (parseError) {
            debugLog(`JSON Parse Error: ${parseError.message}`);
            return { success: false, error: 'Invalid JSON response', rawResponse: text };
        }
    } catch (error) {
        debugLog('Erreur API: ' + error.message);
        return { success: false, error: error.message };
    }
}

async function loadData() {
    debugLog('Chargement des données...');
    const result = await apiCall('ficheproduction_get_data');
    
    debugLog(`Résultat API: ${JSON.stringify(result)}`);
    
    if (result && result.products) {
        products = result.products;
        colis = result.colis || [];
        debugLog(`Chargé ${products.length} produits et ${colis.length} colis`);
        
        renderInventory();
        renderColisOverview();
        
        if (colis.length > 0) {
            selectColis(colis[0]);
        }
    } else {
        debugLog('Erreur lors du chargement des données: ' + (result?.error || 'Unknown error'));
        if (result?.rawResponse) {
            debugLog('Raw response: ' + result.rawResponse.substring(0, 500));
        }
    }
}

async function addNewColis() {
    debugLog('Ajout nouveau colis');
    const result = await apiCall('ficheproduction_add_colis');
    
    if (result && result.success) {
        // Créer un faux colis temporaire pour l'interface
        const newColis = {
            id: result.colis_id,
            numero_colis: colis.length + 1,
            poids_max: 25,
            poids_total: 0,
            multiple_colis: 1,
            status: 'ok',
            products: []
        };
        colis.push(newColis);
        renderColisOverview();
        selectColis(newColis);
    }
}

async function deleteColis(colisId) {
    debugLog(`Tentative suppression colis ID: ${colisId}`);
    
    const confirmed = await showConfirm('Êtes-vous sûr de vouloir supprimer ce colis ?');
    if (!confirmed) {
        debugLog('Suppression annulée par utilisateur');
        return;
    }

    const result = await apiCall('ficheproduction_delete_colis', { colis_id: colisId });
    
    if (result && result.success) {
        // Supprimer de l'interface
        const index = colis.findIndex(c => c.id == colisId);
        if (index > -1) {
            colis.splice(index, 1);
        }
        selectedColis = null;
        renderColisOverview();
        renderColisDetail();
    }
}

async function addProductToColis(colisId, productId, quantity) {
    debugLog(`Ajout produit ${productId} (qté: ${quantity}) au colis ${colisId}`);
    
    const result = await apiCall('ficheproduction_add_product', {
        colis_id: colisId,
        product_id: productId,
        quantite: quantity
    });
    
    if (result && result.success) {
        // Mettre à jour l'interface localement
        const targetColis = colis.find(c => c.id == colisId);
        const product = products.find(p => p.id == productId);
        
        if (targetColis && product) {
            // Vérifier si le produit existe déjà
            const existingProduct = targetColis.products.find(p => p.product_id == productId);
            if (existingProduct) {
                existingProduct.quantite += quantity;
                existingProduct.poids_total = existingProduct.quantite * product.weight;
            } else {
                targetColis.products.push({
                    product_id: productId,
                    quantite: quantity,
                    poids_total: quantity * product.weight
                });
            }
            
            // Recalculer le poids total
            targetColis.poids_total = targetColis.products.reduce((sum, p) => sum + p.poids_total, 0);
            
            // Mettre à jour les quantités utilisées
            product.used += quantity * targetColis.multiple_colis;
            
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id == colisId) {
                selectColis(targetColis);
            }
        }
    } else {
        await showConfirm('Erreur lors de l\'ajout du produit');
    }
}

// Interface rendering functions - Identiques à la maquette
function renderInventory() {
    const container = document.getElementById('inventoryList');
    container.innerHTML = '';

    if (products.length === 0) {
        container.innerHTML = '<div class="empty-state">Aucun produit trouvé dans cette commande</div>';
        return;
    }

    // Trier les produits selon le critère sélectionné
    const sortedProducts = [...products].sort((a, b) => {
        switch(currentSort) {
            case 'ref': return a.ref.localeCompare(b.ref);
            case 'name': return a.label.localeCompare(b.label);
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
                <span class="product-color">${product.color || 'Standard'}</span>
            </div>
            <div class="product-name">${product.label}</div>
            <div style="font-size: 11px; color: #666; margin: 4px 0;">
                L: ${product.length || 0}mm × l: ${product.width || 0}mm
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

    if (colis.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Aucun colis créé. Cliquez sur "Nouveau Colis" pour commencer.</td></tr>';
        return;
    }

    colis.forEach(c => {
        const weightPercentage = (c.poids_total / c.poids_max) * 100;
        let statusIcon = '✅';
        let statusClass = '';
        if (weightPercentage > 90) {
            statusIcon = '⚠️';
            statusClass = 'warning';
        } else if (weightPercentage > 100) {
            statusIcon = '❌';
            statusClass = 'error';
        }

        const multipleDisplay = c.multiple_colis > 1 ? ` (×${c.multiple_colis})` : '';

        // Ligne d'en-tête pour le colis
        const headerRow = document.createElement('tr');
        headerRow.className = 'colis-group-header';
        headerRow.dataset.colisId = c.id;
        if (selectedColis && selectedColis.id === c.id) {
            headerRow.classList.add('selected');
        }

        headerRow.innerHTML = `
            <td colspan="6">
                <strong>📦 Colis ${c.numero_colis}${multipleDisplay}</strong>
                <span style="margin-left: 15px; color: #666;">
                    ${c.products ? c.products.length : 0} produit${c.products && c.products.length > 1 ? 's' : ''} • 
                    ${c.poids_total ? c.poids_total.toFixed(1) : '0.0'} kg • 
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
        if (!c.products || c.products.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'colis-group-item';
            emptyRow.innerHTML = `
                <td></td>
                <td colspan="5" style="font-style: italic; color: #999; padding: 10px;">
                    Colis vide - Glissez des produits ici
                </td>
            `;
            
            // Drop zone pour colis vide
            setupDropZone(emptyRow, c.id);
            tbody.appendChild(emptyRow);
        } else {
            c.products.forEach((productInColis, index) => {
                const product = products.find(p => p.id == productInColis.product_id);
                if (!product) return;

                const productRow = document.createElement('tr');
                productRow.className = 'colis-group-item';
                productRow.dataset.colisId = c.id;
                productRow.dataset.productId = product.id;

                productRow.innerHTML = `
                    <td></td>
                    <td>
                        <div class="product-label">
                            <span>${product.label}</span>
                            <span class="product-color-badge">${product.color || 'Standard'}</span>
                        </div>
                        <div style="font-size: 11px; color: #666;">${product.ref}</div>
                    </td>
                    <td style="font-weight: bold; text-align: right; vertical-align: top;">
                        ${productInColis.quantite}
                        ${c.multiple_colis > 1 ? `<div style="font-size: 10px; color: #666;">×${c.multiple_colis} = ${productInColis.quantite * c.multiple_colis}</div>` : ''}
                    </td>
                    <td style="font-weight: bold; text-align: left; vertical-align: top;">
                        ${product.length || 0}×${product.width || 0}
                        <div style="font-size: 10px; color: #666;">${productInColis.poids_total ? productInColis.poids_total.toFixed(1) : '0.0'}kg</div>
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
                                                data-colis-id="${c.id}">×${c.multiple_colis}</button>` : ''}
                    </td>
                `;

                // Event listeners pour les boutons
                setupProductRowButtons(productRow, c, product, productInColis);
                setupDropZone(productRow, c.id);
                tbody.appendChild(productRow);
            });
        }
    });
}

function setupProductRowButtons(productRow, coli, product, productInColis) {
    const editBtn = productRow.querySelector('.btn-edit');
    const deleteBtn = productRow.querySelector('.btn-delete');
    const duplicateBtn = productRow.querySelector('.btn-duplicate');

    if (editBtn) {
        editBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const newQuantity = await showPrompt(
                `Nouvelle quantité pour ${product.ref} :`,
                productInColis.quantite.toString()
            );
            if (newQuantity !== null && !isNaN(newQuantity) && parseInt(newQuantity) > 0) {
                // TODO: Call API to update quantity
                debugLog(`Update quantity: ${newQuantity}`);
            }
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const confirmed = await showConfirm(
                `Supprimer ${product.ref} du colis ${coli.numero_colis} ?`
            );
            if (confirmed) {
                // TODO: Call API to remove product
                debugLog(`Remove product: ${product.id} from colis ${coli.id}`);
            }
        });
    }

    if (duplicateBtn) {
        duplicateBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const currentMultiple = coli.multiple_colis || 1;
            const message = `Combien de fois créer ce colis identique ?\n\nActuellement: ${currentMultiple} colis`;
            const newMultiple = await showPrompt(message, currentMultiple.toString());
            
            if (newMultiple !== null && !isNaN(newMultiple) && parseInt(newMultiple) > 0) {
                // TODO: Call API to update multiple
                debugLog(`Update multiple: ${newMultiple}`);
            }
        });
    }
}

function setupDropZone(element, colisId) {
    element.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.background = '#e8f5e8';
    });

    element.addEventListener('dragleave', function(e) {
        this.style.background = '';
    });

    element.addEventListener('drop', async function(e) {
        e.preventDefault();
        this.style.background = '';
        if (draggedProduct) {
            await addProductToColis(colisId, draggedProduct.id, 1);
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

    const weightPercentage = (selectedColis.poids_total / selectedColis.poids_max) * 100;
    let weightStatus = 'ok';
    if (weightPercentage > 90) weightStatus = 'danger';
    else if (weightPercentage > 70) weightStatus = 'warning';

    const multipleSection = selectedColis.multiple_colis > 1 ? 
        `<div class="duplicate-controls">
            <span>📦 Ce colis sera créé</span>
            <input type="number" value="${selectedColis.multiple_colis}" min="1" max="100" 
                   class="duplicate-input" id="multipleInput">
            <span>fois identique(s)</span>
            <span style="margin-left: 10px; font-weight: bold;">
                Total: ${(selectedColis.poids_total * selectedColis.multiple_colis).toFixed(1)} kg
            </span>
        </div>` : '';

    container.innerHTML = `
        <div class="colis-detail-header">
            <h3 class="colis-detail-title">📦 Colis ${selectedColis.numero_colis}</h3>
            <button class="btn-delete-colis" id="deleteColisBtn">🗑️ Supprimer</button>
        </div>

        ${multipleSection}

        <div class="constraints-section">
            <div class="constraint-item">
                <div class="constraint-label">Poids:</div>
                <div class="constraint-values">
                    ${selectedColis.poids_total ? selectedColis.poids_total.toFixed(1) : '0.0'} / ${selectedColis.poids_max} kg
                </div>
                <div class="constraint-bar">
                    <div class="constraint-progress ${weightStatus}" style="width: ${Math.min(weightPercentage, 100)}%"></div>
                </div>
            </div>
        </div>

        <div class="colis-content" id="colisContent">
            ${selectedColis.products ? selectedColis.products.map((p, index) => {
                const product = products.find(prod => prod.id == p.product_id);
                if (!product) return '';
                return `
                    <div class="colis-line" draggable="true" data-line-index="${index}">
                        <span class="drag-handle">⋮⋮</span>
                        <span class="line-product">${product.ref} - ${product.label}</span>
                        <input type="number" class="line-quantity" value="${p.quantite}" min="1" 
                               data-product-id="${p.product_id}">
                        <span class="line-weight">${p.poids_total ? p.poids_total.toFixed(1) : '0.0'} kg</span>
                        <button class="btn-remove-line" data-product-id="${p.product_id}">✕</button>
                    </div>
                `;
            }).join('') : ''}
            <div class="drop-hint">Glissez un produit ici pour l'ajouter</div>
        </div>
    `;

    // Event listeners pour les boutons et inputs
    setupColisDetailEvents();
}

function setupColisDetailEvents() {
    // Bouton supprimer colis
    const deleteBtn = document.getElementById('deleteColisBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            await deleteColis(selectedColis.id);
        });
    }

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
            }
        });
    }
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

// Initialisation - Identique à la maquette
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM chargé, initialisation...');
    setupEventListeners();
    loadData(); // Charger les vraies données
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
