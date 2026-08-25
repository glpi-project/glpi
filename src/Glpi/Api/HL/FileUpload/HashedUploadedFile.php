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

namespace Glpi\Api\HL\FileUpload;

use GuzzleHttp\Psr7\UploadedFile;

class HashedUploadedFile extends UploadedFile
{
    private string $hash_algo;
    private string $hash;

    public function __construct($streamOrFile, ?int $size, int $errorStatus, ?string $clientFilename, ?string $clientMediaType, string $hash_algo, string $hash)
    {
        parent::__construct($streamOrFile, $size, $errorStatus, $clientFilename, $clientMediaType);
        $this->hash_algo = $hash_algo;
        $this->hash = $hash;
    }

    public function getHashAlgo(): string
    {
        return $this->hash_algo;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
