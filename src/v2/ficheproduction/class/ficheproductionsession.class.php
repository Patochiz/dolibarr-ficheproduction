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
 * \file        class/ficheproductionsession.class.php
 * \ingroup     ficheproduction
 * \brief       This file is a CRUD class file for FicheProductionSession (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for FicheProductionSession
 */
class FicheProductionSession extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'ficheproduction';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'ficheproductionsession';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'ficheproduction_session';

    /**
     * @var int Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for ficheproductionsession. Must be the part after the 'object_' into object_ficheproductionsession.png
     */
    public $picto = 'ficheproductionsession@ficheproduction';

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = array(
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
        'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
        'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'css'=>'maxwidth500 widthcentpercentminusxx', 'help'=>"LinkToThirparty"),
        'fk_commande' => array('type'=>'integer:Commande:commande/class/commande.class.php', 'label'=>'Order', 'enabled'=>'1', 'position'=>51, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'css'=>'maxwidth500 widthcentpercentminusxx'),
        'ref_chantier' => array('type'=>'varchar(255)', 'label'=>'RefChantier', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>1),
        'commentaires' => array('type'=>'text', 'label'=>'Comments', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>1),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>2),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>2),
        'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>2, 'foreignkey'=>'user.rowid'),
        'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>2),
        'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'default'=>'1', 'arrayofkeyval'=>array('0'=>'Draft', '1'=>'Active', '9'=>'Canceled')),
        'active' => array('type'=>'integer', 'label'=>'Active', 'enabled'=>'1', 'position'=>1001, 'notnull'=>1, 'visible'=>0, 'default'=>'1'),
    );

    public $rowid;
    public $ref;
    public $fk_soc;
    public $fk_commande;
    public $ref_chantier;
    public $commentaires;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $status;
    public $active;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Example to show how to set values of fields definition dynamically
        /*if ($user->rights->ficheproduction->read) {
            $this->fields['myfield']['visible'] = 1;
            $this->fields['myfield']['noteditable'] = 0;
        }*/

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false)
    {
        $resultcreate = $this->createCommon($user, $notrigger);

        if ($resultcreate > 0) {
            // Auto-generate ref if it was '(PROV)'
            if ($this->ref == '(PROV)') {
                $this->ref = 'FP'.sprintf('%04d', $this->id);
                $this->update($user, 1);
            }
        }

        return $resultcreate;
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id   Id object
     * @param string $ref  Ref
     * @return int         <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null)
    {
        $result = $this->fetchCommon($id, $ref);
        return $result;
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = false)
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        return $this->deleteCommon($user, $notrigger);
    }

    /**
     * Get session for a specific order
     *
     * @param int $fk_commande Order ID
     * @return int <0 if KO, 0 if not found, >0 if OK
     */
    public function fetchByOrder($fk_commande)
    {
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_commande = ".((int) $fk_commande);
        $sql .= " AND active = 1";
        $sql .= " ORDER BY date_creation DESC LIMIT 1";

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->db->free($resql);
                return $this->fetch($obj->rowid);
            } else {
                $this->db->free($resql);
                return 0;
            }
        } else {
            $this->error = "Error ".$this->db->lasterror();
            dol_syslog(__METHOD__." ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * Create session for order if not exists
     *
     * @param int $fk_commande Order ID
     * @param int $fk_soc Society ID
     * @param User $user User object
     * @return int <0 if KO, session ID if OK
     */
    public function createForOrder($fk_commande, $fk_soc, User $user)
    {
        // Check if session already exists
        $result = $this->fetchByOrder($fk_commande);
        if ($result > 0) {
            return $this->id;
        }

        // Create new session
        $this->ref = '(PROV)';
        $this->fk_commande = $fk_commande;
        $this->fk_soc = $fk_soc;
        $this->date_creation = dol_now();
        $this->fk_user_creat = $user->id;
        $this->status = 1;
        $this->active = 1;

        return $this->create($user);
    }
}