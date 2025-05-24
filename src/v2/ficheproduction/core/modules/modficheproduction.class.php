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
 * \defgroup ficheproduction Module Fiche de Production
 * \brief Module de gestion des fiches de production et plans de colisage.
 */

/**
 * \file        core/modules/modficheproduction.class.php
 * \ingroup     ficheproduction
 * \brief       Fichier de description et activation du module Fiche de Production
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Classe de description et activation du module Fiche de Production
 */
class modFicheProduction extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        // Id for module (must be unique).
        $this->numero = 436571;

        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'ficheproduction';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (interface modules),'other','...
        $this->family = "projects";

        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';

        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleFicheProductionName' not found (FicheProduction is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description, used if translation string 'ModuleFicheProductionDesc' not found (FicheProduction is name of module).
        $this->description = "Gestion des fiches de production et plans de colisage v2.0";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "Module de gestion des fiches de production avec interface moderne de drag & drop pour le colisage";

        // Author
        $this->editor_name = 'SuperAdmin';
        $this->editor_url = '';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '2.0.0';
        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where FICHEPRODUCTION is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Name of image file used for this module.
        $this->picto = 'ficheproduction@ficheproduction';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 0,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'models' => 0,
            // Set this to 1 if module has its own printing directory (core/modules/printing)
            'printing' => 0,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => array(
                '/ficheproduction/css/ficheproduction.css',
            ),
            // Set this to relative path of js file if module must load a js on all pages
            'js' => array(
                // '/ficheproduction/js/ficheproduction.js',
            ),
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>executeHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => array(
                'ordercard',
                'ordersuppliercard'
            ),
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0,
        );

        // Data directories to create when module is enabled.
        $this->dirs = array("/ficheproduction/temp");

        // Config pages. Put here list of php page, stored into ficheproduction/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@ficheproduction");

        // Dependencies
        $this->hidden = false; // A condition to hide module
        $this->depends = array('modCommande'); // List of module class names as string that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR'...))
        $this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

        // The language file dedicated to your module
        $this->langfiles = array("ficheproduction@ficheproduction");

        // Prerequisites
        $this->phpmin = array(7, 0); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(20, 0); // Minimum version of Dolibarr required by module

        // Messages at activation
        $this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        //$this->automatic_activation = array('FR'=>'FicheProductionWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true;                // If true, can't be disabled

        // Constants
        $this->const = array(
            1 => array('FICHEPRODUCTION_POIDS_MAX_COLIS', 'chaine', '25', 'Poids maximum par défaut pour un colis (kg)', 1, 'allentities', 1),
            2 => array('FICHEPRODUCTION_AUTO_CREATE_SESSION', 'chaine', '1', 'Créer automatiquement une session de colisage pour les nouvelles commandes', 1, 'allentities', 1),
        );

        // Array to add new pages in new tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes/Widgets
        $this->boxes = array();

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        $this->cronjobs = array();

        // Permissions provided by this module
        $this->rights = array();
        $r = 0;

        // Add here list of permission defined by an id, a label, a boolean and two constant strings.
        $this->rights[$r][0] = $this->numero.'01'; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Lire les fiches de production'; // Permission label
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->ficheproduction->level1->read)
        $r++;
        $this->rights[$r][0] = $this->numero.'02'; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Créer/modifier les fiches de production'; // Permission label
        $this->rights[$r][4] = 'write';
        $this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->ficheproduction->level1->write)
        $r++;
        $this->rights[$r][0] = $this->numero.'03'; // Permission id (must not be already used)
        $this->rights[$r][1] = 'Supprimer les fiches de production'; // Permission label
        $this->rights[$r][4] = 'delete';
        $this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->ficheproduction->level1->delete)
        $r++;
    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf, $langs;

        $result = $this->_load_tables('/ficheproduction/sql/');
        if ($result < 0) {
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        // Permissions
        $this->remove($options);

        $sql = array();

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}