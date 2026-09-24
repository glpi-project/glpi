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

namespace tests\units\Glpi\Api\HL\FileUpload;

use Glpi\Api\HL\FileUpload\FileManager;
use Glpi\Tests\DbTestCase;

class FileManagerTest extends DbTestCase
{
    /**
     * A 1x1 PNG. Declared as image/jpg in the tests below so that the declared type never matches the
     * detected one, which is what makes handleInlineImagesInHTML() reject the image.
     */
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAEElEQVR4nGLK06gFBAAA//8CIwEWK2unAQAAAABJRU5ErkJggg==';

    public function testHandleInlineImagesInHTML_MismatchedMime(): void
    {
        // Ensure an img tag with a declared mime type that doesn't match the actual file is removed - security measure
        $html = '<p>Here is an image: <img src="data:image/jpg;base64,' . self::PNG_1X1 . '" alt="test image"></p>';
        $this->assertEquals('<p>Here is an image: </p>', trim(FileManager::handleInlineImagesInHTML($html, 0, false)));
    }

    public function testHandleInlineImagesInHTML_ValidImage(): void
    {
        // Ensure an img tag with a valid base64 image is processed correctly
        $html = '<p>Été déjà</p><p>Here is an image: <img src="data:image/png;base64,' . self::PNG_1X1 . '" alt="test image"></p>';
        $processedHtml = FileManager::handleInlineImagesInHTML($html, 0, false);
        $this->assertStringContainsString('<p>Été déjà</p>', $processedHtml);
        $this->assertStringContainsString('document.send.php?docid=', $processedHtml);
        $this->assertStringContainsString('alt="test image"', $processedHtml);
        $this->assertStringNotContainsString('data:image/png;base64', $processedHtml);

        // Same test but with a different case for the mime type, to ensure the comparison is case-insensitive
        $html = '<p>Été déjà</p><p>Here is an image: <img src="data:image/PnG;base64,' . self::PNG_1X1 . '" alt="test image"></p>';
        $processedHtml = FileManager::handleInlineImagesInHTML($html, 0, false);
        $this->assertStringContainsString('<p>Été déjà</p>', $processedHtml);
        $this->assertStringContainsString('document.send.php?docid=', $processedHtml);
        $this->assertStringContainsString('alt="test image"', $processedHtml);
        $this->assertStringNotContainsString('data:image/png;base64', $processedHtml);
    }

    public function testDeletePictureWithEmptyReference(): void
    {
        $this->assertFalse(FileManager::deletePicture(''));
    }

    public function testDeletePictureAlreadyMissingFromDisk(): void
    {
        // Nothing to delete, but the caller's intent (no picture left) is satisfied so this isn't a failure
        $this->assertTrue(FileManager::deletePicture('ab/' . __FUNCTION__ . '.png'));
    }

    /**
     * Every rejected inline image must be dropped, including when several of them follow each other.
     *
     * getElementsByTagName() returns a live DOMNodeList: removing the node the loop is currently on shifts
     * every later node down one index, so foreach skips the next one. With three images to reject, the second
     * one survives with its base64 data URI intact, which is exactly what the extraction is meant to prevent.
     */
    public function testAllRejectedInlineImagesAreRemoved(): void
    {
        $this->login();

        $img = '<img src="data:image/jpg;base64,' . self::PNG_1X1 . '" alt="rejected">';
        $html = '<p>' . $img . $img . $img . '</p>';

        $result = FileManager::handleInlineImagesInHTML($html, 0, false);

        $this->assertStringNotContainsString('base64,', $result);
        $this->assertStringNotContainsString('<img', $result);
    }
}
