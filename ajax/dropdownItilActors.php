<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

// Make a select box
if (isset($_POST["type"])
    && isset($_POST["actortype"])
    && isset($_POST["itemtype"])) {
   $rand = mt_rand();
   if ($item = getItemForItemtype($_POST["itemtype"])) {
      switch ($_POST["type"]) {
         case "user" :
            $right = 'all';
            // Only steal or own ticket whit empty assign
            if ($_POST["actortype"] == 'assign') {
               $right = "own_ticket";
               if (!$item->canAssign()) {
                  $right = 'id';
               }
            }

            $options = array('name'        => '_itil_'.$_POST["actortype"].'[users_id]',
                             'entity'      => $_POST['entity_restrict'],
                             'right'       => $right,
                             'rand'        => $rand,
                             'ldap_import' => true);

            if ($CFG_GLPI["use_mailing"]) {
               $withemail     = (isset($_POST["allow_email"]) ? $_POST["allow_email"] : false);
               $paramscomment = array('value'       => '__VALUE__',
                                      'allow_email' => $withemail,
                                      'field'       => "_itil_".$_POST["actortype"]);
               // Fix rand value
               $options['rand']     = $rand;
               $options['toupdate'] = array('value_fieldname' => 'value',
                                            'to_update'       => "notif_user_$rand",
                                            'url'             => $CFG_GLPI["root_doc"].
                                                                     "/ajax/uemailUpdate.php",
                                            'moreparams'      => $paramscomment);
            }

            if (($_POST["itemtype"] == 'Ticket')
                && ($_POST["actortype"] == 'assign')) {
               $toupdate = array();
               if (isset($options['toupdate']) && is_array($options['toupdate'])) {
                  $toupdate[] = $options['toupdate'];
               }
               $toupdate[] = array('value_fieldname' => 'value',
                                   'to_update'       => "countassign_$rand",
                                   'url'             => $CFG_GLPI["root_doc"].
                                                            "/ajax/ticketassigninformation.php",
                                   'moreparams'      => array('users_id_assign' => '__VALUE__'));
               $options['toupdate'] = $toupdate;
            }

            $rand = User::dropdown($options);


            // Display active tickets for a tech
            // Need to update information on dropdown changes
            if (($_POST["itemtype"] == 'Ticket')
                && ($_POST["actortype"] == 'assign')) {
               echo "<br><span id='countassign_$rand'>--";
               echo "</span>";
            }

            if ($CFG_GLPI["use_mailing"] == 1) {
               echo "<br><span id='notif_user_$rand'>";
               if ($withemail) {
                  echo __('Email followup').'&nbsp;';
                  $rand = Dropdown::showYesNo('_itil_'.$_POST["actortype"].'[use_notification]', 1);
                  echo '<br>';
                  printf(__('%1$s: %2$s'),__('Email'),
                         "<input type='text' size='25' name='_itil_".$_POST["actortype"].
                           "[alternative_email]'>");
               }
               echo "</span>";
            }
            break;

         case "group" :

            $cond  = (($_POST["actortype"] == 'assign') ? $cond = '`is_assign`'
                                                        : $cond = '`is_requester`');
            $param = array('name'      => '_itil_'.$_POST["actortype"].'[groups_id]',
                           'entity'    => $_POST['entity_restrict'],
                           'condition' => $cond,
                           'rand'      => $rand);
            if (($_POST["itemtype"] == 'Ticket')
                && ($_POST["actortype"] == 'assign')) {
               $param['toupdate'] = array('value_fieldname' => 'value',
                                          'to_update'       => "countgroupassign_$rand",
                                          'url'             => $CFG_GLPI["root_doc"].
                                                                  "/ajax/ticketassigninformation.php",
                                          'moreparams'      => array('groups_id_assign'
                                                                        => '__VALUE__'));
            }

            $rand = Group::dropdown($param);

            if (($_POST["itemtype"] == 'Ticket')
                && ($_POST["actortype"] == 'assign')) {
               echo "<br><span id='countgroupassign_$rand'>";
               echo "</span>";
            }

            break;

         case "supplier" :

            $param = array('name'      => '_itil_'.$_POST["actortype"].'[suppliers_id]',
                           'entity'    => $_POST['entity_restrict'],
                           'rand'      => $rand);


            $rand = Supplier::dropdown($param);
            break;


      }
   }
}
?>
