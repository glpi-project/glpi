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

namespace Glpi\Api\HL\GraphQL\Type;

use GLPIKey;
use GraphQL\Type\Definition\StringType;
use UnexpectedValueException;

/**
 * GraphQL type for strings encrypted with GLPI's encryption key that should be decrypted before being returned to the client.
 * Do not use for any values that need to remain secret. In fact, you probably shouldn't even make those properties anything but write-only.
 */
class EncryptedStringType extends StringType
{
    public string $name = 'EncryptedString';

    private static ?EncryptedStringType $instance = null;

    public function serialize($value): string
    {
        $value = (new GLPIKey())->decrypt($value);
        // At this point, the value provided to this function should not be null, so we should never get null back from the decryption.
        if ($value === null) {
            throw new UnexpectedValueException('Decrypted value is null');
        }
        return $value;
    }

    public static function encryptedString(): self
    {
        if (self::$instance === null) {
            self::$instance = new EncryptedStringType();
        }
        return self::$instance;
    }
}
