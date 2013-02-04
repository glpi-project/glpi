<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

// Make a select box
if (isset($_POST["type"]) && isset($_POST["actortype"])) {
   $rand = mt_rand();

   switch ($_POST["type"]) {
      case "user" :
         $right = 'all';
         /// TODO : review depending of itil object
         // Only steal or own ticket whit empty assign
         if ($_POST["actortype"]=='assign') {
            $right = "own_ticket";
            if (!Session::haveRight("assign_ticket","1")) {
               $right = 'id';
            }
         }

         $options = array('name'        => '_itil_'.$_POST["actortype"].'[users_id]',
                          'entity'      => $_POST['entity_restrict'],
                          'right'       => $right,
                          'ldap_import' => true);

         if ($CFG_GLPI["use_mailing"]) {
            $withemail = (isset($_POST["allow_email"]) ? $_POST["allow_email"] : false);
            $paramscomment = array('value'       => '__VALUE__',
                                   'allow_email' => $withemail,
                                   'field'       => "_itil_".$_POST["actortype"]);
            // Fix rand value
            $options['rand']     = $rand;
            $options['toupdate'] = array('value_fieldname' => 'value',
                                         'to_update'  => "notif_user_$rand",
                                         'url'        => $CFG_GLPI["root_doc"]."/ajax/uemailUpdate.php",
                                         'moreparams' => $paramscomment);
         }
         $rand = User::dropdown($options);

         if ($CFG_GLPI["use_mailing"]==1) {
            echo "<br><span id='notif_user_$rand'>";
            if ($withemail) {
               echo $LANG['job'][19].'&nbsp;:&nbsp;';
               $rand = Dropdown::showYesNo('_itil_'.$_POST["actortype"].'[use_notification]', 1);
               echo '<br>'.$LANG['mailing'][118].'&nbsp;:&nbsp;';
               echo "<input type='text' size='25' name='_itil_".$_POST["actortype"]."[alternative_email]'>";
            }
            echo "</span>";
         }
         break;

      case "group" :
         $cond = ($_POST["actortype"]=='assign' ? $cond = '`is_assign`' : $cond = '`is_requester`');
         Dropdown::show('Group', array('name'      => '_itil_'.$_POST["actortype"].'[groups_id]',
                                       'entity'    => $_POST['entity_restrict'],
                                       'condition' => $cond));
         break;

      case "supplier" :
         Dropdown::show('Supplier', array('name'   => 'suppliers_id_assign',
                                          'entity' => $_POST['entity_restrict']));
         break;
   }
}

?>
