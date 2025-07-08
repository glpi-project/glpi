<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Error\ErrorDisplayHandler;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleErrorDisplayHandler implements ErrorDisplayHandler
{
    private static ?OutputInterface $output = null;

    public static function setOutput(OutputInterface $output): void
    {
        self::$output = $output;
    }

    public function canOutput(): bool
    {
        return self::$output !== null;
    }

    public function displayErrorMessage(string $error_label, string $message, string $log_level): void
    {
        $format = 'comment';
        switch ($log_level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                $format    = 'error';
                $verbosity = OutputInterface::VERBOSITY_QUIET;
                break;
            case LogLevel::WARNING:
                $verbosity = OutputInterface::VERBOSITY_NORMAL;
                break;
            case LogLevel::NOTICE:
            case LogLevel::INFO:
            default:
                $verbosity = OutputInterface::VERBOSITY_VERBOSE;
                break;
            case LogLevel::DEBUG:
                $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
                break;
        }

        self::$output->writeln(
            \sprintf(
                '<%1$s>%2$s</%1$s>',
                $format,
                $error_label . ': ' . $message
            ),
            $verbosity
        );
    }
}
