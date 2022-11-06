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

namespace Glpi\Console\Ldap;

use AuthLDAP;
use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use User;

class SynchronizeUsersCommand extends AbstractCommand
{
    /**
     * Error code returned if LDAP connection failed.
     *
     * @var integer
     */
    const ERROR_LDAP_CONNECTION_FAILED = 1;

    /**
     * Error code returned if LDAP limit exceeded.
     *
     * @var integer
     */
    const ERROR_LDAP_LIMIT_EXCEEDED = 2;

    protected function configure()
    {

        global $CFG_GLPI;

        parent::configure();

        $this->setName('glpi:ldap:synchronize_users');
        $this->setAliases(['ldap:sync']);
        $this->setDescription(__('Synchronize users against LDAP server information'));

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
            __('Only update existing users')
        );

        $this->addOption(
            'ldap-server-id',
            's',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            __('Synchronize only users attached to this LDAP server')
        );

        $this->addOption(
            'ldap-filter',
            'f',
            InputOption::VALUE_OPTIONAL,
            __('Filter to apply on LDAP search')
        );

        $this->addOption(
            'begin-date',
            null,
            InputOption::VALUE_OPTIONAL,
            sprintf(
                __('Begin date to apply in "modifyTimestamp" filter (see %s for supported formats)'),
                'http://php.net/manual/en/datetime.formats.php'
            )
        );

        $this->addOption(
            'end-date',
            null,
            InputOption::VALUE_OPTIONAL,
            sprintf(
                __('End date to apply in "modifyTimestamp" filter (see %s for supported formats)'),
                'http://php.net/manual/en/datetime.formats.php'
            )
        );

        $deleted_strategies = AuthLDAP::getLdapDeletedUserActionOptions();
        $description = sprintf(
            __('Force strategy used for deleted users (current configured action: "%s")'),
            ($CFG_GLPI['user_deleted_ldap'] ?? __('unknown'))
        );
        $description .= "\n" . __('Possible values are:') . "\n";
        $description .= implode(
            "\n",
            array_map(
                static function ($key, $value) {
                    return '- ' . sprintf(__('%1$s: %2$s'), $key, $value);
                },
                array_keys($deleted_strategies),
                $deleted_strategies
            )
        );
        $this->addOption(
            'deleted-user-strategy',
            'd',
            InputOption::VALUE_OPTIONAL,
            $description
        );

        $restored_strategies = AuthLDAP::getLdapRestoredUserActionOptions();
        $description = sprintf(
            __('Force strategy used for restored users (current configured action: "%s")'),
            ($CFG_GLPI['user_restored_ldap'] ?? __('unknown'))
        );
        $description .= "\n" . __('Possible values are:') . "\n";
        $description .= implode(
            "\n",
            array_map(
                static function ($key, $value) {
                    return '- ' . sprintf(__('%1$s: %2$s'), $key, $value);
                },
                array_keys($restored_strategies),
                $restored_strategies
            )
        );
        $this->addOption(
            'restored-user-strategy',
            'r',
            InputOption::VALUE_OPTIONAL,
            $description
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        global $CFG_GLPI;

        $this->validateInput($input);

        $only_create = $input->getOption('only-create-new');
        $only_update = $input->getOption('only-update-existing');

        $ldap_filter = $input->getOption('ldap-filter');
        $begin_date  = $input->getOption('begin-date');
        $end_date    = $input->getOption('end-date');

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
                AuthLDAP::ACTION_ALL, // Update existing users and handle deleted ones
            ];
            $deleted_user_strategy = $input->getOption('deleted-user-strategy');
            if (null !== $deleted_user_strategy) {
                $CFG_GLPI['user_deleted_ldap'] = $deleted_user_strategy;
            }
            $restored_user_strategy = $input->getOption('restored-user-strategy');
            if (null !== $restored_user_strategy) {
                $CFG_GLPI['user_restored_ldap'] = $restored_user_strategy;
            }
        }

        $servers_id = $input->getOption('ldap-server-id');
        if (empty($servers_id)) {
            $servers_iterator = $this->db->request(
                [
                    'SELECT' => 'id',
                    'FROM'   => AuthLDAP::getTable(),
                    'WHERE'  => [
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
                    'FROM'   => AuthLDAP::getTable(),
                    'WHERE'  => [
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
            $informations->addRow([__('Begin date'), $begin_date]);
            $informations->addRow([__('End date'), $end_date]);
            $informations->render();

            $this->askForConfirmation();
        }

        foreach ($servers_id as $server_id) {
            $server = new AuthLDAP();
            if (!$server->getFromDB($server_id)) {
                throw new \Symfony\Component\Console\Exception\RuntimeException(__('Unable to load LDAP server information.'));
            }
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
                    AuthLDAP::USER_IMPORTED       => 0,
                    AuthLDAP::USER_SYNCHRONIZED   => 0,
                    AuthLDAP::USER_DELETED_LDAP   => 0,
                    AuthLDAP::USER_RESTORED_LDAP  => 0,
                ];
                $limitexceeded = false;

                $users = AuthLDAP::getAllUsers(
                    [
                        'authldaps_id' => $server_id,
                        'mode'         => $action,
                        'ldap_filter'  => null !== $ldap_filter ? $ldap_filter : '',
                        'script'       => true,
                        'begin_date'   => null !== $begin_date ? $begin_date : '',
                        'end_date'     => null !== $end_date ? $end_date : '',
                    ],
                    $results,
                    $limitexceeded
                );

                if (false === $users) {
                    if ($limitexceeded) {
                        $message = sprintf(
                            __('LDAP server "%s" size limit exceeded.'),
                            $server_id
                        );
                        $code = self::ERROR_LDAP_LIMIT_EXCEEDED;
                    } else {
                        $message = sprintf(
                            __('Error while contacting the LDAP server "%s".'),
                            $server_id
                        );
                        $code = self::ERROR_LDAP_CONNECTION_FAILED;
                    }
                    $output->writeln(
                        '<error>' . $message . '</error>',
                        OutputInterface::VERBOSITY_QUIET
                    );
                     return $code;
                }

                 $action_message = '';
                switch ($action) {
                    case AuthLDAP::ACTION_IMPORT:
                          $action_message = __('Import new users from server "%s"...');
                        break;
                    case AuthLDAP::ACTION_SYNCHRONIZE:
                        $action_message = __('Update existing users with server "%s"...');
                        break;
                    case AuthLDAP::ACTION_ALL:
                        $action_message = __('Synchronize users with server "%s"...');
                        break;
                }

                 $output->writeln(
                     '<info>' . sprintf($action_message, $server_id) . '</info>',
                     OutputInterface::VERBOSITY_NORMAL
                 );

                if (count($users) === 0) {
                     $output->writeln(
                         '<info>' . __('No users found.') . '</info>',
                         OutputInterface::VERBOSITY_NORMAL
                     );
                     continue;
                }

                 $users_progress_bar = new ProgressBar($output, count($users));
                 $users_progress_bar->start();

                foreach ($users as $user) {
                    $users_progress_bar->advance(1);

                    $user_sync_field = null;
                    if ($server->isSyncFieldEnabled()) {
                          $sync_field = $server->fields['sync_field'];
                        if (isset($user[$sync_field])) {
                            $user_sync_field = $server->getFieldValue($user, $sync_field);
                        }
                    }

                    $existing_user = $server->getLdapExistingUser(
                        $user['user'],
                        $server_id,
                        $user_sync_field
                    );

                    if ($existing_user instanceof User && $action == AuthLDAP::ACTION_IMPORT) {
                           continue; // Do not update existing user if current action is only import
                    }

                    $user_field = 'name';
                    $id_field = $server->fields['login_field'];
                    $value = $user['user'];
                    if (
                        $server->isSyncFieldEnabled()
                        && (!($existing_user instanceof User)
                        || !empty($existing_user->fields['sync_field']))
                    ) {
                        $value      = $user_sync_field;
                        $user_field = 'sync_field';
                        $id_field   = $server->fields['sync_field'];
                    }

                    $result = AuthLDAP::ldapImportUserByServerId(
                        [
                            'method'           => AuthLDAP::IDENTIFIER_LOGIN,
                            'value'            => $value,
                            'identifier_field' => $id_field,
                            'user_field'       => $user_field
                        ],
                        $action,
                        $server_id
                    );

                    if (false !== $result) {
                           $results[$result['action']] += 1;
                    } else {
                          $this->writelnOutputWithProgressBar(
                              sprintf(__('Unable to synchronize user "%s".'), $user['user']),
                              $users_progress_bar,
                              OutputInterface::VERBOSITY_VERBOSE
                          );
                    }
                }
                 $users_progress_bar->finish();
                 $output->write(PHP_EOL);
            }

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                $result_output = new Table($output);
                $result_output->setHeaders(
                    [
                        __('LDAP server'),
                        __('Imported'),
                        __('Synchronized'),
                        __('Deleted from LDAP'),
                        __('Restored from LDAP'),
                    ]
                );
                $result_output->addRow(
                    [
                        $server_id,
                        $results[AuthLDAP::USER_IMPORTED],
                        $results[AuthLDAP::USER_SYNCHRONIZED],
                        $results[AuthLDAP::USER_DELETED_LDAP],
                        $results[AuthLDAP::USER_RESTORED_LDAP],
                    ]
                );
                $result_output->render();
            }
        }

        return 0; // Success
    }

    /**
     * Validate command input.
     *
     * @param InputInterface $input
     *
     * @return void
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    private function validateInput(InputInterface $input)
    {

        $only_create = $input->getOption('only-create-new');
        $only_update = $input->getOption('only-update-existing');
        if (false !== $only_create && false !== $only_update) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                __('Option --only-create-new is not compatible with option --only-update-existing.')
            );
        }

        $servers_id = $input->getOption('ldap-server-id');
        $server = new AuthLDAP();
        foreach ($servers_id as $server_id) {
            if (!$server->getFromDB($server_id)) {
                throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                    sprintf(__('--ldap-server-id value "%s" is not a valid LDAP server id.'), $server_id)
                );
            }
        }

        foreach (['begin-date', 'end-date'] as $option_name) {
           // Convert date to 'Y:m:d H:i:s' formatted string
            $date = $input->getOption($option_name);

            if (null !== $date) {
                $parsed_date = strtotime($date);
                if (false === $parsed_date) {
                    throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                        sprintf(__('Unable to parse --%1$s value "%2$s".'), $option_name, $date)
                    );
                }
                $input->setOption($option_name, date('Y-m-d H:i:s', $parsed_date));
            }
        }

        $begin_date = $input->getOption('begin-date');
        $end_date   = $input->getOption('end-date');
        if ($only_create === false && $only_update === false && ($begin_date !== null || $end_date !== null)) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                __('Options --begin-date and --end-date can only be used with --only-create-new or --only-update-existing option.')
            );
        }
        if ($begin_date > $end_date) {
            throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                __('Option --begin-date value has to be lower than option --end-date value.')
            );
        }

        $deleted_user_strategy = $input->getOption('deleted-user-strategy');
        if (null !== $deleted_user_strategy) {
            $strategies = AuthLDAP::getLdapDeletedUserActionOptions();
            if (!in_array($deleted_user_strategy, array_keys($strategies))) {
                throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
                    sprintf(
                        __('--deleted-user-strategy value "%s" is not valid.'),
                        $deleted_user_strategy
                    )
                );
            }
        }
    }
}
