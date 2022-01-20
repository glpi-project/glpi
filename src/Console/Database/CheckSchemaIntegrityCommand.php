<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

namespace Glpi\Console\Database;

use Glpi\Console\AbstractCommand;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckSchemaIntegrityCommand extends AbstractCommand
{
    /**
     * Error code returned when failed to read empty SQL file.
     *
     * @var integer
     */
    const ERROR_UNABLE_TO_READ_EMPTYSQL = 1;

    /**
     * Error code returned when differences are found.
     *
     * @var integer
     */
    const ERROR_FOUND_DIFFERENCES = 2;

    protected function configure()
    {
        parent::configure();

        $this->setName('glpi:database:check_schema_integrity');
        $this->setAliases(
            [
                'db:check_schema_integrity',
                'glpi:database:check', // old name
                'db:check', // old alias
            ]
        );
        $this->setDescription(__('Check for schema differences between current database and installation file.'));

        $this->addOption(
            'strict',
            null,
            InputOption::VALUE_NONE,
            __('Strict comparison of definitions')
        );

        $this->addOption(
            'check-all-migrations',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to all databases migrations.')
        );

        $this->addOption(
            'check-innodb-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from "MyISAM" to "InnoDB".')
        );

        $this->addOption(
            'check-timestamps-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from "datetime" to "timestamp".')
        );

        $this->addOption(
            'check-utf8mb4-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from "utf8" to "utf8mb4".')
        );

        $this->addOption(
            'check-dynamic-row-format-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to "DYNAMIC" row format migration.')
        );

        $this->addOption(
            'check-unsigned-keys-migration',
            null,
            InputOption::VALUE_NONE,
            __('Check tokens related to migration from signed to unsigned integers in primary/foreign keys.')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $checker = new DatabaseSchemaIntegrityChecker(
            $this->db,
            $input->getOption('strict'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-innodb-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-timestamps-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-utf8mb4-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-dynamic-row-format-migration'),
            !$input->getOption('check-all-migrations') && !$input->getOption('check-unsigned-keys-migration')
        );

        if (
            false === ($empty_file = realpath(GLPI_ROOT . '/install/mysql/glpi-empty.sql'))
            || false === ($empty_sql = file_get_contents($empty_file))
        ) {
            $message = sprintf(__('Unable to read installation file "%s".'), $empty_file);
            $output->writeln(
                '<error>' . $message . '</error>',
                OutputInterface::VERBOSITY_QUIET
            );
            return self::ERROR_UNABLE_TO_READ_EMPTYSQL;
        }

        $matches = [];
        preg_match_all('/CREATE TABLE[^`]*`(.+)`[^;]+/', $empty_sql, $matches);
        $empty_tables_names   = $matches[1];
        $empty_tables_schemas = $matches[0];

        $has_differences = false;

        foreach ($empty_tables_schemas as $index => $table_schema) {
            $table_name = $empty_tables_names[$index];

            $output->writeln(
                sprintf(__('Processing table "%s"...'), $table_name),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            if ($checker->hasDifferences($table_name, $table_schema)) {
                $diff = $checker->getDiff($table_name, $table_schema);

                $has_differences = true;
                $message = sprintf(__('Table schema differs for table "%s".'), $table_name);
                $output->writeln(
                    '<info>' . $message . '</info>',
                    OutputInterface::VERBOSITY_QUIET
                );
                 $output->write($diff);
            }
        }

        if ($has_differences) {
            return self::ERROR_FOUND_DIFFERENCES;
        }

        $output->writeln('<info>' . __('Database schema is OK.') . '</info>');

        return 0; // Success
    }
}
