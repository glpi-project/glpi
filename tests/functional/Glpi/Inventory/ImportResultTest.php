<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Inventory;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Inventory\ImportResult;
use Glpi\Tests\GLPITestCase;
use LogicException;
use Psr\Log\LogLevel;

class ImportResultTest extends GLPITestCase
{
    public function testGetters(): void
    {
        $result = new ImportResult(
            filename: 'computer_1.json',
            success: true,
            message: 'File has been successfully imported.',
        );

        $this->assertSame('computer_1.json', $result->getFilename());
        $this->assertTrue($result->isSuccess());
        $this->assertSame('File has been successfully imported.', $result->getMessage());
        $this->assertSame([], $result->getItems());
        $this->assertNull($result->getRequest());
    }

    private function expectDeprecated(): void
    {
        $this->hasPhpLogRecordThatContains(
            'ImportResult array access is deprecated. Use the dedicated getters instead.',
            LogLevel::INFO
        );
    }

    public function testLegacyArrayAccess(): void
    {
        $result = new ImportResult(
            filename: 'computer_1.json',
            success: false,
            message: 'File has not been imported.',
        );

        // Legacy keys map to the getters
        $this->assertSame($result->getFilename(), $result['filename']);
        $this->expectDeprecated();
        $this->assertSame($result->isSuccess(), $result['success']);
        $this->expectDeprecated();
        $this->assertSame($result->getMessage(), $result['message']);
        $this->expectDeprecated();
        $this->assertSame($result->getItems(), $result['items']);
        $this->expectDeprecated();
        $this->assertSame($result->getRequest(), $result['request']);
        $this->expectDeprecated();

        $this->assertTrue(isset($result['success']));
        $this->expectDeprecated();
        $this->assertFalse(isset($result['unknown']));
        $this->expectDeprecated();
        $this->assertNull($result['unknown']);
        $this->expectDeprecated();
    }

    public function testIsReadOnly(): void
    {
        $result = new ImportResult(
            filename: 'computer_1.json',
            success: true,
            message: null,
        );

        $this->expectException(LogicException::class);
        $result['success'] = false;
    }

    public function testUnsetIsReadOnly(): void
    {
        $result = new ImportResult(
            filename: 'computer_1.json',
            success: true,
            message: null,
        );

        $this->expectException(LogicException::class);
        unset($result['success']);
    }

    public function testTemplateDoesNotTriggerDeprecatedArrayAccess(): void
    {
        $result = new ImportResult(
            filename: 'computer_1.json',
            success: true,
            message: 'OK',
            items: [],
        );

        $errors = [];
        set_error_handler(static function ($errno, $errstr) use (&$errors) {
            $errors[] = $errstr;
            return true;
        }, E_USER_DEPRECATED);

        TemplateRenderer::getInstance()->render(
            'pages/admin/inventory/upload_result.html.twig',
            ['imported_files' => ['computer_1.json' => $result]]
        );

        restore_error_handler();

        $this->assertSame([], $errors);
    }
}
