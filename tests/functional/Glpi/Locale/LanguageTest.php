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

namespace tests\units\Glpi\Locale;

use Glpi\Locale\Language;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LanguageTest extends GLPITestCase
{
    public function testDefaultsAreDerivedFromCode(): void
    {
        $language = new Language(code: 'fr_FR', native_name: 'Français');

        $this->assertSame('fr_FR', $language->code);
        $this->assertSame('Français', $language->native_name);
        // MO file, jQuery code and JS code default from the code.
        $this->assertSame('fr_FR.mo', $language->mo_file);
        $this->assertSame('fr', $language->jquery_code);
        $this->assertSame('fr', $language->js_code);
        // English name and plural number have acceptable defaults.
        $this->assertSame('', $language->english_name);
        $this->assertSame(2, $language->plural_number);
    }

    public function testExplicitValuesOverrideDefaults(): void
    {
        $language = new Language(
            code: 'en_GB',
            native_name: 'English',
            mo_file: 'custom.mo',
            jquery_code: 'en-GB',
            js_code: 'en',
            english_name: 'english',
            plural_number: 3,
        );

        $this->assertSame('custom.mo', $language->mo_file);
        $this->assertSame('en-GB', $language->jquery_code);
        $this->assertSame('en', $language->js_code);
        $this->assertSame('english', $language->english_name);
        $this->assertSame(3, $language->plural_number);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function regionlessProvider(): iterable
    {
        yield 'regionalized'    => ['fr_FR', 'fr'];
        yield 'numeric region'  => ['es_419', 'es'];
        yield 'no region'       => ['eo', 'eo'];
    }

    #[DataProvider('regionlessProvider')]
    public function testGetRegionlessCode(string $code, string $expected): void
    {
        $language = new Language(code: $code, native_name: 'whatever');
        $this->assertSame($expected, $language->getRegionlessCode());
        // The JS/jQuery codes default to the region-less code.
        $this->assertSame($expected, $language->js_code);
        $this->assertSame($expected, $language->jquery_code);
    }

    public function testGetPageLangReturnsJsCode(): void
    {
        $language = new Language(code: 'zh_CN', native_name: '简体中文', js_code: 'zh');
        $this->assertSame('zh', $language->getPageLang());
        $this->assertSame($language->js_code, $language->getPageLang());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function rtlProvider(): iterable
    {
        yield 'arabic'  => ['ar_SA', true];
        yield 'hebrew'  => ['he_IL', true];
        yield 'persian' => ['fa_IR', true];
        yield 'french'  => ['fr_FR', false];
        yield 'english' => ['en_GB', false];
    }

    #[DataProvider('rtlProvider')]
    public function testIsRTL(string $code, bool $expected): void
    {
        $language = new Language(code: $code, native_name: 'whatever');
        $this->assertSame($expected, $language->isRTL());
    }
}
