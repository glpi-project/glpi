<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Com\Tecnick\Barcode\Barcode;

class BarcodeManager
{
    public function generateQRCode(CommonDBTM $item)
    {
        global $CFG_GLPI;
        if (
            $item->isNewItem()
            || !in_array($item::class, $CFG_GLPI["asset_types"])
        ) {
            return false;
        }
        $barcode = new Barcode();
        $qrcode = $barcode->getBarcodeObj(
            'QRCODE,H',
            $CFG_GLPI["url_base"] . $item->getLinkURL(),
            200,
            200,
            'black',
            [10, 10, 10, 10]
        )->setBackgroundColor('white');
        return $qrcode;
    }

    public static function renderQRCode(CommonDBTM $item)
    {
        $barcode_manager = new self();
        $qrcode = $barcode_manager->generateQRCode($item);
        if ($qrcode) {
            return "<img src=\"data:image/png;base64," . base64_encode($qrcode->getPngData()) . "\" />";
        }
        return false;
    }
}
