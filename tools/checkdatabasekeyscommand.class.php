<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Console\AbstractCommand;
use Glpi\System\Diagnostic\DatabaseKeysChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDatabaseKeysCommand extends AbstractCommand
{
    /**
     * Error code returned when missing keys are found.
     *
     * @var integer
     */
    const ERROR_FOUND_MISSING_KEYS = 1;

    /**
     * Error code returned when misnamed keys are found.
     *
     * @var integer
     */
    const ERROR_FOUND_MISNAMED_KEYS = 2;

    /**
     * Error code returned when useless keys are found.
     *
     * @var integer
     */
    const ERROR_FOUND_USELESS_KEYS = 3;

    protected function configure()
    {
        parent::configure();

        $this->setName('tools:check_database_keys');
        $this->setDescription(__('Check database for missing and errounous keys.'));

        $this->addOption(
            'detect-misnamed-keys',
            null,
            InputOption::VALUE_NONE,
            __('Detect misnamed keys')
        );

        $this->addOption(
            'detect-useless-keys',
            null,
            InputOption::VALUE_NONE,
            __('Detect misnamed keys')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $checker = new DatabaseKeysChecker($this->db);

        $has_missing_keys  = false;
        $has_misnamed_keys = false;
        $has_useless_keys  = false;

        $table_iterator = $this->db->listTables('glpi\_%', ['NOT' => ['table_name' => ['LIKE', 'glpi\_plugin\_%']]]);
        foreach ($table_iterator as $table_data) {
            $table_name = $table_data['TABLE_NAME'];

            $missing_keys  = $checker->getMissingKeys($table_name);
            if (count($missing_keys) > 0) {
                ksort($missing_keys);
                $has_missing_keys = true;
                $message = '<error>' . sprintf(__('Table "%s" has missing keys:'), $table_name) . '</error>';
                foreach ($missing_keys as $key => $fields) {
                    $message .= sprintf("\n    <comment>KEY `%s` (`%s`)</comment>", $key, implode('`,`', $fields));
                }
                $output->writeln($message, OutputInterface::VERBOSITY_QUIET);
            }

            if ($input->getOption('detect-misnamed-keys')) {
                $misnamed_keys = $checker->getMisnamedKeys($table_name);
                if (count($misnamed_keys) > 0) {
                    ksort($misnamed_keys);
                    $has_misnamed_keys = true;
                    $message = '<info>' . sprintf(__('Table "%s" has misnamed keys:'), $table_name) . '</info>';
                    foreach ($misnamed_keys as $current_key_name => $expected_key_name) {
                        $message .= sprintf("\n    KEY `%s` should be `%s`", $current_key_name, $expected_key_name);
                    }
                    $output->writeln($message, OutputInterface::VERBOSITY_QUIET);
                }
            }

            if ($input->getOption('detect-useless-keys')) {
                $useless_keys = $checker->getUselessKeys($table_name);
                if (count($useless_keys) > 0) {
                    ksort($useless_keys);
                    $has_useless_keys = true;
                    $message = '<info>' . sprintf(__('Table "%s" has useless keys:'), $table_name) . '</info>';
                    foreach ($useless_keys as $current_key_name => $larger_key_name) {
                        $message .= sprintf("\n    KEY `%s` is included in `%s`", $current_key_name, $larger_key_name);
                    }
                    $output->writeln($message, OutputInterface::VERBOSITY_QUIET);
                }
            }
        }

        if ($has_missing_keys) {
            return self::ERROR_FOUND_MISSING_KEYS;
        }
        if ($has_misnamed_keys) {
            return self::ERROR_FOUND_MISNAMED_KEYS;
        }
        if ($has_useless_keys) {
            return self::ERROR_FOUND_USELESS_KEYS;
        }

        $output->writeln('<info>' . __('Database has no missing keys.') . '</info>');

        return 0; // Success
    }
}
