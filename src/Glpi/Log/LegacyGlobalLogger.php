<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LegacyGlobalLogger implements LoggerInterface
{
    private function getLogger(): LoggerInterface
    {
        /**
         * @var null|\Psr\Log\LoggerInterface $PHPLOGGER
         */
        global $PHPLOGGER;

        return $PHPLOGGER ?? new NullLogger();
    }

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->emergency($message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->alert($message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->critical($message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->error($message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->warning($message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->notice($message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->info($message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        // Debug is disabled for now because Symfony generates a lot of debug logs that GLPI doesn't need yet.
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->getLogger()->log($level, $message, $context);
    }
}
