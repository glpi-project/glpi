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

class RuleDictionnaryOperatingSystemEdition extends RuleDictionnaryDropdown
{
    /**
     * Constructor
     **/
    public function __construct()
    {
        parent::__construct('RuleDictionnaryOperatingSystemEdition');
    }

    /**
     * @see Rule::getCriterias()
     **/
    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['os_edition']['field'] = 'name';
        $criterias['os_edition']['name']  = _n('Edition', 'Editions', 1);
        $criterias['os_edition']['table'] = 'glpi_operatingsystemeditions';

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

        return $criterias;
    }


    /**
     * @see Rule::getActions()
     **/
    public function getActions()
    {

        $actions                          = [];
        $actions['name']['name']          = _n('Edition', 'Editions', 1);
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
            $rules = $rule->find(['sub_type' => 'RuleDictionnaryOperatingSystemEdition']);
            foreach ($rules as $data) {
                $rule->delete($data);
            }
        }

        $rules = [];
        $rules[] = [
            'sub_type' => 'RuleDictionnaryOperatingSystemEdition',
            'ranking' => '1',
            'name' => 'Clean Linux OS Edition',
            'match' => 'AND',
            'is_active' => '0',
            'is_recursive' => '1',
            'uuid' => 'clean_linux_os_edition',
            'condition' => '0',
            'comment' => "/(SUSE|SunOS|Red Hat|CentOS|Ubuntu|Debian|Fedora|AlmaLinux|Oracle)(?:\D+|)([\d.]+) ?(?:\(?([\w ]+)\)?)?/

        Example :
        Ubuntu 22.04.1 LTS -> #2 = LTS
        SUSE Linux Enterprise Server 11 (x86_64) -> #2 = x86_64
        Red Hat Enterprise Linux Server release 7.9 (Maipo) -> #2 = Maipo
        Red Hat Enterprise Linux Server release 6.10 (Santiago) -> #2 = Santiago
        Fedora release 36 (Thirty Six) -> #2 = Thirty Six
        Debian GNU/Linux 9.5 (stretch) -> #2 = stretch
        Debian GNU/Linux 8.9 (jessie) -> #2 = jessie
        CentOS Linux release 7.2.1511 (Core) -> #2 = Core
        AlmaLinux 9.0 (Emerald Puma) -> #2 = Emerald Puma
        AlmaLinux 8.6 (Sky Tiger) -> #2 = Sky Tiger",
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
                    'value' => '#2',
                ]
            ]
        ];

        $rules[] = [
            'sub_type' => 'RuleDictionnaryOperatingSystemEdition',
            'ranking' => '2',
            'name' => 'Clean Windows OS Edition',
            'match' => 'AND',
            'is_active' => '0',
            'is_recursive' => '1',
            'uuid' => 'clean_windows_os_edition',
            'condition' => '0',
            'comment' => "/(Microsoft)(?>\(R\)|®)? (Windows) (XP|\d\.\d|\d{1,4}|Vista)(™)? ?(.*)/

        Example :
        Microsoft Windows XP Professionnel -> #4 : Professionnel
        Microsoft Windows 7 Enterprise  -> #4 : Enterprise
        Microsoft® Windows Vista Professionnel  -> #4 : Professionnel
        Microsoft Windows XP Édition familiale  -> #4 : Édition familiale
        Microsoft Windows 10 Entreprise  -> #4 : Entreprise
        Microsoft Windows 10 Professionnel  -> #4 : Professionnel
        Microsoft Windows 11 Professionnel  -> #4 : Professionnel",
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
                    'value' => '#4',
                ]
            ]
        ];

        $rules[] = [
            'sub_type' => 'RuleDictionnaryOperatingSystemEdition',
            'ranking' => '3',
            'name' => 'Clean Windows Server OS Edition',
            'match' => 'AND',
            'is_active' => '0',
            'is_recursive' => '1',
            'uuid' => 'clean_windows_server_os_edition',
            'condition' => '0',
            'comment' => "/(Microsoft)(?>\(R\)|®)? (?:(Hyper-V|Windows)(?:\(R\))?) ((?:Server|))(?:\(R\)|®)? (\d{4}(?: R2)?)(?:[,\s]++)?([^\s]*)(?: Edition(?: x64)?)?$/

        Example :
        Microsoft Windows Server 2012 R2 Datacenter -> #4 : Datacenter
        Microsoft(R) Windows(R) Server 2003, Standard Edition x64 -> #4 : Standard
        Microsoft Hyper-V Server 2012 R2 -> #4 :
        Microsoft® Windows Server® 2008 Standard -> #4: Standard",
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
                    'value' => '#4',
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
