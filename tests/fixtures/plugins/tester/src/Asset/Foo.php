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

namespace GlpiPlugin\Tester\Asset;

use CommonDBChild;
use Override;

final class Foo extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return 'Foo';
    }

    #[Override]
    public static function getIcon()
    {
        return 'ti ti-foo';
    }

    public static function rawSearchOptionsToAdd()
    {

        $so = [];

        $so[] = [
            'id'       => 'foo',
            'name'     => static::getTypeName(),
        ];

        $so[] = [
            'id'       => '123456',
            'table'    => self::getTable(),
            'field'    => 'name',
            'name'     => 'Name',
            'datatype' => 'itemlink',
        ];

        return $so;
    }
}
