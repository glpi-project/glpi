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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use KnowbaseItem;

/**
 * Parameters for "KnowbaseItem" items.
 *
 * @since 10.0.0
 */
class KnowbaseItemParameters extends AbstractParameters
{
    public static function getDefaultNodeName(): string
    {
        return 'knowbaseitem';
    }

    public static function getObjectLabel(): string
    {
        return KnowbaseItem::getTypeName(1);
    }

    protected function getTargetClasses(): array
    {
        return [KnowbaseItem::class];
    }

    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
            new AttributeParameter("name", __('Subject')),
            new AttributeParameter("answer", __('Content'), "raw"),
            new AttributeParameter("link", _n('Link', 'Links', 1), "raw"),
        ];
    }

    protected function defineValues(CommonDBTM $kbi): array
    {
        $fields = $kbi->fields;

        return [
            'id'     => $fields['id'],
            'name'   => $fields['name'],
            'answer' => $fields['answer'],
            'link'   => $kbi->getLink(),
        ];
    }
}
