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

namespace Glpi\Tests\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestDataSanitizationCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setName(self::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $DB;

        $errors = 0;

        $table_iterator = $DB->listTables();
        foreach ($table_iterator as $table_data) {
            $table_name = $table_data['TABLE_NAME'];
            $excluded_fields = $this->getExcludedFields($table_name);

            $row_iterator = $DB->request(['FROM' => $table_name]);
            foreach ($row_iterator as $row_data) {
                foreach ($row_data as $field_name => $field_value) {
                    if (in_array($field_name, $excluded_fields)) {
                        continue;
                    }
                    if (!is_string($field_value)) {
                        continue;
                    }
                    if (preg_match('/(<|>|(&(?!#?[a-z0-9]+;)))/i', $field_value) === 1) {
                        $identifier_property = array_key_exists('name', $row_data)
                            ? 'name'
                            : 'id';
                        $msg = sprintf(
                            '`%s`.`%s` (%s: "%s") contains raw html value: "%s"',
                            $table_name,
                            $field_name,
                            $identifier_property,
                            $row_data[$identifier_property],
                            $field_value
                        );
                        $output->writeln(
                            '<error>' . $msg . '</error>',
                            OutputInterface::VERBOSITY_QUIET
                        );
                        $errors++;
                    }
                }
            }
        }

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Return list of fields to exclude from check.
     * These fields are known to be containing non encoded html data.
     *
     * @return array
     */
    private function getExcludedFields(string $table_name): array
    {
        $excluded_fields = [
            '*' => [
                'completename', // completename `>` separator is never encoded
            ],
        ];
        return array_merge(
            $excluded_fields['*'],
            $excluded_fields[$table_name] ?? []
        );
    }
}
