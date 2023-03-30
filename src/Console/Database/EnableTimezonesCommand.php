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

namespace Glpi\Console\Database;

use DBConnection;
use Glpi\Console\AbstractCommand;
use Glpi\System\Requirement\DbTimezones;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableTimezonesCommand extends AbstractCommand
{
    /**
     * Error code returned if DB configuration file cannot be updated.
     *
     * @var integer
     */
    const ERROR_UNABLE_TO_UPDATE_CONFIG = 1;

    /**
     * Error code returned if prerequisites are missing.
     *
     * @var integer
     */
    const ERROR_MISSING_PREREQUISITES = 2;

    /**
     * Error code returned if some tables are still using datetime field type.
     *
     * @var integer
     */
    const ERROR_TIMESTAMP_FIELDS_REQUIRED = 3;

    protected function configure()
    {
        parent::configure();

        $this->setName('database:enable_timezones');
        $this->setAliases(['db:enable_timezones']);
        $this->setDescription(__('Enable timezones usage.'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timezones_requirement = new DbTimezones($this->db);

        if (!$timezones_requirement->isValidated()) {
            $message = '<error>' . __('Timezones usage cannot be activated due to following errors:') . '</error>';
            foreach ($timezones_requirement->getValidationMessages() as $validation_message) {
                $message .= PHP_EOL . ' - <error>' . $validation_message . '</error>';
            }
            throw new \Glpi\Console\Exception\EarlyExitException(
                $message,
                self::ERROR_MISSING_PREREQUISITES
            );
        }

        if (($datetime_count = $this->db->getTzIncompatibleTables()->count()) > 0) {
            $message = sprintf(__('%1$s columns are using the deprecated datetime storage field type.'), $datetime_count)
            . ' '
            . sprintf(__('Run the "%1$s" command to migrate them.'), 'php bin/console migration:timestamps');
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . $message . '</error>',
                self::ERROR_TIMESTAMP_FIELDS_REQUIRED
            );
        }

        if (!DBConnection::updateConfigProperty(DBConnection::PROPERTY_USE_TIMEZONES, true)) {
            throw new \Glpi\Console\Exception\EarlyExitException(
                '<error>' . __('Unable to update DB configuration file.') . '</error>',
                self::ERROR_UNABLE_TO_UPDATE_CONFIG
            );
        }

        $output->writeln('<info>' . __('Timezone usage has been enabled.') . '</info>');

        return 0; // Success
    }
}
