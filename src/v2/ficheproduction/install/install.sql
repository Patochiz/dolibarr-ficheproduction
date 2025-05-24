-- Installation script for Fiche de Production v2.0
-- This file will be executed during module installation

-- Create session table
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

-- Create colis table
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

-- Create colis line table
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

-- Add indexes and constraints
ALTER TABLE llx_ficheproduction_session ADD INDEX IF NOT EXISTS idx_ficheproduction_session_fk_commande (fk_commande);
ALTER TABLE llx_ficheproduction_session ADD INDEX IF NOT EXISTS idx_ficheproduction_session_fk_soc (fk_soc);
ALTER TABLE llx_ficheproduction_session ADD INDEX IF NOT EXISTS idx_ficheproduction_session_ref (ref);

ALTER TABLE llx_ficheproduction_colis ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_fk_session (fk_session);
ALTER TABLE llx_ficheproduction_colis ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_numero (numero_colis);

ALTER TABLE llx_ficheproduction_colis_line ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_line_fk_colis (fk_colis);
ALTER TABLE llx_ficheproduction_colis_line ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_line_fk_product (fk_product);
ALTER TABLE llx_ficheproduction_colis_line ADD INDEX IF NOT EXISTS idx_ficheproduction_colis_line_rang (rang);

-- Add foreign keys if they don't exist
-- Note: We use IF NOT EXISTS equivalent for foreign keys by checking if constraint exists first

-- Session foreign keys
SET @sql = IF((
    SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE constraint_schema = DATABASE() 
    AND table_name = 'llx_ficheproduction_session' 
    AND constraint_name = 'fk_ficheproduction_session_fk_commande'
) = 0,
'ALTER TABLE llx_ficheproduction_session ADD CONSTRAINT fk_ficheproduction_session_fk_commande FOREIGN KEY (fk_commande) REFERENCES llx_commande(rowid)',
'SELECT "Foreign key already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colis foreign keys  
SET @sql = IF((
    SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE constraint_schema = DATABASE() 
    AND table_name = 'llx_ficheproduction_colis' 
    AND constraint_name = 'fk_ficheproduction_colis_fk_session'
) = 0,
'ALTER TABLE llx_ficheproduction_colis ADD CONSTRAINT fk_ficheproduction_colis_fk_session FOREIGN KEY (fk_session) REFERENCES llx_ficheproduction_session(rowid)',
'SELECT "Foreign key already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Colis line foreign keys
SET @sql = IF((
    SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE constraint_schema = DATABASE() 
    AND table_name = 'llx_ficheproduction_colis_line' 
    AND constraint_name = 'fk_ficheproduction_colis_line_fk_colis'
) = 0,
'ALTER TABLE llx_ficheproduction_colis_line ADD CONSTRAINT fk_ficheproduction_colis_line_fk_colis FOREIGN KEY (fk_colis) REFERENCES llx_ficheproduction_colis(rowid)',
'SELECT "Foreign key already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;