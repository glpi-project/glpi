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

namespace tests\units\Glpi\Api\HL;

use Glpi\DBAL\QueryExpression;
use GLPITestCase;

class RSQLInput extends GLPITestCase
{
    private function cleanSQLCriteria(array $criteria)
    {
        $cleaned = [];
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $value = $this->cleanSQLCriteria($value);
                if (empty($value)) {
                    continue;
                }
            }
            if ($value instanceof QueryExpression) {
                $value = (string) $value;
            }
            $cleaned[$key] = $value;
        }
        return $cleaned;
    }

    private function getFlattenedSchema() {
        return [
            'id' =>  ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'comment' => ['type' => 'string'],
            'date_creation' => ['type' => 'string'],
            'date_mod' => ['type' => 'string'],
            'status.id' => ['type' => 'integer'],
            'status.name' => ['type' => 'string'],
            'location.id' => ['type' => 'integer'],
            'location.name' => ['type' => 'string'],
            'entity.id' => ['type' => 'integer'],
            'entity.name' => ['type' => 'string'],
            'entity.completename' => ['type' => 'string'],
            'is_recursive' => ['type' => 'boolean'],
            'type.id' => ['type' => 'integer'],
            'type.name' => ['type' => 'string'],
            'manufacturer.id' => ['type' => 'integer'],
            'manufacturer.name' => ['type' => 'string'],
            'model.id' => ['type' => 'integer'],
            'model.name' => ['type' => 'string'],
            'user_tech.id' => ['type' => 'integer'],
            'user_tech.name' => ['type' => 'string'],
            'group_tech.id' => ['type' => 'integer'],
            'group_tech.name' => ['type' => 'string'],
            'user.id' => ['type' => 'integer'],
            'user.name' => ['type' => 'string'],
            'group.id' => ['type' => 'integer'],
            'group.name' => ['type' => 'string'],
            'contact' => ['type' => 'string'],
            'contact_num' => ['type' => 'string'],
            'serial' => ['type' => 'string'],
            'otherserial' => ['type' => 'string'],
            'network.id' => ['type' => 'integer'],
            'network.name' => ['type' => 'string'],
            'uuid' => ['type' => 'string'],
            'autoupdatesystem.id' => ['type' => 'integer'],
            'autoupdatesystem.name' => ['type' => 'string'],
            'is_deleted' => ['type' => 'boolean']
        ];
    }
}
