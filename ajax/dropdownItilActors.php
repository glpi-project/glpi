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

            $options = ['name'        => '_itil_'.$_POST["actortype"].'[users_id]',
                             'entity'      => $_POST['entity_restrict'],
                             'right'       => $right,
                             'rand'        => $rand,
                             'ldap_import' => true];

            if ($CFG_GLPI["notifications_mailing"]) {
               $withemail     = (isset($_POST["allow_email"]) ? $_POST["allow_email"] : false);
               $paramscomment = ['value'       => '__VALUE__',
                                      'allow_email' => $withemail,
                                      'field'       => "_itil_".$_POST["actortype"],
                                      'use_notification' => $_POST["use_notif"]];
               // Fix rand value
               $options['rand']     = $rand;
               $options['toupdate'] = ['value_fieldname' => 'value',
                                            'to_update'       => "notif_user_$rand",
                                            'url'             => $CFG_GLPI["root_doc"].
                                                                     "/ajax/uemailUpdate.php",
                                            'moreparams'      => $paramscomment];
            }

            if (($_POST["itemtype"] == 'Ticket')
                && ($_POST["actortype"] == 'assign')) {
               $toupdate = [];
               if (isset($options['toupdate']) && is_array($options['toupdate'])) {
                  $toupdate[] = $options['toupdate'];
               }
               $toupdate[] = ['value_fieldname' => 'value',
                                   'to_update'       => "countassign_$rand",
                                   'url'             => $CFG_GLPI["root_doc"].
                                                            "/ajax/ticketassigninformation.php",
                                   'moreparams'      => ['users_id_assign' => '__VALUE__']];
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

            if ($CFG_GLPI["notifications_mailing"]) {
               echo "<br><span id='notif_user_$rand'>";
               if ($withemail) {
                  echo __('Email followup').'&nbsp;';
                  $rand = Dropdown::showYesNo('_itil_'.$_POST["actortype"].'[use_notification]', $_POST["use_notif"]);
                  echo '<br>';
                  printf(__('%1$s: %2$s'), __('Email'),
                         "<input type='text' size='25' name='_itil_".$_POST["actortype"].
                           "[alternative_email]'>");
               }
               echo "</span>";
            }
            break;

         case "group" :
            $cond = '`is_requester`';
            if ($_POST["actortype"] == 'assign') {
               $cond = '`is_assign`';
            }
            if ($_POST["actortype"] == 'observer') {
               $cond = '`is_watcher`';
            }

            $param = ['name'      => '_itil_'.$_POST["actortype"].'[groups_id]',
                           'entity'    => $_POST['entity_restrict'],
                           'condition' => $cond,
                           'rand'      => $rand];
            if (($_POST["itemtype"] == 'Ticket')
                && ($_POST["actortype"] == 'assign')) {
               $param['toupdate'] = ['value_fieldname' => 'value',
                                          'to_update'       => "countgroupassign_$rand",
                                          'url'             => $CFG_GLPI["root_doc"].
                                                                  "/ajax/ticketassigninformation.php",
                                          'moreparams'      => ['groups_id_assign'
                                                                        => '__VALUE__']];
            }

            $rand = Group::dropdown($param);

            if (($_POST["itemtype"] == 'Ticket')
                && ($_POST["actortype"] == 'assign')) {
               echo "<br><span id='countgroupassign_$rand'>";
               echo "</span>";
            }

            break;

         case "supplier" :
            $options = ['name'      => '_itil_'.$_POST["actortype"].'[suppliers_id]',
                             'entity'    => $_POST['entity_restrict'],
                             'rand'      => $rand];
            if ($CFG_GLPI["notifications_mailing"]) {
               $withemail     = (isset($_POST["allow_email"]) ? $_POST["allow_email"] : false);
               $paramscomment = ['value'       => '__VALUE__',
                                      'allow_email' => $withemail,
                                      'field'       => '_itil_'.$_POST["actortype"],
                                      'typefield'   => "supplier",
                                      'use_notification' => $_POST["use_notif"]];
               // Fix rand value
               $options['rand']     = $rand;
               $options['toupdate'] = ['value_fieldname' => 'value',
                                            'to_update'       => "notif_supplier_$rand",
                                            'url'             => $CFG_GLPI["root_doc"].
                                                                     "/ajax/uemailUpdate.php",
                                            'moreparams'      => $paramscomment];
            }
            if ($_POST["itemtype"] == 'Ticket') {
               $toupdate = [];
               if (isset($options['toupdate']) && is_array($options['toupdate'])) {
                  $toupdate[] = $options['toupdate'];
               }
               $toupdate[] = ['value_fieldname' => 'value',
                                   'to_update'       => "countassign_$rand",
                                   'url'             => $CFG_GLPI["root_doc"].
                                                            "/ajax/ticketassigninformation.php",
                                   'moreparams'      => ['suppliers_id_assign' => '__VALUE__']];
               $options['toupdate'] = $toupdate;
            }

            $rand = Supplier::dropdown($options);
            // Display active tickets for a supplier
            // Need to update information on dropdown changes
            if ($_POST["itemtype"] == 'Ticket') {
               echo "<span id='countassign_$rand'>";
               echo "</span>";
            }
            if ($CFG_GLPI["notifications_mailing"]) {
               echo "<br><span id='notif_supplier_$rand'>";
               if ($withemail) {
                  echo __('Email followup').'&nbsp;';
                  $rand = Dropdown::showYesNo('_itil_'.$_POST["actortype"].'[use_notification]', $_POST['use_notif']);
                  echo '<br>';
                  printf(__('%1$s: %2$s'), __('Email'),
                         "<input type='text' size='25' name='_itil_".$_POST["actortype"].
                           "[alternative_email]'>");
               }
               echo "</span>";
            }
            break;


      }
   }
}
