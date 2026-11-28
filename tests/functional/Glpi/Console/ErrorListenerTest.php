<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Console;

use Glpi\Console\ErrorListener;
use Glpi\Kernel\Kernel;
use Glpi\Tests\GLPITestCase;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\EventListener\ErrorListener as SymfonyErrorListener;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

class ErrorListenerTest extends GLPITestCase
{
    public function testListenerReplacesSymfonyListener(): void
    {
        /** @var Kernel $kernel */
        global $kernel;

        $listeners = array_map(
            static fn(array $listener) => $listener[0]::class,
            $kernel->getContainer()->get('event_dispatcher')->getListeners(ConsoleEvents::ERROR)
        );

        $this->assertContains(ErrorListener::class, $listeners);
        $this->assertNotContains(SymfonyErrorListener::class, $listeners);
    }

    public function testErrorLogDoesNotContainOptionsValues(): void
    {
        global $PHPLOGGER;

        $event = new ConsoleErrorEvent(
            new ArgvInput(['bin/console', 'database:install', '--db-password=secret']),
            new NullOutput(),
            new RuntimeException('Unable to install the database.'),
            new Command('database:install')
        );
        (new ErrorListener($PHPLOGGER))->onConsoleError($event);

        $this->hasPhpLogRecordThatContains(
            'Error thrown while running command "database:install". Message: "Unable to install the database."',
            LogLevel::CRITICAL
        );
    }
}
