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

use Glpi\Application\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

/**
 *  GLPI (instantiation and so on)
 **/
class GLPI
{
    /**
     * Production environment.
     */
    public const ENV_PRODUCTION  = 'production';

    /**
     * Staging environment.
     * Suitable for pre-production servers and customer acceptance tests.
     */
    public const ENV_STAGING     = 'staging';

    /**
     * Testing environment.
     * Suitable for CI runners, quality control and internal acceptance tests.
     */
    public const ENV_TESTING     = 'testing';

    /**
     * Development environment.
     * Suitable for developer machines and development servers.
     */
    public const ENV_DEVELOPMENT = 'development';

    private $error_handler;

    /**
     * Init logger
     *
     * @return void
     */
    public function initLogger()
    {
        /**
         * @var \Psr\Log\LoggerInterface $PHPLOGGER
         * @var \Psr\Log\LoggerInterface $SQLLOGGER
         */
        global $PHPLOGGER, $SQLLOGGER;

        if (defined('GLPI_LOG_LVL')) {
            $log_level = GLPI_LOG_LVL;
        } else {
            switch (GLPI_ENVIRONMENT_TYPE) {
                case self::ENV_DEVELOPMENT:
                    // All error/messages are logs, including deprecations.
                    $log_level = LogLevel::DEBUG;
                    break;
                case self::ENV_TESTING:
                    // Silent deprecation and info, as they should have no functional impact.
                    // Keep notices as they have may indicate that code is not correctly handling a specific case.
                    $log_level = LogLevel::NOTICE;
                    break;
                case self::ENV_STAGING:
                case self::ENV_PRODUCTION:
                default:
                    // Keep only warning/error messages.
                    $log_level = LogLevel::WARNING;
                    break;
            }
        }

        foreach (['php', 'sql'] as $type) {
            $logger = new Logger('glpi' . $type . 'log');
            $handler = new StreamHandler(
                GLPI_LOG_DIR . "/{$type}-errors.log",
                $log_level
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
     * Init and register error handler.
     *
     * @return void
     */
    public function initErrorHandler(): void
    {
        ErrorHandler::getInstance()->register();
    }
}
