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

namespace tests\units;

use BarcodeManager;
use Com\Tecnick\Barcode\Type\Square\QrCode;
use Computer;
use DbTestCase;
use Software;

class BarcodeManagerTest extends DbTestCase
{
    private function getNewComputer(): Computer
    {
        return $this->createItem(Computer::class, [
            'name' => 'my computer name',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
    }

    public function testValidQrCodeGeneration()
    {
        global $CFG_GLPI;
        $computer = $this->getNewComputer();
        $barcode_manager = new BarcodeManager();
        $qrcode = $barcode_manager->generateQRCode($computer);
        $this->assertInstanceOf(QrCode::class, $qrcode);
        $qrcodeInfos = $qrcode->getArray();
        $this->assertEquals($qrcodeInfos["code"], $CFG_GLPI["url_base"] . $computer->getLinkURL());
    }

    public function testInvalidQrCodeGeneration()
    {
        $softaware = new Software();
        $barcode_manager = new BarcodeManager();
        $qrcode = $barcode_manager->generateQRCode($softaware);
        $this->assertFalse($qrcode);
    }

    public function testValidRenderQrCode()
    {
        $computer = $this->getNewComputer();
        $this->assertIsString(BarcodeManager::renderQRCode($computer));
    }

    public function testInalidRenderQrCode()
    {
        $softaware = new Software();
        $this->assertFalse(BarcodeManager::renderQRCode($softaware));
    }
}
