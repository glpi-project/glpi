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

namespace Glpi\Form\Destination;

// singleton
final class FormDestinationTypeManager
{
    /**
     * Singleton instance
     */
    private static ?FormDestinationTypeManager $instance = null;

    /**
     * Private constructor (singleton)
     */
    private function __construct()
    {
    }

    /**
     * Singleton access method
     *
     * @return FormDestinationTypeManager
     */
    public static function getInstance(): FormDestinationTypeManager
    {
        if (self::$instance === null) {
            self::$instance = new FormDestinationTypeManager();
        }

        return self::$instance;
    }

    /**
     * Get all available destinations types
     *
     * @return AbstractFormDestinationType[]
     */
    public function getDestinationTypes(): array
    {
        // TODO: support plugin types
        return  [
            new FormDestinationTicket(),
            new FormDestinationProblem(),
            new FormDestinationChange(),
        ];
    }

    /**
     * Get a array of class => value pairs to be used in dropdowns.
     *
     * @return array
     */
    public function getDestinationTypesDropdownValues(): array
    {
        $values = [];
        foreach ($this->getDestinationTypes() as $type) {
            $values[get_class($type)] = $type->getTypeName(1);
        }

        return $values;
    }

    /**
     * Default (most common) type.
     *
     * @return AbstractFormDestinationType
     */
    public function getDefaultType(): AbstractFormDestinationType
    {
        return new FormDestinationTicket();
    }
}
