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

use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Form;

// singleton
final class FormDestinationManager
{
    /**
     * Singleton instance
     */
    private static ?FormDestinationManager $instance = null;

    /** @var FormDestinationInterface[] */
    private array $plugins_destinations_types = [];

    /**
     * @var array<class-string<AbstractCommonITILFormDestination>, AbstractConfigField[]>
     * Mapping of common ITIL destination types to their additional config fields
     */
    private array $plugins_common_itil_config_fields = [];

    /**
     * Private constructor (singleton)
     */
    private function __construct() {}

    /**
     * Singleton access method
     *
     * @return FormDestinationManager
     */
    public static function getInstance(): FormDestinationManager
    {
        if (self::$instance === null) {
            self::$instance = new FormDestinationManager();
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

    public function getWarnings(Form $form): array
    {
        $warnings = [];
        $destinations = $form->getDestinations();
        $destinations_without_conditions = array_filter(
            $destinations,
            fn($d) => !in_array($d->fields['creation_strategy'], [
                CreationStrategy::CREATED_IF->value,
                CreationStrategy::CREATED_UNLESS->value,
            ]),
        );

        if (count($destinations) == 0) {
            // Add a warning if a form does not have at least one destination.
            $warnings[] = __("This form is invalid, it must create at least one item.");
        } elseif (count($destinations_without_conditions) == 0) {
            // Add a warning if a form does not have at least one destination that
            // is always created.
            $warnings[] = __("You have defined conditions for all the items below. This may be dangerous, please make sure that in every situation at least one item will be created.");
        }

        return $warnings;
    }

    public function registerPluginDestinationType(
        FormDestinationInterface $type
    ): void {
        $this->plugins_destinations_types[] = $type;
    }

    /**
     * Register a config field from a plugin for a specific common ITIL destination type
     *
     * @param class-string<AbstractCommonITILFormDestination> $destination_class The common ITIL destination class
     * @param AbstractConfigField $field The config field to register
     * @return void
     */
    public function registerPluginCommonITILConfigField(
        string $destination_class,
        AbstractConfigField $field
    ): void {
        if (!isset($this->plugins_common_itil_config_fields[$destination_class])) {
            $this->plugins_common_itil_config_fields[$destination_class] = [];
        }
        $this->plugins_common_itil_config_fields[$destination_class][] = $field;
    }

    /**
     * Get all registered plugin config fields for a specific common ITIL destination type
     *
     * @param class-string<AbstractCommonITILFormDestination> $destination_class The common ITIL destination class
     * @return AbstractConfigField[]
     */
    public function getPluginCommonITILConfigFields(string $destination_class): array
    {
        $config_fields = [];
        foreach (array_keys($this->plugins_common_itil_config_fields) as $dest_class) {
            if (is_a($destination_class, $dest_class, true)) {
                $config_fields = array_merge(
                    $config_fields,
                    $this->plugins_common_itil_config_fields[$dest_class]
                );
            }
        }

        return $config_fields;
    }
}
