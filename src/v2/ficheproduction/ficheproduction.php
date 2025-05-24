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
                $data = array('products' => array(), 'colis' => array());
                
                // Get products from order lines
                if ($object->id > 0) {
                    if (empty($object->lines)) {
                        $object->fetch_lines();
                    }
                    
                    foreach ($object->lines as $line) {
                        if ($line->fk_product > 0) {
                            $product = new Product($db);
                            if ($product->fetch($line->fk_product) > 0 && $product->type == 0) {
                                
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
                                
                                $data['products'][] = array(
                                    'id' => $product->id,
                                    'ref' => $product->ref,
                                    'label' => $product->label,
                                    'weight' => (!empty($product->weight) ? $product->weight : 1.0),
                                    'length' => $length,
                                    'width' => $width,
                                    'color' => $color,
                                    'total' => $line->qty,
                                    'used' => 0
                                );
                            }
                        }
                    }
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
            z-index: 1000;
        }

        .product-item.exhausted {
            opacity: 0.6;
            background: #ffebee;
            border-color: #ef5350;
            cursor: not-allowed;
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
            background: #e8f5e8 !important;
            border-left: 4px solid #4CAF50 !important;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { background: #e8f5e8; }
            50% { background: #c8e6c9; }
            100% { background: #e8f5e8; }
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

        .empty-state {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 40px;
        }

        .colis-detail {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            position: relative;
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

        .colis-content {
            border: 2px dashed #ddd;
            border-radius: 8px;
            min-height: 200px;
            padding: 15px;
            position: relative;
            transition: all 0.3s ease;
        }

        .colis-content.drop-zone-active {
            border-color: #4CAF50;
            background: #e8f5e8;
            transform: scale(1.02);
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
            transition: all 0.2s;
        }

        .colis-line:hover {
            border-color: #2196F3;
            box-shadow: 0 2px 4px rgba(33,150,243,0.2);
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
            transition: opacity 0.3s;
        }

        .colis-content.drop-zone-active .drop-hint {
            opacity: 0;
        }

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

        .modal-btn.secondary {
            background: #e0e0e0;
            color: #333;
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

        /* Animations pour le drag */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
</style>

<div class="header">
    <h1>🚀 Gestionnaire de Colisage v2.0</h1>
    <div class="subtitle">Interface drag & drop pour colis mixtes - Commande <?php echo $object->ref; ?> (<?php echo count($object->lines ?? []); ?> produits commandés)</div>
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
                </select>
                <select id="sortSelect" class="sort-select">
                    <option value="ref">Trier par Référence</option>
                    <option value="name">Trier par Nom</option>
                    <option value="length">Trier par Longueur</option>
                    <option value="width">Trier par Largeur</option>
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

<!-- Modal simple -->
<div class="modal-overlay" id="simpleModal">
    <div class="modal-content">
        <div class="modal-header">Information</div>
        <div class="modal-message" id="modalMessage"></div>
        <div class="modal-buttons">
            <button class="modal-btn primary" id="modalOk">OK</button>
        </div>
    </div>
</div>

<script type="text/javascript">
// Variables globales
let products = [];
let colis = [];
let selectedColis = null;
let draggedProduct = null;

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

// Modal simple
function showMessage(message) {
    const modal = document.getElementById('simpleModal');
    const messageEl = document.getElementById('modalMessage');
    const okBtn = document.getElementById('modalOk');
    
    messageEl.textContent = message;
    modal.classList.add('show');
    
    okBtn.onclick = function() {
        modal.classList.remove('show');
    };
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
        debugLog(`Response (${text.length} chars): ${text.substring(0, 200)}...`);
        
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
    
    if (result && result.products) {
        products = result.products;
        debugLog(`Chargé ${products.length} produits`);
        renderInventory();
    } else {
        debugLog('Erreur lors du chargement des données: ' + JSON.stringify(result));
        showMessage('Erreur lors du chargement des données. Consultez la console de debug.');
    }
}

function renderInventory() {
    const container = document.getElementById('inventoryList');
    container.innerHTML = '';

    if (products.length === 0) {
        container.innerHTML = '<div class="empty-state">Aucun produit trouvé dans cette commande</div>';
        return;
    }

    debugLog(`Rendu de ${products.length} produits`);

    products.forEach(product => {
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
            <div class="product-name">${product.label}</div>
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
            <div class="status-indicator ${status === 'exhausted' ? 'error' : status === 'partial' ? 'warning' : ''}"></div>
        `;

        // Événements drag & drop
        productElement.addEventListener('dragstart', function(e) {
            if (status === 'exhausted') {
                e.preventDefault();
                return;
            }
            
            draggedProduct = product;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'copy';
            debugLog(`🚀 Drag start: ${product.ref}`);
            
            // Activer toutes les zones de drop
            activateDropZones();
        });

        productElement.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            draggedProduct = null;
            debugLog(`🛑 Drag end: ${product.ref}`);
            
            // Désactiver toutes les zones de drop
            deactivateDropZones();
        });

        container.appendChild(productElement);
    });
}

function activateDropZones() {
    debugLog('🎯 Activation des zones de drop');
    
    // Activer le tableau des colis (lignes vides et lignes produits)
    const colisRows = document.querySelectorAll('#colisTableBody tr');
    colisRows.forEach(row => {
        if (row.dataset.colisId || row.classList.contains('colis-group-item')) {
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

async function addProductToColis(colisId, productId, quantity = 1) {
    debugLog(`➕ Ajout produit ${productId} au colis ${colisId} (qté: ${quantity})`);
    
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
            // Vérifier les quantités disponibles
            const available = product.total - product.used;
            if (available < quantity) {
                showMessage(`Quantité insuffisante ! Disponible: ${available}, Demandé: ${quantity}`);
                return;
            }
            
            // Ajouter ou mettre à jour le produit dans le colis
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
            
            // Recalculer le poids total du colis
            targetColis.poids_total = targetColis.products.reduce((sum, p) => sum + p.poids_total, 0);
            
            // Mettre à jour les quantités utilisées
            product.used += quantity;
            
            // Re-render les interfaces
            renderInventory();
            renderColisOverview();
            if (selectedColis && selectedColis.id == colisId) {
                renderColisDetail();
            }
            
            debugLog(`✅ Produit ${product.ref} ajouté au colis ${targetColis.numero}`);
            showMessage(`${product.ref} ajouté au colis ${targetColis.numero}`);
        }
    } else {
        debugLog('❌ Erreur ajout produit: ' + JSON.stringify(result));
        showMessage('Erreur lors de l\'ajout du produit');
    }
}

async function addNewColis() {
    debugLog('Ajout nouveau colis');
    const result = await apiCall('ficheproduction_add_colis');
    
    if (result && result.success) {
        const newColis = {
            id: result.colis_id,
            numero: colis.length + 1,
            products: [],
            poids_total: 0,
            poids_max: 25,
            multiple_colis: 1,
            status: 'ok'
        };
        colis.push(newColis);
        debugLog(`Colis ${newColis.numero} créé`);
        renderColisOverview();
        
        // Sélectionner automatiquement le nouveau colis
        selectColis(newColis);
    } else {
        debugLog('Erreur création colis: ' + JSON.stringify(result));
        showMessage('Erreur lors de la création du colis');
    }
}

function selectColis(coliData) {
    debugLog(`🎯 Sélection colis ${coliData.numero}`);
    selectedColis = coliData;
    renderColisOverview();
    renderColisDetail();
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

        // Ligne d'en-tête pour le colis
        const headerRow = document.createElement('tr');
        headerRow.className = 'colis-group-header';
        headerRow.dataset.colisId = c.id;
        if (selectedColis && selectedColis.id === c.id) {
            headerRow.classList.add('selected');
        }

        headerRow.innerHTML = `
            <td colspan="6">
                <strong>📦 Colis ${c.numero}</strong>
                <span style="margin-left: 15px; color: #666;">
                    ${c.products.length} produit${c.products.length > 1 ? 's' : ''} • 
                    ${c.poids_total ? c.poids_total.toFixed(1) : '0.0'} kg • 
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
                            <span class="product-color-badge">${product.color}</span>
                        </div>
                        <div style="font-size: 11px; color: #666;">${product.ref}</div>
                    </td>
                    <td style="font-weight: bold; text-align: right;">
                        ${productInColis.quantite}
                    </td>
                    <td style="font-weight: bold; text-align: left;">
                        ${product.length}×${product.width}
                        <div style="font-size: 10px; color: #666;">${productInColis.poids_total ? productInColis.poids_total.toFixed(1) : '0.0'}kg</div>
                    </td>
                    <td class="${statusClass}" style="text-align: center;">
                        ${statusIcon}
                    </td>
                    <td>
                        <button class="btn-small btn-edit" title="Modifier">📝</button>
                        <button class="btn-small btn-delete" title="Supprimer">🗑️</button>
                    </td>
                `;

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
    });

    element.addEventListener('drop', async function(e) {
        e.preventDefault();
        if (draggedProduct) {
            debugLog(`🎯 Drop sur colis ${colisId}`);
            await addProductToColis(colisId, draggedProduct.id, 1);
        }
    });
}

function renderColisDetail() {
    const container = document.getElementById('colisDetail');
    
    if (!selectedColis) {
        container.innerHTML = '<div class="empty-state">Sélectionnez un colis pour voir les détails</div>';
        return;
    }

    const weightPercentage = (selectedColis.poids_total / selectedColis.poids_max) * 100;
    let weightStatus = '';
    if (weightPercentage > 90) weightStatus = 'danger';
    else if (weightPercentage > 70) weightStatus = 'warning';

    container.innerHTML = `
        <div class="colis-detail-header">
            <h3 class="colis-detail-title">📦 Colis ${selectedColis.numero}</h3>
            <button class="btn-delete-colis" id="deleteColisBtn">🗑️ Supprimer</button>
        </div>

        <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 80px; font-weight: bold;">Poids:</div>
                <div style="flex: 1; margin-right: 10px;">
                    ${selectedColis.poids_total ? selectedColis.poids_total.toFixed(1) : '0.0'} / ${selectedColis.poids_max} kg
                </div>
                <div style="width: 100px; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">
                    <div style="height: 100%; background: ${weightStatus === 'danger' ? '#f44336' : weightStatus === 'warning' ? '#FF9800' : '#4CAF50'}; width: ${Math.min(weightPercentage, 100)}%; transition: width 0.3s;"></div>
                </div>
            </div>
        </div>

        <div class="colis-content" id="colisContent">
            ${selectedColis.products.map((p, index) => {
                const product = products.find(prod => prod.id == p.product_id);
                if (!product) return '';
                return `
                    <div class="colis-line">
                        <span class="line-product">${product.ref} - ${product.label}</span>
                        <input type="number" class="line-quantity" value="${p.quantite}" min="1">
                        <span class="line-weight">${p.poids_total ? p.poids_total.toFixed(1) : '0.0'} kg</span>
                        <button class="btn-remove-line">✕</button>
                    </div>
                `;
            }).join('')}
            <div class="drop-hint">Glissez un produit ici pour l'ajouter</div>
        </div>
    `;

    // Setup drop zone pour la zone de détail
    const colisContent = document.getElementById('colisContent');
    if (colisContent) {
        setupDropZone(colisContent, selectedColis.id);
    }
}

function setupEventListeners() {
    debugLog('Configuration des event listeners');
    
    // Bouton Nouveau Colis
    const addNewColisBtn = document.getElementById('addNewColisBtn');
    if (addNewColisBtn) {
        addNewColisBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addNewColis();
        });
    }

    // Debug console toggle
    const header = document.querySelector('.header h1');
    if (header) {
        header.addEventListener('dblclick', function() {
            const debugConsole = document.getElementById('debugConsole');
            if (debugConsole) {
                const isVisible = debugConsole.style.display !== 'none';
                debugConsole.style.display = isVisible ? 'none' : 'block';
                debugLog(isVisible ? 'Console masquée' : 'Console affichée');
            }
        });
    }
    
    debugLog('Event listeners configurés');
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    debugLog('DOM chargé, initialisation...');
    debugLog(`Configuration: ORDER_ID=${ORDER_ID}, TOKEN présent=${TOKEN ? 'oui' : 'non'}`);
    setupEventListeners();
    loadData();
    debugLog('Initialisation terminée - Double-cliquez sur le titre pour la console');
});
</script>

<?php
print '</div>'; // End fichecenter
print dol_get_fiche_end();

llxFooter();
$db->close();
?>
