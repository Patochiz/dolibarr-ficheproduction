-- Migration pour supporter les produits libres
-- Ajouter les champs pour les produits libres

ALTER TABLE llx_ficheproduction_colis_line 
ADD COLUMN is_libre_product TINYINT(1) DEFAULT 0 NOT NULL AFTER fk_product,
ADD COLUMN libre_product_name VARCHAR(255) NULL AFTER is_libre_product,
ADD COLUMN libre_product_description TEXT NULL AFTER libre_product_name;

-- Modifier la contrainte pour permettre fk_product NULL pour les produits libres
ALTER TABLE llx_ficheproduction_colis_line 
MODIFY COLUMN fk_product INTEGER NULL;

-- Ajouter des index pour optimiser les requêtes
ALTER TABLE llx_ficheproduction_colis_line 
ADD INDEX idx_is_libre_product (is_libre_product),
ADD INDEX idx_fk_product_libre (fk_product, is_libre_product);
