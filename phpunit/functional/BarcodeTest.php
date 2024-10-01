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

namespace tests\units;

use DbTestCase;
use Barcode;
use Software;

/* Test for inc/computer.class.php */

class BarcodeTest extends DbTestCase
{
    private function getNewComputer(): \Computer
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $fields   = $computer->fields;
        unset($fields['id']);
        unset($fields['date_creation']);
        unset($fields['date_mod']);
        $fields['name'] = $this->getUniqueString();
        $this->assertGreaterThan(0, (int)$computer->add($fields));
        return $computer;
    }

    public function testValidQrCodeGeneration()
    {
        global $CFG_GLPI;
        $computer = $this->getNewComputer();

        $qrcode = Barcode::generateQRCode($computer);
        $this->assertInstanceOf(\Com\Tecnick\Barcode\Type\Square\QrCode::class, $qrcode);
        $qrcodeInfos = $qrcode->getArray();
        $this->assertEquals($qrcodeInfos["code"], $CFG_GLPI["url_base"] . $computer->getLinkURL());
    }

    public function testInvalidQrCodeGeneration()
    {
        $softaware = new Software();
        $qrcode = Barcode::generateQRCode($softaware);
        $this->assertFalse($qrcode);
    }

    public function testValidRenderQrCode()
    {
        $computer = $this->getNewComputer();
        $this->assertIsString(Barcode::renderQRCode($computer));
    }

    public function testInalidRenderQrCode()
    {
        $softaware = new Software();
        $this->assertFalse(Barcode::renderQRCode($softaware));
    }
}
