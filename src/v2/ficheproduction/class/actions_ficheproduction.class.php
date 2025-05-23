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
 * \file        class/actions_ficheproduction.class.php
 * \ingroup     ficheproduction
 * \brief       Actions/hooks for module ficheproduction
 */

/**
 * Class ActionsMyObject
 */
class ActionsFicheProduction
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Execute action
     *
     * @param array        $parameters     Array of parameters
     * @param CommonObject $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param string       $action         'add', 'update', 'view'
     * @return int                         <0 if KO,
     *                                     =0 if OK but we want to process standard actions too,
     *                                     >0 if OK and we want to replace standard actions.
     */
    public function getNomUrl($parameters, &$object, &$action)
    {
        global $db, $langs, $conf, $user;
        $this->resprints = '';
        return 0;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param array        $parameters     Hook metadatas (context, etc...)
     * @param CommonObject $object         The object to process
     * @param string       $action         Current action (if set). Generally create or edit or null
     * @param HookManager  $hookmanager    Hook manager propagated to allow calling another hook
     * @return int                         < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], array('ordercard', 'ordersuppliercard'))) {
            // Handle AJAX actions for ficheproduction
            if ($action == 'ficheproduction_save_session') {
                $this->handleSaveSession($object);
                return 1; // Replace standard action
            } elseif ($action == 'ficheproduction_add_colis') {
                $this->handleAddColis($object);
                return 1;
            } elseif ($action == 'ficheproduction_delete_colis') {
                $this->handleDeleteColis($object);
                return 1;
            } elseif ($action == 'ficheproduction_add_product') {
                $this->handleAddProduct($object);
                return 1;
            } elseif ($action == 'ficheproduction_remove_product') {
                $this->handleRemoveProduct($object);
                return 1;
            } elseif ($action == 'ficheproduction_update_quantity') {
                $this->handleUpdateQuantity($object);
                return 1;
            } elseif ($action == 'ficheproduction_update_multiple') {
                $this->handleUpdateMultiple($object);
                return 1;
            } elseif ($action == 'ficheproduction_get_data') {
                $this->handleGetData($object);
                return 1;
            }
        }

        if (!$error) {
            $this->results = array('myreturn' => 999);
            $this->resprints = 'A text to show';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

    /**
     * Overloading the doMassActions function : replacing the parent's function with the one below
     *
     * @param array        $parameters     Hook metadatas (context, etc...)
     * @param CommonObject $object         The object to process
     * @param string       $action         Current action (if set). Generally create or edit or null
     * @param HookManager  $hookmanager    Hook manager propagated to allow calling another hook
     * @return int                         < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
            // Do something here...
        }

        if (!$error) {
            $this->results = array('myreturn' => 999);
            $this->resprints = 'A text to show';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

    /**
     * Overloading the addMoreMassActions function : replacing the parent's function with the one below
     *
     * @param array        $parameters     Hook metadatas (context, etc...)
     * @param CommonObject $object         The object to process
     * @param string       $action         Current action (if set). Generally create or edit or null
     * @param HookManager  $hookmanager    Hook manager propagated to allow calling another hook
     * @return int                         < 0 on error, 0 on success, 1 to replace standard code
     */
    public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
            // Do something here...
        }

        if (!$error) {
            $this->results = array('myreturn' => 999);
            $this->resprints = 'A text to show';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
    }

    /**
     * Execute action to complement object
     *
     * @param array         $parameters     Hook metadatas (context, etc...)
     * @param CommonObject  $object         The object to process
     * @param string        $action         Current action (if set). Generally create or edit or null
     * @param HookManager   $hookmanager    Hook manager propagated to allow calling another hook
     * @return int                          < 0 on error, 0 on success, 1 to replace standard code
     */
    public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf, $user;

        if (in_array($parameters['currentcontext'], array('ordercard'))) {
            if ($object->element == 'commande' && $object->id > 0) {
                // Check if tab already exists to avoid duplicates
                $head = $parameters['head'];
                $tabAlreadyExists = false;
                
                if (is_array($head)) {
                    foreach ($head as $tab) {
                        if (isset($tab[2]) && $tab[2] == 'ficheproduction') {
                            $tabAlreadyExists = true;
                            break;
                        }
                    }
                }
                
                // Add ficheproduction tab only if it doesn't exist yet
                if (!$tabAlreadyExists) {
                    $langs->load("ficheproduction@ficheproduction");
                    
                    $h = count($head);
                    
                    $head[$h][0] = DOL_URL_ROOT.'/custom/ficheproduction/ficheproduction.php?id='.$object->id;
                    $head[$h][1] = $langs->trans('ProductionSheet');
                    $head[$h][2] = 'ficheproduction';
                    $h++;
                    
                    $parameters['head'] = $head;
                }
            }
        }

        return 0;
    }

    /**
     * Save session data
     */
    private function handleSaveSession($object)
    {
        global $user;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductionsession.class.php';
        
        $session = new FicheProductionSession($this->db);
        $result = $session->fetchByOrder($object->id);
        
        if ($result <= 0) {
            // Create new session
            $result = $session->createForOrder($object->id, $object->socid, $user);
        }
        
        if ($result > 0) {
            $session->ref_chantier = GETPOST('ref_chantier', 'alpha');
            $session->commentaires = GETPOST('commentaires', 'restricthtml');
            $session->update($user);
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(array('success' => $result > 0, 'session_id' => $session->id));
        exit;
    }
    
    /**
     * Add new colis
     */
    private function handleAddColis($object)
    {
        global $user;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductionsession.class.php';
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
        
        $session = new FicheProductionSession($this->db);
        $result = $session->fetchByOrder($object->id);
        
        if ($result > 0) {
            // Get next colis number
            $sql = "SELECT MAX(numero_colis) as max_num FROM ".MAIN_DB_PREFIX."ficheproduction_colis WHERE fk_session = ".((int) $session->id);
            $resql = $this->db->query($sql);
            $max_num = 0;
            if ($resql && $this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $max_num = $obj->max_num;
            }
            
            $colis = new FicheProductionColis($this->db);
            $colis->fk_session = $session->id;
            $colis->numero_colis = $max_num + 1;
            $colis->date_creation = dol_now();
            $colis->fk_user_creat = $user->id;
            
            $result = $colis->create($user);
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(array('success' => $result > 0, 'colis_id' => isset($colis) ? $colis->id : 0));
        exit;
    }
    
    /**
     * Delete colis
     */
    private function handleDeleteColis($object)
    {
        global $user;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
        
        $colis_id = GETPOST('colis_id', 'int');
        
        $colis = new FicheProductionColis($this->db);
        $result = $colis->fetch($colis_id);
        
        if ($result > 0) {
            $result = $colis->delete($user);
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(array('success' => $result > 0));
        exit;
    }
    
    /**
     * Add product to colis
     */
    private function handleAddProduct($object)
    {
        global $user;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        
        $colis_id = GETPOST('colis_id', 'int');
        $product_id = GETPOST('product_id', 'int');
        $quantite = GETPOST('quantite', 'int');
        
        $colis = new FicheProductionColis($this->db);
        $result = $colis->fetch($colis_id);
        
        if ($result > 0) {
            // Get product weight
            $product = new Product($this->db);
            $product->fetch($product_id);
            $poids_unitaire = $product->weight ?: 1;
            
            $result = $colis->addLine($product_id, $quantite, $poids_unitaire, $user);
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(array('success' => $result > 0));
        exit;
    }
    
    /**
     * Remove product from colis
     */
    private function handleRemoveProduct($object)
    {
        global $user;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
        
        $colis_id = GETPOST('colis_id', 'int');
        $product_id = GETPOST('product_id', 'int');
        
        $colis = new FicheProductionColis($this->db);
        $result = $colis->fetch($colis_id);
        
        if ($result > 0) {
            // Find the line to remove
            $line_id = 0;
            foreach ($colis->lines as $line) {
                if ($line->fk_product == $product_id) {
                    $line_id = $line->id;
                    break;
                }
            }
            
            if ($line_id > 0) {
                $result = $colis->removeLine($line_id, $user);
            }
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(array('success' => $result > 0));
        exit;
    }
    
    /**
     * Update product quantity
     */
    private function handleUpdateQuantity($object)
    {
        global $user;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
        
        $colis_id = GETPOST('colis_id', 'int');
        $product_id = GETPOST('product_id', 'int');
        $quantite = GETPOST('quantite', 'int');
        
        $colis = new FicheProductionColis($this->db);
        $result = $colis->fetch($colis_id);
        
        if ($result > 0) {
            // Find the line to update
            $line_id = 0;
            foreach ($colis->lines as $line) {
                if ($line->fk_product == $product_id) {
                    $line_id = $line->id;
                    break;
                }
            }
            
            if ($line_id > 0) {
                $result = $colis->updateLineQuantity($line_id, $quantite, $user);
            }
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(array('success' => $result > 0));
        exit;
    }
    
    /**
     * Update colis multiple
     */
    private function handleUpdateMultiple($object)
    {
        global $user;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
        
        $colis_id = GETPOST('colis_id', 'int');
        $multiple = GETPOST('multiple', 'int');
        
        $colis = new FicheProductionColis($this->db);
        $result = $colis->fetch($colis_id);
        
        if ($result > 0) {
            $colis->multiple_colis = $multiple;
            $result = $colis->update($user);
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(array('success' => $result > 0));
        exit;
    }
    
    /**
     * Get all data for the interface
     */
    private function handleGetData($object)
    {
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductionsession.class.php';
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
        
        $data = array(
            'products' => array(),
            'colis' => array(),
            'session' => null
        );
        
        // Get session
        $session = new FicheProductionSession($this->db);
        $result = $session->fetchByOrder($object->id);
        if ($result > 0) {
            $data['session'] = array(
                'id' => $session->id,
                'ref_chantier' => $session->ref_chantier,
                'commentaires' => $session->commentaires
            );
            
            // Get colis
            $colis_obj = new FicheProductionColis($this->db);
            $all_colis = $colis_obj->fetchAllBySession($session->id);
            
            foreach ($all_colis as $coli) {
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
                        'quantite' => $line->quantite,
                        'poids_unitaire' => $line->poids_unitaire,
                        'poids_total' => $line->poids_total
                    );
                }
                
                $data['colis'][] = $colis_data;
            }
        }
        
        // Get order products
        if ($object->element == 'commande') {
            // Fetch order lines if not already loaded
            if (empty($object->lines)) {
                $object->fetch_lines();
            }
            
            foreach ($object->lines as $line) {
                if ($line->fk_product > 0) {
                    $product = new Product($this->db);
                    $product->fetch($line->fk_product);
                    
                    // Calculate used quantity in colis
                    $used_quantity = 0;
                    foreach ($data['colis'] as $coli) {
                        foreach ($coli['products'] as $prod) {
                            if ($prod['product_id'] == $line->fk_product) {
                                $used_quantity += $prod['quantite'] * $coli['multiple_colis'];
                            }
                        }
                    }
                    
                    $data['products'][] = array(
                        'id' => $product->id,
                        'ref' => $product->ref,
                        'label' => $product->label,
                        'weight' => $product->weight ?: 1,
                        'length' => $product->length ?: 0,
                        'width' => $product->width ?: 0,
                        'color' => $product->customcode ?: 'Naturel', // Assuming color is in customcode
                        'total' => $line->qty,
                        'used' => $used_quantity
                    );
                }
            }
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}