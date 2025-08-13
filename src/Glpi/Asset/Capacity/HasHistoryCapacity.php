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
use Glpi\Asset\Asset;
use Glpi\Asset\CapacityConfig;
use Log;
use Override;

class HasHistoryCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Log::getTypeName();
    }

    public function getIcon(): string
    {
        return Log::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Records the modifications made to the asset");
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Log::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s logs attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Log::class),
            $this->countAssetsLinkedToPeerItem($classname, Log::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        CommonGLPI::registerStandardTab(
            $classname,
            Log::class,
            PHP_INT_MAX // PHP_INT_MAX to ensure that tab is always the latest
        );
    }

    public function onObjectInstanciation(Asset $object, CapacityConfig $config): void
    {
        $object->dohistory = true;
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        global $DB;

        // Do not use `CommonDBTM::deleteByCriteria()` to prevent performances issues
        $DB->delete(Log::getTable(), ['itemtype' => $classname]);
    }
}
