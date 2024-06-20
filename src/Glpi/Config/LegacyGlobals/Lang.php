<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Config\LegacyGlobals;

use ArrayAccess;
use ArrayObject;
use Glpi\Config\LegacyGlobalProviderInterface;
use Toolbox;

/**
 * This class is made to handle a deprecation phase on the global `$LANG` object.
 *
 * @since 11.0.0
 */
class Lang implements ArrayAccess, LegacyGlobalProviderInterface
{
    /**
     * Multi-dimensional storage.
     * Usage of `ArrayObject` is mandatory to be able to work with references instead of value copies.
     */
    private ArrayObject $data;

    public function __construct()
    {
        $this->data = new ArrayObject();
    }

    public function register(): void
    {
        /**
         * @var self $LANG
         */
        global $LANG;
        $LANG = $this;
    }

    /**
     * @deprecated
     */
    public function offsetGet(mixed $offset): mixed
    {
        Toolbox::deprecated('Usage of the `$LANG` global variable is deprecated.');
        if (!isset($this->data[$offset])) {
            // Mandatory to be compatibile with the `$LANG['plugin']['key'] = 'value'` notation
            // that will be an equivalent of `$LANG->offsetGet('plugin')->offsetSet('key', 'value')`.
            $this->data[$offset] = new ArrayObject();
        }
        return $this->data[$offset];
    }

    /**
     * @deprecated
     */
    public function offsetExists(mixed $offset): bool
    {
        Toolbox::deprecated('Usage of the `$LANG` global variable is deprecated.');
        return $this->data[$offset] instanceof ArrayObject && count($this->data[$offset]) > 0;
    }

    /**
     * @deprecated
     */
    public function offsetUnset(mixed $offset): void
    {
        Toolbox::deprecated('Usage of the `$LANG` global variable is deprecated.');
        unset($this->data[$offset]);
    }

    /**
     * @deprecated
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        Toolbox::deprecated('Usage of the `$LANG` global variable is deprecated.');
        $this->data[$offset] = $value;
    }
}
