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

use Glpi\Error\ErrorUtils;
use Monolog\Formatter\LineFormatter;
use Override;
use Throwable;

abstract class AbstractLogLineFormatter extends LineFormatter
{
    #[Override()]
    protected function normalizeException(Throwable $e, int $depth = 0): string
    {
        $message = \sprintf(
            "\n  Backtrace :\n%s",
            $this->getTraceAsString(
                array_merge(
                    [
                        [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ],
                    ],
                    $e->getTrace()
                )
            )
        );

        if (($previous = $e->getPrevious()) instanceof Throwable) {
            do {
                $depth++;
                $message .= sprintf(
                    "  Previous: %s\n%s",
                    $previous->getMessage(),
                    $this->getTraceAsString(
                        array_merge(
                            [
                                [
                                    'file' => $previous->getFile(),
                                    'line' => $previous->getLine(),
                                ],
                            ],
                            $previous->getTrace()
                        )
                    ),
                );
                if ($depth > $this->maxNormalizeDepth) {
                    break;
                }
            } while ($previous = $previous->getPrevious());
        }

        return $message;
    }

    public function stringify($value): string
    {
        return $this->cleanPath(parent::stringify($value));
    }

    private function getTraceAsString(array $trace): string
    {
        if ($trace === []) {
            return '';
        }

        $message = '';

        foreach ($trace as $item) {
            $script = $this->cleanPath($item['file'] ?? '');

            $script .= ':' . ($item['line'] ?? '');

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

    private function cleanPath(string $path): string
    {
        return ErrorUtils::cleanPaths($path);
    }
}
