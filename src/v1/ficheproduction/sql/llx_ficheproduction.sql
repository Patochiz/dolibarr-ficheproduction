-- Copyright (C) 2024 SuperAdmin
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

CREATE TABLE llx_ficheproduction(
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    ref varchar(128) NOT NULL,
    fk_soc integer NOT NULL,
    fk_commande integer NOT NULL,
    fk_product integer NOT NULL,
    nombre integer DEFAULT NULL,
    longueur integer DEFAULT NULL,
    largeur integer DEFAULT NULL,
    quantite decimal(10,3) DEFAULT NULL,
    couleur varchar(64) DEFAULT NULL,
    date_creation datetime NOT NULL,
    tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer,
    import_key varchar(14),
    active integer DEFAULT 1 NOT NULL,
    status smallint DEFAULT 0 NOT NULL,
    note_public text,
    note_private text,
    colisage_data text
) ENGINE=innodb;