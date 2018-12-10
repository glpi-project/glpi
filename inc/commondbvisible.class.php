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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Common DataBase visibility for items
 */
abstract class CommonDBVisible extends CommonDBTM {

   /**
    * Is the login user have access to item based on visibility configuration
    *
    * @since 0.83
    * @since 9.2 moved from each class to parent class
    *
    * @return boolean
   **/
   public function haveVisibilityAccess() {
      // Author
      if ($this->fields['users_id'] == Session::getLoginUserID()) {
         return true;
      }
      // Users
      if (isset($this->users[Session::getLoginUserID()])) {
         return true;
      }

      // Groups
      if (count($this->groups)
          && isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
         foreach ($this->groups as $data) {
            foreach ($data as $group) {
               if (in_array($group['groups_id'], $_SESSION["glpigroups"])) {
                  // All the group
                  if ($group['entities_id'] < 0) {
                     return true;
                  }
                  // Restrict to entities
                  if (Session::haveAccessToEntity($group['entities_id'], $group['is_recursive'])) {
                     return true;
                  }
               }
            }
         }
      }

      // Entities
      if (count($this->entities)
          && isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         foreach ($this->entities as $data) {
            foreach ($data as $entity) {
               if (Session::haveAccessToEntity($entity['entities_id'], $entity['is_recursive'])) {
                  return true;
               }
            }
         }
      }

      // Profiles
      if (count($this->profiles)
          && isset($_SESSION["glpiactiveprofile"])
          && isset($_SESSION["glpiactiveprofile"]['id'])) {
         if (isset($this->profiles[$_SESSION["glpiactiveprofile"]['id']])) {
            foreach ($this->profiles[$_SESSION["glpiactiveprofile"]['id']] as $profile) {
               // All the profile
               if ($profile['entities_id'] < 0) {
                  return true;
               }
               // Restrict to entities
               if (Session::haveAccessToEntity($profile['entities_id'], $profile['is_recursive'])) {
                  return true;
               }
            }
         }
      }

      return false;
   }

   /**
    * Count visibilities
    *
    * @since 0.83
    * @since 9.2 moved from each class to parent class
    *
    * @return integer
    */
   public function countVisibilities() {

      return (count($this->entities)
              + count($this->users)
              + count($this->groups)
              + count($this->profiles));
   }

   /**
    * Show visibility configuration
    *
    * @since 9.2 moved from each class to parent class
    *
    * @return void
   **/
   public function showVisibility() {
      global $CFG_GLPI;

      $ID      = $this->fields['id'];
      $canedit = $this->canEdit($ID);
      $rand    = mt_rand();
      $nb      = $this->countVisibilities();
      $str_type = strtolower($this::getType());

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='{$str_type}visibility_form$rand' id='{$str_type}visibility_form$rand' ";
         echo " method='post' action='".static::getFormURL()."'>";
         echo "<input type='hidden' name='{$str_type}s_id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Add a target')."</tr>";
         echo "<tr class='tab_bg_1'><td class='tab_bg_2' width='100px'>";

         $types   = ['Entity', 'Group', 'Profile', 'User'];

         $addrand = Dropdown::showItemTypes('_type', $types);
         $params = $this->getShowVisibilityDropdownParams();

         Ajax::updateItemOnSelectEvent("dropdown__type".$addrand, "visibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/visibility.php", $params);

         echo "</td>";
         echo "<td><span id='visibility$rand'></span>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed'
                              => min($_SESSION['glpilist_limit'], $nb),
                           'container'
                              => 'mass'.__CLASS__.$rand,
                           'specific_actions'
                              => ['delete' => _x('button', 'Delete permanently')]];

         if ($this->fields['users_id'] != Session::getLoginUserID()) {
            $massiveactionparams['confirm']
               = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
         }
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $nb) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>"._n('Recipient', 'Recipients', Session::getPluralNumber())."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      // Users
      if (count($this->users)) {
         foreach ($this->users as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox($this::getType() . '_User', $data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('User')."</td>";
               echo "<td>".getUserName($data['users_id'])."</td>";
               echo "</tr>";
            }
         }
      }

      // Groups
      if (count($this->groups)) {
         foreach ($this->groups as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('Group_' . $this::getType(), $data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Group')."</td>";

               $names   = Dropdown::getDropdownName('glpi_groups', $data['groups_id'], 1);
               $entname = sprintf(__('%1$s %2$s'), $names["name"],
                                  Html::showToolTip($names["comment"], ['display' => false]));
               if ($data['entities_id'] >= 0) {
                  $entname = sprintf(__('%1$s / %2$s'), $entname,
                                     Dropdown::getDropdownName('glpi_entities',
                                                               $data['entities_id']));
                  if ($data['is_recursive']) {
                     //TRANS: R for Recursive
                     $entname = sprintf(__('%1$s %2$s'),
                                        $entname, "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      // Entity
      if (count($this->entities)) {
         foreach ($this->entities as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('Entity_' . $this::getType(), $data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Entity')."</td>";
               $names   = Dropdown::getDropdownName('glpi_entities', $data['entities_id'], 1);
               $tooltip = Html::showToolTip($names["comment"], ['display' => false]);
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['is_recursive']) {
                  $entname = sprintf(__('%1$s %2$s'), $entname,
                                     "<span class='b'>(".__('R').")</span>");
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      // Profiles
      if (count($this->profiles)) {
         foreach ($this->profiles as $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  //Knowledgebase-specific case
                  if ($this::getType() === "KnowbaseItem") {
                     Html::showMassiveActionCheckBox($this::getType() . '_Profile', $data["id"]);
                  } else {
                     Html::showMassiveActionCheckBox('Profile_' . $this::getType(), $data["id"]);
                  }
                  echo "</td>";
               }
               echo "<td>"._n('Profile', 'Profiles', 1)."</td>";

               $names   = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id'], 1);
               $tooltip = Html::showToolTip($names["comment"], ['display' => false]);
               $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
               if ($data['entities_id'] >= 0) {
                  $entname = sprintf(__('%1$s / %2$s'), $entname,
                                     Dropdown::getDropdownName('glpi_entities',
                                                                $data['entities_id']));
                  if ($data['is_recursive']) {
                     $entname = sprintf(__('%1$s %2$s'), $entname,
                                        "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo "<td>".$entname."</td>";
               echo "</tr>";
            }
         }
      }

      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>";
      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

      return true;
   }

   /**
    * Get dropdown parameters from showVisibility method
    *
    * @return array
    */
   protected function getShowVisibilityDropdownParams() {
      return [
         'type'  => '__VALUE__',
         'right' => strtolower($this::getType()) . '_public'
      ];
   }
}
