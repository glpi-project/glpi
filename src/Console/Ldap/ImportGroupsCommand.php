<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Console\Ldap;

use AuthLDAP;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Group;
use Toolbox;

class ImportGroupsCommand extends AbstractCommand
{
    /**
     * Error code returned if LDAP connection failed.
     *
     * @var integer
     * @FIXME Remove in GLPI 10.1.
     */
    const ERROR_LDAP_CONNECTION_FAILED = 1;

    /**
     * Error code returned if LDAP limit exceeded.
     *
     * @var integer
     * @FIXME Remove in GLPI 10.1.
     */
    const ERROR_LDAP_LIMIT_EXCEEDED = 2;

    protected function configure()
    {
        try {

        } catch (\Exception $e) {
            return true;
        }
        parent::configure();

        $this->setName('ldap:import_groups');
        $this->setAliases(['ldap:importgroups']);
        $this->setDescription(__('Import or synchronize groups against LDAP server information'));

        $this->addOption(
            'only-create-new',
            'c',
            InputOption::VALUE_NONE,
            __('Only create new users')
        );

        $this->addOption(
            'only-update-existing',
            'u',
            InputOption::VALUE_NONE,
            __('Only update existing users (will not handle deleted users)')
        );
        $this->addOption(
            'ldap-server-id',
            's',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            __('Import only groups attached to this LDAP server')
        );

        $this->addOption(
            'ldap-filter',
            'f',
            InputOption::VALUE_OPTIONAL,
            __('Filter to apply on LDAP search')
        );

        $this->addOption(
            'only-cn',
            'cn',
            InputOption::VALUE_OPTIONAL,
            __('Filter to apply on LDAP group retrieve CN')
        );

        $this->addOption(
            'entity-filter',
            'e',
            InputOption::VALUE_OPTIONAL,
            __('Filter to apply on group search')
        );

        $this->addOption(
            'oldgroups',
            'o',
            InputOption::VALUE_OPTIONAL,
            __('Remove old groups')
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $DB;

        $only_create = $input->getOption('only-create-new');
        $only_update = $input->getOption('only-update-existing');
        $ldap_filter = $input->getOption('ldap-filter');
        $onlycn = $input->getOption('only-cn');
        $entity_filter = $input->getOption('entity-filter');
        $oldgroups = $input->getOption('oldgroups');

        $ldap_server_error = false;

        $actions = [];
        if ($only_create) {
            $actions = [
                AuthLDAP::ACTION_IMPORT, // Import unexisting users
            ];
        } else if ($only_update) {
            $actions = [
                AuthLDAP::ACTION_SYNCHRONIZE, // Update existing users but does not handle deleted ones
            ];
        } else {
            $actions = [
                AuthLDAP::ACTION_IMPORT, // Import unexisting users
                AuthLDAP::ACTION_SYNCHRONIZE, // Update existing users and handle deleted ones
            ];
        }

        $servers_id = $input->getOption('ldap-server-id');
        if (empty($servers_id)) {
            $servers_iterator = $this->db->request(
                [
                    'SELECT' => 'id',
                    'FROM' => AuthLDAP::getTable(),
                    'WHERE' => [
                        'is_active' => 1,
                    ],
                ]
            );
            if ($servers_iterator->count() === 0) {
                $output->writeln('<info>' . __('No active LDAP server found.') . '</info>');
                return 0;
            }
            foreach ($servers_iterator as $server) {
                $servers_id[] = $server['id'];
            }
        }

        if (!$input->getOption('no-interaction')) {
            // Ask for confirmation (unless --no-interaction)

            $servers_iterator = $this->db->request(
                [
                    'SELECT' => ['id', 'name'],
                    'FROM' => AuthLDAP::getTable(),
                    'WHERE' => [
                        'id' => $servers_id,
                    ],
                ]
            );
            $servers_names = [];
            foreach ($servers_iterator as $server) {
                $servers_names[] = sprintf(__('%1$s (%2$s)'), $server['name'], $server['id']);
            }

            $informations = new Table($output);
            $informations->addRow([__('LDAP servers'), implode(', ', $servers_names)]);
            $informations->addRow([__('LDAP filter'), $ldap_filter]);
            $informations->addRow([__('CN filter'), $onlycn]);
            $informations->addRow([__('Entity filter'), $entity_filter]);
            $informations->addRow([__('Remove old groups'), $oldgroups]);
            $informations->render();

            $this->askForConfirmation();
        }

        foreach ($servers_id as $server_id) {
            $server = new AuthLDAP();
            if (!$server->getFromDB($server_id)) {
                throw new \Symfony\Component\Console\Exception\RuntimeException(__('Unable to load LDAP server information.'));
            }
            $sync_field = $server->isSyncFieldGroupEnabled() ? $server->fields['sync_field_group'] : null;
            if (!$server->isActive()) {
                // Can happen if id is specified in command call
                $message = sprintf(
                    __('LDAP server "%s" is inactive, no synchronization will be done against it.'),
                    $server_id
                );
                $output->writeln('<info>' . $message . '</info>');
                continue;
            }

            $output->writeln(
                '<info>' . sprintf(__('Processing LDAP server "%s"...'), $server_id) . '</info>',
                OutputInterface::VERBOSITY_NORMAL
            );

            foreach ($actions as $action) {
                $results = [
                    0 => 0,
                ];
                $limitexceeded = false;

                $filter = null !== $ldap_filter ? $ldap_filter : '';
                $filter2 = '';
                $entity = null !== $entity_filter ? $entity_filter : 0;
                $order = "ASC";

                $groups = AuthLDAP::getAllGroups(
                    $server_id,
                    $filter,
                    $filter2,
                    $entity,
                    $limitexceeded,
                    $order
                );

                if (false === $groups) {
                    $ldap_server_error = true;

                    if ($limitexceeded) {
                        $message = sprintf(
                            __('LDAP server "%s" size limit exceeded.'),
                            $server_id
                        );
                    } else {
                        $message = sprintf(
                            __('Error while contacting the LDAP server "%s".'),
                            $server_id
                        );
                    }
                    $output->writeln(
                        '<error>' . $message . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                    continue;
                }

                $action_message = '';
                switch ($action) {
                    case AuthLDAP::ACTION_IMPORT:
                        $action_message = __('Import new group from server "%s"...');
                        break;
                }

                $output->writeln(
                    '<info>' . sprintf($action_message, $server_id) . '</info>',
                    OutputInterface::VERBOSITY_NORMAL
                );

                if (count($groups) === 0) {
                    $output->writeln(
                        '<info>' . __('No groups found.') . '</info>',
                        OutputInterface::VERBOSITY_NORMAL
                    );
                    continue;
                }

                $groups_progress_bar = new ProgressBar($output, count($groups));
                $groups_progress_bar->start();
                $is_recursive = 0;
                $options = ['authldaps_id' => $server_id,
                    'entities_id' => $entity,
                    'is_recursive' => $is_recursive
                ];

                switch ($server->fields["group_search_type"]) {
                    case AuthLDAP::GROUP_SEARCH_USER:
                        $options['type'] = "users";
                        break;

                    case AuthLDAP::GROUP_SEARCH_GROUP:
                        $options['type'] = "groups";
                        break;
                }
$glpi_groups = [];
//Get all groups from GLPI DB for the current entity and the subentities
                $iterator = $DB->request([
                    'SELECT' => ['ldap_group_dn','ldap_value'],
                    'FROM'   => 'glpi_groups',
                    'WHERE'  => getEntitiesRestrictCriteria('glpi_groups')
                ]);

                //If the group exists in DB -> unset it from the LDAP groups
                foreach ($iterator as $group) {
                    //use DN for next step
                    //depending on the type of search when groups are imported
                    //the DN may be in two separate fields
                    if (isset($group["ldap_group_dn"]) && !empty($group["ldap_group_dn"])) {
                        $glpi_groups[$group["ldap_group_dn"]] = 1;
                    } else if (isset($group["ldap_value"]) && !empty($group["ldap_value"])) {
                        $glpi_groups[$group["ldap_value"]] = 1;
                    }
                }

                foreach ($groups as $group) {
                    $groups_progress_bar->advance(1);

                    $group_sync_field = $server->isSyncFieldGroupEnabled() && isset($group[$sync_field])
                        ? AuthLDAP::getFieldValue($group, $sync_field)
                        : null;

                    $groupFind = $server->getLdapExistingGroup(
                        $group['dn'],
                        $glpi_groups,
                        $group_sync_field
                    );
                    if (!$action && $groupFind  || ($action && !$groupFind)) {
                        continue;
                    }
                    if ($onlycn != '') {
                        if (str_contains($group["dn"], $onlycn)) {
                            $result = AuthLDAP::ldapImportGroup($group["dn"], $options);
                            if (isset($result) && false !== $result) {
                                $results[0] += 1;
                            } else {
                                $this->writelnOutputWithProgressBar(
                                    sprintf(__('Unable to import group "%s".'), $group["dn"]),
                                    $groups_progress_bar,
                                    OutputInterface::VERBOSITY_VERBOSE
                                );
                            }
                        }
                    } else {
                        $result = AuthLDAP::ldapImportGroup($group["dn"], $options);
                        if (isset($result) && false !== $result) {
                            $results[0] += 1;
                        } else {
                            $this->writelnOutputWithProgressBar(
                                sprintf(__('Unable to import group "%s".'), $group["dn"]),
                                $groups_progress_bar,
                                OutputInterface::VERBOSITY_VERBOSE
                            );
                        }
                    }
                }
                //Drop old groups
                if ($oldgroups == 1) {
                    $infos       = [];
                    $all_ldap_groups = [];
                    $ds = $server->connect();
                    $infos = AuthLDAP::getGroupsFromLDAP(
                        $ds,
                        $server,
                        $filter,
                        $limitexceeded,
                        false,
                        $infos
                    );
                    if (!empty($infos)) {
                        foreach ($infos as $dn => $info) {
                            $all_ldap_groups[] = Toolbox::strtolower($dn);
                        }
                        //Get all groups from GLPI DB for the current entity and the subentities
                        $iterator = $DB->request([
                            'SELECT' => ['id', 'ldap_group_dn', 'ldap_value'],
                            'FROM' => 'glpi_groups',
                            'WHERE' => [
                                    'is_assign'     => 0
                                ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity)
                        ]);

                        //If the group exists in DB -> unset it from the LDAP groups
                        foreach ($iterator as $glpigroups) {
                            if (!in_array(Toolbox::strtolower($glpigroups['ldap_value']), $all_ldap_groups)) {
                                $group = new Group();
                                $values['id'] = $glpigroups['id'];
                                $values['entities_id'] = 0;
                                $values['is_requester'] = 0;
                                $values['is_watcher'] = 0;
                                $values['is_assign'] = 0;
                                $values['is_task'] = 0;
                                $values['is_notify'] = 0;
                                $values['is_manager'] = 0;
                                $group->update($values);
                            }
                        }
                    }
                }

                $groups_progress_bar->finish();
                $output->write(PHP_EOL);
            }

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                $result_output = new Table($output);
                $result_output->setHeaders(
                    [
                        __('LDAP server'),
                        __('Imported'),
                    ]
                );
                $result_output->addRow(
                    [
                        $server_id,
                        $results[0],
                    ]
                );
                $result_output->render();
            }
        }

        if ($ldap_server_error) {
            return self::FAILURE; // At least one LDAP server had an error
        }
        return self::SUCCESS; // Success
    }
}
