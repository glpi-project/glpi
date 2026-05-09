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

namespace Glpi\Api\HL;

use Glpi\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * This class wraps a Symfony {@link StreamedResponse} to allow it to be used in the API, which expects a PSR-7 response.
 *
 * @internal Intended to be removed in the future, when the API requests/responses use Symfony's classes which aren't PSR-7 compatible.
 */
final class StreamedResponseWrapper extends Response
{
    public function __construct(
        private StreamedResponse $symfony_response
    ) {
        parent::__construct(
            $symfony_response->getStatusCode(),
            $symfony_response->headers->all(),
            $symfony_response->getContent()
        );
    }

    /**
     * Get the underlying Symfony StreamedResponse with some of its data synced with the PSR-7 response.
     * @return StreamedResponse
     */
    public function getSymfonyResponse(): StreamedResponse
    {
        $this->symfony_response->setStatusCode($this->getStatusCode());
        foreach ($this->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                $this->symfony_response->headers->set($header, $value);
            }
        }
        return $this->symfony_response;
    }
}
