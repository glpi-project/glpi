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
use Knowbase;
use KnowbaseItem;
use KnowbaseItem_Item;
use Override;
use Session;

class HasKnowbaseCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Knowbase::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return KnowbaseItem::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Knowledge base articles can be associated to these assets");
    }

    public function getCloneRelations(): array
    {
        return [
            KnowbaseItem_Item::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, KnowbaseItem_Item::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s knowbase items attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, KnowbaseItem_Item::class),
            $this->countAssetsLinkedToPeerItem($classname, KnowbaseItem_Item::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('kb_types', $classname);

        CommonGLPI::registerStandardTab($classname, KnowbaseItem_Item::class, 70);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        $this->unregisterFromTypeConfig('kb_types', $classname);

        $kb_item = new KnowbaseItem_Item();
        $kb_item->deleteByCriteria([
            'itemtype' => $classname,
        ], true, false);

        $this->deleteRelationLogs($classname, KnowbaseItem::class);
        $this->deleteRelationLogs($classname, KnowbaseItem_Item::class);
    }
}
