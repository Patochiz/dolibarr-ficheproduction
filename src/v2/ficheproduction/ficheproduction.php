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
 * \brief       Interface drag & drop de colisage - Version corrigée avec token CSRF
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

        .colis-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #FF9800;
            cursor: pointer;
            transition: all 0.2s;
        }

        .colis-item:hover {
            background: #fff3e0;
            transform: translateX(5px);
        }

        .colis-header {
            font-weight: bold;
            color: #FF9800;
            margin-bottom: 5px;
        }

        .colis-info {
            color: #666;
            font-size: 14px;
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
            <div class="empty-state">Aucun colis créé. Cliquez sur "Nouveau Colis" pour commencer.</div>
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
            debugLog(`Full response: ${text}`);
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
        if (result && result.rawResponse) {
            debugLog('Réponse brute: ' + result.rawResponse.substring(0, 500));
        }
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
        const percentage = (product.used / product.total) * 100;

        const productElement = document.createElement('div');
        productElement.className = 'product-item';
        productElement.draggable = true;
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
        `;

        // Événements drag & drop
        productElement.addEventListener('dragstart', function(e) {
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'copy';
            debugLog(`Drag start: ${product.ref}`);
        });

        productElement.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
        });

        container.appendChild(productElement);
    });
}

async function addNewColis() {
    debugLog('Ajout nouveau colis');
    const result = await apiCall('ficheproduction_add_colis');
    
    if (result && result.success) {
        const newColis = {
            id: result.colis_id,
            numero: colis.length + 1,
            products: []
        };
        colis.push(newColis);
        debugLog(`Colis ${newColis.numero} créé`);
        showMessage(`Colis ${newColis.numero} créé avec succès`);
        renderColisOverview();
    } else {
        debugLog('Erreur création colis: ' + JSON.stringify(result));
        showMessage('Erreur lors de la création du colis');
    }
}

function renderColisOverview() {
    const container = document.getElementById('colisOverview');
    container.innerHTML = '';

    if (colis.length === 0) {
        container.innerHTML = '<div class="empty-state">Aucun colis créé. Cliquez sur "Nouveau Colis" pour commencer.</div>';
        return;
    }

    colis.forEach(c => {
        const colisElement = document.createElement('div');
        colisElement.className = 'colis-item';
        colisElement.innerHTML = `
            <div class="colis-header">📦 Colis ${c.numero}</div>
            <div class="colis-info">${c.products.length} produit(s) • 0.0 kg</div>
        `;
        container.appendChild(colisElement);
    });
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
