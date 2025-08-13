<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
    public $menu_option         = 'right';

    // Specific ones
    /** @var array Array containing results : entity + right */
    public $rules_entity_rights = [];
    /** @var array Array containing results : only entity */
    public $rules_entity        = [];
    /** @var array Array containing results : only right */
    public $rules_rights        = [];

    public function getTitle()
    {
        return __('Authorizations assignment rules');
    }

    public function cleanTestOutputCriterias(array $output)
    {
        if (isset($output["_rule_process"])) {
            unset($output["_rule_process"]);
        }
        return $output;
    }

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
                        'glpi_rulecriterias'       => 'criteria',
                    ],
                ],
                'glpi_rules'         => [
                    'ON' => [
                        'glpi_rulecriterias' => 'rules_id',
                        'glpi_rules'         => 'id',
                    ],
                ],
            ],
            'WHERE'           => ['glpi_rules.sub_type' => 'RuleRight'],
        ]);

        foreach ($iterator as $param) {
            //Dn is alwsays retreived from ldap : don't need to ask for it !
            if ($param["value"] != "dn") {
                $params[] = Toolbox::strtolower($param["value"]);
            }
        }
        return $params;
    }

    public function prepareInputDataForProcess($input, $params)
    {
        $groups = [];
        if (is_array($input)) {
            $groups = $input;
        }

        // Some of the rule criteria is uppercase, but most other rule criterias are lowercase only
        $params_lower = array_change_key_case($params, CASE_LOWER);

        //common parameters
        $rule_parameters = [
            'TYPE'       => $params_lower["type"] ?? "",
            'LOGIN'      => $params_lower["login"] ?? "",
            'MAIL_EMAIL' => $params_lower["email"] ?? $params_lower["mail_email"] ?? "",
            '_groups_id' => $groups,
        ];

        //IMAP/POP login method
        if ($params_lower["type"] == Auth::MAIL) {
            $rule_parameters["MAIL_SERVER"] = $params_lower["mail_server"] ?? "";
        }

        //LDAP type method
        if ($params_lower["type"] == Auth::LDAP) {
            //Get all the field to retrieve to be able to process rule matching
            $rule_fields = $this->getFieldsToLookFor();

            //If we are oustide authentication process, $params_lower["connection"] is not set
            if (empty($params_lower["connection"])) {
                return $rule_parameters;
            }

            //Get all the data we need from ldap to process the rules
            $sz = @ldap_read(
                $params_lower["connection"],
                $params_lower["userdn"],
                "objectClass=*",
                $rule_fields
            );
            if ($sz === false) {
                // 32 = LDAP_NO_SUCH_OBJECT => This error can be silented as it just means that search produces no result.
                if (ldap_errno($params_lower["connection"]) !== 32) {
                    trigger_error(
                        AuthLDAP::buildError(
                            $params_lower["connection"],
                            sprintf('Unable to get LDAP user having DN `%s` with filter `%s`', $params_lower["userdn"], 'objectClass=*')
                        ),
                        E_USER_WARNING
                    );
                }
                return $rule_parameters;
            }

            $rule_input = AuthLDAP::get_entries_clean($params_lower["connection"], $sz);

            if (count($rule_input)) {
                $rule_input = $rule_input[0];
                //Get all the ldap fields
                $fields = $this->getFieldsForQuery();
                foreach ($fields as $field) {
                    switch (Toolbox::strtoupper($field)) {
                        case "LDAP_SERVER":
                            $rule_parameters["LDAP_SERVER"] = $params_lower["ldap_server"];
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
