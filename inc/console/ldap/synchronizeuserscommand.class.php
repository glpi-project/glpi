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

namespace Glpi\Console\Ldap;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use AuthLDAP;
use User;
use Glpi\Console\AbstractCommand;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

class SynchronizeUsersCommand extends AbstractCommand {

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

   protected function configure() {

      global $CFG_GLPI;

      parent::configure();

      $this->setName('glpi:ldap:synchronize_users');
      $this->setAliases(['ldap:sync']);
      $this->setDescription(__('Synchronize users against LDAP server informations'));

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

      $strategies = AuthLDAP::getLdapDeletedUserActionOptions();
      $description = sprintf(
         __('Force strategy used for deleted users (current configured action: "%s")'),
         (isset($CFG_GLPI['user_deleted_ldap']) ? $CFG_GLPI['user_deleted_ldap'] : __('unknown'))
      );
      $description .= "\n" . __('Possible values are:') . "\n";
      $description .= implode(
         "\n",
         array_map(
            function ($key, $value) { return '- ' . sprintf(__('%1$s: %2$s'), $key, $value); },
            array_keys($strategies),
            $strategies
         )
      );
      $this->addOption(
         'deleted-user-strategy',
         'd',
         InputOption::VALUE_OPTIONAL,
         $description
      );
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      global $CFG_GLPI;

      $this->validateInput($input);

      $only_create = $input->getOption('only-create-new');
      $only_update = $input->getOption('only-update-existing');

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

      $ldap_filter = $input->getOption('ldap-filter');
      $begin_date  = $input->getOption('begin-date');
      $end_date    = $input->getOption('end-date');

      foreach ($servers_id as $server_id) {
         $server = new AuthLDAP();
         if (!$server->getFromDB($server_id)) {
            throw new RuntimeException(__('Unable to load LDAP server informations.'));
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
               AuthLDAP::USER_IMPORTED     => 0,
               AuthLDAP::USER_SYNCHRONIZED => 0,
               AuthLDAP::USER_DELETED_LDAP => 0,
            ];
            $limitexceeded = false;

            $users = AuthLdap::getAllUsers(
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
               case AuthLDAP::ACTION_IMPORT;
                  $action_message = __('Import new users from server "%s"...');
                  break;
               case AuthLDAP::ACTION_SYNCHRONIZE;
                  $action_message = __('Update existing users with server "%s"...');
                  break;
               case AuthLDAP::ACTION_ALL;
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

               if ($existing_user instanceof User && $action == AuthLdap::ACTION_IMPORT) {
                  continue; // Do not update existing user if current action is only import
               }

               $user_field = 'name';
               $id_field = $server->fields['login_field'];
               $value = $user['user'];
               if ($server->isSyncFieldEnabled()
                   && (!($existing_user instanceof User)
                       || !empty($existing_user->fields['sync_field']))) {
                  $value      = $user_sync_field;
                  $user_field = 'sync_field';
                  $id_field   = $server->fields['sync_field'];
               }

               $result = AuthLdap::ldapImportUserByServerId(
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
                  $output->writeln(
                     sprintf(__('Unable to synchronize user "%s".'), $user['user']),
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
               ]
            );
            $result_output->addRow(
               [
                  $server_id,
                  $results[AuthLDAP::USER_IMPORTED],
                  $results[AuthLDAP::USER_SYNCHRONIZED],
                  $results[AuthLDAP::USER_DELETED_LDAP],
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
    * @throws InvalidArgumentException
    */
   private function validateInput(InputInterface $input) {

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
            $parsed_date = strtotime($date);
            if (false === $parsed_date) {
               throw new InvalidArgumentException(
                  sprintf(__('Unable to parse --%1$s value "%2$s".'), $option_name, $date)
               );
            }
            $input->setOption($option_name, date('Y:m:d H:i:s', $parsed_date));
         }
      }

      $begin_date = $input->getOption('begin-date');
      $end_date   = $input->getOption('end-date');
      if ($begin_date > $end_date) {
         throw new InvalidArgumentException(
            __('Option --begin-date value has to be lower than option --end-date value.')
         );
      }

      $deleted_user_strategy = $input->getOption('deleted-user-strategy');
      if (null !== $deleted_user_strategy) {
         $strategies = AuthLDAP::getLdapDeletedUserActionOptions();
         if (!in_array($deleted_user_strategy, array_keys($strategies))) {
            throw new InvalidArgumentException(
               sprintf(
                  __('--deleted-user-strategy value "%s" is not valid.'),
                  $deleted_user_strategy
               )
            );
         }
      }
   }
}
