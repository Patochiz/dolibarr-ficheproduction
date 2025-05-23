-- Demo data for Fiche de Production v2.0
-- This file contains sample data for testing the module

-- Insert demo session (assuming order ID 1 exists)
INSERT IGNORE INTO llx_ficheproduction_session (
    ref, fk_soc, fk_commande, ref_chantier, commentaires, 
    date_creation, fk_user_creat, status, active
) VALUES (
    'FP0001', 1, 1, 'CHANTIER-2025-001', 'Session de démonstration pour tests',
    NOW(), 1, 1, 1
);

-- Get the session ID
SET @session_id = LAST_INSERT_ID();

-- Insert demo colis
INSERT IGNORE INTO llx_ficheproduction_colis (
    fk_session, numero_colis, poids_max, poids_total, multiple_colis,
    status, date_creation, fk_user_creat, active
) VALUES 
(
    @session_id, 1, 25.000, 18.500, 1,
    'ok', NOW(), 1, 1
),
(
    @session_id, 2, 25.000, 22.800, 2,
    'warning', NOW(), 1, 1
),
(
    @session_id, 3, 25.000, 12.300, 1,
    'ok', NOW(), 1, 1
);

-- Get colis IDs
SET @colis1_id = (SELECT rowid FROM llx_ficheproduction_colis WHERE fk_session = @session_id AND numero_colis = 1);
SET @colis2_id = (SELECT rowid FROM llx_ficheproduction_colis WHERE fk_session = @session_id AND numero_colis = 2);
SET @colis3_id = (SELECT rowid FROM llx_ficheproduction_colis WHERE fk_session = @session_id AND numero_colis = 3);

-- Insert demo colis lines (assuming products with IDs 1-6 exist)
INSERT IGNORE INTO llx_ficheproduction_colis_line (
    fk_colis, fk_product, quantite, poids_unitaire, poids_total,
    rang, date_creation, fk_user_creat
) VALUES 
-- Colis 1
(@colis1_id, 1, 5, 2.5, 12.5, 0, NOW(), 1),
(@colis1_id, 2, 2, 3.0, 6.0, 1, NOW(), 1),

-- Colis 2  
(@colis2_id, 3, 8, 1.8, 14.4, 0, NOW(), 1),
(@colis2_id, 4, 3, 2.8, 8.4, 1, NOW(), 1),

-- Colis 3
(@colis3_id, 5, 15, 0.1, 1.5, 0, NOW(), 1),
(@colis3_id, 6, 12, 0.9, 10.8, 1, NOW(), 1);

-- Note: This demo data assumes:
-- - Order with ID 1 exists
-- - Products with IDs 1-6 exist 
-- - User with ID 1 exists
-- - Society with ID 1 exists
--
-- Adjust the IDs according to your actual data
--
-- To clean demo data, run:
-- DELETE FROM llx_ficheproduction_colis_line WHERE fk_colis IN (SELECT rowid FROM llx_ficheproduction_colis WHERE fk_session = @session_id);
-- DELETE FROM llx_ficheproduction_colis WHERE fk_session = @session_id;
-- DELETE FROM llx_ficheproduction_session WHERE ref = 'FP0001';