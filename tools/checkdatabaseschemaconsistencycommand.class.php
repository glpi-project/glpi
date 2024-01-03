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
use Glpi\System\Diagnostic\DatabaseSchemaConsistencyChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDatabaseSchemaConsistencyCommand extends AbstractCommand
{
    /**
     * Error code returned when missing fields are found.
     *
     * @var integer
     */
    const ERROR_FOUND_MISSING_FIELDS = 1;

    protected function configure()
    {
        parent::configure();

        $this->setName('tools:check_database_schema_consistency');
        $this->setDescription(__('Check database schema consistency.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $checker = new DatabaseSchemaConsistencyChecker($this->db);

        $has_missing_fields = false;

        $table_iterator = $this->db->listTables('glpi\_%', ['NOT' => ['table_name' => ['LIKE', 'glpi\_plugin\_%']]]);
        foreach ($table_iterator as $table_data) {
            $table_name = $table_data['TABLE_NAME'];

            $missing_fields  = $checker->getMissingfields($table_name);
            if (count($missing_fields) > 0) {
                ksort($missing_fields);
                $has_missing_fields = true;
                $message = sprintf(
                    __('Table "%s" has missing fields: `%s`'),
                    $table_name,
                    implode('`,`', $missing_fields)
                );
                $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
            }
        }

        if ($has_missing_fields) {
            return self::ERROR_FOUND_MISSING_FIELDS;
        }

        $output->writeln('<info>' . __('Database schema is consistent.') . '</info>');

        return 0; // Success
    }
}
