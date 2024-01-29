<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Asset\Capacity;

use CommonGLPI;
use Document;
use Document_Item;
use Session;

class HasDocumentsCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Document::getTypeName(Session::getPluralNumber());
    }

    public function getCloneRelations(): array
    {
        return [
            Document_Item::class
        ];
    }

    public function onClassBootstrap(string $classname): void
    {
        $this->registerToTypeConfig('document_types', $classname);

        CommonGLPI::registerStandardTab($classname, Document_Item::class, 55);
    }

    public function onCapacityDisabled(string $classname): void
    {
        // Unregister from document types
        $this->unregisterFromTypeConfig('document_types', $classname);

        // Delete relations to documents
        $document_item = new Document_Item();
        $document_item->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        // Clean history related to documents
        $this->deleteRelationLogs($classname, Document::class);

        // Clean display preferences
        $documents_search_options = Document::rawSearchOptionsToAdd($classname);
        $this->deleteDisplayPreferences($classname, $documents_search_options);
    }
}
