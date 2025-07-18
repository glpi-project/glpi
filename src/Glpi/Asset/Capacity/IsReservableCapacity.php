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

use CommonGLPI;
use Glpi\Asset\CapacityConfig;
use Override;
use Reservation;
use ReservationItem;
use Session;

class IsReservableCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Reservation::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Reservation::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("These assets can be made reservable");
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, ReservationItem::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('Used by %1$s of %2$s assets'),
            $this->countPeerItemsUsage($classname, ReservationItem::class),
            $this->countAssets($classname)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('reservation_types', $classname);

        CommonGLPI::registerStandardTab($classname, Reservation::class, 85);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from reservable types
        $this->unregisterFromTypeConfig('reservation_types', $classname);

        // Delete related reservations
        $reservation_item = new ReservationItem();
        $reservation_item->deleteByCriteria([
            'itemtype' => $classname,
        ], true, false);

        // Clean history related to links
        $this->deleteRelationLogs($classname, ReservationItem::class);

        // Clean display preferences
        $this->deleteDisplayPreferences($classname, ReservationItem::rawSearchOptionsToAdd($classname));
    }
}
