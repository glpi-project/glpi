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

namespace tests\units\Glpi\Front;

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LocaleTest extends DbTestCase
{
    // `glpi` domain translations come from Transifex and may be reset by any sync, so a fixture domain is used instead.
    private const TEST_DOMAIN = 'test_locale';

    public static function frontLocaleFileProvider(): iterable
    {
        yield ['en_GN', 'en_GB', 'Active']; // unknown language, falls back to the default one
        yield ['fr_FR', 'fr_FR', 'Activé'];
    }

    #[DataProvider('frontLocaleFileProvider')]
    public function testFrontLocaleFile(string $locale, string $expected_language, string $expected_translation): void
    {
        global $TRANSLATE;

        // Arrange: load languages
        $TRANSLATE->addTranslationFile('phparray', FIXTURE_DIR . '/locales/en_GB.php', self::TEST_DOMAIN, 'en_GB');
        $TRANSLATE->addTranslationFile('phparray', FIXTURE_DIR . '/locales/fr_FR.php', self::TEST_DOMAIN, 'fr_FR');

        // Act: render locale file
        $_GET['lang'] = $locale;
        $_GET['domain'] = self::TEST_DOMAIN;
        ob_start();
        include(GLPI_ROOT . '/front/locale.php');
        $locales = ob_get_clean();

        // Assert: locale headers and messages should match the expected language
        $locales = json_decode($locales, true);
        $this->assertEquals($expected_language, $locales['']['language']);
        $this->assertArrayHasKey('plural-forms', $locales['']);
        $this->assertEquals($expected_translation, $locales['Active']);
    }
}
