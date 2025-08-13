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

namespace Glpi\Console\Ldap;

use AuthLDAP;
use Glpi\Console\AbstractCommand;
use Safe\Exceptions\DatetimeException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Toolbox;
use User;

use function Safe\preg_match;
use function Safe\strtotime;

class SynchronizeUsersCommand extends AbstractCommand
{
    /**
     * Error code returned if LDAP connection failed.
     *
     * @var integer
     * @FIXME Remove in GLPI 11.0.
     */
    public const ERROR_LDAP_CONNECTION_FAILED = 1;

    /**
     * Error code returned if LDAP limit exceeded.
     *
     * @var integer
     * @FIXME Remove in GLPI 11.0.
     */
    public const ERROR_LDAP_LIMIT_EXCEEDED = 2;

    protected function configure()
    {

        global $CFG_GLPI;

        parent::configure();

        $this->setName('ldap:synchronize_users');
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
            __('Only update existing users (will not handle deleted users)')
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

        // Get possible values for deleted user actions
        $user_actions = AuthLDAP::getLdapDeletedUserActionOptions_User();
        $groups_actions = AuthLDAP::getLdapDeletedUserActionOptions_Groups();
        $authorizations_actions = AuthLDAP::getLdapDeletedUserActionOptions_Authorizations();

        // Get current config values of deleted user actions
        $cfg_value_user = $CFG_GLPI['user_deleted_ldap_user'] ?? __('unknown');
        $cfg_value_groups = $CFG_GLPI['user_deleted_ldap_groups'] ?? __('unknown');
        $cfg_value_authorizations = $CFG_GLPI['user_deleted_ldap_authorizations'] ?? __('unknown');
        $description = sprintf(
            __('Force strategy used for deleted users (default configured actions: "%s")'),
            "$cfg_value_user,$cfg_value_groups,$cfg_value_authorizations"
        );

        // Show possible values
        $description .= "\n" . __('Three comma-separated values are expected.') . "\n";
        $description .= "\n" . __("1) Actions on the user's account:") . "\n";
        $description .= $this->formatPossiblesDeletedUserOptions($user_actions);
        $description .= "\n" . __("2) Actions on the user's associated groups:") . "\n";
        $description .= $this->formatPossiblesDeletedUserOptions($groups_actions);
        $description .= "\n" . __("3) Actions on the user's authorizations:") . "\n";
        $description .= $this->formatPossiblesDeletedUserOptions($authorizations_actions);

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
                static fn($key, $value) => '- ' . sprintf(__('%1$s: %2$s'), $key, $value),
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

    /**
     * Format array of options into a list
     *
     * @param array $options
     *
     * @return string
     */
    protected function formatPossiblesDeletedUserOptions(array $options): string
    {
        return implode(
            "\n",
            array_map(
                fn($key, $value) => '- ' . sprintf(__('%1$s: %2$s'), $key, $value),
                array_keys($options),
                $options
            )
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

        $ldap_server_error = false;

        $actions = [];
        if ($only_create) {
            $actions = [
                AuthLDAP::ACTION_IMPORT, // Import unexisting users
            ];
        } elseif ($only_update) {
            $actions = [
                AuthLDAP::ACTION_SYNCHRONIZE, // Update existing users but does not handle deleted ones
            ];
        } else {
            $actions = [
                AuthLDAP::ACTION_IMPORT, // Import unexisting users
                AuthLDAP::ACTION_ALL, // Update existing users and handle deleted ones
            ];
            $deleted_user_strategies = $input->getOption('deleted-user-strategy');
            if (null !== $deleted_user_strategies) {
                $deleted_user_strategies = explode(",", $deleted_user_strategies);
                $CFG_GLPI['user_deleted_ldap_user'] = $deleted_user_strategies[0];
                $CFG_GLPI['user_deleted_ldap_groups'] = $deleted_user_strategies[1];
                $CFG_GLPI['user_deleted_ldap_authorizations'] = $deleted_user_strategies[2];
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
                throw new RuntimeException(__('Unable to load LDAP server information.'));
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

            $name = $server->fields['name'] ?? $server_id;
            $output->writeln(
                '<info>' . sprintf(__('Processing LDAP server "%s"...'), $name) . '</info>',
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
                        'ldap_filter'  => $ldap_filter ?? '',
                        'script'       => true,
                        'begin_date'   => $begin_date ?? '',
                        'end_date'     => $end_date ?? '',
                    ],
                    $results,
                    $limitexceeded
                );

                if (false === $users) {
                    $ldap_server_error = true;

                    if ($limitexceeded) {
                        $message = sprintf(
                            __('LDAP server "%s" size limit exceeded.'),
                            $name
                        );
                    } else {
                        $message = sprintf(
                            __('Error while contacting the LDAP server "%s".'),
                            $name
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
                    '<info>' . sprintf($action_message, $name) . '</info>',
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
                            'user_field'       => $user_field,
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
                        $name,
                        $results[AuthLDAP::USER_IMPORTED],
                        $results[AuthLDAP::USER_SYNCHRONIZED],
                        $results[AuthLDAP::USER_DELETED_LDAP],
                        $results[AuthLDAP::USER_RESTORED_LDAP],
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

    /**
     * Validate command input.
     *
     * @param InputInterface  $input
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private function validateInput(InputInterface $input)
    {

        $only_create = $input->getOption('only-create-new');
        $only_update = $input->getOption('only-update-existing');
        if (false !== $only_create && false !== $only_update) {
            throw new InvalidArgumentException(
                __('Option --only-create-new is not compatible with option --only-update-existing.')
            );
        }

        $servers_id = $input->getOption('ldap-server-id');
        $server = new AuthLDAP();
        foreach ($servers_id as $server_id) {
            if (!$server->getFromDB($server_id)) {
                throw new InvalidArgumentException(
                    sprintf(__('--ldap-server-id value "%s" is not a valid LDAP server id.'), $server_id)
                );
            }
        }

        foreach (['begin-date', 'end-date'] as $option_name) {
            // Convert date to 'Y:m:d H:i:s' formatted string
            $date = $input->getOption($option_name);

            if (null !== $date) {
                try {
                    $parsed_date = strtotime($date);
                } catch (DatetimeException $e) {
                    throw new InvalidArgumentException(
                        sprintf(__('Unable to parse --%1$s value "%2$s".'), $option_name, $date),
                        $e->getCode(),
                        $e
                    );
                }
                $input->setOption($option_name, date('Y-m-d H:i:s', $parsed_date));
            }
        }

        $begin_date = $input->getOption('begin-date');
        $end_date   = $input->getOption('end-date');
        if ($only_create === false && $only_update === false && ($begin_date !== null || $end_date !== null)) {
            throw new InvalidArgumentException(
                __('Options --begin-date and --end-date can only be used with --only-create-new or --only-update-existing option.')
            );
        }
        if ($begin_date > $end_date) {
            throw new InvalidArgumentException(
                __('Option --begin-date value has to be lower than option --end-date value.')
            );
        }

        // Handle deleted-user-strategy option
        $deleted_user_strategy = $input->getOption('deleted-user-strategy');
        if (null !== $deleted_user_strategy) {
            // Detect "single integer" old format
            if (in_array($deleted_user_strategy, [0, 1, 2, 3, 4, 5])) {
                // Fix input by converting the value
                // @phpstan-ignore-next-line
                $deleted_user_strategy = $this->convertOldDeletedUserStrategyToNew($deleted_user_strategy);
                $this->input->setOption('deleted-user-strategy', $deleted_user_strategy);
            }

            if (preg_match("/^[0-9],[0-9],[0-9]$/", $deleted_user_strategy)) {
                // New format with 3 comma-separated integers
                $values = explode(",", $deleted_user_strategy);
                $strategies_user = AuthLDAP::getLdapDeletedUserActionOptions_User();
                $strategies_groups = AuthLDAP::getLdapDeletedUserActionOptions_Groups();
                $strategies_authorizations = AuthLDAP::getLdapDeletedUserActionOptions_Authorizations();
                if (
                    !in_array($values[0], array_keys($strategies_user))
                    || !in_array($values[1], array_keys($strategies_groups))
                    || !in_array($values[2], array_keys($strategies_authorizations))
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            __('--deleted-user-strategy value "%s" is not valid.'),
                            $deleted_user_strategy
                        )
                    );
                }
            } else {
                // Unknown format
                throw new InvalidArgumentException(
                    sprintf(
                        __('--deleted-user-strategy value "%s" is not valid.'),
                        $deleted_user_strategy
                    )
                );
            }
        }
    }

    /**
     * Convert the old "single integer" format for the --deleted-user-strategy
     * option into the new "3 comma-separated integers" format
     *
     * @deprecated
     *
     * @param int $deleted_user_strategy old format
     *
     * @return string new format
     */
    protected function convertOldDeletedUserStrategyToNew(int $deleted_user_strategy): string
    {
        Toolbox::deprecated("Usage of a deprecated format for the '--deleted-user-strategy' option.");
        $this->output->writeln('<info>' . sprintf(
            __('Warning: using deprecated %s format'),
            '--deleted-user-strategy'
        ) . '</info>');
        $this->output->writeln('<info>' . sprintf(
            __('Run "%s" for more details'),
            "php bin/console ldap:synchronize --help"
        ) . '</info>');

        switch ($deleted_user_strategy) {
            default:
            case AuthLDAP::DELETED_USER_PRESERVE: // (preserve user)
                $deleted_user_strategy = [
                    AuthLDAP::DELETED_USER_ACTION_USER_DO_NOTHING,
                    AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING,
                    AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING,
                ];
                break;
            case AuthLDAP::DELETED_USER_DELETE: // (put user in trashbin)
                $deleted_user_strategy = [
                    AuthLDAP::DELETED_USER_ACTION_USER_MOVE_TO_TRASHBIN,
                    AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING,
                    AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING,
                ];
                break;
            case AuthLDAP::DELETED_USER_WITHDRAWDYNINFO: // (withdraw dynamic authorizations and groups)
                $deleted_user_strategy = [
                    AuthLDAP::DELETED_USER_ACTION_USER_DO_NOTHING,
                    AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC,
                    AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC,
                ];
                break;
            case AuthLDAP::DELETED_USER_DISABLE: // (disable user)
                $deleted_user_strategy = [
                    AuthLDAP::DELETED_USER_ACTION_USER_DISABLE,
                    AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING,
                    AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING,
                ];
                break;
            case AuthLDAP::DELETED_USER_DISABLEANDWITHDRAWDYNINFO: // (disable user and withdraw dynamic authorizations/groups)
                $deleted_user_strategy = [
                    AuthLDAP::DELETED_USER_ACTION_USER_DISABLE,
                    AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC,
                    AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC,
                ];
                break;
            case AuthLDAP::DELETED_USER_DISABLEANDDELETEGROUPS: // (disable user and withdraw groups)
                $deleted_user_strategy = [
                    AuthLDAP::DELETED_USER_ACTION_USER_DISABLE,
                    AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_ALL,
                    AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING,
                ];
                break;
        }

        return implode(",", $deleted_user_strategy);
    }
}
