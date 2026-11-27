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
use Html;
use MassiveAction;

/**
 * Entry point of the import/export system: which itemtypes it supports, with
 * which serializer, and where their wizard lives.
 */
final class ImportExportManager
{
    /** @param class-string<CommonDBTM>|string $itemtype */
    public static function getHandler(string $itemtype): ?ImportExportHandlerInterface
    {
        if (!self::isExportable($itemtype)) {
            return null;
        }
        return $itemtype::getImportExportHandler();
    }

    /**
     * Whether the itemtype supports import/export, and so gets the generic
     * "Export" massive action and "Import" menu link.
     *
     * @phpstan-assert-if-true class-string<CommonDBTM&HasImportExportHandlerInterface> $itemtype
     */
    public static function isExportable(string $itemtype): bool
    {
        return is_a($itemtype, CommonDBTM::class, true)
            && is_a($itemtype, HasImportExportHandlerInterface::class, true);
    }

    /** @param class-string<CommonDBTM> $itemtype */
    public static function getImportUrl(string $itemtype, string $step = ''): string
    {
        return '/ImportExport/Import' . $step . '?' . http_build_query(['itemtype' => $itemtype]);
    }

    /**
     * @param class-string<CommonDBTM> $itemtype
     * @param array<int> $ids
     */
    public static function getExportUrl(string $itemtype, array $ids): string
    {
        return '/ImportExport/Export?' . http_build_query(['itemtype' => $itemtype, 'ids' => array_values($ids)]);
    }

    /**
     * The export is a download: the massive action sends the browser to it.
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma): bool
    {
        global $CFG_GLPI;

        foreach ($ma->getItems() as $itemtype => $ids) {
            /** @var class-string<CommonDBTM> $itemtype */
            $url = $CFG_GLPI['url_base'] . self::getExportUrl($itemtype, $ids);
            echo '<a href="' . htmlescape($url) . '">' . __s('Click here to download the export...') . '</a>';
            echo Html::scriptBlock("window.location.href = '" . jsescape($url) . "';");
        }
        return true;
    }

    /**
     * The export is a download, which processing a massive action (from the
     * API, typically) cannot return: nothing to process.
     *
     * @param array<int> $ids
     */
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids): void {}
}
