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
.colisage-header {
    background: #2c5f7a;
    color: white;
    padding: 15px 20px;
    border-radius: 8px 8px 0 0;
    margin: 20px 0 0 0;
}
.colisage-title {
    font-size: 24px;
    margin-bottom: 5px;
    color: white;
    margin: 0;
}
.colisage-subtitle {
    opacity: 0.8;
    font-size: 14px;
    margin: 5px 0 0 0;
}
.colisage-container {
    display: flex;
    min-height: 700px;
    background: white;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    border: 1px solid #e0e0e0;
}
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
    font-size: 14px;
}
.sort-controls {
    display: flex;
    gap: 10px;
}
.sort-select {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}
.inventory-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    max-height: 600px;
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
    font-size: 14px;
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
    font-size: 14px;
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
    font-size: 13px;
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
.colis-detail {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}
.empty-state {
    text-align: center;
    color: #999;
    font-style: italic;
    padding: 40px;
    font-size: 16px;
}
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
.modal-btn.secondary {
    background: #e0e0e0;
    color: #333;
}
.modal-btn.danger {
    background: #f44336;
    color: white;
}
</style>

<div class="colisage-header">
    <h2 class="colisage-title">🚀 Gestionnaire de Colisage v2.0</h2>
    <div class="colisage-subtitle">
        Interface drag & drop pour colis mixtes - Commande <strong><?php echo $object->ref; ?></strong> - 
        Client: <strong><?php echo (is_object($object->thirdparty) ? $object->thirdparty->name : 'Non défini'); ?></strong>
        (<?php echo count($object->lines); ?> produits commandés)
    </div>
</div>

<div class="colisage-container">
    <!-- Zone Inventaire -->
    <div class="inventory-zone">
        <div class="inventory-header">📦 Inventaire Produits</div>
        
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
                </select>
            </div>
        </div>
        
        <div class="inventory-list" id="inventoryList">
            <!-- Products will be loaded via JavaScript -->
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
                    <!-- Colis will be loaded via JavaScript -->
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

<!-- Modales -->
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
            // NOTE: Adjust these field names based on your actual extrafield configuration
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
                'line_id' => $line->rowid // Add line ID for reference
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
// Products from actual order
var products = <?php echo $products_json; ?>;

// Default products if none in order
if (products.length === 0) {
    products = [
        { id: 1, ref: "PROD-A1", name: "Profilé Aluminium Standard", color: "Naturel", used: 15, total: 50, weight: 2.5, length: 6000, width: 40 },
        { id: 2, ref: "PROD-A2", name: "Profilé Aluminium Renforcé", color: "Naturel", used: 8, total: 25, weight: 3.2, length: 6000, width: 60 },
        { id: 3, ref: "PROD-B1", name: "Panneau Composite", color: "Blanc", used: 12, total: 30, weight: 1.8, length: 3000, width: 1500 }
    ];
}

var colis = [
    { 
        id: 1, 
        number: 1, 
        products: [],
        totalWeight: 0,
        maxWeight: 25,
        status: "ok",
        multiple: 1
    }
];

var selectedColis = null;
var draggedProduct = null;
var currentSort = "ref";
var currentFilter = "all";

// Modal functions
function showConfirm(message) {
    return new Promise(function(resolve) {
        var modal = document.getElementById("confirmModal");
        var messageEl = document.getElementById("confirmMessage");
        var okBtn = document.getElementById("confirmOk");
        var cancelBtn = document.getElementById("confirmCancel");

        messageEl.textContent = message;
        modal.classList.add("show");

        var cleanup = function() {
            modal.classList.remove("show");
            okBtn.removeEventListener("click", handleOk);
            cancelBtn.removeEventListener("click", handleCancel);
        };

        var handleOk = function() {
            cleanup();
            resolve(true);
        };

        var handleCancel = function() {
            cleanup();
            resolve(false);
        };

        okBtn.addEventListener("click", handleOk);
        cancelBtn.addEventListener("click", handleCancel);
    });
}

// Render functions
function renderInventory() {
    var container = document.getElementById("inventoryList");
    container.innerHTML = "";

    var sortedProducts = products.slice().sort(function(a, b) {
        switch(currentSort) {
            case "ref": return a.ref.localeCompare(b.ref);
            case "name": return a.name.localeCompare(b.name);
            case "length": return b.length - a.length;
            case "width": return b.width - a.width;
            default: return 0;
        }
    });

    var filteredProducts = sortedProducts.filter(function(product) {
        var available = product.total - product.used;
        switch(currentFilter) {
            case "available": return available > 0 && product.used === 0;
            case "partial": return available > 0 && product.used > 0;
            case "exhausted": return available === 0;
            default: return true;
        }
    });

    filteredProducts.forEach(function(product) {
        var available = product.total - product.used;
        var percentage = (product.used / product.total) * 100;

        var productElement = document.createElement("div");
        productElement.className = "product-item";
        productElement.draggable = true;
        productElement.dataset.productId = product.id;

        productElement.innerHTML = 
            '<div class="product-header">' +
                '<span class="product-ref">' + product.ref + '</span>' +
                '<span class="product-color">' + product.color + '</span>' +
            '</div>' +
            '<div class="product-name">' + product.name + '</div>' +
            '<div style="font-size: 11px; color: #666; margin: 4px 0;">' +
                'L: ' + product.length + 'mm × l: ' + product.width + 'mm' +
            '</div>' +
            '<div class="quantity-info">' +
                '<span class="quantity-used">' + product.used + '</span>' +
                '<span>/</span>' +
                '<span class="quantity-total">' + product.total + '</span>' +
                '<div class="quantity-bar">' +
                    '<div class="quantity-progress" style="width: ' + percentage + '%"></div>' +
                '</div>' +
            '</div>';

        productElement.addEventListener("dragstart", function(e) {
            draggedProduct = product;
            this.classList.add("dragging");
            e.dataTransfer.effectAllowed = "copy";
        });

        productElement.addEventListener("dragend", function(e) {
            this.classList.remove("dragging");
            draggedProduct = null;
        });

        container.appendChild(productElement);
    });
}

function renderColisOverview() {
    var tbody = document.getElementById("colisTableBody");
    tbody.innerHTML = "";

    colis.forEach(function(c) {
        var weightPercentage = (c.totalWeight / c.maxWeight) * 100;
        var statusIcon = "✅";
        var statusClass = "";
        if (weightPercentage > 90) {
            statusIcon = "⚠️";
            statusClass = "warning";
        } else if (weightPercentage > 100) {
            statusIcon = "❌";
            statusClass = "error";
        }

        var multipleDisplay = c.multiple > 1 ? " (×" + c.multiple + ")" : "";

        // Header row for the colis
        var headerRow = document.createElement("tr");
        headerRow.style.cssText = "background: #fff3e0; font-weight: bold; border-left: 4px solid #FF9800;";
        headerRow.dataset.colisId = c.id;
        if (selectedColis && selectedColis.id === c.id) {
            headerRow.style.background = "#fff3e0";
        }

        headerRow.innerHTML = 
            '<td colspan="6">' +
                '<strong>📦 Colis ' + c.number + multipleDisplay + '</strong>' +
                '<span style="margin-left: 15px; color: #666;">' +
                    c.products.length + ' produit' + (c.products.length > 1 ? 's' : '') + ' • ' +
                    c.totalWeight.toFixed(1) + ' kg • ' +
                    statusIcon +
                '</span>' +
            '</td>';

        headerRow.addEventListener("click", function() {
            selectColis(c);
        });

        // Drop zone functionality on header rows
        headerRow.addEventListener("dragover", function(e) {
            e.preventDefault();
            this.style.backgroundColor = "#e8f5e8";
        });

        headerRow.addEventListener("dragleave", function(e) {
            this.style.backgroundColor = "#fff3e0";
        });

        headerRow.addEventListener("drop", function(e) {
            e.preventDefault();
            this.style.backgroundColor = "#fff3e0";
            
            if (draggedProduct) {
                addProductToColis(c.id, draggedProduct.id, 1);
                selectColis(c);
            }
        });

        tbody.appendChild(headerRow);

        // Product rows for each product in the colis
        if (c.products.length === 0) {
            var emptyRow = document.createElement("tr");
            emptyRow.style.cssText = "background: #fafafa; border-left: 2px solid #FFE0B2;";
            emptyRow.innerHTML = 
                '<td></td>' +
                '<td colspan="5" style="font-style: italic; color: #999; padding: 10px;">Colis vide - Glissez des produits ici</td>';
            
            // Drop zone for empty colis
            emptyRow.addEventListener("dragover", function(e) {
                e.preventDefault();
                this.style.background = "#e8f5e8";
            });

            emptyRow.addEventListener("dragleave", function(e) {
                this.style.background = "#fafafa";
            });

            emptyRow.addEventListener("drop", function(e) {
                e.preventDefault();
                this.style.background = "#fafafa";
                if (draggedProduct) {
                    addProductToColis(c.id, draggedProduct.id, 1);
                    selectColis(c);
                }
            });

            tbody.appendChild(emptyRow);
        } else {
            c.products.forEach(function(productInColis, index) {
                var product = products.find(function(p) { return p.id === productInColis.productId; });
                if (!product) return;

                var productRow = document.createElement("tr");
                productRow.style.cssText = "background: #fafafa; border-left: 2px solid #FFE0B2;";
                productRow.dataset.colisId = c.id;
                productRow.dataset.productId = product.id;

                productRow.innerHTML = 
                    '<td></td>' +
                    '<td>' +
                        '<div style="display: flex; align-items: center; gap: 8px;">' +
                            '<span>' + product.name + '</span>' +
                            '<span style="background: #e3f2fd; color: #1976D2; padding: 2px 6px; border-radius: 8px; font-size: 10px; font-weight: bold;">' + product.color + '</span>' +
                        '</div>' +
                        '<div style="font-size: 11px; color: #666;">' + product.ref + '</div>' +
                    '</td>' +
                    '<td style="font-weight: bold; text-align: right; vertical-align: top;">' +
                        productInColis.quantity +
                        (c.multiple > 1 ? '<div style="font-size: 10px; color: #666;">×' + c.multiple + ' = ' + (productInColis.quantity * c.multiple) + '</div>' : '') +
                    '</td>' +
                    '<td style="font-weight: bold; text-align: left; vertical-align: top;">' +
                        product.length + '×' + product.width +
                        '<div style="font-size: 10px; color: #666;">' + productInColis.weight.toFixed(1) + 'kg</div>' +
                    '</td>' +
                    '<td class="' + statusClass + '" style="text-align: center;">' +
                        statusIcon +
                    '</td>' +
                    '<td>' +
                        '<button onclick="editProductQuantity(' + c.id + ', ' + product.id + ')" title="Modifier quantité" ' +
                                'style="background: #2196F3; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; margin: 1px; font-size: 10px;">📝</button>' +
                        '<button onclick="removeProductFromColis(' + c.id + ', ' + product.id + ')" title="Supprimer" ' +
                                'style="background: #f44336; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; margin: 1px; font-size: 10px;">🗑️</button>' +
                        (index === 0 ? '<button onclick="duplicateColisDialog(' + c.id + ')" title="Dupliquer colis" ' +
                                              'style="background: #FF9800; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; margin: 1px; font-size: 10px;">×' + c.multiple + '</button>' : '') +
                    '</td>';

                // Drop zone functionality on product rows
                productRow.addEventListener("dragover", function(e) {
                    e.preventDefault();
                    this.style.background = "#e8f5e8";
                });

                productRow.addEventListener("dragleave", function(e) {
                    this.style.background = "#fafafa";
                });

                productRow.addEventListener("drop", function(e) {
                    e.preventDefault();
                    this.style.background = "#fafafa";
                    if (draggedProduct) {
                        addProductToColis(c.id, draggedProduct.id, 1);
                        selectColis(c);
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
    var container = document.getElementById("colisDetail");
    
    if (!selectedColis) {
        container.innerHTML = '<div class="empty-state">Sélectionnez un colis pour voir les détails<br>ou créez un nouveau colis pour commencer</div>';
        return;
    }

    var weightPercentage = (selectedColis.totalWeight / selectedColis.maxWeight) * 100;
    var weightStatus = "ok";
    if (weightPercentage > 90) weightStatus = "danger";
    else if (weightPercentage > 70) weightStatus = "warning";

    var multipleSection = selectedColis.multiple > 1 ? 
        '<div style="display: flex; align-items: center; gap: 10px; margin: 10px 0; padding: 10px; background: #e3f2fd; border-radius: 6px;">' +
            '<span>📦 Ce colis sera créé</span>' +
            '<input type="number" value="' + selectedColis.multiple + '" min="1" max="100" ' +
                   'style="width: 60px; padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center;" ' +
                   'onchange="updateColisMultiple(' + selectedColis.id + ', this.value)">' +
            '<span>fois identique(s)</span>' +
            '<span style="margin-left: 10px; font-weight: bold;">Total: ' + (selectedColis.totalWeight * selectedColis.multiple).toFixed(1) + ' kg</span>' +
        '</div>' : '';

    var productsHtml = "";
    selectedColis.products.forEach(function(p) {
        var product = products.find(function(prod) { return prod.id === p.productId; });
        if (!product) return;
        
        productsHtml += 
            '<div style="background: white; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; cursor: grab; transition: all 0.2s;" ' +
                 'onmouseover="this.style.borderColor=\'#2196F3\'; this.style.boxShadow=\'0 2px 4px rgba(33,150,243,0.2)\';" ' +
                 'onmouseout="this.style.borderColor=\'#e0e0e0\'; this.style.boxShadow=\'none\';">' +
                '<span style="color: #999; cursor: grab; font-size: 16px;">⋮⋮</span>' +
                '<span style="flex: 1; font-weight: bold;">' + product.ref + ' - ' + product.name + '</span>' +
                '<input type="number" value="' + p.quantity + '" min="1" ' +
                       'style="width: 60px; padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center;" ' +
                       'onchange="updateProductQuantity(' + selectedColis.id + ', ' + p.productId + ', this.value)">' +
                '<span style="color: #666; font-size: 12px;">' + p.weight.toFixed(1) + ' kg</span>' +
                '<button onclick="removeProductFromColis(' + selectedColis.id + ', ' + p.productId + ')" ' +
                        'style="background: #f44336; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 12px;">✕</button>' +
            '</div>';
    });

    container.innerHTML = 
        '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0;">' +
            '<h3 style="color: #333; font-size: 18px; margin: 0;">📦 Colis ' + selectedColis.number + '</h3>' +
            '<button onclick="deleteColis(' + selectedColis.id + ')" ' +
                    'style="background: #f44336; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 14px;"' +
                    'onmouseover="this.style.background=\'#d32f2f\';" onmouseout="this.style.background=\'#f44336\';">🗑️ Supprimer</button>' +
        '</div>' +

        multipleSection +

        '<div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">' +
            '<div style="display: flex; align-items: center; margin-bottom: 10px;">' +
                '<div style="width: 80px; font-weight: bold; font-size: 14px;">Poids:</div>' +
                '<div style="flex: 1; margin-right: 10px; font-size: 14px;">' +
                    selectedColis.totalWeight.toFixed(1) + ' / ' + selectedColis.maxWeight + ' kg' +
                '</div>' +
                '<div style="width: 100px; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden;">' +
                    '<div style="height: 100%; background: ' + 
                        (weightStatus === 'danger' ? '#f44336' : weightStatus === 'warning' ? '#FF9800' : '#4CAF50') + 
                        '; width: ' + Math.min(weightPercentage, 100) + '%; transition: width 0.3s;"></div>' +
                '</div>' +
            '</div>' +
        '</div>' +

        '<div id="colisContent" style="border: 2px dashed #ddd; border-radius: 8px; min-height: 200px; padding: 15px; position: relative;">' +
            productsHtml +
            (selectedColis.products.length === 0 ? 
                '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #999; font-style: italic; pointer-events: none;">Glissez un produit ici pour l\'ajouter</div>' : 
                ''
            ) +
        '</div>';

    // Setup drop zone for the content
    var colisContent = document.getElementById("colisContent");
    if (colisContent) {
        colisContent.addEventListener("dragover", function(e) {
            e.preventDefault();
            this.style.borderColor = "#4CAF50";
            this.style.backgroundColor = "#e8f5e8";
        });

        colisContent.addEventListener("dragleave", function(e) {
            this.style.borderColor = "#ddd";
            this.style.backgroundColor = "transparent";
        });

        colisContent.addEventListener("drop", function(e) {
            e.preventDefault();
            this.style.borderColor = "#ddd";
            this.style.backgroundColor = "transparent";
            
            if (draggedProduct && selectedColis) {
                addProductToColis(selectedColis.id, draggedProduct.id, 1);
            }
        });
    }
}

function addNewColis() {
    var newId = Math.max.apply(Math, colis.map(function(c) { return c.id; })) + 1;
    var newNumber = Math.max.apply(Math, colis.map(function(c) { return c.number; })) + 1;
    
    var newColis = {
        id: newId,
        number: newNumber,
        products: [],
        totalWeight: 0,
        maxWeight: 25,
        status: "ok",
        multiple: 1
    };

    colis.push(newColis);
    renderColisOverview();
    selectColis(newColis);
}

function deleteColis(colisId) {
    showConfirm("Êtes-vous sûr de vouloir supprimer ce colis ?").then(function(confirmed) {
        if (!confirmed) return;

        var colisIndex = colis.findIndex(function(c) { return c.id === colisId; });
        if (colisIndex > -1) {
            // Restore products to inventory before deleting
            var coliData = colis[colisIndex];
            coliData.products.forEach(function(p) {
                var product = products.find(function(prod) { return prod.id === p.productId; });
                if (product) {
                    product.used -= p.quantity;
                }
            });
            
            colis.splice(colisIndex, 1);
        }
        
        if (selectedColis && selectedColis.id === colisId) {
            selectedColis = null;
        }

        renderInventory();
        renderColisOverview();
        renderColisDetail();
    });
}

function addProductToColis(colisId, productId, quantity) {
    var coliData = colis.find(function(c) { return c.id === colisId; });
    var product = products.find(function(p) { return p.id === productId; });
    
    if (!coliData || !product) return;

    // Check availability
    var available = product.total - product.used;
    if (available < quantity) {
        alert("Quantité insuffisante ! Disponible: " + available + ", Demandé: " + quantity);
        return;
    }

    // Check if product already in colis
    var existingProduct = coliData.products.find(function(p) { return p.productId === productId; });
    
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

    // Recalculate total weight
    coliData.totalWeight = coliData.products.reduce(function(sum, p) { return sum + p.weight; }, 0);

    // Update used quantities
    product.used += quantity;

    // Re-render
    renderInventory();
    renderColisOverview();
    renderColisDetail();
}

function removeProductFromColis(colisId, productId) {
    var coliData = colis.find(function(c) { return c.id === colisId; });
    var productInColis = coliData ? coliData.products.find(function(p) { return p.productId === productId; }) : null;
    
    if (!coliData || !productInColis) return;

    // Restore to inventory
    var product = products.find(function(p) { return p.id === productId; });
    if (product) {
        product.used -= productInColis.quantity * coliData.multiple;
    }

    // Remove from colis
    var productIndex = coliData.products.findIndex(function(p) { return p.productId === productId; });
    if (productIndex > -1) {
        coliData.products.splice(productIndex, 1);
    }
    
    // Recalculate total weight
    coliData.totalWeight = coliData.products.reduce(function(sum, p) { return sum + p.weight; }, 0);

    // Re-render
    renderInventory();
    renderColisOverview();
    renderColisDetail();
}

function updateProductQuantity(colisId, productId, newQuantity) {
    var coliData = colis.find(function(c) { return c.id === colisId; });
    var productInColis = coliData ? coliData.products.find(function(p) { return p.productId === productId; }) : null;
    var product = products.find(function(p) { return p.id === productId; });
    
    if (!productInColis || !product || !coliData) return;

    var oldQuantity = productInColis.quantity;
    var quantityDiff = parseInt(newQuantity) - oldQuantity;

    // Check availability (consider multiples)
    var totalQuantityNeeded = quantityDiff * coliData.multiple;
    var available = product.total - product.used;
    
    if (totalQuantityNeeded > available) {
        alert("Quantité insuffisante ! Disponible: " + available + ", Besoin: " + totalQuantityNeeded);
        return;
    }

    // Update quantities
    productInColis.quantity = parseInt(newQuantity);
    productInColis.weight = productInColis.quantity * product.weight;
    product.used += totalQuantityNeeded;

    // Recalculate total weight
    coliData.totalWeight = coliData.products.reduce(function(sum, p) { return sum + p.weight; }, 0);

    // Re-render
    renderInventory();
    renderColisOverview();
    renderColisDetail();
}

function editProductQuantity(colisId, productId) {
    var coliData = colis.find(function(c) { return c.id === colisId; });
    var productInColis = coliData ? coliData.products.find(function(p) { return p.productId === productId; }) : null;
    var product = products.find(function(p) { return p.id === productId; });
    
    if (!productInColis || !product) return;

    var newQuantity = prompt("Nouvelle quantité pour " + product.ref + " :", productInColis.quantity);
    if (newQuantity !== null && !isNaN(newQuantity) && parseInt(newQuantity) > 0) {
        updateProductQuantity(colisId, productId, parseInt(newQuantity));
    }
}

function updateColisMultiple(colisId, multiple) {
    var coliData = colis.find(function(c) { return c.id === colisId; });
    if (!coliData) return;

    var oldMultiple = coliData.multiple;
    var newMultiple = parseInt(multiple);
    
    if (isNaN(newMultiple) || newMultiple < 1) {
        alert("Le nombre de colis doit être un entier positif");
        return;
    }

    // Calculate difference to adjust used quantities
    var multipleDiff = newMultiple - oldMultiple;
    
    // Update used quantities for each product
    for (var i = 0; i < coliData.products.length; i++) {
        var p = coliData.products[i];
        var product = products.find(function(prod) { return prod.id === p.productId; });
        if (product) {
            product.used += p.quantity * multipleDiff;
            
            // Check we don't exceed total available
            if (product.used > product.total) {
                alert("Attention: " + product.ref + " - Quantité dépassée! Utilisé: " + product.used + ", Total: " + product.total);
                // Revert to old value
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

function duplicateColisDialog(colisId) {
    var coliData = colis.find(function(c) { return c.id === colisId; });
    if (!coliData) return;

    var currentMultiple = coliData.multiple || 1;
    var message = "Combien de fois créer ce colis identique ?\n\nActuellement: " + currentMultiple + " colis";
    var newMultiple = prompt(message, currentMultiple.toString());
    
    if (newMultiple !== null && !isNaN(newMultiple) && parseInt(newMultiple) > 0) {
        updateColisMultiple(colisId, parseInt(newMultiple));
    }
}

// Event listeners
document.addEventListener("DOMContentLoaded", function() {
    renderInventory();
    renderColisOverview();

    document.getElementById("addNewColisBtn").addEventListener("click", addNewColis);

    document.getElementById("filterSelect").addEventListener("change", function(e) {
        currentFilter = e.target.value;
        renderInventory();
    });

    document.getElementById("sortSelect").addEventListener("change", function(e) {
        currentSort = e.target.value;
        renderInventory();
    });

    document.getElementById("searchBox").addEventListener("input", function(e) {
        var searchTerm = e.target.value.toLowerCase();
        var productItems = document.querySelectorAll(".product-item");
        
        productItems.forEach(function(item) {
            var text = item.textContent.toLowerCase();
            item.style.display = text.indexOf(searchTerm) !== -1 ? "block" : "none";
        });
    });
});
</script>

<?php
llxFooter();
$db->close();
?>