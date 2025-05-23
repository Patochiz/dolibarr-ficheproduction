<?php
/* Copyright (C) 2024
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module FicheProduction
 */
class modFicheProduction extends DolibarrModules
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        
        $this->db = $db;
        
        // Module identity
        $this->numero = 250000;
        $this->rights_class = 'ficheproduction';
        $this->family = "products";
        $this->module_position = 90;
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Module de gestion des fiches de production";
        $this->descriptionlong = "Module pour gérer les plans de colisage pour les commandes clients";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'generic';
        
        // Module settings
        $this->config_page_url = array("setup.php@ficheproduction");
        $this->depends = array('modCommande');
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->phpmin = array(5, 6);
        $this->need_dolibarr_version = array(10, 0);
        $this->langfiles = array("ficheproduction@ficheproduction");
        
        // Module parts - minimal pour éviter les interférences
        $this->module_parts = array();
        
        // Data directories
        $this->dirs = array('/ficheproduction/temp');
        
        // IMPORTANT: Définition des onglets selon la syntaxe standard de Dolibarr
        // Syntaxe: objecttype:+tabname:Title:langfile@module:condition:url?id=__ID__
        $this->tabs = array(
            // Ajouter un onglet à la fiche commande
            array('data'=>'order:+ficheproduction:FicheProduction:ficheproduction@ficheproduction:1:/ficheproduction/ficheproduction.php?id=__ID__')
        );
        
        // Constants
        $this->const = array();
        
        // Permissions
        $this->rights = array();
    }
    
    /**
     * Function called when module is enabled
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $sql = array();
        $result = $this->_load_tables('/ficheproduction/sql/');
        return $this->_init($sql, $options);
    }
    
    /**
     * Function called when module is disabled
     *
     * @param string $options Options when disabling module ('', 'noboxes')
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}