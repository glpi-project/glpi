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

use Glpi\Application\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 *  GLPI (instantiation and so on)
 **/
class GLPI
{
    private $error_handler;
    private $log_level;

    /**
     * Init logger
     *
     * @return void
     */
    public function initLogger()
    {
        global $PHPLOGGER, $SQLLOGGER;

        $this->log_level = Logger::WARNING;
        if (defined('GLPI_LOG_LVL')) {
            $this->log_level = GLPI_LOG_LVL;
        } else if (
            !isset($_SESSION['glpi_use_mode'])
            || ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
        ) {
            $this->log_level = Logger::DEBUG;
        }

        foreach (['php', 'sql'] as $type) {
            $logger = new Logger('glpi' . $type . 'log');
            $handler = new StreamHandler(
                GLPI_LOG_DIR . "/{$type}-errors.log",
                $this->log_level
            );
            $formatter = new LineFormatter(null, 'Y-m-d H:i:s', true, true);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
            switch ($type) {
                case 'php':
                    $PHPLOGGER = $logger;
                    break;
                case 'sql':
                    $SQLLOGGER = $logger;
                    break;
            }
        }
    }

    /**
     * Get log level
     *
     * @return string
     */
    public function getLogLevel()
    {
        Toolbox::deprecated();
        return $this->log_level;
    }

    /**
     * Init and register error handler.
     *
     * @return ErrorHandler
     */
    public function initErrorHandler()
    {
        $this->error_handler = ErrorHandler::getInstance();
        $this->error_handler->register();

        return $this->error_handler;
    }

    /**
     * Get registered error handler.
     *
     * @return null|ErrorHandler
     */
    public function getErrorHandler()
    {
        return $this->error_handler;
    }
}
