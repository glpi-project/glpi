<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\ImportExport;

use CommonDBTM;
use Glpi\ImportExport\Context\DatabaseMapper;
use Glpi\ImportExport\Result\ExportResult;
use Glpi\ImportExport\Result\ImportResult;
use Glpi\ImportExport\Result\ImportResultIssues;
use Glpi\ImportExport\Result\ImportResultPreview;

/**
 * What the import/export wizard needs from a serializer.
 *
 * Items are identified in the file by their id on the source instance, which
 * is the key used to skip them or to resolve their issues.
 */
interface ImportExportHandlerInterface
{
    /** @param CommonDBTM[] $items */
    public function exportItems(array $items): ExportResult;

    /** @param array<int|string> $skipped_items */
    public function previewImport(string $json, DatabaseMapper $mapper, array $skipped_items = []): ImportResultPreview;

    public function listIssues(DatabaseMapper $mapper, string $json): ImportResultIssues;

    /** @param array<int|string> $skipped_items */
    public function importItems(string $json, DatabaseMapper $mapper, array $skipped_items = []): ImportResult;
}
