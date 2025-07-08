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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Form\AnswersSet;

enum TemplateFieldStrategy: string
{
    case DEFAULT_TEMPLATE = 'default_template';
    case SPECIFIC_TEMPLATE = 'specific_template';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEFAULT_TEMPLATE     => __("Default template"),
            self::SPECIFIC_TEMPLATE    => __("Specific template"),
        };
    }

    public function getTemplateID(
        TemplateFieldConfig $config,
        AnswersSet $answers_set,
    ): ?int {
        return match ($this) {
            self::DEFAULT_TEMPLATE => null, // Use default template
            self::SPECIFIC_TEMPLATE => $config->getSpecificTemplateID()
        };
    }
}
