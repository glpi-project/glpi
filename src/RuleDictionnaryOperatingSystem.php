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

use Glpi\Toolbox\Sanitizer;

class RuleDictionnaryOperatingSystem extends RuleDictionnaryDropdown
{
    /**
     * Constructor
     **/
    public function __construct()
    {
        parent::__construct('RuleDictionnaryOperatingSystem');
    }


    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['os_name']['field'] = 'name';
        $criterias['os_name']['name']  = OperatingSystem::getTypeName(1);
        $criterias['os_name']['table'] = 'glpi_operatingsystems';

        $criterias['os_version_name']['field'] = 'name';
        $criterias['os_version_name']['name']  = OperatingSystemVersion::getTypeName(1);
        $criterias['os_version_name']['table'] = 'glpi_operatingsystemversions';

        $criterias['arch_name']['field'] = 'name';
        $criterias['arch_name']['name']  = OperatingSystemArchitecture::getTypeName(1);
        $criterias['arch_name']['table'] = 'glpi_operatingsystemarchitectures';

        $criterias['servicepack_name']['field'] = 'name';
        $criterias['servicepack_name']['name']  = OperatingSystemServicePack::getTypeName(1);
        $criterias['servicepack_name']['table'] = 'glpi_operatingsystemservicepacks';

        $criterias['os_edition']['field'] = 'name';
        $criterias['os_edition']['name']  = OperatingSystemEdition::getTypeName(1);
        $criterias['os_edition']['table'] = 'glpi_operatingsystemeditions';

        return $criterias;
    }


    /**
     * @see Rule::getActions()
     **/
    public function getActions()
    {

        $actions                          = [];
        $actions['name']['name']          = OperatingSystem::getTypeName(1);
        $actions['name']['force_actions'] = ['append_regex_result', 'assign', 'regex_result'];

        return $actions;
    }

    /**
     * Create rules (initialisation)
     *
     *
     * @param boolean $reset        Whether to reset before adding new rules, defaults to true
     * @param boolean $with_plugins Use plugins rules or not
     * @param boolean $check        Check if rule exists before creating
     *
     * @return boolean
     */
    public static function initRules($reset = true, $with_plugins = true, $check = false): bool
    {

        if ($reset) {
            $rule = new Rule();
            $rules = $rule->find(['sub_type' => 'RuleDictionnaryOperatingSystem']);
            foreach ($rules as $data) {
                $rule->delete($data);
            }
        }

        $rules = [];
        $rules[] = [
            'sub_type' => 'RuleDictionnaryOperatingSystem',
            'ranking' => '1',
            'name' => 'Clean Linux OS Name',
            'match' => 'AND',
            'is_active' => '0',
            'is_recursive' => '1',
            'uuid' => 'clean_linux_os_name',
            'condition' => '0',
            'comment' => "/(SUSE|SunOS|Red Hat|CentOS|Ubuntu|Debian|Fedora|AlmaLinux|Oracle)(?:\D+|)([\d.]+) ?(?:\(?([\w ]+)\)?)?/

            Example :
            Ubuntu 22.04.1 LTS -> #0 = Ubuntu
            SUSE Linux Enterprise Server 11 (x86_64)  ->#0 = SUSE
            SunOS -> #0 = SunOS
            Red Hat Enterprise Linux Server release 7.9 (Maipo) -> #0 = Red Hat
            Oracle Linux Server release 7.3 -> #0 = Oracle
            Fedora release 36 (Thirty Six) -> #0 = Fedora
            Debian GNU/Linux 9.5 (stretch) -> #0 = Debian
            CentOS Stream release 8 -> #0 = CentOS
            AlmaLinux 9.0 (Emerald Puma) -> #0 = AlmaLinux",
            'criteria'  => [
                [
                    'criteria' => 'os_name',
                    'condition' => 6,
                    'pattern' => '/(SUSE|SunOS|Red Hat|CentOS|Ubuntu|Debian|Fedora|AlmaLinux|Oracle)(?:\D+|)([\d.]+) ?(?:\(?([\w ]+)\)?)?/',
                ]
            ],
            'action'  => [
                [
                    'action_type' => 'append_regex_result',
                    'field' => 'name',
                    'value' => '#0',
                ]
            ]
        ];

        $rules[] = [
            'sub_type' => 'RuleDictionnaryOperatingSystem',
            'ranking' => '2',
            'name' => 'Clean Windows OS Name',
            'match' => 'AND',
            'is_active' => '0',
            'is_recursive' => '1',
            'uuid' => 'clean_windows_os_name',
            'condition' => '0',
            'comment' => "/(Microsoft)(?>\(R\)|®)? (Windows) (XP|\d\.\d|\d{1,4}|Vista)(™)? ?(.*)/

            Example :
            Microsoft Windows XP Professionnel -> #1 : Windows
            Microsoft Windows 7 Enterprise  -> #1 : Windows
            Microsoft® Windows Vista Professionnel  -> #1 : Windows
            Microsoft Windows XP Édition familiale  -> #1 : Windows
            Microsoft Windows 10 Entreprise  -> #1 : Windows
            Microsoft Windows 10 Professionnel  -> #1 : Windows
            Microsoft Windows 11 Professionnel  -> #1 : Windows",
            'criteria'  => [
                [
                    'criteria' => 'os_name',
                    'condition' => 6,
                    'pattern' => '/(Microsoft)(?>\(R\)|®)? (Windows) (XP|\d\.\d|\d{1,4}|Vista)(™)? ?(.*)/',
                ]
            ],
            'action'  => [
                [
                    'action_type' => 'append_regex_result',
                    'field' => 'name',
                    'value' => '#1',
                ]
            ]
        ];

        $rules[] = [
            'sub_type' => 'RuleDictionnaryOperatingSystem',
            'ranking' => '3',
            'name' => 'Clean Windows Server OS Name',
            'match' => 'AND',
            'is_active' => '0',
            'is_recursive' => '1',
            'uuid' => 'clean_windows_server_os_name',
            'condition' => '0',
            'comment' => "/(Microsoft)(?>\(R\)|®)? (?:(Hyper-V|Windows)(?:\(R\))?) ((?:Server|))(?:\(R\)|®)? (\d{4}(?: R2)?)(?:[,\s]++)?([^\s]*)(?: Edition(?: x64)?)?$/

            Example :
            Microsoft Windows Server 2012 R2 Datacenter -> #1 #2 : Windows Server
            Microsoft(R) Windows(R) Server 2003, Standard Edition x64 -> #1 #2 : Windows Server
            Microsoft Hyper-V Server 2012 R2 -> #1 #2 : Hyper-V Server
            Microsoft® Windows Server® 2008 Standard -> #1 #2 : Windows Server",
            'criteria'  => [
                [
                    'criteria' => 'os_name',
                    'condition' => 6,
                    'pattern' => '/(Microsoft)(?>\(R\)|®)? (?:(Hyper-V|Windows)(?:\(R\))?) ((?:Server|))(?:\(R\)|®)? (\d{4}(?: R2)?)(?:[,\s]++)?([^\s]*)(?: Edition(?: x64)?)?$/',
                ]
            ],
            'action'  => [
                [
                    'action_type' => 'append_regex_result',
                    'field' => 'name',
                    'value' => '#1 #2',
                ]
            ]
        ];

        foreach ($rules as $rule) {
            $rule = Sanitizer::sanitize($rule);
            $rulecollection = new RuleDictionnaryOperatingSystemEditionCollection();
            $input = [
                'is_active'     => $rule['is_active'],
                'is_recursive'  => $rule['is_recursive'],
                'name'          => $rule['name'],
                'condition'     => $rule['condition'],
                'uuid'          => $rule['uuid'],
                'match'         => $rule['match'],
                'sub_type'      => self::getType(),
            ];

            $exists = false;
            if ($check === true) {
                $exists = $rulecollection->getFromDBByCrit($input);
            }

            $input['comment']       = $rule['comment'];
            $input['ranking']       = $rule['ranking'];

            if ($exists === true) {
                //rule already exists, ignore.
                continue;
            }

            $rule_id = $rulecollection->add($input);
            $ruleclass = $rulecollection->getRuleClass();

            // Add criteria
            foreach ($rule['criteria'] as $criteria) {
                $rulecriteria = new RuleCriteria(get_class($ruleclass));
                $criteria['rules_id'] = $rule_id;
                $rulecriteria->add($criteria, [], false);
            }

            // Add action
            foreach ($rule['action'] as $action) {
                $ruleaction = new RuleAction(get_class($ruleclass));
                $action['rules_id'] = $rule_id;
                $ruleaction->add($action, [], false);
            }
        }
        return true;
    }
}
