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

namespace Glpi\Form\Destination;

// singleton
final class FormDestinationTypeManager
{
    /**
     * Singleton instance
     */
    private static ?FormDestinationTypeManager $instance = null;

    /** @var FormDestinationInterface[] */
    private array $plugins_destinations_types = [];

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
     * @return FormDestinationInterface[]
     */
    public function getDestinationTypes(): array
    {
        return [
            new FormDestinationTicket(),
            new FormDestinationProblem(),
            new FormDestinationChange(),
            ...$this->plugins_destinations_types,
        ];
    }

    /**
     * Get a array of class => value pairs to be used in dropdowns.
     *
     * @return array
     */
    public function getDestinationTypesDropdownValues(): array
    {
        $types = $this->getDestinationTypes();
        uasort(
            $types,
            fn(
                FormDestinationInterface $a,
                FormDestinationInterface $b,
            ): int => $a->getWeight() <=> $b->getWeight()
        );

        $values = [];
        foreach ($this->getDestinationTypes() as $type) {
            $values[get_class($type)] = $type->getLabel();
        }

        return $values;
    }

    /**
     * Default (most common) type.
     *
     * @return FormDestinationInterface
     */
    public function getDefaultType(): FormDestinationInterface
    {
        return new FormDestinationTicket();
    }

    public function registerPluginDestinationType(
        FormDestinationInterface $type
    ): void {
        $this->plugins_destinations_types[] = $type;
    }
}
