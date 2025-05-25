<?php
/**
 * \file        includes/ficheproduction-ajax.php
 * \ingroup     ficheproduction
 * \brief       Gestion des actions AJAX pour FicheProduction
 */

// Prevent direct access
if (!defined('DOL_VERSION')) {
    print "Error: This module requires Dolibarr framework.\n";
    exit;
}

// Load FicheProduction classes
require_once dol_buildpath('/ficheproduction/class/ficheproductionmanager.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductionsession.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolis.class.php');
require_once dol_buildpath('/ficheproduction/class/ficheproductioncolisline.class.php');

/**
 * Handle all AJAX actions for FicheProduction
 * 
 * @param string $action    The action to perform
 * @param int    $id        Order ID
 * @param DoliDB $db        Database connection
 * @param User   $user      Current user
 */
function handleFicheProductionAjax($action, $id, $db, $user) 
{
    header('Content-Type: application/json');
    
    try {
        $object = new Commande($db);
        if ($id > 0) {
            $object->fetch($id);
        }
        
        switch ($action) {
            case 'ficheproduction_get_data':
                handleGetData($object, $db);
                break;
                
            case 'ficheproduction_load_saved_data':
                handleLoadSavedData($object, $db);
                break;
                
            case 'ficheproduction_save_colis':
                handleSaveColis($object, $db, $user);
                break;
                
            case 'ficheproduction_add_colis':
                handleAddColis();
                break;
                
            case 'ficheproduction_add_product':
                handleAddProduct();
                break;
                
            default:
                echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Handle GET data action
 */
function handleGetData($object, $db) 
{
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
                    $quantity = getProductQuantity($line);
                    
                    // Get dimensions from line extrafields
                    $dimensions = getProductDimensions($line);
                    
                    // Only add products with quantity > 0
                    if ($quantity > 0) {
                        $productData = createProductData($product, $line, $productIndex++, $quantity, $dimensions, $lineIndex);
                        $data['products'][] = $productData;
                        
                        // Create product groups for the selector
                        addToProductGroups($productGroups, $product, $dimensions['color'], $productData['id']);
                    }
                }
            }
        }
        
        // Convert product groups to array
        $data['product_groups'] = array_values($productGroups);
    }
    
    echo json_encode($data);
}

/**
 * Handle load saved data action
 */
function handleLoadSavedData($object, $db) 
{
    $manager = new FicheProductionManager($db);
    $result = $manager->loadColisageData($object->id);
    echo json_encode($result);
}

/**
 * Handle save colis action
 */
function handleSaveColis($object, $db, $user) 
{
    // Get JSON data from POST
    $colisData = GETPOST('colis_data', 'alpha');
    if (empty($colisData)) {
        echo json_encode(['success' => false, 'error' => 'Aucune donnée de colis reçue']);
        return;
    }
    
    // Decode JSON data
    $decodedData = json_decode($colisData, true);
    if (!$decodedData || !is_array($decodedData)) {
        echo json_encode(['success' => false, 'error' => 'Données de colis invalides']);
        return;
    }
    
    // Use FicheProductionManager to save data
    $manager = new FicheProductionManager($db);
    $result = $manager->saveColisageData($object->id, $object->socid, $decodedData, $user);
    
    echo json_encode($result);
}

/**
 * Handle add colis action
 */
function handleAddColis() 
{
    echo json_encode(['success' => true, 'colis_id' => rand(1000, 9999)]);
}

/**
 * Handle add product action
 */
function handleAddProduct() 
{
    $colis_id = GETPOST('colis_id', 'int');
    $product_id = GETPOST('product_id', 'int');
    $quantite = GETPOST('quantite', 'int');
    
    echo json_encode(['success' => true, 'message' => "Produit $product_id ajouté au colis $colis_id (qté: $quantite)"]);
}

/**
 * Helper functions
 */
function getProductQuantity($line) 
{
    $quantity = 0;
    if (isset($line->array_options['options_nombre']) && !empty($line->array_options['options_nombre'])) {
        $quantity = intval($line->array_options['options_nombre']);
    } else {
        // Fallback to standard qty if nombre is not set
        $quantity = intval($line->qty);
    }
    return $quantity;
}

function getProductDimensions($line) 
{
    $dimensions = [
        'length' => 1000, // default
        'width' => 100,   // default
        'color' => 'Standard', // default
        'ref_ligne' => '' // default
    ];
    
    if (isset($line->array_options) && is_array($line->array_options)) {
        // Length variations
        if (isset($line->array_options['options_length']) && !empty($line->array_options['options_length'])) {
            $dimensions['length'] = floatval($line->array_options['options_length']);
        } elseif (isset($line->array_options['options_longueur']) && !empty($line->array_options['options_longueur'])) {
            $dimensions['length'] = floatval($line->array_options['options_longueur']);
        } elseif (isset($line->array_options['options_long']) && !empty($line->array_options['options_long'])) {
            $dimensions['length'] = floatval($line->array_options['options_long']);
        }
        
        // Width variations
        if (isset($line->array_options['options_width']) && !empty($line->array_options['options_width'])) {
            $dimensions['width'] = floatval($line->array_options['options_width']);
        } elseif (isset($line->array_options['options_largeur']) && !empty($line->array_options['options_largeur'])) {
            $dimensions['width'] = floatval($line->array_options['options_largeur']);
        } elseif (isset($line->array_options['options_larg']) && !empty($line->array_options['options_larg'])) {
            $dimensions['width'] = floatval($line->array_options['options_larg']);
        }
        
        // Color variations
        if (isset($line->array_options['options_color']) && !empty($line->array_options['options_color'])) {
            $dimensions['color'] = $line->array_options['options_color'];
        } elseif (isset($line->array_options['options_couleur']) && !empty($line->array_options['options_couleur'])) {
            $dimensions['color'] = $line->array_options['options_couleur'];
        }
        
        // Ref ligne from extrafield
        if (isset($line->array_options['options_ref_ligne']) && !empty($line->array_options['options_ref_ligne'])) {
            $dimensions['ref_ligne'] = $line->array_options['options_ref_ligne'];
        }
    }
    
    return $dimensions;
}

function createProductData($product, $line, $productIndex, $quantity, $dimensions, $lineIndex) 
{
    return array(
        'id' => $productIndex,
        'ref' => $product->ref,
        'name' => $product->label,
        'color' => $dimensions['color'],
        'ref_ligne' => $dimensions['ref_ligne'],
        'weight' => (!empty($product->weight) ? $product->weight : 1.0),
        'length' => $dimensions['length'],
        'width' => $dimensions['width'],
        'total' => $quantity,
        'used' => 0,
        'line_id' => $line->id,
        'line_order' => $lineIndex
    );
}

function addToProductGroups(&$productGroups, $product, $color, $productId) 
{
    $groupKey = $product->label . ' - ' . $color;
    if (!isset($productGroups[$groupKey])) {
        $productGroups[$groupKey] = array(
            'key' => $groupKey,
            'name' => $product->label,
            'color' => $color,
            'products' => array()
        );
    }
    $productGroups[$groupKey]['products'][] = $productId;
}
?>