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
use Glpi\Locale\LanguageRegistry;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LanguageRegistryTest extends GLPITestCase
{
    public function testAllReturnsLanguageObjectsKeyedByCode(): void
    {
        $all = LanguageRegistry::all();

        $this->assertNotEmpty($all);
        foreach ($all as $code => $language) {
            $this->assertInstanceOf(Language::class, $language);
            $this->assertSame($code, $language->code);
        }
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hasProvider(): iterable
    {
        yield 'known regionalized'   => ['fr_FR', true];
        yield 'known no-region'      => ['eo', true];
        yield 'short code is no key' => ['fr', false];
        yield 'unknown'              => ['xx_XX', false];
    }

    #[DataProvider('hasProvider')]
    public function testHas(string $code, bool $expected): void
    {
        $this->assertSame($expected, LanguageRegistry::has($code));
    }

    public function testGetKnownLanguage(): void
    {
        $language = LanguageRegistry::get('fr_FR');
        $this->assertSame('fr_FR', $language->code);
        $this->assertSame('Français', $language->native_name);
        $this->assertSame('fr', $language->getPageLang());
    }

    public function testGetUnknownLanguageReturnsAcceptableDefaultAndNeverNull(): void
    {
        $language = LanguageRegistry::get('xx_YY');

        // Never null: callers can always rely on a valid lang
        $this->assertInstanceOf(Language::class, $language);
        $this->assertSame('xx_YY', $language->code);
        // The page lang falls back to the region-less part of the requested code.
        $this->assertSame('xx', $language->getPageLang());
        $this->assertSame('en_GB.mo', $language->mo_file);
        $this->assertSame(2, $language->plural_number);
    }

    public function testTryGet(): void
    {
        $this->assertInstanceOf(Language::class, LanguageRegistry::tryGet('fr_FR'));
        $this->assertNull(LanguageRegistry::tryGet('xx_YY'));
    }

    public function testGetMainLanguage(): void
    {
        $this->assertSame('fr_FR', LanguageRegistry::getMainLanguage('fr'));
        $this->assertSame('zh_CN', LanguageRegistry::getMainLanguage('zh'));
        $this->assertNull(LanguageRegistry::getMainLanguage('xx'));
        // A full locale is not a short-code key.
        $this->assertNull(LanguageRegistry::getMainLanguage('fr_FR'));
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function resolveProvider(): iterable
    {
        yield 'direct match'                   => ['fr_FR', 'fr_FR'];
        yield 'short code fallback'            => ['fr', 'fr_FR'];
        yield 'short code fallback (polish)'   => ['pl', 'pl_PL'];
        yield 'english short code'             => ['en', 'en_GB'];
        yield 'supported region variant'       => ['fr_CA', 'fr_CA'];
        yield 'unsupported region, no mapping' => ['es_PE', null];
        yield 'unknown'                        => ['xx', null];
    }

    #[DataProvider('resolveProvider')]
    public function testResolve(string $code, ?string $expected): void
    {
        $this->assertSame($expected, LanguageRegistry::resolve($code));
    }

    public function testToLegacyArrayReproducesTheHistoricalPositionalArray(): void
    {
        // `$CFG_GLPI['languages']` array must remain identical to the historical positional definition, so
        // for backward compatibility.
        $this->assertSame($this->historicalLegacyArray(), LanguageRegistry::toLegacyArray());
    }

    public function testToLegacyArrayEntriesAreWellFormed(): void
    {
        foreach (LanguageRegistry::toLegacyArray() as $code => $row) {
            $this->assertCount(6, $row, "Language $code must have 6 columns");
            $this->assertIsString($row[0], "native name of $code");
            $this->assertIsString($row[1], "MO file of $code");
            $this->assertIsString($row[2], "jQuery code of $code");
            $this->assertIsString($row[3], "JS code of $code");
            $this->assertIsString($row[4], "english name of $code");
            $this->assertIsInt($row[5], "plural number of $code");
        }
    }

    /**
     * Snapshot of the positional `$CFG_GLPI['languages']` array as it was
     * defined before the introduction of {@see LanguageRegistry}.
     *
     * Columns: [0] native name, [1] MO file, [2] jQuery code, [3] JS code,
     * [4] english name, [5] plural number.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: int}>
     */
    private function historicalLegacyArray(): array
    {
        return [
            'ar_SA'   => ['العربية السعودية', 'ar_SA.mo', 'ar', 'ar_SA', 'arabic', 103],
            'ar_IQ'   => ['العربية العراق', 'ar_IQ.mo', 'ar', 'ar', 'irak arabic', 103],
            'ar_SY'   => ['العربية سوريا', 'ar_SY.mo', 'ar', 'ar', 'syria arabic', 103],
            'az_AZ'   => ['Azərbaycan dili', 'az_AZ.mo', 'az', 'az', 'azerbaijani', 2],
            'bg_BG'   => ['Български', 'bg_BG.mo', 'bg', 'bg', 'bulgarian', 2],
            'bn_BD'   => ['বাংলা (বাংলাদেশ)', 'bn_BD.mo', 'bn', 'bn_BD', 'bengali', 2],
            'id_ID'   => ['Bahasa Indonesia', 'id_ID.mo', 'id', 'id', 'indonesian', 2],
            'ms_MY'   => ['Bahasa Melayu', 'ms_MY.mo', 'ms', 'ms', 'malay', 2],
            'ca_ES'   => ['Català', 'ca_ES.mo', 'ca', 'ca', 'catalan', 2],
            'cs_CZ'   => ['Čeština', 'cs_CZ.mo', 'cs', 'cs', 'czech', 10],
            'de_DE'   => ['Deutsch', 'de_DE.mo', 'de', 'de', 'german', 2],
            'da_DK'   => ['Dansk', 'da_DK.mo', 'da', 'da', 'danish', 2],
            'et_EE'   => ['Eesti', 'et_EE.mo', 'et', 'et', 'estonian', 2],
            'en_GB'   => ['English', 'en_GB.mo', 'en-GB', 'en', 'english', 2],
            'en_US'   => ['English (US)', 'en_US.mo', 'en-GB', 'en', 'english', 2],
            'es_AR'   => ['Español (Argentina)', 'es_AR.mo', 'es', 'es', 'spanish', 2],
            'es_EC'   => ['Español (Ecuador)', 'es_EC.mo', 'es', 'es', 'spanish', 2],
            'es_CO'   => ['Español (Colombia)', 'es_CO.mo', 'es', 'es', 'spanish', 2],
            'es_ES'   => ['Español (España)', 'es_ES.mo', 'es', 'es', 'spanish', 2],
            'es_419'  => ['Español (América Latina)', 'es_419.mo', 'es', 'es', 'spanish', 2],
            'es_MX'   => ['Español (México)', 'es_MX.mo', 'es', 'es', 'spanish', 2],
            'es_VE'   => ['Español (Venezuela)', 'es_VE.mo', 'es', 'es', 'spanish', 2],
            'eu_ES'   => ['Euskara', 'eu_ES.mo', 'eu', 'eu', 'basque', 2],
            'fr_FR'   => ['Français', 'fr_FR.mo', 'fr', 'fr', 'french', 2],
            'fr_CA'   => ['Français (Canada)', 'fr_CA.mo', 'fr', 'fr', 'french', 2],
            'fr_BE'   => ['Français (Belgique)', 'fr_BE.mo', 'fr', 'fr', 'french', 2],
            'gl_ES'   => ['Galego', 'gl_ES.mo', 'gl', 'gl', 'galician', 2],
            'el_GR'   => ['Ελληνικά', 'el_GR.mo', 'el', 'el', 'greek', 2],
            'he_IL'   => ['עברית', 'he_IL.mo', 'he', 'he', 'hebrew', 2],
            'hi_IN'   => ['हिन्दी', 'hi_IN.mo', 'hi', 'hi_IN', 'hindi', 2],
            'hr_HR'   => ['Hrvatski', 'hr_HR.mo', 'hr', 'hr', 'croatian', 2],
            'hu_HU'   => ['Magyar', 'hu_HU.mo', 'hu', 'hu', 'hungarian', 2],
            'it_IT'   => ['Italiano', 'it_IT.mo', 'it', 'it', 'italian', 2],
            'km_KH'   => ['ខ្មែរ (កម្ពុជា)', 'km_KH.mo', 'km', 'km_KH', 'cambodgian khmer', 0],
            'kn'      => ['ಕನ್ನಡ', 'kn.mo', 'en-GB', 'en', 'kannada', 2],
            'lv_LV'   => ['Latviešu', 'lv_LV.mo', 'lv', 'lv', 'latvian', 2],
            'lt_LT'   => ['Lietuvių', 'lt_LT.mo', 'lt', 'lt', 'lithuanian', 2],
            'mn_MN'   => ['Монгол хэл', 'mn_MN.mo', 'mn', 'mn', 'mongolian', 2],
            'nl_NL'   => ['Nederlands', 'nl_NL.mo', 'nl', 'nl', 'dutch', 2],
            'nl_BE'   => ['Vlaams', 'nl_BE.mo', 'nl', 'nl', 'flemish', 2],
            'nb_NO'   => ['Norsk (Bokmål)', 'nb_NO.mo', 'no', 'nb', 'norwegian', 2],
            'nn_NO'   => ['Norsk (Nynorsk)', 'nn_NO.mo', 'no', 'nn', 'norwegian', 2],
            'fa_IR'   => ['فارسی', 'fa_IR.mo', 'fa', 'fa', 'persian', 2],
            'pl_PL'   => ['Polski', 'pl_PL.mo', 'pl', 'pl', 'polish', 2],
            'pt_PT'   => ['Português', 'pt_PT.mo', 'pt', 'pt', 'portuguese', 2],
            'pt_BR'   => ['Português do Brasil', 'pt_BR.mo', 'pt-BR', 'pt', 'brazilian portuguese', 2],
            'ro_RO'   => ['Română', 'ro_RO.mo', 'ro', 'en', 'romanian', 2],
            'ru_RU'   => ['Русский', 'ru_RU.mo', 'ru', 'ru', 'russian', 2],
            'sk_SK'   => ['Slovenčina', 'sk_SK.mo', 'sk', 'sk', 'slovak', 10],
            'sl_SI'   => ['Slovenščina', 'sl_SI.mo', 'sl', 'sl', 'slovenian slovene', 2],
            'sq_AL'   => ['Shqip', 'sq_AL.mo', 'sq', 'sq', 'albanian', 2],
            'sr_RS'   => ['Srpski', 'sr_RS.mo', 'sr', 'sr', 'serbian', 2],
            'fi_FI'   => ['Suomi', 'fi_FI.mo', 'fi', 'fi', 'finish', 2],
            'sv_SE'   => ['Svenska', 'sv_SE.mo', 'sv', 'sv', 'swedish', 2],
            'vi_VN'   => ['Tiếng Việt', 'vi_VN.mo', 'vi', 'vi', 'vietnamese', 2],
            'th_TH'   => ['ภาษาไทย', 'th_TH.mo', 'th', 'th', 'thai', 2],
            'tr_TR'   => ['Türkçe', 'tr_TR.mo', 'tr', 'tr', 'turkish', 2],
            'uk_UA'   => ['Українська', 'uk_UA.mo', 'uk', 'en', 'ukrainian', 2],
            'ja_JP'   => ['日本語', 'ja_JP.mo', 'ja', 'ja', 'japanese', 2],
            'zh_CN'   => ['简体中文', 'zh_CN.mo', 'zh-CN', 'zh', 'chinese', 2],
            'zh_TW'   => ['繁體中文', 'zh_TW.mo', 'zh-TW', 'zh', 'chinese', 2],
            'ko_KR'   => ['한국어', 'ko_KR.mo', 'ko', 'ko', 'korean', 1],
            'zh_HK'   => ['繁體中文（香港）', 'zh_HK.mo', 'zh-HK', 'zh', 'chinese', 2],
            'be_BY'   => ['Беларуская', 'be_BY.mo', 'be', 'be', 'belarusian', 3],
            'is_IS'   => ['Íslenska', 'is_IS.mo', 'is', 'en', 'icelandic', 2],
            'eo'      => ['Esperanto', 'eo.mo', 'eo', 'en', 'esperanto', 2],
            'es_CL'   => ['Español (Chile)', 'es_CL.mo', 'es', 'es', 'spanish chilean', 2],
        ];
    }
}
