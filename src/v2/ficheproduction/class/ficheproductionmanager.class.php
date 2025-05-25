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
 * \file        class/ficheproductionmanager.class.php
 * \ingroup     ficheproduction
 * \brief       Manager class for FicheProduction operations - Version corrigée
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductionsession.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolisline.class.php';

/**
 * Class for FicheProductionManager - Version améliorée avec meilleure validation
 */
class FicheProductionManager
{
    /**
     * @var DoliDB Database connection
     */
    private $db;

    /**
     * @var array Errors array
     */
    public $errors = array();

    /**
     * @var string Last error message
     */
    public $error = '';

    /**
     * @var bool Debug mode
     */
    private $debug = false;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
        $this->debug = getDolGlobalString('FICHEPRODUCTION_DEBUG', false);
    }

    /**
     * Log debug information
     *
     * @param string $message Debug message
     * @param mixed $data Additional data to log
     */
    private function debugLog($message, $data = null)
    {
        if ($this->debug) {
            $logMessage = '[FicheProductionManager] ' . $message;
            if ($data !== null) {
                $logMessage .= ' - Data: ' . json_encode($data);
            }
            dol_syslog($logMessage, LOG_DEBUG);
        }
    }

    /**
     * Validate JavaScript colis data structure
     *
     * @param array $colisData Colis data from JavaScript
     * @return array Array with validation result
     */
    private function validateColisData($colisData)
    {
        $validation = array(
            'valid' => true,
            'errors' => array()
        );

        if (empty($colisData) || !is_array($colisData)) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Les données de colis doivent être un tableau non vide';
            return $validation;
        }

        foreach ($colisData as $index => $colis) {
            $colisPrefix = "Colis $index";

            // Vérifier la structure de base
            if (!is_array($colis)) {
                $validation['valid'] = false;
                $validation['errors'][] = "$colisPrefix: doit être un objet";
                continue;
            }

            // Vérifier les champs obligatoires
            $requiredFields = ['number', 'products'];
            foreach ($requiredFields as $field) {
                if (!isset($colis[$field])) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "$colisPrefix: champ '$field' manquant";
                }
            }

            // Valider le numéro de colis
            if (isset($colis['number'])) {
                if (!is_numeric($colis['number']) || $colis['number'] <= 0) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "$colisPrefix: 'number' doit être un nombre positif";
                }
            }

            // Valider les produits
            if (isset($colis['products'])) {
                if (!is_array($colis['products'])) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "$colisPrefix: 'products' doit être un tableau";
                } else {
                    foreach ($colis['products'] as $prodIndex => $product) {
                        $productPrefix = "$colisPrefix, Produit $prodIndex";

                        if (!is_array($product)) {
                            $validation['valid'] = false;
                            $validation['errors'][] = "$productPrefix: doit être un objet";
                            continue;
                        }

                        // Quantité obligatoire et positive
                        if (!isset($product['quantity']) || !is_numeric($product['quantity']) || $product['quantity'] <= 0) {
                            $validation['valid'] = false;
                            $validation['errors'][] = "$productPrefix: 'quantity' doit être un nombre positif";
                        }

                        // Poids obligatoire et positif
                        if (!isset($product['weight']) || !is_numeric($product['weight']) || $product['weight'] < 0) {
                            $validation['valid'] = false;
                            $validation['errors'][] = "$productPrefix: 'weight' doit être un nombre positif ou nul";
                        }

                        // Validation spécifique selon le type de produit
                        if (!empty($product['isLibre'])) {
                            // Produit libre - nom obligatoire
                            if (empty($product['name']) || !is_string($product['name'])) {
                                $validation['valid'] = false;
                                $validation['errors'][] = "$productPrefix (libre): 'name' doit être une chaîne non vide";
                            }
                        } else {
                            // Produit standard - ID obligatoire
                            if (!isset($product['productId']) || !is_numeric($product['productId']) || $product['productId'] <= 0) {
                                $validation['valid'] = false;
                                $validation['errors'][] = "$productPrefix (standard): 'productId' doit être un nombre positif";
                            }
                        }
                    }
                }
            }

            // Valider les champs optionnels s'ils sont présents
            if (isset($colis['maxWeight']) && (!is_numeric($colis['maxWeight']) || $colis['maxWeight'] <= 0)) {
                $validation['valid'] = false;
                $validation['errors'][] = "$colisPrefix: 'maxWeight' doit être un nombre positif";
            }

            if (isset($colis['multiple']) && (!is_numeric($colis['multiple']) || $colis['multiple'] <= 0)) {
                $validation['valid'] = false;
                $validation['errors'][] = "$colisPrefix: 'multiple' doit être un nombre entier positif";
            }
        }

        return $validation;
    }

    /**
     * Convert and normalize JavaScript data for database storage
     *
     * @param array $colisData Raw colis data from JavaScript
     * @return array Normalized data
     */
    private function normalizeColisData($colisData)
    {
        $normalized = array();

        foreach ($colisData as $colis) {
            $normalizedColis = array(
                'number' => intval($colis['number']),
                'maxWeight' => isset($colis['maxWeight']) ? floatval($colis['maxWeight']) : 25.0,
                'totalWeight' => isset($colis['totalWeight']) ? floatval($colis['totalWeight']) : 0.0,
                'multiple' => isset($colis['multiple']) ? intval($colis['multiple']) : 1,
                'status' => isset($colis['status']) ? trim($colis['status']) : 'ok',
                'isLibre' => !empty($colis['isLibre']),
                'products' => array()
            );

            if (!empty($colis['products']) && is_array($colis['products'])) {
                foreach ($colis['products'] as $product) {
                    $normalizedProduct = array(
                        'quantity' => intval($product['quantity']),
                        'weight' => floatval($product['weight']),
                        'isLibre' => !empty($product['isLibre'])
                    );

                    if ($normalizedProduct['isLibre']) {
                        // Produit libre
                        $normalizedProduct['name'] = trim($product['name']);
                        $normalizedProduct['description'] = isset($product['description']) ? trim($product['description']) : '';
                    } else {
                        // Produit standard
                        $normalizedProduct['productId'] = intval($product['productId']);
                    }

                    $normalizedColis['products'][] = $normalizedProduct;
                }
            }

            $normalized[] = $normalizedColis;
        }

        return $normalized;
    }

    /**
     * Save complete colisage data from JavaScript
     *
     * @param int $fk_commande Order ID
     * @param int $fk_soc Society ID
     * @param array $colisData Complete colis data from JavaScript
     * @param User $user User object
     * @return array Array with result status and messages
     */
    public function saveColisageData($fk_commande, $fk_soc, $colisData, User $user)
    {
        $result = array(
            'success' => false,
            'message' => '',
            'session_id' => 0,
            'colis_saved' => 0,
            'errors' => array()
        );

        $this->debugLog('Starting saveColisageData', array(
            'fk_commande' => $fk_commande,
            'fk_soc' => $fk_soc,
            'data_count' => count($colisData)
        ));

        // Validation préliminaire
        $validation = $this->validateColisData($colisData);
        if (!$validation['valid']) {
            $result['message'] = 'Données de colis invalides';
            $result['errors'] = $validation['errors'];
            $this->debugLog('Validation failed', $validation['errors']);
            return $result;
        }

        // Normalisation des données
        $normalizedData = $this->normalizeColisData($colisData);
        $this->debugLog('Data normalized', array('count' => count($normalizedData)));

        $this->db->begin();

        try {
            // 1. Créer ou récupérer la session
            $session = new FicheProductionSession($this->db);
            $session_result = $session->fetchByOrder($fk_commande);
            
            if ($session_result <= 0) {
                $this->debugLog('Creating new session');
                $session_id = $session->createForOrder($fk_commande, $fk_soc, $user);
                if ($session_id < 0) {
                    throw new Exception('Erreur lors de la création de la session: ' . $session->error);
                }
                $this->debugLog('Session created', array('session_id' => $session_id));
            } else {
                $session_id = $session->id;
                $this->debugLog('Using existing session', array('session_id' => $session_id));
                
                // Supprimer tous les colis existants pour cette session
                $this->deleteAllColisForSession($session_id, $user);
                $this->debugLog('Deleted existing colis for session');
            }

            $result['session_id'] = $session_id;

            // 2. Sauvegarder chaque colis
            $colis_count = 0;
            foreach ($normalizedData as $index => $colisInfo) {
                $this->debugLog("Creating colis $index", $colisInfo);
                
                $colis = new FicheProductionColis($this->db);
                $colis_id = $colis->createFromJSData($colisInfo, $session_id, $user);
                
                if ($colis_id < 0) {
                    $error_msg = 'Erreur lors de la création du colis ' . $colisInfo['number'] . ': ' . join(', ', $colis->errors);
                    $result['errors'][] = $error_msg;
                    $this->debugLog('Error creating colis', array('colis' => $colisInfo, 'error' => $error_msg));
                } else {
                    $colis_count++;
                    $this->debugLog("Colis created successfully", array('colis_id' => $colis_id));
                }
            }

            if (count($result['errors']) > 0) {
                throw new Exception('Erreurs lors de la sauvegarde: ' . join('; ', $result['errors']));
            }

            $this->db->commit();

            $result['success'] = true;
            $result['colis_saved'] = $colis_count;
            $result['message'] = "Colisage sauvegardé avec succès: {$colis_count} colis créés";
            
            $this->debugLog('Save completed successfully', array(
                'colis_saved' => $colis_count,
                'session_id' => $session_id
            ));

        } catch (Exception $e) {
            $this->db->rollback();
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $this->error = $e->getMessage();
            $this->debugLog('Save failed with exception', array('error' => $e->getMessage()));
            dol_syslog(__METHOD__ . ' ' . $e->getMessage(), LOG_ERR);
        }

        return $result;
    }

    /**
     * Load colisage data for JavaScript
     *
     * @param int $fk_commande Order ID
     * @return array Colis data formatted for JavaScript
     */
    public function loadColisageData($fk_commande)
    {
        $result = array(
            'success' => false,
            'session_id' => 0,
            'colis' => array(),
            'message' => ''
        );

        $this->debugLog('Loading colisage data', array('fk_commande' => $fk_commande));

        try {
            // Rechercher la session
            $session = new FicheProductionSession($this->db);
            $session_result = $session->fetchByOrder($fk_commande);
            
            if ($session_result <= 0) {
                $result['message'] = 'Aucune session de colisage trouvée pour cette commande';
                $this->debugLog('No session found for order');
                return $result;
            }

            $result['session_id'] = $session->id;
            $this->debugLog('Session found', array('session_id' => $session->id));

            // Charger tous les colis de la session
            $colisList = new FicheProductionColis($this->db);
            $allColis = $colisList->fetchAllBySession($session->id);
            $this->debugLog('Colis loaded from database', array('count' => count($allColis)));

            foreach ($allColis as $colis) {
                $colisData = array(
                    'id' => $colis->id,
                    'number' => $colis->numero_colis,
                    'maxWeight' => floatval($colis->poids_max),
                    'totalWeight' => floatval($colis->poids_total),
                    'multiple' => intval($colis->multiple_colis),
                    'status' => $colis->status,
                    'isLibre' => false, // Déterminé par les produits
                    'products' => array()
                );

                // Charger les lignes
                foreach ($colis->lines as $line) {
                    $productData = array(
                        'line_id' => $line->id,
                        'quantity' => intval($line->quantite),
                        'weight' => floatval($line->poids_unitaire),
                        'totalWeight' => floatval($line->poids_total)
                    );

                    if ($line->is_libre_product) {
                        // Produit libre
                        $productData['isLibre'] = true;
                        $productData['name'] = $line->libre_product_name;
                        $productData['description'] = $line->libre_product_description;
                        $colisData['isLibre'] = true; // Le colis contient au moins un produit libre
                    } else {
                        // Produit standard
                        $productData['isLibre'] = false;
                        $productData['productId'] = intval($line->fk_product);
                        $productData['name'] = $line->product_label;
                        $productData['ref'] = $line->product_ref;
                    }

                    $colisData['products'][] = $productData;
                }

                $result['colis'][] = $colisData;
            }

            $result['success'] = true;
            $result['message'] = count($result['colis']) . ' colis chargés';
            $this->debugLog('Load completed successfully', array('colis_count' => count($result['colis'])));

        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $this->error = $e->getMessage();
            $this->debugLog('Load failed with exception', array('error' => $e->getMessage()));
            dol_syslog(__METHOD__ . ' ' . $e->getMessage(), LOG_ERR);
        }

        return $result;
    }

    /**
     * Delete all colis for a session
     *
     * @param int $session_id Session ID
     * @param User $user User object
     * @return int <0 if KO, >0 if OK
     */
    private function deleteAllColisForSession($session_id, User $user)
    {
        $colis = new FicheProductionColis($this->db);
        $allColis = $colis->fetchAllBySession($session_id);

        foreach ($allColis as $coliToDelete) {
            $result = $coliToDelete->delete($user);
            if ($result < 0) {
                throw new Exception('Erreur lors de la suppression du colis ' . $coliToDelete->numero_colis . ': ' . $coliToDelete->error);
            }
        }

        return 1;
    }

    /**
     * Get statistics for a session
     *
     * @param int $fk_commande Order ID
     * @return array Statistics array
     */
    public function getSessionStatistics($fk_commande)
    {
        $stats = array(
            'total_colis' => 0,
            'total_weight' => 0,
            'total_products' => 0,
            'total_free_products' => 0,
            'session_exists' => false
        );

        try {
            $session = new FicheProductionSession($this->db);
            $session_result = $session->fetchByOrder($fk_commande);
            
            if ($session_result > 0) {
                $stats['session_exists'] = true;
                
                $colisList = new FicheProductionColis($this->db);
                $allColis = $colisList->fetchAllBySession($session->id);

                foreach ($allColis as $colis) {
                    $stats['total_colis'] += $colis->multiple_colis;
                    $stats['total_weight'] += $colis->poids_total * $colis->multiple_colis;
                    
                    foreach ($colis->lines as $line) {
                        $stats['total_products'] += $line->quantite * $colis->multiple_colis;
                        
                        if ($line->is_libre_product) {
                            $stats['total_free_products'] += $line->quantite * $colis->multiple_colis;
                        }
                    }
                }
            }

        } catch (Exception $e) {
            $this->debugLog('Error getting statistics', array('error' => $e->getMessage()));
            dol_syslog(__METHOD__ . ' ' . $e->getMessage(), LOG_ERR);
        }

        return $stats;
    }
}
