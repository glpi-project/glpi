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

use Monolog\LogRecord;
use Override;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessLogHandler extends AbstractLogHandler
{
    public function __construct()
    {
        parent::__construct(GLPI_LOG_DIR . '/access-errors.log');

        $this->setFormatter(new AccessLogLineFormatter());
    }

    #[Override()]
    public function isHandling(LogRecord $record): bool
    {
        if (
            !isset($record->context['exception'])
            || !($record->context['exception'] instanceof AccessDeniedHttpException)
        ) {
            // Do not log anything else than "access denied" exceptions.
            return false;
        }

        return parent::isHandling($record);
    }
}
