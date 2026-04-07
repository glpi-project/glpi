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

namespace Glpi\Exception;

use Exception;

class ReauthRedirectException extends Exception
{
    /**
     * @param array<string, string> $data
     */
    public function __construct(
        private readonly string $url,
        private readonly array  $data,
        /** @var 'POST'|'GET' */
        private readonly string $http_method,
    ) {
        parent::__construct();
    }

    public function getUrl(): string
    {
        // if method is post, retrieved id and add it in url
        if ($this->http_method === 'POST' && isset($this->data['id'])) {
            return $this->url . '?id=' . $this->data['id'];
        }

        return $this->url;
    }

    /**
     * @return array<string, string> $post
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getHttpMethod(): string
    {
        return $this->http_method;
    }
}
