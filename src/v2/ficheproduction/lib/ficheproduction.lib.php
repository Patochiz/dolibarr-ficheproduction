<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        lib/ficheproduction.lib.php
 * \ingroup     ficheproduction
 * \brief       Library files with common functions for FicheProduction
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function ficheproductionAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("ficheproduction@ficheproduction");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/ficheproduction/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;

    /*
    $head[$h][0] = dol_buildpath("/ficheproduction/admin/myobject_extrafields.php", 1);
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'myobject_extrafields';
    $h++;
    */

    $head[$h][0] = dol_buildpath("/ficheproduction/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //  'entity:+tabname:Title:@ficheproduction:/ficheproduction/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //  'entity:-tabname:Title:@ficheproduction:/ficheproduction/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'ficheproduction@ficheproduction');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'ficheproduction@ficheproduction', 'remove');

    return $head;
}

/**
 * Get array of available product colors from order
 *
 * @param object $order Order object
 * @return array Array of colors
 */
function ficheproductionGetProductColors($order)
{
    $colors = array();
    
    if (!empty($order->lines)) {
        foreach ($order->lines as $line) {
            if ($line->fk_product > 0) {
                require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                $product = new Product($order->db);
                $product->fetch($line->fk_product);
                
                $color = !empty($product->customcode) ? $product->customcode : 'Naturel';
                if (!in_array($color, $colors)) {
                    $colors[] = $color;
                }
            }
        }
    }
    
    sort($colors);
    return $colors;
}

/**
 * Get products by color from order
 *
 * @param object $order Order object
 * @param string $color Color filter
 * @return array Array of products
 */
function ficheproductionGetProductsByColor($order, $color = '')
{
    $products = array();
    
    if (!empty($order->lines)) {
        foreach ($order->lines as $line) {
            if ($line->fk_product > 0) {
                require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                $product = new Product($order->db);
                $product->fetch($line->fk_product);
                
                $product_color = !empty($product->customcode) ? $product->customcode : 'Naturel';
                
                if (empty($color) || $product_color == $color) {
                    $products[] = array(
                        'product' => $product,
                        'quantity' => $line->qty,
                        'color' => $product_color
                    );
                }
            }
        }
    }
    
    return $products;
}

/**
 * Calculate total weight for a colis
 *
 * @param array $products Array of products with quantities
 * @return float Total weight
 */
function ficheproductionCalculateWeight($products)
{
    $total_weight = 0;
    
    foreach ($products as $product_data) {
        $weight = $product_data['product']->weight ?: 1; // Default weight if not set
        $quantity = $product_data['quantity'];
        $total_weight += $weight * $quantity;
    }
    
    return $total_weight;
}

/**
 * Check if weight exceeds limit
 *
 * @param float $current_weight Current weight
 * @param float $max_weight Maximum allowed weight
 * @return string Status: 'ok', 'warning', 'danger'
 */
function ficheproductionGetWeightStatus($current_weight, $max_weight)
{
    $percentage = ($current_weight / $max_weight) * 100;
    
    if ($percentage > 100) {
        return 'danger';
    } elseif ($percentage > 90) {
        return 'warning';
    } else {
        return 'ok';
    }
}

/**
 * Format weight for display
 *
 * @param float $weight Weight in kg
 * @param int $decimals Number of decimal places
 * @return string Formatted weight
 */
function ficheproductionFormatWeight($weight, $decimals = 1)
{
    return number_format($weight, $decimals, ',', ' ').' kg';
}

/**
 * Get status icon for weight
 *
 * @param string $status Weight status
 * @return string HTML icon
 */
function ficheproductionGetStatusIcon($status)
{
    switch ($status) {
        case 'ok':
            return '✅';
        case 'warning':
            return '⚠️';
        case 'danger':
            return '❌';
        default:
            return '❓';
    }
}

/**
 * Generate next colis number for a session
 *
 * @param DoliDB $db Database object
 * @param int $session_id Session ID
 * @return int Next colis number
 */
function ficheproductionGetNextColisNumber($db, $session_id)
{
    $sql = "SELECT MAX(numero_colis) as max_num FROM ".MAIN_DB_PREFIX."ficheproduction_colis";
    $sql .= " WHERE fk_session = ".((int) $session_id);
    $sql .= " AND active = 1";
    
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        $obj = $db->fetch_object($resql);
        return ($obj->max_num ?: 0) + 1;
    }
    
    return 1;
}

/**
 * Export colis data to array format for JSON
 *
 * @param object $session Session object
 * @param array $colis_list Array of colis objects
 * @return array Export data
 */
function ficheproductionExportData($session, $colis_list)
{
    $export_data = array(
        'session' => array(
            'id' => $session->id,
            'ref' => $session->ref,
            'ref_chantier' => $session->ref_chantier,
            'commentaires' => $session->commentaires,
            'date_creation' => $session->date_creation
        ),
        'colis' => array()
    );
    
    foreach ($colis_list as $coli) {
        $colis_data = array(
            'id' => $coli->id,
            'numero_colis' => $coli->numero_colis,
            'poids_max' => $coli->poids_max,
            'poids_total' => $coli->poids_total,
            'multiple_colis' => $coli->multiple_colis,
            'status' => $coli->status,
            'products' => array()
        );
        
        foreach ($coli->lines as $line) {
            $colis_data['products'][] = array(
                'line_id' => $line->id,
                'product_id' => $line->fk_product,
                'product_ref' => $line->product_ref,
                'product_label' => $line->product_label,
                'quantite' => $line->quantite,
                'poids_unitaire' => $line->poids_unitaire,
                'poids_total' => $line->poids_total,
                'rang' => $line->rang
            );
        }
        
        $export_data['colis'][] = $colis_data;
    }
    
    return $export_data;
}