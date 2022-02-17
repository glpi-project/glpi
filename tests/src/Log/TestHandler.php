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

namespace Glpi\Tests\Log;

use Monolog\Handler\TestHandler as BaseTestHandler;
use Monolog\Logger;

class TestHandler extends BaseTestHandler
{
    public function dropFromRecords(string $message, int $level): void
    {
        foreach ($this->records as $index => $record) {
            if (Logger::toMonologLevel($record['level']) === $level && $record['message'] === $message) {
                unset($this->records[$index]);
                break;
            }
        }

        foreach ($this->recordsByLevel[$level] as $index => $record) {
            if ($record['message'] === $message) {
                unset($this->recordsByLevel[$level][$index]);
                break;
            }
        }
    }
}
