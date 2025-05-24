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
 * \brief       Manager class for FicheProduction operations
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductionsession.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolis.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolisline.class.php';

/**
 * Class for FicheProductionManager
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
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
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

        if (empty($colisData) || !is_array($colisData)) {
            $result['message'] = 'Aucune donnée de colisage à sauvegarder';
            return $result;
        }

        $this->db->begin();

        try {
            // 1. Créer ou récupérer la session
            $session = new FicheProductionSession($this->db);
            $session_result = $session->fetchByOrder($fk_commande);
            
            if ($session_result <= 0) {
                // Créer une nouvelle session
                $session_id = $session->createForOrder($fk_commande, $fk_soc, $user);
                if ($session_id < 0) {
                    throw new Exception('Erreur lors de la création de la session: ' . $session->error);
                }
            } else {
                $session_id = $session->id;
                
                // Supprimer tous les colis existants pour cette session
                $this->deleteAllColisForSession($session_id, $user);
            }

            $result['session_id'] = $session_id;

            // 2. Sauvegarder chaque colis
            $colis_count = 0;
            foreach ($colisData as $colisInfo) {
                $colis = new FicheProductionColis($this->db);
                $colis_id = $colis->createFromJSData($colisInfo, $session_id, $user);
                
                if ($colis_id < 0) {
                    $error_msg = 'Erreur lors de la création du colis ' . $colisInfo['number'] . ': ' . join(', ', $colis->errors);
                    $result['errors'][] = $error_msg;
                    dol_syslog(__METHOD__ . ' ' . $error_msg, LOG_ERR);
                } else {
                    $colis_count++;
                }
            }

            if (count($result['errors']) > 0) {
                throw new Exception('Erreurs lors de la sauvegarde: ' . join('; ', $result['errors']));
            }

            $this->db->commit();

            $result['success'] = true;
            $result['colis_saved'] = $colis_count;
            $result['message'] = "Colisage sauvegardé avec succès: {$colis_count} colis créés";

        } catch (Exception $e) {
            $this->db->rollback();
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $this->error = $e->getMessage();
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

        try {
            // Rechercher la session
            $session = new FicheProductionSession($this->db);
            $session_result = $session->fetchByOrder($fk_commande);
            
            if ($session_result <= 0) {
                $result['message'] = 'Aucune session de colisage trouvée pour cette commande';
                return $result;
            }

            $result['session_id'] = $session->id;

            // Charger tous les colis de la session
            $colisList = new FicheProductionColis($this->db);
            $allColis = $colisList->fetchAllBySession($session->id);

            foreach ($allColis as $colis) {
                $colisData = array(
                    'id' => $colis->id,
                    'number' => $colis->numero_colis,
                    'maxWeight' => $colis->poids_max,
                    'totalWeight' => $colis->poids_total,
                    'multiple' => $colis->multiple_colis,
                    'status' => $colis->status,
                    'products' => array()
                );

                // Charger les lignes
                foreach ($colis->lines as $line) {
                    $productData = array(
                        'line_id' => $line->id,
                        'quantity' => $line->quantite,
                        'weight' => $line->poids_unitaire,
                        'totalWeight' => $line->poids_total
                    );

                    if ($line->is_libre_product) {
                        // Produit libre
                        $productData['isLibre'] = true;
                        $productData['name'] = $line->libre_product_name;
                        $productData['description'] = $line->libre_product_description;
                    } else {
                        // Produit standard
                        $productData['isLibre'] = false;
                        $productData['productId'] = $line->fk_product;
                        $productData['name'] = $line->product_label;
                        $productData['ref'] = $line->product_ref;
                    }

                    $colisData['products'][] = $productData;
                }

                $result['colis'][] = $colisData;
            }

            $result['success'] = true;
            $result['message'] = count($result['colis']) . ' colis chargés';

        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            $this->error = $e->getMessage();
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
            dol_syslog(__METHOD__ . ' ' . $e->getMessage(), LOG_ERR);
        }

        return $stats;
    }

    /**
     * Convert colis data from JavaScript format to database format
     *
     * @param array $jsColisData JavaScript colis data
     * @param array $products Available products from order
     * @return array Converted data
     */
    public function convertJSColisData($jsColisData, $products = array())
    {
        $convertedData = array();

        foreach ($jsColisData as $jsColis) {
            $colisData = array(
                'number' => $jsColis['number'],
                'maxWeight' => !empty($jsColis['maxWeight']) ? $jsColis['maxWeight'] : 25,
                'multiple' => !empty($jsColis['multiple']) ? $jsColis['multiple'] : 1,
                'status' => !empty($jsColis['status']) ? $jsColis['status'] : 'ok',
                'products' => array()
            );

            if (!empty($jsColis['products']) && is_array($jsColis['products'])) {
                foreach ($jsColis['products'] as $jsProduct) {
                    $productData = array(
                        'quantity' => $jsProduct['quantity'],
                        'weight' => 0
                    );

                    if (!empty($jsProduct['isLibre'])) {
                        // Produit libre
                        $productData['isLibre'] = true;
                        $productData['name'] = $jsProduct['name'];
                        $productData['description'] = !empty($jsProduct['description']) ? $jsProduct['description'] : '';
                        $productData['weight'] = $jsProduct['weight'];
                    } else {
                        // Produit standard
                        $productData['isLibre'] = false;
                        $productData['productId'] = $jsProduct['productId'];
                        
                        // Récupérer le poids depuis la liste des produits disponibles
                        foreach ($products as $product) {
                            if ($product['id'] == $jsProduct['productId']) {
                                $productData['weight'] = $product['weight'];
                                break;
                            }
                        }
                    }

                    $colisData['products'][] = $productData;
                }
            }

            $convertedData[] = $colisData;
        }

        return $convertedData;
    }
}
