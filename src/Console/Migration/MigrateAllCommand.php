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

namespace Glpi\Console\Migration;

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class MigrateAllCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('migration:migrate_all');
        $this->setDescription(__('Execute all recommended optional migrations.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = [
            'migration:myisam_to_innodb',
            'migration:dynamic_row_format',
            'migration:timestamps',
            'migration:utf8mb4',
            'migration:unsigned_keys',
        ];

        $options = [];
        foreach ($this->input->getOptions() as $option => $value) {
            if ($value === false || $value === null) {
                continue;
            }
            $options['--' . $option] = $value;
        }

        foreach ($commands as $name) {
            $this->output->writeln(
                '<comment>' . sprintf(__('Executing command "%s"...'), $name) . '</comment>',
            );
            $result = $this->getApplication()
                ->find($name)
                ->run(
                    new ArrayInput($options),
                    $this->output
                );

            if ($result !== self::SUCCESS) {
                return $result;
            }
        }

        return self::SUCCESS;
    }
}
