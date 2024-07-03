<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Config;

final readonly class AppConfig implements \ArrayAccess
{
    public function getRootDoc(): string
    {
        return $this->get('root_doc');
    }

    public function offsetExists(mixed $offset): bool
    {
        // TODO: trigger deprecation for deprecated keys
        return \array_key_exists($offset, $this->all());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // TODO: throw error, unsetting config should not be supported.
        unset($CFG_GLPI[$offset]);
    }

    private function set(mixed $offset, mixed $value): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // TODO: add validation for readonly fields
        $CFG_GLPI[$offset] = $value;
    }

    private function get(mixed $offset)
    {
        // TODO: add validation for inexistent keys
        // TODO: trigger deprecation for deprecated keys

        return $this->all()[$offset];
    }

    private function all(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI;
    }
}
