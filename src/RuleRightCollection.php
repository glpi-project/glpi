<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/// Rule collection class for Rights management
class RuleRightCollection extends RuleCollection
{
   // From RuleCollection
    public $stop_on_first_match = false;
    public static $rightname           = 'rule_ldap';
    public $orderby             = "name";
    public $menu_option         = 'right';

   // Specific ones
   /// Array containing results : entity + right
    public $rules_entity_rights = [];
   /// Array containing results : only entity
    public $rules_entity        = [];
   /// Array containing results : only right
    public $rules_rights        = [];


    public function getTitle()
    {
        return __('Authorizations assignment rules');
    }


    /**
     * @see RuleCollection::cleanTestOutputCriterias()
     */
    public function cleanTestOutputCriterias(array $output)
    {

        if (isset($output["_rule_process"])) {
            unset($output["_rule_process"]);
        }
        return $output;
    }


    public function showTestResults($rule, array $output, $global_result)
    {

        $actions = $rule->getActions();
        echo "<tr><th colspan='4'>" . __('Rule results') . "</th></tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td class='center' colspan='2'>" . _n('Validation', 'Validations', 1) . "</td><td colspan='2'>" .
           "<span class='b'>" . Dropdown::getYesNo($global_result) . "</span></td>";

        if (isset($output["_ldap_rules"]["rules_entities"])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan='4'>" . __('Entities assignment') . "</td>";
            foreach ($output["_ldap_rules"]["rules_entities"] as $entities) {
                foreach ($entities as $entity) {
                    $this->displayActionByName("entity", $entity[0]);
                    if (isset($entity[1])) {
                        $this->displayActionByName("recursive", $entity[1]);
                    }
                }
            }
        }

        if (isset($output["_ldap_rules"]["rules_rights"])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='4' class='center'>" . __('Rights assignment') . "</td>";
            foreach ($output["_ldap_rules"]["rules_rights"] as $val) {
                $this->displayActionByName("profile", $val[0]);
            }
        }

        if (isset($output["_ldap_rules"]["rules_entities_rights"])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='4' class='center'>" . __('Rights and entities assignment') . "</td>";
            foreach ($output["_ldap_rules"]["rules_entities_rights"] as $val) {
                if (is_array($val[0])) {
                    foreach ($val[0] as $tmp) {
                        $this->displayActionByName("entity", $tmp);
                    }
                } else {
                    $this->displayActionByName("entity", $val[0]);
                }
                if (isset($val[1])) {
                    $this->displayActionByName("profile", $val[1]);
                }
                if (isset($val[2])) {
                    $this->displayActionByName("is_recursive", $val[2]);
                }
            }
        }

        if (isset($output["_ldap_rules"])) {
            unset($output["_ldap_rules"]);
        }
        foreach ($output as $criteria => $value) {
            if (isset($actions[$criteria])) { // ignore _* fields
                if (isset($actions[$criteria]['action_type'])) {
                    $actiontype = $actions[$criteria]['action_type'];
                } else {
                    $actiontype = '';
                }
                echo "<tr class='tab_bg_2'>";
                echo "<td class='center'>" . $actions[$criteria]["name"] . "</td>";
                echo "<td class='center'>" . $rule->getActionValue($criteria, $actiontype, $value);
                echo "</td></tr>\n";
            }
        }
        echo "</tr>";
    }


    /**
     * Display action using its name
     *
     * @param $name   action name
     * @param $value  default value
     **/
    public function displayActionByName($name, $value)
    {

        echo "<tr class='tab_bg_2'>";
        switch ($name) {
            case "entity":
                echo "<td class='center'>" . Entity::getTypeName(1) . " </td>\n";
                echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $value) . "</td>";
                break;

            case "profile":
                echo "<td class='center'>" . _n('Profile', 'Profiles', Session::getPluralNumber()) . " </td>\n";
                echo "<td class='center'>" . Dropdown::getDropdownName("glpi_profiles", $value) . "</td>";
                break;

            case "is_recursive":
                echo "<td class='center'>" . __('Recursive') . " </td>\n";
                echo "<td class='center'>" . Dropdown::getYesNo($value) . "</td>";
                break;
        }
        echo "</tr>";
    }


    /**
     * Get all the fields needed to perform the rule
     *
     * @see RuleCollection::getFieldsToLookFor()
     **/
    public function getFieldsToLookFor()
    {
        global $DB;

        $params = [];
        $iterator = $DB->request([
            'SELECT'          => 'value',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_rulerightparameters',
            'LEFT JOIN'       => [
                'glpi_rulecriterias' => [
                    'ON' => [
                        'glpi_rulerightparameters' => 'value',
                        'glpi_rulecriterias'       => 'criteria'
                    ]
                ],
                'glpi_rules'         => [
                    'ON' => [
                        'glpi_rulecriterias' => 'rules_id',
                        'glpi_rules'         => 'id'
                    ]
                ]
            ],
            'WHERE'           => ['glpi_rules.sub_type' => 'RuleRight']
        ]);

        foreach ($iterator as $param) {
            //Dn is alwsays retreived from ldap : don't need to ask for it !
            if ($param["value"] != "dn") {
                $params[] = Toolbox::strtolower($param["value"]);
            }
        }
        return $params;
    }


    /**
     * Get the attributes needed for processing the rules
     *
     * @see RuleCollection::prepareInputDataForProcess()
     *
     * @param $input  input datas
     * @param $params extra parameters given
     *
     * @return an array of attributes
     **/
    public function prepareInputDataForProcess($input, $params)
    {
        $groups = [];
        if (isset($input) && is_array($input)) {
            $groups = $input;
        }

       //common parameters
        $rule_parameters = [
            'TYPE'       => $params["type"] ?? "",
            'LOGIN'      => $params["login"] ?? "",
            'MAIL_EMAIL' => $params["email"] ?? $params["mail_email"] ?? "",
            '_groups_id' => $groups
        ];

       //IMAP/POP login method
        if ($params["type"] == Auth::MAIL) {
            $rule_parameters["MAIL_SERVER"] = $params["mail_server"] ?? "";
        }

       //LDAP type method
        if ($params["type"] == Auth::LDAP) {
           //Get all the field to retrieve to be able to process rule matching
            $rule_fields = $this->getFieldsToLookFor();

           //Get all the datas we need from ldap to process the rules
            $sz         = @ldap_read(
                $params["connection"],
                $params["userdn"],
                "objectClass=*",
                $rule_fields
            );
            $rule_input = AuthLDAP::get_entries_clean($params["connection"], $sz);

            if (count($rule_input)) {
                $rule_input = $rule_input[0];
                //Get all the ldap fields
                $fields = $this->getFieldsForQuery();
                foreach ($fields as $field) {
                    switch (Toolbox::strtoupper($field)) {
                        case "LDAP_SERVER":
                            $rule_parameters["LDAP_SERVER"] = $params["ldap_server"];
                            break;

                        default: // ldap criteria (added by user)
                            if (isset($rule_input[$field])) {
                                if (!is_array($rule_input[$field])) {
                                     $rule_parameters[$field] = $rule_input[$field];
                                } else {
                                    if (count($rule_input[$field])) {
                                        foreach ($rule_input[$field] as $key => $val) {
                                            if ($key !== 'count') {
                                                $rule_parameters[$field][] = $val;
                                            }
                                        }
                                    }
                                }
                            }
                    }
                }
                return $rule_parameters;
            }
            return $rule_input;
        }

        return $rule_parameters;
    }


    /**
     * Get the list of fields to be retreived to process rules
     **/
    public function getFieldsForQuery()
    {

        $rule      = new RuleRight();
        $criterias = $rule->getCriterias();

        $fields = [];
        foreach ($criterias as $criteria) {
            if (!is_array($criteria)) {
                continue;
            }
            if (isset($criteria['virtual']) && $criteria['virtual']) {
                $fields[] = $criteria['id'];
            } else {
                $fields[] = $criteria['field'];
            }
        }
        return $fields;
    }
}
