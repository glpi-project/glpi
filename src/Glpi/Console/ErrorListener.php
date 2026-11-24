<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replaces the Symfony `console.error_listener`, to log only the command name
 * and never its arguments and options values (e.g. `--db-password`).
 */
final readonly class ErrorListener implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::ERROR => ['onConsoleError', -128],
            ConsoleEvents::TERMINATE => ['onConsoleTerminate', -128],
        ];
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $error = $event->getError();

        if (($command = $event->getCommand()?->getName()) === null) {
            $this->logger->critical(
                'An error occurred while using the console. Message: "{message}"',
                ['exception' => $error, 'message' => $error->getMessage()]
            );
            return;
        }

        $this->logger->critical(
            'Error thrown while running command "{command}". Message: "{message}"',
            ['exception' => $error, 'command' => $command, 'message' => $error->getMessage()]
        );
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $exit_code = $event->getExitCode();

        if ($exit_code === 0) {
            return;
        }

        if (($command = $event->getCommand()?->getName()) === null) {
            $this->logger->debug('The console exited with code "{code}"', ['code' => $exit_code]);
            return;
        }

        $this->logger->debug(
            'Command "{command}" exited with code "{code}"',
            ['command' => $command, 'code' => $exit_code]
        );
    }
}
