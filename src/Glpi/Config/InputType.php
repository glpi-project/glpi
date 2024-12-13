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

namespace Glpi\Config;

/**
 * Enumeration of the different types of configuration options provided by GLPI.
 * These types are used to determine how to display the option in the UI when editing it.
 * @note If you add/change/remove types here, you must also update the following files:
 * - templates/pages/setup/general/base_form.twig
 */
enum InputType
{
    case CHECKBOX;
    case COLOR;
    case CUSTOM;
    case DATE;
    case DATETIME;
    case DROPDOWN_ARRAY;
    case DROPDOWN_NUMBER;
    case DROPDOWN_FREQUENCY;
    case DROPDOWN_HOUR;
    case DROPDOWN_ICON;
    case DROPDOWN_ITEM;
    case DROPDOWN_ITEMTYPE;
    case DROPDOWN_TIMESTAMP;
    case DROPDOWN_YES_NO;
    case EMAIL;
    case FILE;
    case IMAGE;
    case IMAGE_GALLERY;
    case NUMBER;
    case PASSWORD;
    case SLIDER;
    case TEXT;
    case TEXTAREA;
}
