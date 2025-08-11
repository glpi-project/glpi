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

namespace Glpi\Asset\Capacity;

use Cable;
use CommonGLPI;
use Glpi\Asset\CapacityConfig;
use Glpi\Socket;
use Override;
use Session;

class HasSocketCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Socket::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Socket::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Manage sockets and cable links");
    }

    public function getCloneRelations(): array
    {
        return [
            Socket::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Socket::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s sockets attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Socket::class),
            $this->countAssetsLinkedToPeerItem($classname, Socket::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('socket_types', $classname);

        CommonGLPI::registerStandardTab(
            $classname,
            Socket::class,
            50
        );
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        global $DB;

        $this->unregisterFromTypeConfig('socket_types', $classname);

        $socket = new Socket();
        $socket->deleteByCriteria(
            [
                'itemtype' => $classname,
            ],
            force: true,
            history: false
        );

        // Clean cable data by unlinking the custom asset side
        $it = $DB->request(
            [
                'SELECT' => ['id', 'itemtype_endpoint_a', 'itemtype_endpoint_b'],
                'FROM'   => Cable::getTable(),
                'WHERE'  => [
                    'OR' => [
                        'itemtype_endpoint_a' => $classname,
                        'itemtype_endpoint_b' => $classname,
                    ],
                ],
            ]
        );
        $cable = new Cable();
        foreach ($it as $data) {
            if ($data['itemtype_endpoint_a'] === $classname) {
                $cable->update(
                    [
                        'id' => $data['id'],
                        'itemtype_endpoint_a' => null,
                        'items_id_endpoint_a' => 0,
                        'socketmodels_id_endpoint_a' => 0,
                        'sockets_id_endpoint_a' => 0,
                    ],
                    history: false
                );
            } else {
                $cable->update(
                    [
                        'id' => $data['id'],
                        'itemtype_endpoint_b' => null,
                        'items_id_endpoint_b' => 0,
                        'socketmodels_id_endpoint_b' => 0,
                        'sockets_id_endpoint_b' => 0,
                    ],
                    history: false
                );
            }
        }

        $this->deleteRelationLogs($classname, Socket::class);
        $this->deleteDisplayPreferences($classname, Socket::rawSearchOptionsToAdd());
    }
}
