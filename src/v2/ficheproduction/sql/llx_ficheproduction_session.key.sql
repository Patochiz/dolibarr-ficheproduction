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

-- Clés et index pour llx_ficheproduction_session
ALTER TABLE llx_ficheproduction_session ADD INDEX idx_ficheproduction_session_fk_commande (fk_commande);
ALTER TABLE llx_ficheproduction_session ADD INDEX idx_ficheproduction_session_fk_soc (fk_soc);
ALTER TABLE llx_ficheproduction_session ADD INDEX idx_ficheproduction_session_ref (ref);
ALTER TABLE llx_ficheproduction_session ADD CONSTRAINT fk_ficheproduction_session_fk_commande FOREIGN KEY (fk_commande) REFERENCES llx_commande(rowid);
ALTER TABLE llx_ficheproduction_session ADD CONSTRAINT fk_ficheproduction_session_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid);
ALTER TABLE llx_ficheproduction_session ADD CONSTRAINT fk_ficheproduction_session_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_ficheproduction_session ADD CONSTRAINT fk_ficheproduction_session_fk_user_modif FOREIGN KEY (fk_user_modif) REFERENCES llx_user(rowid);