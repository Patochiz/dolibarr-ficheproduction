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

-- Table des colis créés
CREATE TABLE llx_ficheproduction_colis(
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