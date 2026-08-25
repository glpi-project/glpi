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
    public function testHandleInlineImagesInHTML_MismatchedMime(): void
    {
        // Ensure an img tag with a declared mime type that doesn't match the actual file is removed - security measure
        $html = '<p>Here is an image: <img src="data:image/jpg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAEElEQVR4nGLK06gFBAAA//8CIwEWK2unAQAAAABJRU5ErkJggg==" alt="test image"></p>';
        $this->assertEquals('<p>Here is an image: </p>', trim(FileManager::handleInlineImagesInHTML($html)));
    }

    public function testHandleInlineImagesInHTML_ValidImage(): void
    {
        // Ensure an img tag with a valid base64 image is processed correctly
        $html = '<p>Here is an image: <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAEElEQVR4nGLK06gFBAAA//8CIwEWK2unAQAAAABJRU5ErkJggg==" alt="test image"></p>';
        $processedHtml = FileManager::handleInlineImagesInHTML($html);
        $this->assertStringContainsString('document.send.php?docid=', $processedHtml);
        $this->assertStringContainsString('alt="test image"', $processedHtml);
        $this->assertStringNotContainsString('data:image/png;base64', $processedHtml);
    }
}
