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

namespace Glpi\Api\HL;

use Exception;
use Throwable;

/**
 * An exception thrown by the API.
 * A user message can be provided to be displayed to the user.
 * Otherwise, only a generic message will be displayed to the user.
 */
class APIException extends Exception
{
    private string $user_message;

    private string|array|null $details;

    public function __construct(string $message = '', string $user_message = '', string|array|null $details = null, int $code = 0, ?Throwable $previous = null)
    {
        if ($user_message === '') {
            $user_message = __('An error occurred while processing your request.');
        }
        if ($message === '') {
            $message = $user_message;
        }
        $this->user_message = $user_message;
        $this->details = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getUserMessage(): string
    {
        return $this->user_message;
    }

    public function getDetails(): string|array|null
    {
        return $this->details;
    }
}
