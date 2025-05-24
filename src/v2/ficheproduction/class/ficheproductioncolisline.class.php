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
 * \file        class/ficheproductioncolisline.class.php
 * \ingroup     ficheproduction
 * \brief       This file is a CRUD class file for FicheProductionColisLine (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for FicheProductionColisLine
 */
class FicheProductionColisLine extends CommonObject
{
    /**
     * @var string ID of module.
     */
    public $module = 'ficheproduction';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'ficheproductioncolisline';

    /**
     * @var string Name of table without prefix where object is stored.
     */
    public $table_element = 'ficheproduction_colis_line';

    /**
     * @var int Does this object support multicompany module ?
     */
    public $ismultientitymanaged = 0;

    public $rowid;
    public $fk_colis;
    public $fk_product;
    public $is_libre_product;
    public $libre_product_name;
    public $libre_product_description;
    public $quantite;
    public $poids_unitaire;
    public $poids_total;
    public $rang;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;

    // Product info (loaded via fetchLines)
    public $product_ref;
    public $product_label;
    public $product_weight;

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
        $error = 0;

        // Clean parameters
        if (isset($this->fk_colis)) {
            $this->fk_colis = (int) $this->fk_colis;
        }
        if (isset($this->fk_product)) {
            $this->fk_product = (int) $this->fk_product;
        }
        if (isset($this->is_libre_product)) {
            $this->is_libre_product = (int) $this->is_libre_product;
        }
        if (isset($this->libre_product_name)) {
            $this->libre_product_name = trim($this->libre_product_name);
        }
        if (isset($this->libre_product_description)) {
            $this->libre_product_description = trim($this->libre_product_description);
        }
        if (isset($this->quantite)) {
            $this->quantite = (int) $this->quantite;
        }
        if (isset($this->poids_unitaire)) {
            $this->poids_unitaire = trim($this->poids_unitaire);
        }
        if (isset($this->poids_total)) {
            $this->poids_total = trim($this->poids_total);
        }
        if (isset($this->rang)) {
            $this->rang = (int) $this->rang;
        }

        // Check parameters
        if (!$this->fk_colis) {
            $this->errors[] = 'Error: fk_colis is mandatory';
            return -1;
        }
        
        // Validation selon le type de produit
        if ($this->is_libre_product) {
            if (empty($this->libre_product_name)) {
                $this->errors[] = 'Error: libre_product_name is mandatory for free products';
                return -1;
            }
        } else {
            if (!$this->fk_product) {
                $this->errors[] = 'Error: fk_product is mandatory for standard products';
                return -1;
            }
        }
        
        if (!$this->quantite) {
            $this->errors[] = 'Error: quantite is mandatory';
            return -1;
        }

        // Set default values
        if (!isset($this->is_libre_product)) {
            $this->is_libre_product = 0;
        }
        if (!isset($this->rang)) {
            $this->rang = 0;
        }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= "fk_colis,";
        $sql .= "fk_product,";
        $sql .= "is_libre_product,";
        $sql .= "libre_product_name,";
        $sql .= "libre_product_description,";
        $sql .= "quantite,";
        $sql .= "poids_unitaire,";
        $sql .= "poids_total,";
        $sql .= "rang,";
        $sql .= "date_creation,";
        $sql .= "fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= "".(int) $this->fk_colis.",";
        $sql .= ($this->fk_product ? "".(int) $this->fk_product : "NULL").",";
        $sql .= "".(int) $this->is_libre_product.",";
        $sql .= ($this->libre_product_name ? "'".$this->db->escape($this->libre_product_name)."'" : "NULL").",";
        $sql .= ($this->libre_product_description ? "'".$this->db->escape($this->libre_product_description)."'" : "NULL").",";
        $sql .= "".(int) $this->quantite.",";
        $sql .= "".(float) $this->poids_unitaire.",";
        $sql .= "".(float) $this->poids_total.",";
        $sql .= "".(int) $this->rang.",";
        $sql .= "'".$this->db->idate(dol_now())."',";
        $sql .= "".(int) $user->id;
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
        $sql .= " t.fk_colis,";
        $sql .= " t.fk_product,";
        $sql .= " t.is_libre_product,";
        $sql .= " t.libre_product_name,";
        $sql .= " t.libre_product_description,";
        $sql .= " t.quantite,";
        $sql .= " t.poids_unitaire,";
        $sql .= " t.poids_total,";
        $sql .= " t.rang,";
        $sql .= " t.date_creation,";
        $sql .= " t.tms,";
        $sql .= " t.fk_user_creat,";
        $sql .= " t.fk_user_modif";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " WHERE t.rowid = ".((int) $id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->rowid = $obj->rowid;
                $this->fk_colis = $obj->fk_colis;
                $this->fk_product = $obj->fk_product;
                $this->is_libre_product = $obj->is_libre_product;
                $this->libre_product_name = $obj->libre_product_name;
                $this->libre_product_description = $obj->libre_product_description;
                $this->quantite = $obj->quantite;
                $this->poids_unitaire = $obj->poids_unitaire;
                $this->poids_total = $obj->poids_total;
                $this->rang = $obj->rang;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->tms = $this->db->jdate($obj->tms);
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;
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
        if (isset($this->is_libre_product)) {
            $this->is_libre_product = (int) $this->is_libre_product;
        }
        if (isset($this->libre_product_name)) {
            $this->libre_product_name = trim($this->libre_product_name);
        }
        if (isset($this->libre_product_description)) {
            $this->libre_product_description = trim($this->libre_product_description);
        }
        if (isset($this->quantite)) {
            $this->quantite = (int) $this->quantite;
        }
        if (isset($this->poids_unitaire)) {
            $this->poids_unitaire = trim($this->poids_unitaire);
        }
        if (isset($this->poids_total)) {
            $this->poids_total = trim($this->poids_total);
        }
        if (isset($this->rang)) {
            $this->rang = (int) $this->rang;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= " is_libre_product = ".(int) $this->is_libre_product.",";
        $sql .= " libre_product_name = ".($this->libre_product_name ? "'".$this->db->escape($this->libre_product_name)."'" : "NULL").",";
        $sql .= " libre_product_description = ".($this->libre_product_description ? "'".$this->db->escape($this->libre_product_description)."'" : "NULL").",";
        $sql .= " quantite = ".(int) $this->quantite.",";
        $sql .= " poids_unitaire = ".(float) $this->poids_unitaire.",";
        $sql .= " poids_total = ".(float) $this->poids_total.",";
        $sql .= " rang = ".(int) $this->rang.",";
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

        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
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
     * Create a free product line
     *
     * @param int $fk_colis Colis ID
     * @param string $name Product name
     * @param string $description Product description
     * @param int $quantite Quantity
     * @param float $poids_unitaire Unit weight
     * @param User $user User object
     * @return int <0 if KO, line ID if OK
     */
    public function createFreeLine($fk_colis, $name, $description, $quantite, $poids_unitaire, User $user)
    {
        $this->fk_colis = $fk_colis;
        $this->fk_product = null;
        $this->is_libre_product = 1;
        $this->libre_product_name = $name;
        $this->libre_product_description = $description;
        $this->quantite = $quantite;
        $this->poids_unitaire = $poids_unitaire;
        $this->poids_total = $quantite * $poids_unitaire;
        $this->rang = 0; // Will be set by colis manager
        $this->date_creation = dol_now();
        $this->fk_user_creat = $user->id;

        return $this->create($user);
    }

    /**
     * Get display name for the line (product name or free product name)
     *
     * @return string Display name
     */
    public function getDisplayName()
    {
        if ($this->is_libre_product) {
            return $this->libre_product_name;
        } else {
            return $this->product_label ? $this->product_label : $this->product_ref;
        }
    }

    /**
     * Check if this line is a free product
     *
     * @return bool
     */
    public function isFreeProduct()
    {
        return (bool) $this->is_libre_product;
    }
}
