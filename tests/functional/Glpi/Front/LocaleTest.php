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

namespace tests\units\Glpi\Front;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LocaleTest extends DbTestCase
{
    public static function frontLocaleFileProvider(): iterable
    {
        yield ['en_GN', 'Active'];
        yield ['fr_FR', 'Activé'];
    }

    #[DataProvider('frontLocaleFileProvider')]
    public function testFrontLocaleFile(string $locale, string $expected): void
    {
        global $TRANSLATE;

        // Arrange: load languages
        $TRANSLATE->addTranslationFile('gettext', GLPI_I18N_DIR . '/en_GB.mo', 'glpi', 'en_GB');
        $TRANSLATE->addTranslationFile('gettext', GLPI_I18N_DIR . '/fr_FR.mo', 'glpi', 'fr_FR');

        // Act: render locale file
        $_GET['lang'] = $locale;
        $_GET['domain'] = 'glpi';
        ob_start();
        include(GLPI_ROOT . '/front/locale.php');
        $locales = ob_get_clean();

        // Assert: locales should be translated to expected string
        $locales = json_decode($locales, true);
        $this->assertEquals($expected, $locales['Active']);
    }
}
