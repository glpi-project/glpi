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

namespace Glpi\Log;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class ConsoleCommandRedactionProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record)
    {
        if (array_key_exists('command', $record->context) && is_string($record->context['command'])) {
            // Tokenize respecting single and double quotes (how Input::__toString() formats values)
            preg_match_all('/\'[^\']*\'|"[^"]*"|\S+/', $record->context['command'], $matches);
            $tokens = $matches[0];

            $command_found = false;
            $end_of_options = false;
            $sanitized = [];

            foreach ($tokens as $token) {
                if (!$end_of_options && $token === '--') {
                    // End of options: every following token is positional
                    $end_of_options = true;
                    $sanitized[] = $token;
                    continue;
                }
                if (!$end_of_options && str_starts_with($token, '-')) {
                    // Option token - Keep the flag name, strip everything after '='
                    $sanitized[] = str_contains($token, '=')
                        ? explode('=', $token, 2)[0] . '=***'
                        : $token;
                    continue;
                }

                if (!$command_found) {
                    // First non-option is command name token
                    $sanitized[] = $token;
                    $command_found = true;
                    continue;
                }

                // Positional argument
                $sanitized[] = '***';
            }

            $context = $record->context;
            $context['command'] = implode(' ', $sanitized);

            return $record->with(context: $context);
        }
        return $record;
    }
}
