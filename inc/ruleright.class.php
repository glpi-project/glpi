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
 * RuleRight Class
 *
 * Rule class for Rights management
**/
class RuleRight extends Rule {

   // From Rule
   static $rightname           = 'rule_ldap';
   public $orderby             = "name";
   public $specific_parameters = true;


   /**
    * @see Rule::maxActionsCount()
   **/
   function maxActionsCount() {
      // Unlimited
      return 4;
   }


   /**
    * @see Rule::showNewRuleForm()
   **/
   function showNewRuleForm($ID) {

      echo "<form method='post' action='".Toolbox::getItemTypeFormURL('Entity')."'>";
      echo "<table  class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" .__('Authorizations assignment rules') . "</th></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name') . "</td><td>";
      Html::autocompletionTextField($this, "name", ['value' => '',
                                                         'size'  => 33]);
      echo '</td><td>'.__('Description') . "</td><td>";
      Html::autocompletionTextField($this, "description", ['value' => '',
                                                                'size'  => 33]);
      echo "</td><td>".__('Logical operator') . "</td><td>";
      $this->dropdownRulesMatch();
      echo "</td><td rowspan='2' class='tab_bg_2 center middle'>";
      echo "<input type=hidden name='sub_type' value='" . get_class($this) . "'>";
      echo "<input type=hidden name='entities_id' value='-1'>";
      echo "<input type=hidden name='affectentity' value='$ID'>";
      echo "<input type=hidden name='_method' value='AddRule'>";
      echo "<input type='submit' name='execute' value=\""._sx('button', 'Add')."\" class='submit'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>"._n('Profile', 'Profiles', 1) . "</td><td>";
      Profile::dropdown();
      echo "</td><td><span class='small_space'>".__('Recursive') . "</span></td><td colspan='3'>";
      Dropdown::showYesNo("is_recursive", 0);
      echo "</td></tr>\n";

      echo "</table>";
      Html::closeForm();
   }


   function executeActions($output, $params, array $input = []) {
      global $CFG_GLPI;

      $entity       = '';
      $right        = '';
      $is_recursive = 0;
      $continue     = true;
      $output_src   = $output;

      if (count($this->actions)) {
         $entity = [];
         foreach ($this->actions as $action) {

            switch ($action->fields["action_type"]) {
               case "assign" :
                  switch ($action->fields["field"]) {
                     case "entities_id" :
                        $entity[] = $action->fields["value"];
                        break;

                     case "profiles_id" :
                        $right = $action->fields["value"];
                        break;

                     case "is_recursive" :
                        $is_recursive = $action->fields["value"];
                        break;

                     case '_entities_id_default':
                        $output['entities_id'] = $action->fields["value"];
                        break;

                     case '_profiles_id_default':
                        $output['profiles_id'] = $action->fields["value"];
                        break;

                     case "is_active" :
                        $output["is_active"] = $action->fields["value"];
                        break;

                     case 'timezone':
                        $output['timezone'] = $action->fields['value'];
                        break;

                     case "_ignore_user_import" :
                        $continue                   = false;
                        $output_src["_stop_import"] = true;
                        break;

                  } // switch (field)
                  break;

               case "regex_result" :
                  switch ($action->fields["field"]) {
                     case "_affect_entity_by_dn" :
                     case "_affect_entity_by_tag" :
                     case "_affect_entity_by_domain" :
                     case "_affect_entity_by_completename" :
                        foreach ($this->regex_results as $regex_result) {
                           $res = RuleAction::getRegexResultById($action->fields["value"],
                                                                 $regex_result);
                           if ($res != null) {
                              switch ($action->fields["field"]) {
                                 case "_affect_entity_by_dn" :
                                    $entity_found = Entity::getEntityIDByDN($res);
                                    break;

                                 case "_affect_entity_by_tag" :
                                    $entity_found = Entity::getEntityIDByTag($res);
                                    break;

                                 case "_affect_entity_by_domain" :
                                    $entity_found = Entity::getEntityIDByDomain($res);
                                    break;

                                 case "_affect_entity_by_completename" :
                                    $res          = Toolbox::unclean_cross_side_scripting_deep($res);
                                    $entity_found = Entity::getEntityIDByCompletename($res);
                                    break;

                                 default:
                                    $entity_found = -1;
                                    break;
                              }

                              //If an entity was found
                              if ($entity_found > -1) {
                                 $entity[] = $entity_found;
                              }
                           }
                        }

                        if (!count($entity)) {
                           //Not entity assigned : action processing must be stopped for this rule
                           $continue = false;
                        }
                        break;
                  } // switch (field)
                  break;

            } // switch (action_type)
         } // foreach (action)
      } // count (actions)

      if ($continue) {
         //Nothing to be returned by the function :
         //Store in session the entity and/or right
         if (count($entity)) {
            if ($right != '') {
               foreach ($entity as $entID) {
                  $output["_ldap_rules"]["rules_entities_rights"][] = [$entID, $right,
                                                                            $is_recursive];
               }
            } else {
               foreach ($entity as $entID) {
                  $output["_ldap_rules"]["rules_entities"][] = [$entID, $is_recursive];
               }
            }
         } else if ($right != '') {
            $output["_ldap_rules"]["rules_rights"][] = $right;
         }

         return $output;
      }
      return $output_src;
   }


   function getTitle() {
      return __('Automatic user assignment');
   }


   /**
    * @see Rule::getCriterias()
   **/
   function getCriterias() {
      static $criterias = [];

      if (!count($criterias)) {
         $criterias['common']                   = __('Global criteria');

         $criterias['LDAP_SERVER']['table']     = 'glpi_authldaps';
         $criterias['LDAP_SERVER']['field']     = 'name';
         $criterias['LDAP_SERVER']['name']      = __('LDAP directory');
         $criterias['LDAP_SERVER']['linkfield'] = '';
         $criterias['LDAP_SERVER']['type']      = 'dropdown';
         $criterias['LDAP_SERVER']['virtual']   = true;
         $criterias['LDAP_SERVER']['id']        = 'ldap_server';

         $criterias['MAIL_SERVER']['table']     = 'glpi_authmails';
         $criterias['MAIL_SERVER']['field']     = 'name';
         $criterias['MAIL_SERVER']['name']      = __('Email server');
         $criterias['MAIL_SERVER']['linkfield'] = '';
         $criterias['MAIL_SERVER']['type']      = 'dropdown';
         $criterias['MAIL_SERVER']['virtual']   = true;
         $criterias['MAIL_SERVER']['id']        = 'mail_server';

         $criterias['MAIL_EMAIL']['table']      = '';
         $criterias['MAIL_EMAIL']['field']      = '';
         $criterias['MAIL_EMAIL']['name']       = _n('Email', 'Emails', 1);
         $criterias['MAIL_EMAIL']['linkfield']  = '';
         $criterias['MAIL_EMAIL']['virtual']    = true;
         $criterias['MAIL_EMAIL']['id']         = 'mail_email';

         $criterias['LOGIN']['table']           = '';
         $criterias['LOGIN']['field']           = '';
         $criterias['LOGIN']['name']            = __('Login');
         $criterias['LOGIN']['linkfield']       = '';
         $criterias['LOGIN']['virtual']         = true;
         $criterias['LOGIN']['id']              = 'login';

         $criterias['GROUPS']['table']          = 'glpi_groups';
         $criterias['GROUPS']['field']          = 'completename';
         $criterias['GROUPS']['name']           = __('Imported group from an LDAP directory');
         $criterias['GROUPS']['linkfield']      = '';
         $criterias['GROUPS']['type']           = 'dropdown';
         $criterias['GROUPS']['virtual']        = true;
         $criterias['GROUPS']['id']             = 'groups';

         //Dynamically add all the ldap criterias to the current list of rule's criterias
         $this->addSpecificCriteriasToArray($criterias);
      }
      return $criterias;
   }


   /**
    * @see Rule::getActions()
   **/
   function getActions() {

      $actions                                              = [];

      $actions['entities_id']['name']                       = __('Entity');
      $actions['entities_id']['type']                       = 'dropdown';
      $actions['entities_id']['table']                      = 'glpi_entities';

      $actions['_affect_entity_by_dn']['name']              = __('Entity based on LDAP information');
      $actions['_affect_entity_by_dn']['type']              = 'text';
      $actions['_affect_entity_by_dn']['force_actions']     = ['regex_result'];
      $actions['_affect_entity_by_dn']['duplicatewith']     = 'entities_id';

      $actions['_affect_entity_by_tag']['name']             = __('Entity from TAG');
      $actions['_affect_entity_by_tag']['type']             = 'text';
      $actions['_affect_entity_by_tag']['force_actions']    = ['regex_result'];
      $actions['_affect_entity_by_tag']['duplicatewith']    = 'entities_id';

      $actions['_affect_entity_by_domain']['name']          = __('Entity from mail domain');
      $actions['_affect_entity_by_domain']['type']          = 'text';
      $actions['_affect_entity_by_domain']['force_actions'] = ['regex_result'];
      $actions['_affect_entity_by_domain']['duplicatewith'] = 'entities_id';

      $actions['_affect_entity_by_completename']['name']          = __('Entity from complete name');
      $actions['_affect_entity_by_completename']['type']          = 'text';
      $actions['_affect_entity_by_completename']['force_actions'] = ['regex_result'];
      $actions['_affect_entity_by_completename']['duplicatewith'] = 'entities_id';

      $actions['profiles_id']['name']                       = _n('Profile', 'Profiles', Session::getPluralNumber());
      $actions['profiles_id']['type']                       = 'dropdown';
      $actions['profiles_id']['table']                      = 'glpi_profiles';

      $actions['is_recursive']['name']                      = __('Recursive');
      $actions['is_recursive']['type']                      = 'yesno';
      $actions['is_recursive']['table']                     = '';

      $actions['is_active']['name']                         = __('Active');
      $actions['is_active']['type']                         = 'yesno';
      $actions['is_active']['table']                        = '';

      $actions['_ignore_user_import']['name']               = __('To be unaware of import');
      $actions['_ignore_user_import']['type']               = 'yesonly';
      $actions['_ignore_user_import']['table']              = '';

      $actions['_entities_id_default']['table']             = 'glpi_entities';
      $actions['_entities_id_default']['field']             = 'name';
      $actions['_entities_id_default']['name']              = __('Default entity');
      $actions['_entities_id_default']['linkfield']         = 'entities_id';
      $actions['_entities_id_default']['type']              = 'dropdown';

      $actions['groups_id']['table']                        = 'glpi_groups';
      $actions['groups_id']['field']                        = 'name';
      $actions['groups_id']['name']                         = __('Default group');
      $actions['groups_id']['linkfield']                    = 'groups_id';
      $actions['groups_id']['type']                         = 'dropdown';
      $actions['groups_id']['condition']                    = "`is_usergroup`='1'";

      $actions['_profiles_id_default']['table']             = 'glpi_profiles';
      $actions['_profiles_id_default']['field']             = 'name';
      $actions['_profiles_id_default']['name']              = __('Default profile');
      $actions['_profiles_id_default']['linkfield']         = 'profiles_id';
      $actions['_profiles_id_default']['type']              = 'dropdown';

      $actions['timezone']['name']                          = __('Timezone');
      $actions['timezone']['type']                          = 'timezone';

      return $actions;
   }

   function displayAdditionalRuleAction(array $action, $value = '') {
      global $DB;

      switch ($action['type']) {
         case 'timezone' :

            $timezones = $DB->getTimezones();
            Dropdown::showFromArray(
               'value',
               $timezones, [
                  'display_emptychoice' => true
               ]
            );
            return true;
      }
      return false;
   }



   /**
    * Get all ldap rules criteria from the DB and add them into the RULES_CRITERIAS
    *
    * @param &$criteria
   **/
   function addSpecificCriteriasToArray(&$criteria) {

      $criteria['ldap'] = __('LDAP criteria');
      foreach (getAllDatasFromTable('glpi_rulerightparameters', [], true) as $data) {
         $criteria[$data["value"]]['name']      = $data["name"];
         $criteria[$data["value"]]['field']     = $data["value"];
         $criteria[$data["value"]]['linkfield'] = '';
         $criteria[$data["value"]]['table']     = '';
      }
   }

}
