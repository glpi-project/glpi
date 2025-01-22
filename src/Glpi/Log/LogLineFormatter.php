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

use Monolog\Formatter\LineFormatter;

final class LogLineFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct(
            "[%datetime%] %channel%.%level_name%: %message% %context.exception% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );

        $this->setBasePath(\GLPI_ROOT);
        $this->allowInlineLineBreaks();
        $this->ignoreEmptyContextAndExtra();
    }

    #[\Override()]
    protected function normalizeException(\Throwable $e, int $depth = 0): string
    {
        $message = "  Backtrace :\n" . $e->getMessage() . "\n" . $this->getTraceAsString($e->getTrace());

        if (($previous = $e->getPrevious()) instanceof \Throwable) {
            do {
                $depth++;
                $message .= "  Previous: " . $previous->getMessage() . "\n" . $this->getTraceAsString($previous->getTrace());
                if ($depth > $this->maxNormalizeDepth) {
                    break;
                }
            } while ($previous = $previous->getPrevious());
        }

        return $message;
    }

    private function getTraceAsString(array $trace): string
    {
        if (empty($trace)) {
            return '';
        }

        $message = '';

        foreach ($trace as $item) {
            $script = ($item['file'] ?? '') . ':' . ($item['line'] ?? '');
            if (\str_starts_with($script, \GLPI_ROOT)) {
                $script = \substr($script, \strlen(\GLPI_ROOT) + 1);
            }
            if (\strlen($script) > 50) {
                $script = '...' . \substr($script, -47);
            } else {
                $script = \str_pad($script, 50);
            }

            $call = ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? '');
            if (!empty($call)) {
                $call .= '()';
            }
            $message .= "  $script $call\n";
        }

        return $message;
    }
}
