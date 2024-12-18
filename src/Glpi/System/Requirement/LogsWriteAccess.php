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

namespace Glpi\System\Requirement;

use Psr\Log\LoggerInterface;

/**
 * @since 9.5.0
 */
class LogsWriteAccess extends AbstractRequirement
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct(
            __('Permissions for log files')
        );

        $this->logger = $logger;
    }

    protected function check()
    {
       // Only write test for GLPI_LOG as SElinux prevent removing log file.
        try {
            $this->logger->warning('Test logger');
            $this->validated = true;
            $this->validation_messages[] = __('The log file has been created successfully.');
        } catch (\UnexpectedValueException $e) {
            $this->validated = false;
            $this->validation_messages[] = sprintf(__('The log file could not be created in %s.'), GLPI_LOG_DIR);
        }
    }
}
