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

// ...existing headers et éventuels use statements...
use Glpi\DBAL\QuerySubQuery;

class PurgeSoftwareTask extends Software
{
    const TASK_NAME = 'purgesoftware';
    const MAX_BATCH_SIZE = 2000;

    // Méthode principale de purge (non statique)
    public function run(?int $max): int
    {
        $total = 0;
        // Purge deleted software
        $criteria = $this->getDeletedSoftwareWithNoVersionsCriteria();
        $software = new Software();
        $total += $this->purgeItems($criteria, $software, $max - $total);
        return $total;
    }

    // Récupère les critères pour sélectionner les logiciels à purger
    protected function getDeletedSoftwareWithNoVersionsCriteria(): array
    {
        return [
            'SELECT' => 'id',
            'FROM'   => Software::getTable(),
            'WHERE'  => [
                'is_deleted' => 1,
                'NOT' => [
                    'id' => new QuerySubQuery(
                        [
                            'SELECT' => 'softwares_id',
                            'FROM'   => SoftwareVersion::getTable(),
                        ]
                    ),
                ]
            ]
        ];
    }

    // Exécute la purge étape par étape
    protected function purgeItems(array $scope, $em, int $max): int
    {
        global $DB;
        $total = 0;
        do {
            $scope['LIMIT'] = min($max - $total, self::MAX_BATCH_SIZE);
            $items = $DB->request($scope);
            $count = count($items);
            $total += $count;
            foreach ($items as $item) {
                $em->delete($item, true);
            }
        } while ($count > 0 && $total < $max);
        return $total;
    }
}
