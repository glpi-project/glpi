<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
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

   if (!$DB->fieldExists("printers", "ramSize", false)) {
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

   // Before 0.31
   if (!$DB->tableExists("glpi_config") && !$DB->tableExists("glpi_configs")) {
      $query = "CREATE TABLE `glpi_config` (
                  `ID` int(11) NOT NULL auto_increment,
                  `num_of_events` varchar(200) NOT NULL default '',
                  `jobs_at_login` varchar(200) NOT NULL default '',
                  `sendexpire` varchar(200) NOT NULL default '',
                  `cut` varchar(200) NOT NULL default '',
                  `expire_events` varchar(200) NOT NULL default '',
                  `list_limit` varchar(200) NOT NULL default '',
                  `version` varchar(200) NOT NULL default '',
                  `logotxt` varchar(200) NOT NULL default '',
                  `root_doc` varchar(200) NOT NULL default '',
                  `event_loglevel` varchar(200) NOT NULL default '',
                  `mailing` varchar(200) NOT NULL default '',
                  `imap_auth_server` varchar(200) NOT NULL default '',
                  `imap_host` varchar(200) NOT NULL default '',
                  `ldap_host` varchar(200) NOT NULL default '',
                  `ldap_basedn` varchar(200) NOT NULL default '',
                  `ldap_rootdn` varchar(200) NOT NULL default '',
                  `ldap_pass` varchar(200) NOT NULL default '',
                  `admin_email` varchar(200) NOT NULL default '',
                  `mailing_signature` varchar(200) NOT NULL default '',
                  `mailing_new_admin` varchar(200) NOT NULL default '',
                  `mailing_followup_admin` varchar(200) NOT NULL default '',
                  `mailing_finish_admin` varchar(200) NOT NULL default '',
                  `mailing_new_all_admin` varchar(200) NOT NULL default '',
                  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
                  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
                  `mailing_new_all_normal` varchar(200) NOT NULL default '',
                  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
                  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
                  `mailing_new_attrib` varchar(200) NOT NULL default '',
                  `mailing_followup_attrib` varchar(200) NOT NULL default '',
                  `mailing_finish_attrib` varchar(200) NOT NULL default '',
                  `mailing_new_user` varchar(200) NOT NULL default '',
                  `mailing_followup_user` varchar(200) NOT NULL default '',
                  `mailing_finish_user` varchar(200) NOT NULL default '',
                  `ldap_field_name` varchar(200) NOT NULL default '',
                  `ldap_field_email` varchar(200) NOT NULL default '',
                  `ldap_field_location` varchar(200) NOT NULL default '',
                  `ldap_field_realname` varchar(200) NOT NULL default '',
                  `ldap_field_phone` varchar(200) NOT NULL default '',
               PRIMARY KEY (`ID`)
               ) TYPE=MyISAM AUTO_INCREMENT=2 ";
      $DB->queryOrDie($query);

      $query = "INSERT INTO `glpi_config`
               VALUES (1, '10', '1', '1', '80', '30', '15', ' 0.31', 'GLPI powered by indepnet',
                        '/glpi', '5', '0', '', '', '', '', '', '', 'admsys@xxxxx.fr', 'SIGNATURE',
                        '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0','1', '1', '1',
                        'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber')";
      $DB->queryOrDie($query);

      echo "<p class='center'>Version > 0.31  </p>";
   }
}
