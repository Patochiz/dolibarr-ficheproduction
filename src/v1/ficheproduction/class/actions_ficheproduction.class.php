<?php
/* Copyright (C) 2024
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Class ActionsFicheProduction
 * Hooks handler for FicheProduction module
 */
class ActionsFicheProduction
{
    /**
     * @var DoliDB Database handler
     */
    public $db;
    
    /**
     * @var string Error
     */
    public $error = '';
    
    /**
     * @var array Errors
     */
    public $errors = array();
    
    /**
     * @var array Hook results
     */
    public $results = array();
    
    /**
     * @var string HTML content
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
     * Overloading the addMoreActionsButtons function
     *
     * @param array           $parameters     Hook parameters
     * @param CommonObject    $object         Current object
     * @param string          $action         Current action
     * @param HookManager     $hookmanager    Hook manager
     * @return int                            0=OK, 1=Replace standard button, -1=KO
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user;
        
        $langs->load("ficheproduction@ficheproduction");
        
        // Cette méthode ne sera pas utilisée pour l'onglet mais pourrait servir pour ajouter des boutons d'action
        
        return 0;
    }

    /**
     * Traitement personnalisé avant le chargement de la page
     *
     * @param array           $parameters     Hook parameters
     * @param CommonObject    $object         Current object
     * @param string          $action         Current action
     * @param HookManager     $hookmanager    Hook manager
     * @return int                            0=OK, 1=Replace standard content, -1=KO
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf;
        
        // Cette méthode peut être utilisée pour exécuter des actions avant le chargement de la page
        
        return 0;
    }

    /**
     * MÉTHODE ESSENTIELLE: Ajoute un onglet aux commandes
     *
     * @param array           $parameters     Hook parameters
     * @param CommonObject    $object         Current object
     * @param string          $action         Current action
     * @param HookManager     $hookmanager    Hook manager
     * @return int                            0=OK, 1=Replace standard content, -1=KO
     */
    public function printObjectTabs($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user, $db;
        
        if ($parameters['currentcontext'] == 'ordercard') {
            if (is_object($object) && $object->element == 'commande') {
                $langs->load('ficheproduction@ficheproduction');
                $id = $object->id;
                
                // Ajouter l'onglet au tableau d'onglets Dolibarr
                $head = &$parameters['head'];
                $head[] = array(
                    dol_buildpath('/ficheproduction/ficheproduction.php', 1).'?id='.$id,
                    $langs->trans('FicheProduction'),
                    'ficheproduction'
                );
                
                return 0;
            }
        }
        
        return 0;
    }
}