<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Http;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends a streamed response without specific headers.
 * Headers could still be sent by the callback function.
 * This class is for internal use only and is a temporary solution to get around the following PHP bug:
 * - https://bugs.php.net/bug.php?id=81451
 * - https://stackoverflow.com/questions/69197771/why-is-function-http-response-code-acting-strange-that-was-called-after-functi/69213593#69213593
 *
 * @since 11.0.0
 * @deprecated 11.0.0
 */
class HeaderlessStreamedResponse extends StreamedResponse
{
    public function __construct(?callable $callback = null)
    {
        parent::__construct($callback);
    }

    public function sendHeaders(): static
    {
        // Sending headers is disabled.

        return $this;
    }
}
