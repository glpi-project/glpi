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
use Toolbox;

/* Test for inc/documenttype.class.php */

class DocumentTypeTest extends DbTestCase
{
    public function testGetUploadableFilePattern()
    {
        $doctype = new \DocumentType();

       // Clear types to prevent test to be impacted by potential default types changes
        $this->assertTrue($doctype->deleteByCriteria(['1']));

        $this->assertGreaterThan(0, (int)$doctype->add(Toolbox::addslashes_deep(['name' => 'JPG' ,'ext' => '/\.jpe?g$/'])));
        $this->assertGreaterThan(0, (int)$doctype->add(Toolbox::addslashes_deep(['name' => 'DOC' ,'ext' => 'doc'])));
        $this->assertGreaterThan(0, (int)$doctype->add(Toolbox::addslashes_deep(['name' => 'XML' ,'ext' => 'xml'])));
        $this->assertGreaterThan(0, (int)$doctype->add(Toolbox::addslashes_deep(['name' => 'Tarball' ,'ext' => 'tar.gz'])));

       // Validate generated pattern
        $pattern = \DocumentType::getUploadableFilePattern();
        $this->assertSame('/((\.jpe?g$)|\.doc$|\.xml$|\.tar\.gz$)/i', $pattern);

       // Validate matches
        $this->assertEquals(1, preg_match($pattern, 'test.jpg'));
        $this->assertEquals(1, preg_match($pattern, 'test.jpeg'));
        $this->assertEquals(0, preg_match($pattern, 'test.jpag'));
        $this->assertEquals(1, preg_match($pattern, 'test.doc'));
        $this->assertEquals(1, preg_match($pattern, 'test.xml'));
        $this->assertEquals(0, preg_match($pattern, 'testxml'));
        $this->assertEquals(1, preg_match($pattern, 'test.tar.gz'));
    }
}
