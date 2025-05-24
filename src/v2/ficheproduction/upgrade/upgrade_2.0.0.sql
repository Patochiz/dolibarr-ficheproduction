-- Upgrade script from any previous version to v2.0.0
-- This script handles the migration to the new architecture

-- Create new tables if they don't exist
CREATE TABLE IF NOT EXISTS llx_ficheproduction_session(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    ref varchar(128) NOT NULL,
    fk_soc integer NOT NULL,
    fk_commande integer NOT NULL,
    ref_chantier varchar(255) DEFAULT NULL,
    commentaires text DEFAULT NULL,
    date_creation datetime NOT NULL,
    tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    status smallint DEFAULT 1 NOT NULL,
    active integer DEFAULT 1 NOT NULL
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_ficheproduction_colis(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    fk_session integer NOT NULL,
    numero_colis integer NOT NULL,
    poids_max decimal(10,3) DEFAULT 25.000 NOT NULL,
    poids_total decimal(10,3) DEFAULT 0.000 NOT NULL,
    multiple_colis integer DEFAULT 1 NOT NULL,
    status varchar(32) DEFAULT 'ok' NOT NULL,
    date_creation datetime NOT NULL,
    tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    active integer DEFAULT 1 NOT NULL
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_ficheproduction_colis_line(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    fk_colis integer NOT NULL,
    fk_product integer NOT NULL,
    quantite integer NOT NULL,
    poids_unitaire decimal(10,3) NOT NULL,
    poids_total decimal(10,3) NOT NULL,
    rang integer DEFAULT 0 NOT NULL,
    date_creation datetime NOT NULL,
    tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer
) ENGINE=innodb;

-- Add indexes
ALTER TABLE llx_ficheproduction_session ADD INDEX IF NOT EXISTS idx_ficheproduction_session_fk_commande (fk_commande);
ALTER TABLE llx_ficheproduction_session ADD INDEX IF NOT EXISTS idx_ficheproduction_session_fk_soc (fk_soc);
ALTER TABLE llx_ficheproduction_session ADD INDEX IF NOT EXISTS idx_ficheproduction_session_ref (ref);

ALTER TABLE llx_ficheproduction_colis ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_fk_session (fk_session);
ALTER TABLE llx_ficheproduction_colis ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_numero (numero_colis);

ALTER TABLE llx_ficheproduction_colis_line ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_line_fk_colis (fk_colis);
ALTER TABLE llx_ficheproduction_colis_line ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_line_fk_product (fk_product);
ALTER TABLE llx_ficheproduction_colis_line ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_line_rang (rang);

-- Check if old table exists (from v1)
SET @old_table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'llx_ficheproduction'
);

-- If old table exists, we could migrate data (but we decided not to for this version)
-- The old table can be renamed for backup purposes
SET @sql = IF(@old_table_exists > 0,
    'RENAME TABLE llx_ficheproduction TO llx_ficheproduction_v1_backup',
    'SELECT "No old table to backup"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new configuration constants if they don't exist
INSERT IGNORE INTO llx_const (name, value, type, note, visible, entity) VALUES
('FICHEPRODUCTION_POIDS_MAX_COLIS', '25', 'chaine', 'Poids maximum par défaut pour un colis (kg)', 1, 0),
('FICHEPRODUCTION_AUTO_CREATE_SESSION', '1', 'chaine', 'Créer automatiquement une session de colisage pour les nouvelles commandes', 1, 0);

-- Update module version
UPDATE llx_const 
SET value = '2.0.0' 
WHERE name = 'MAIN_MODULE_FICHEPRODUCTION_VERSION';

-- Log the upgrade
INSERT INTO llx_events (type, entity, dateevent, fk_user, description) VALUES
('MODULE_UPGRADE', 0, NOW(), 1, 'Module Fiche de Production upgraded to v2.0.0');

-- Success message
SELECT 'Module Fiche de Production successfully upgraded to v2.0.0' as message;
SELECT 'Old data backed up to llx_ficheproduction_v1_backup if existed' as backup_info;
SELECT 'New architecture with 3 normalized tables is ready' as architecture_info;