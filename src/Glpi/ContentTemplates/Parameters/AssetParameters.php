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
use Entity;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ObjectParameter;
use State;

/**
 * Parameters for "Assets" items (Computer, Monitor, ...).
 *
 * @since 10.0.0
 */
class AssetParameters extends AbstractParameters
{
    public static function getDefaultNodeName(): string
    {
        return 'asset';
    }

    public static function getObjectLabel(): string
    {
        return _n('Asset', 'Assets', 1);
    }

    protected function getTargetClasses(): array
    {
        global $CFG_GLPI;
        return $CFG_GLPI["asset_types"];
    }

    public function getAvailableParameters(): array
    {
        return [
            new AttributeParameter("id", __('ID')),
            new AttributeParameter("name", __('Name')),
            new AttributeParameter("itemtype", __('Itemtype')),
            new AttributeParameter("serial", __('Serial number')),
            new AttributeParameter("model", _n('Model', 'Models', 1)),
            new AttributeParameter("state", __('Status')),
            new ObjectParameter(new EntityParameters()),
        ];
    }

    protected function defineValues(CommonDBTM $asset): array
    {
        $fields = $asset->fields;

        $values = [
            'id'       => $fields['id'],
            'name'     => $fields['name'],
            'itemtype' => $asset->getType(),
            'serial'   => $fields['serial'],
            'model'    => '',
            'state'    => '',
        ];

        // Add model if asset has a model
        $model_class = $asset->getModelClass();
        if ($model_class !== null) {
            $model_fk = $model_class::getForeignKeyField();
            if (isset($fields[$model_fk]) && $fields[$model_fk] > 0) {
                $model = new $model_class();
                if ($model->getFromDB($fields[$model_fk])) {
                    $values['model'] = $model->getName();
                }
            }
        }

        // Add state if asset has a state
        if (isset($fields['states_id']) && $fields['states_id'] > 0) {
            $state = new State();
            if ($state->getFromDB($fields['states_id'])) {
                $values['state'] = $state->getName();
            }
        }

        // Add asset's entity
        if ($entity = Entity::getById($fields['entities_id'])) {
            $entity_parameters = new EntityParameters();
            $values['entity'] = $entity_parameters->getValues($entity);
        }

        return $values;
    }
}
