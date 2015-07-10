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

/**
 *  Class used to manage LDAP replicate config
**/
class AuthLdapReplicate extends CommonDBTM {

   static $rightname = 'config';


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return static::canUpdate();
   }


   /**
    * @since version 0.84
    *
    * @return string
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   function prepareInputForAdd($input) {

      if (isset($input["port"]) && (intval($input["port"]) == 0)) {
         $input["port"] = 389;
      }
      return $input;
   }


   function prepareInputForUpdate($input) {

      if (isset($input["port"]) && (intval($input["port"]) == 0)) {
         $input["port"] = 389;
      }
      return $input;
   }


   /**
    * Form to add a replicate to a ldap server
    *
    * @param $target       target page for add new replicate
    * @param $master_id    master ldap server ID
   **/
   static function addNewReplicateForm($target, $master_id) {

      echo "<form action='$target' method='post' name='add_replicate_form' id='add_replicate_form'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>".__('Add a LDAP directory replica'). "</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>".__('Name')."</td>";
      echo "<td class='center'>".__('Server')."</td>";
      echo "<td class='center'>".__('Port')."</td><td></td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'><input type='text' name='name'></td>";
      echo "<td class='center'><input type='text' name='host'></td>";
      echo "<td class='center'><input type='text' name='port'></td>";
      echo "<td class='center'><input type='hidden' name='next' value='extauth_ldap'>";
      echo "<input type='hidden' name='authldaps_id' value='$master_id'>";
      echo "<input type='submit' name='add_replicate' value='"._sx('button','Add') ."' class='submit'></td>";
      echo "</tr></table></div>";
      Html::closeForm();
   }

}
?>