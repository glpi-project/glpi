<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

 namespace Glpi\Application\View\Extension;

use Glpi\UI\Component\ComponentInterface;
use Glpi\UI\Component\StaticCachedComponent;
use LogicException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension used to register UI component related funtions
 */
class ComponentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_component', [$this, 'renderComponent']),
        ];
    }

    /**
     * Render the given component
     *
     * @param string $ui_component_class Component class
     * @param array  $parameters         Component parameters
     * @param array  $options            Optional parameters:
     *  - (bool) static_cache: enable simple, single request, static caching for
     *                         this component
     *
     * @return string
     *
     * @throws LogicException
     */
    public function renderComponent(
        string $ui_component_class,
        array $parameters = [],
        array $options = []
    ): string {
        // Validate component class
        if (!is_a($ui_component_class, ComponentInterface::class, true)) {
            throw new LogicException("'$ui_component_class' is not a component");
        }

        // Create component
        $component = new $ui_component_class();

        // Enable static caching if requested
        if ($options['static_cache'] ?? false) {
            $component = new StaticCachedComponent($component);
        }

        // Render component
        return $component->render($parameters);
    }
}
