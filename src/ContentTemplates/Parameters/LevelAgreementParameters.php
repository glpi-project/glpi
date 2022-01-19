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
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\Toolbox\Sanitizer;
use LevelAgreement;

/**
 * Parameters for "LevelAgreement" items.
 *
 * @since 10.0.0
 */
abstract class LevelAgreementParameters extends AbstractParameters
{
    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
            new AttributeParameter("name", __('Name')),
            new AttributeParameter("type", _n('Type', 'Types', 1)),
            new AttributeParameter("duration", __('Duration')),
            new AttributeParameter("unit", __('Duration unit')),
        ];
    }

    protected function defineValues(CommonDBTM $sla): array
    {

       // Output "unsanitized" values
        $fields = Sanitizer::unsanitize($sla->fields);

        return [
            'id'       => $fields['id'],
            'name'     => $fields['name'],
            'type'     => LevelAgreement::getOneTypeName($fields['type']),
            'duration' => $fields['number_time'],
            'unit'     => strtolower(LevelAgreement::getDefinitionTimeLabel($fields['definition_time'])),
        ];
    }
}
