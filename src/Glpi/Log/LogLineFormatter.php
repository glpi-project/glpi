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

namespace Glpi\Log;

use Glpi\Error\StackTraceFormatter;
use Monolog\Formatter\LineFormatter;

final class LogLineFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct("[%datetime%] %channel%.%level_name%: %message% %context.exception% %context% %extra%\n", 'Y-m-d H:i:s', true, true);

        $this->setBasePath(GLPI_ROOT);
        $this->allowInlineLineBreaks();
        $this->ignoreEmptyContextAndExtra();
        $this->indentStacktraces('  ');
        $this->includeStacktraces();
    }

    protected function normalizeException(\Throwable $e, int $depth = 0): string
    {
        $message = $e->getMessage() . "\n" . StackTraceFormatter::getTraceAsString($e->getTrace());

        if (($previous = $e->getPrevious()) instanceof \Throwable) {
            do {
                $depth++;
                $message .= "\n Previous: " . $previous->getMessage() . "\n" . StackTraceFormatter::getTraceAsString($previous->getTrace());
                if ($depth > $this->maxNormalizeDepth) {
                    break;
                }
            } while ($previous = $previous->getPrevious());
        }

        return $message;
    }
}
