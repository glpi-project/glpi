<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;

/**
 * Abstract twig content templates parameters definition.
 *
 * @since 10.0.0
 */
abstract class AbstractParameters implements TemplatesParametersInterface
{
    /**
     * To by defined in each subclasses, get the exposed values for a given item
     * These values will be used as parameters when rendering a twig template.
     *
     * Result will be returned by `self::getValues()`.
     *
     * @param CommonDBTM $item
     *
     * @return array
     */
    abstract protected function defineValues(CommonDBTM $item): array;

    /**
     * Get supported classes by this parameter type.
     *
     * @return array
     */
    abstract protected function getTargetClasses(): array;

    public function getValues(CommonDBTM $item): array
    {
        $valid_class = false;
        foreach ($this->getTargetClasses() as $class) {
            if ($item instanceof $class) {
                $valid_class = true;
                break;
            }
        }

        if (!$valid_class) {
            trigger_error(get_class($item) . " is not allowed for this parameter type.", E_USER_WARNING);
            return [];
        }

        return $this->defineValues($item);
    }
}
