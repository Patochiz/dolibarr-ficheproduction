-- Copyright (C) 2025
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- Clés et index pour llx_ficheproduction_colis_line
ALTER TABLE llx_ficheproduction_colis_line ADD INDEX idx_ficheproduction_colis_line_fk_colis (fk_colis);
ALTER TABLE llx_ficheproduction_colis_line ADD INDEX idx_ficheproduction_colis_line_fk_product (fk_product);
ALTER TABLE llx_ficheproduction_colis_line ADD INDEX idx_ficheproduction_colis_line_rang (rang);
ALTER TABLE llx_ficheproduction_colis_line ADD CONSTRAINT fk_ficheproduction_colis_line_fk_colis FOREIGN KEY (fk_colis) REFERENCES llx_ficheproduction_colis(rowid);
ALTER TABLE llx_ficheproduction_colis_line ADD CONSTRAINT fk_ficheproduction_colis_line_fk_product FOREIGN KEY (fk_product) REFERENCES llx_product(rowid);
ALTER TABLE llx_ficheproduction_colis_line ADD CONSTRAINT fk_ficheproduction_colis_line_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_ficheproduction_colis_line ADD CONSTRAINT fk_ficheproduction_colis_line_fk_user_modif FOREIGN KEY (fk_user_modif) REFERENCES llx_user(rowid);