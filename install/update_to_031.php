<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

///update the database to the 0.31 version
function updateDbTo031() {
   global $DB;

   //amSize ramSize
   $query = "ALTER TABLE `users`
             DROP `can_assign_job`";
   $DB->queryOrDie($query);

   $query = "ALTER TABLE `users`
             ADD `can_assign_job enum('yes','no')` NOT NULL DEFAULT 'no'";
   $DB->queryOrDie($query);

   $query = "UPDATE `users`
             SET `can_assign_job` = 'yes'
             WHERE `type` = 'admin'";
   $DB->queryOrDie($query);

   echo "<p class='center'>Version 0.2 & < </p>";

   //Version 0.21 ajout du champ ramSize a la table printers si non existant.


   if (!FieldExists("printers", "ramSize", false)) {
      $query = "ALTER TABLE `printers`
                ADD `ramSize` varchar(6) NOT NULL default ''";
      $DB->queryOrDie($query);
   }

   echo "<p class='center'>Version 0.21  </p>";

   //Version 0.3
   //Ajout de NOT NULL et des valeurs par defaut.

   $query = "ALTER TABLE `computers`
             MODIFY `achat_date` `date` NOT NULL default '0000-00-00'";
   $DB->queryOrDie($query);
   $query = "ALTER TABLE `computers`
             MODIFY `date_fin_garantie` `date` NOT NULL default '0000-00-00'";

   $query = "ALTER TABLE `monitors`
             MODIFY `achat_date` `date` NOT NULL default '0000-00-00'";
   $DB->queryOrDie($query);
   $query = "ALTER TABLE `monitors`
             MODIFY `date_fin_garantie` `date` NOT NULL default '0000-00-00'";

   $query = "ALTER TABLE `networking`
             MODIFY `achat_date` `date` NOT NULL default '0000-00-00'";
   $DB->queryOrDie($query);
   $query = "ALTER TABLE `networking`
             MODIFY `date_fin_garantie` `date` NOT NULL default '0000-00-00'";

   $query = "ALTER TABLE `printers`
             MODIFY `achat_date` `date` NOT NULL default '0000-00-00'";
   $DB->queryOrDie($query);
   $query = "ALTER TABLE `printers`
             MODIFY `date_fin_garantie` `date` NOT NULL default '0000-00-00'";

   $query = "ALTER TABLE `templates`
             MODIFY `achat_date` `date` NOT NULL default '0000-00-00'";
   $DB->queryOrDie($query);
   $query = "ALTER TABLE `templates`
             MODIFY `date_fin_garantie` `date` NOT NULL default '0000-00-00'";

   echo "<p class='center'>Version 0.3  </p>";
}
?>