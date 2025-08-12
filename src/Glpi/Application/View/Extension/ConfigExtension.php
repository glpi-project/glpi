<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Entity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class ConfigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', [$this, 'config']),
            new TwigFunction('entity_config', [$this, 'getEntityConfig']),
        ];
    }

    /**
     * Get GLPI configuration value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function config(string $key)
    {
        global $CFG_GLPI;

        return $CFG_GLPI[$key] ?? null;
    }

    /**
     * Get entity configuration value.
     *
     * @param string        $key              Configuration key.
     * @param int           $entity_id        Entity ID.
     * @param mixed         $default_value    Default value.
     * @param null|string   $inheritence_key  Key to use for inheritance check if different than key used to get value.
     *
     * @return mixed
     */
    public function getEntityConfig(string $key, int $entity_id, $default_value = -2, ?string $inheritence_key = null)
    {
        if ($inheritence_key === null) {
            $inheritence_key = $key;
        }

        return Entity::getUsedConfig($inheritence_key, $entity_id, $key, $default_value);
    }
}
