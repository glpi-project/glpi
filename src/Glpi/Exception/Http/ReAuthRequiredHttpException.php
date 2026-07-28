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

declare(strict_types=1);

namespace Glpi\Exception\Http;

/**
 * A re-authentication is required, but it cannot be asked for on this request.
 *
 * The re-authentication prompt is a full page: serving it as the answer to an AJAX request would
 * make the caller inject the form somewhere in the current page, and it means nothing to a client
 * expecting anything else than HTML. Those requests get this exception instead of the usual
 * redirection to the prompt.
 *
 * It is a plain 403 carrying the {@see self::HEADER} response header, so that a client can tell it
 * apart from a "not enough rights" 403 and offer the user a way to re-authenticate — typically by
 * reloading the current page, which goes through the regular redirection flow.
 */
final class ReAuthRequiredHttpException extends AccessDeniedHttpException
{
    /**
     * Response header marking the reason of the 403.
     */
    public const string HEADER = 'X-Glpi-Reauth-Required';

    public function __construct(string $message = 'Re-authentication required.')
    {
        parent::__construct($message, headers: [self::HEADER => '1']);
    }
}
