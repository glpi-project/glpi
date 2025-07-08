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
use Notepad;
use Override;
use ReflectionClass;
use Session;

class HasNotepadCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Notepad::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Notepad::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Enable a simple notepad");
    }

    public function getSearchOptions(string $classname): array
    {
        return Notepad::rawSearchOptionsToAdd();
    }

    public function getSpecificRights(): array
    {
        return [READNOTE, UPDATENOTE];
    }

    public function getCloneRelations(): array
    {
        return [
            Notepad::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Notepad::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s notes attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Notepad::class),
            $this->countAssetsLinkedToPeerItem($classname, Notepad::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        CommonGLPI::registerStandardTab($classname, Notepad::class, 80);
    }

    public function onObjectInstanciation(Asset $object, CapacityConfig $config): void
    {
        $reflected_class = new ReflectionClass($object);
        $reflected_property = $reflected_class->getProperty('usenotepad');
        $reflected_property->setValue($object, true);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Delete related infocom data
        $notepad = new Notepad();
        $notepad->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        // Clean history related to notepad
        $this->deleteRelationLogs($classname, Notepad::class);

        // Clean display preferences
        $notepad_search_options = Notepad::rawSearchOptionsToAdd();
        $this->deleteDisplayPreferences($classname, $notepad_search_options);
    }
}
