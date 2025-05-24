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
 * \file        class/ficheproductioncolis.class.php
 * \ingroup     ficheproduction
 * \brief       This file is a CRUD class file for FicheProductionColis (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolisline.class.php';

/**
 * Class for FicheProductionColis
 */
class FicheProductionColis extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'ficheproduction';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'ficheproductioncolis';

    /**
     * @var string Name of table without prefix where object is stored.
     */
    public $table_element = 'ficheproduction_colis';

    /**
     * @var int Does this object support multicompany module ?
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int Does object support extrafields ?
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon
     */
    public $picto = 'ficheproductioncolis@ficheproduction';

    public $rowid;
    public $fk_session;
    public $numero_colis;
    public $poids_max;
    public $poids_total;
    public $multiple_colis;
    public $status;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $active;

    /**
     * @var array Lines of the colis
     */
    public $lines = array();

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Create object into database
     *
     * @param User $user User that creates
     * @param bool $notrigger false=launch triggers after, true=disable triggers
     * @return int <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false)
    {
        global $conf;

        $error = 0;

        // Clean parameters
        if (isset($this->fk_session)) {
            $this->fk_session = (int) $this->fk_session;
        }
        if (isset($this->numero_colis)) {
            $this->numero_colis = (int) $this->numero_colis;
        }
        if (isset($this->poids_max)) {
            $this->poids_max = trim($this->poids_max);
        }
        if (isset($this->poids_total)) {
            $this->poids_total = trim($this->poids_total);
        }
        if (isset($this->multiple_colis)) {
            $this->multiple_colis = (int) $this->multiple_colis;
        }
        if (isset($this->status)) {
            $this->status = trim($this->status);
        }
        if (isset($this->active)) {
            $this->active = (int) $this->active;
        }

        // Check parameters
        if (!$this->fk_session) {
            $this->errors[] = 'Error: fk_session is mandatory';
            return -1;
        }
        if (!$this->numero_colis) {
            $this->errors[] = 'Error: numero_colis is mandatory';
            return -1;
        }

        // Set default values
        if (empty($this->poids_max)) {
            $this->poids_max = getDolGlobalString('FICHEPRODUCTION_POIDS_MAX_COLIS', '25');
        }
        if (empty($this->poids_total)) {
            $this->poids_total = 0;
        }
        if (empty($this->multiple_colis)) {
            $this->multiple_colis = 1;
        }
        if (empty($this->status)) {
            $this->status = 'ok';
        }
        if (!isset($this->active)) {
            $this->active = 1;
        }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= "fk_session,";
        $sql .= "numero_colis,";
        $sql .= "poids_max,";
        $sql .= "poids_total,";
        $sql .= "multiple_colis,";
        $sql .= "status,";
        $sql .= "date_creation,";
        $sql .= "fk_user_creat,";
        $sql .= "active";
        $sql .= ") VALUES (";
        $sql .= "".(int) $this->fk_session.",";
        $sql .= "".(int) $this->numero_colis.",";
        $sql .= "".(float) $this->poids_max.",";
        $sql .= "".(float) $this->poids_total.",";
        $sql .= "".(int) $this->multiple_colis.",";
        $sql .= "'".$this->db->escape($this->status)."',";
        $sql .= "'".$this->db->idate(dol_now())."',";
        $sql .= "".(int) $user->id.",";
        $sql .= "".(int) $this->active;
        $sql .= ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param int $id Id object
     * @return int <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id)
    {
        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.fk_session,";
        $sql .= " t.numero_colis,";
        $sql .= " t.poids_max,";
        $sql .= " t.poids_total,";
        $sql .= " t.multiple_colis,";
        $sql .= " t.status,";
        $sql .= " t.date_creation,";
        $sql .= " t.tms,";
        $sql .= " t.fk_user_creat,";
        $sql .= " t.fk_user_modif,";
        $sql .= " t.active";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.rowid = ".((int) $id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->rowid = $obj->rowid;
                $this->fk_session = $obj->fk_session;
                $this->numero_colis = $obj->numero_colis;
                $this->poids_max = $obj->poids_max;
                $this->poids_total = $obj->poids_total;
                $this->multiple_colis = $obj->multiple_colis;
                $this->status = $obj->status;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->tms = $this->db->jdate($obj->tms);
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;
                $this->active = $obj->active;

                // Load lines
                $this->fetchLines();
            }
            $this->db->free($resql);

            if ($numrows) {
                return 1;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Load object lines in memory from the database
     *
     * @return int <0 if KO, >0 if OK
     */
    public function fetchLines()
    {
        $this->lines = array();

        $sql = "SELECT l.rowid, l.fk_colis, l.fk_product, l.quantite, l.poids_unitaire, l.poids_total, l.rang";
        $sql .= ", p.ref, p.label, p.weight";
        $sql .= " FROM ".MAIN_DB_PREFIX."ficheproduction_colis_line as l";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid";
        $sql .= " WHERE l.fk_colis = ".((int) $this->id);
        $sql .= " ORDER BY l.rang ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $line = new FicheProductionColisLine($this->db);
                $line->id = $obj->rowid;
                $line->fk_colis = $obj->fk_colis;
                $line->fk_product = $obj->fk_product;
                $line->quantite = $obj->quantite;
                $line->poids_unitaire = $obj->poids_unitaire;
                $line->poids_total = $obj->poids_total;
                $line->rang = $obj->rang;
                $line->product_ref = $obj->ref;
                $line->product_label = $obj->label;
                $line->product_weight = $obj->weight;

                $this->lines[] = $line;
                $i++;
            }
            $this->db->free($resql);
            return 1;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param User $user User that modifies
     * @param bool $notrigger false=launch triggers after, true=disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = false)
    {
        $error = 0;

        // Clean parameters
        if (isset($this->poids_max)) {
            $this->poids_max = trim($this->poids_max);
        }
        if (isset($this->poids_total)) {
            $this->poids_total = trim($this->poids_total);
        }
        if (isset($this->multiple_colis)) {
            $this->multiple_colis = (int) $this->multiple_colis;
        }
        if (isset($this->status)) {
            $this->status = trim($this->status);
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= " poids_max = ".(float) $this->poids_max.",";
        $sql .= " poids_total = ".(float) $this->poids_total.",";
        $sql .= " multiple_colis = ".(int) $this->multiple_colis.",";
        $sql .= " status = '".$this->db->escape($this->status)."',";
        $sql .= " fk_user_modif = ".(int) $user->id;
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * Delete object in database
     *
     * @param User $user User that deletes
     * @param bool $notrigger false=launch triggers after, true=disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        $error = 0;

        $this->db->begin();

        // Delete lines first
        foreach ($this->lines as $line) {
            $result = $line->delete($user);
            if ($result < 0) {
                $error++;
                $this->errors[] = $line->error;
            }
        }

        if (!$error) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
            $sql .= " WHERE rowid = ".((int) $this->id);

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error ".$this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * Get all colis for a session
     *
     * @param int $fk_session Session ID
     * @return array Array of colis objects
     */
    public function fetchAllBySession($fk_session)
    {
        $colis = array();

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_session = ".((int) $fk_session);
        $sql .= " AND active = 1";
        $sql .= " ORDER BY numero_colis ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $coli = new FicheProductionColis($this->db);
                if ($coli->fetch($obj->rowid) > 0) {
                    $colis[] = $coli;
                }
            }
            $this->db->free($resql);
        }

        return $colis;
    }

    /**
     * Add a product line to the colis
     *
     * @param int $fk_product Product ID
     * @param int $quantite Quantity
     * @param float $poids_unitaire Unit weight
     * @param User $user User object
     * @return int <0 if KO, >0 if OK
     */
    public function addLine($fk_product, $quantite, $poids_unitaire, User $user)
    {
        require_once DOL_DOCUMENT_ROOT.'/custom/ficheproduction/class/ficheproductioncolisline.class.php';

        $line = new FicheProductionColisLine($this->db);
        $line->fk_colis = $this->id;
        $line->fk_product = $fk_product;
        $line->quantite = $quantite;
        $line->poids_unitaire = $poids_unitaire;
        $line->poids_total = $quantite * $poids_unitaire;
        $line->rang = count($this->lines);
        $line->date_creation = dol_now();
        $line->fk_user_creat = $user->id;

        $result = $line->create($user);
        if ($result > 0) {
            // Update total weight of colis
            $this->poids_total += $line->poids_total;
            $this->update($user, true);

            // Reload lines
            $this->fetchLines();
        }

        return $result;
    }

    /**
     * Remove a product line from the colis
     *
     * @param int $line_id Line ID
     * @param User $user User object
     * @return int <0 if KO, >0 if OK
     */
    public function removeLine($line_id, User $user)
    {
        // Find the line
        $line_to_remove = null;
        foreach ($this->lines as $line) {
            if ($line->id == $line_id) {
                $line_to_remove = $line;
                break;
            }
        }

        if (!$line_to_remove) {
            return -1;
        }

        // Update total weight
        $this->poids_total -= $line_to_remove->poids_total;
        $this->update($user, true);

        // Delete the line
        $result = $line_to_remove->delete($user);
        if ($result > 0) {
            // Reload lines
            $this->fetchLines();
        }

        return $result;
    }

    /**
     * Update line quantity
     *
     * @param int $line_id Line ID
     * @param int $new_quantity New quantity
     * @param User $user User object
     * @return int <0 if KO, >0 if OK
     */
    public function updateLineQuantity($line_id, $new_quantity, User $user)
    {
        // Find the line
        $line_to_update = null;
        foreach ($this->lines as $line) {
            if ($line->id == $line_id) {
                $line_to_update = $line;
                break;
            }
        }

        if (!$line_to_update) {
            return -1;
        }

        // Calculate weight difference
        $old_weight = $line_to_update->poids_total;
        $line_to_update->quantite = $new_quantity;
        $line_to_update->poids_total = $new_quantity * $line_to_update->poids_unitaire;
        $weight_diff = $line_to_update->poids_total - $old_weight;

        // Update the line
        $result = $line_to_update->update($user);
        if ($result > 0) {
            // Update total weight of colis
            $this->poids_total += $weight_diff;
            $this->update($user, true);
        }

        return $result;
    }
}