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

namespace Glpi\Locale;

/**
 * Canonical catalog of the languages supported by GLPI.
 *
 * This registry is the single source of truth for language data. The legacy
 * `$CFG_GLPI['languages']` and `$CFG_GLPI['main_languages']` arrays are derived
 * from it (see {@see self::toLegacyArray()} and {@see self::getMainLanguages()})
 * for backward compatibility.
 */
final class LanguageRegistry
{
    /**
     * @var array<string, Language>|null Memoized catalog, code => Language.
     */
    private static ?array $languages = null;

    /**
     * All supported languages, indexed by their regionalized code.
     *
     * @return array<string, Language>
     */
    public static function all(): array
    {
        if (self::$languages === null) {
            $languages = [];
            foreach (self::definitions() as $language) {
                $languages[$language->code] = $language;
            }
            self::$languages = $languages;
        }

        return self::$languages;
    }

    /**
     * Whether the given code matches a supported language.
     */
    public static function has(string $code): bool
    {
        return array_key_exists($code, self::all());
    }

    /**
     * Get the language matching the given code.
     *
     * When the code is unknown, a default {@see Language} instance
     * is returned, so callers can always rely on a valid page lang,
     * plural number, etc.
     */
    public static function get(string $code): Language
    {
        return self::all()[$code] ?? new Language(
            code: $code,
            native_name: $code,
            mo_file: 'en_GB.mo',
        );
    }

    /**
     * Get the language matching the given code, or `null` if it is unknown.
     */
    public static function tryGet(string $code): ?Language
    {
        return self::all()[$code] ?? null;
    }

    /**
     * Mapping of region-less language codes to their main regionalized locale
     * (e.g. `fr` => `fr_FR`).
     *
     * @return array<string, string>
     */
    public static function getMainLanguages(): array
    {
        return [
            // 'ar' => 'ar_SA', // not sure about the default region for arabic language
            'az' => 'az_AZ',
            'bg' => 'bg_BG',
            'bn' => 'bn_BD',
            'id' => 'id_ID',
            'ms' => 'ms_MY',
            'ca' => 'ca_ES',
            'cs' => 'cs_CZ',
            'de' => 'de_DE',
            'da' => 'da_DK',
            'et' => 'et_EE',
            'en' => 'en_GB',
            'es' => 'es_ES',
            'eu' => 'eu_ES',
            'fr' => 'fr_FR',
            'gl' => 'gl_ES',
            'el' => 'el_GR',
            'he' => 'he_IL',
            'hi' => 'hi_IN',
            'hr' => 'hr_HR',
            'hu' => 'hu_HU',
            'it' => 'it_IT',
            'km' => 'km_KH',
            'lv' => 'lv_LV',
            'lt' => 'lt_LT',
            'mn' => 'mn_MN',
            'nl' => 'nl_NL',
            'nb' => 'nb_NO',
            'nn' => 'nn_NO',
            'fa' => 'fa_IR',
            'pl' => 'pl_PL',
            'pt' => 'pt_BR',
            'ro' => 'ro_RO',
            'ru' => 'ru_RU',
            'sk' => 'sk_SK',
            'sl' => 'sl_SI',
            'sq' => 'sq_AL',
            'sr' => 'sr_RS',
            'fi' => 'fi_FI',
            'sv' => 'sv_SE',
            'vi' => 'vi_VN',
            'th' => 'th_TH',
            'tr' => 'tr_TR',
            'uk' => 'uk_UA',
            'ja' => 'ja_JP',
            'zh' => 'zh_CN',
            'ko' => 'ko_KR',
            'be' => 'be_BY',
            'is' => 'is_IS',
        ];
    }

    /**
     * Resolve a region-less code to its main regionalized locale
     * (e.g. `fr` => `fr_FR`), or `null` if there is no mapping.
     */
    public static function getMainLanguage(string $short_code): ?string
    {
        return self::getMainLanguages()[$short_code] ?? null;
    }

    /**
     * Resolve an arbitrary language code to a supported one, being tolerant to
     * the region: a direct match is preferred, then a fallback through the
     * region-less to main-locale mapping (e.g. `pl` => `pl_PL`).
     *
     * @return string|null The supported code, or `null` if none matches.
     */
    public static function resolve(string $code): ?string
    {
        if (self::has($code)) {
            return $code;
        }

        $main = self::getMainLanguage($code);
        if ($main !== null && self::has($main)) {
            return $main;
        }

        return null;
    }

    /**
     * Build the legacy positional `$CFG_GLPI['languages']` array from the
     * catalog, for backward compatibility.
     *
     * Columns: [0] native name, [1] MO file, [2] jQuery code, [3] JS/page code,
     * [4] english name, [5] plural number.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: int}>
     */
    public static function toLegacyArray(): array
    {
        $legacy = [];
        foreach (self::all() as $code => $language) {
            $legacy[$code] = [
                $language->native_name,
                $language->mo_file,
                $language->jquery_code,
                $language->js_code,
                $language->english_name,
                $language->plural_number,
            ];
        }

        return $legacy;
    }

    /**
     * Canonical language definitions.
     *
     * Order is preserved for backward compatibility with the legacy array.
     *
     * @return list<Language>
     */
    private static function definitions(): array
    {
        return [
            new Language('ar_SA', 'العربية السعودية', js_code: 'ar_SA', english_name: 'arabic', plural_number: 103),
            new Language('ar_IQ', 'العربية العراق', english_name: 'irak arabic', plural_number: 103),
            new Language('ar_SY', 'العربية سوريا', english_name: 'syria arabic', plural_number: 103),
            new Language('az_AZ', 'Azərbaycan dili', english_name: 'azerbaijani'),
            new Language('bg_BG', 'Български', english_name: 'bulgarian'),
            new Language('bn_BD', 'বাংলা (বাংলাদেশ)', js_code: 'bn_BD', english_name: 'bengali'),
            new Language('id_ID', 'Bahasa Indonesia', english_name: 'indonesian'),
            new Language('ms_MY', 'Bahasa Melayu', english_name: 'malay'),
            new Language('ca_ES', 'Català', english_name: 'catalan'), // ca_CA
            new Language('cs_CZ', 'Čeština', english_name: 'czech', plural_number: 10),
            new Language('de_DE', 'Deutsch', english_name: 'german'),
            new Language('da_DK', 'Dansk', english_name: 'danish'), // dk_DK
            new Language('et_EE', 'Eesti', english_name: 'estonian'), // ee_ET
            new Language('en_GB', 'English', jquery_code: 'en-GB', js_code: 'en', english_name: 'english'),
            new Language('en_US', 'English (US)', jquery_code: 'en-GB', js_code: 'en', english_name: 'english'),
            new Language('es_AR', 'Español (Argentina)', english_name: 'spanish'),
            new Language('es_EC', 'Español (Ecuador)', english_name: 'spanish'),
            new Language('es_CO', 'Español (Colombia)', english_name: 'spanish'),
            new Language('es_ES', 'Español (España)', english_name: 'spanish'),
            new Language('es_419', 'Español (América Latina)', english_name: 'spanish'),
            new Language('es_MX', 'Español (Mexico)', english_name: 'spanish'),
            new Language('es_VE', 'Español (Venezuela)', english_name: 'spanish'),
            new Language('eu_ES', 'Euskara', english_name: 'basque'),
            new Language('fr_FR', 'Français', english_name: 'french'),
            new Language('fr_CA', 'Français (Canada)', english_name: 'french'),
            new Language('fr_BE', 'Français (Belgique)', english_name: 'french'),
            new Language('gl_ES', 'Galego', english_name: 'galician'),
            new Language('el_GR', 'Ελληνικά', english_name: 'greek'), // el_EL
            new Language('he_IL', 'עברית', english_name: 'hebrew'), // he_HE
            new Language('hi_IN', 'हिन्दी', js_code: 'hi_IN', english_name: 'hindi'),
            new Language('hr_HR', 'Hrvatski', english_name: 'croatian'),
            new Language('hu_HU', 'Magyar', english_name: 'hungarian'),
            new Language('it_IT', 'Italiano', english_name: 'italian'),
            new Language('km_KH', 'ខ្មែរ (កម្ពុជា)', js_code: 'km_KH', english_name: 'cambodgian khmer', plural_number: 0),
            new Language('kn', 'ಕನ್ನಡ', jquery_code: 'en-GB', js_code: 'en', english_name: 'kannada'),
            new Language('lv_LV', 'Latviešu', english_name: 'latvian'),
            new Language('lt_LT', 'Lietuvių', english_name: 'lithuanian'),
            new Language('mn_MN', 'Монгол хэл', english_name: 'mongolian'),
            new Language('nl_NL', 'Nederlands', english_name: 'dutch'),
            new Language('nl_BE', 'Flemish', english_name: 'flemish'),
            new Language('nb_NO', 'Norsk (Bokmål)', jquery_code: 'no', english_name: 'norwegian'),
            new Language('nn_NO', 'Norsk (Nynorsk)', jquery_code: 'no', english_name: 'norwegian'),
            new Language('fa_IR', 'فارسی', english_name: 'persian'),
            new Language('pl_PL', 'Polski', english_name: 'polish'),
            new Language('pt_PT', 'Português', english_name: 'portuguese'),
            new Language('pt_BR', 'Português do Brasil', jquery_code: 'pt-BR', english_name: 'brazilian portuguese'),
            new Language('ro_RO', 'Română', js_code: 'en', english_name: 'romanian'),
            new Language('ru_RU', 'Русский', english_name: 'russian'),
            new Language('sk_SK', 'Slovenčina', english_name: 'slovak', plural_number: 10),
            new Language('sl_SI', 'Slovenščina', english_name: 'slovenian slovene'),
            new Language('sq_AL', 'Shqip', english_name: 'albanian'),
            new Language('sr_RS', 'Srpski', english_name: 'serbian'),
            new Language('fi_FI', 'Suomi', english_name: 'finish'),
            new Language('sv_SE', 'Svenska', english_name: 'swedish'),
            new Language('vi_VN', 'Tiếng Việt', english_name: 'vietnamese'),
            new Language('th_TH', 'ภาษาไทย', english_name: 'thai'),
            new Language('tr_TR', 'Türkçe', english_name: 'turkish'),
            new Language('uk_UA', 'Українська', js_code: 'en', english_name: 'ukrainian'), // ua_UA
            new Language('ja_JP', '日本語', english_name: 'japanese'),
            new Language('zh_CN', '简体中文', jquery_code: 'zh-CN', english_name: 'chinese'),
            new Language('zh_TW', '繁體中文', jquery_code: 'zh-TW', english_name: 'chinese'),
            new Language('ko_KR', '한국/韓國', english_name: 'korean', plural_number: 1),
            new Language('zh_HK', '香港', jquery_code: 'zh-HK', english_name: 'chinese'),
            new Language('be_BY', 'Belarussian', english_name: 'belarussian', plural_number: 3),
            new Language('is_IS', 'íslenska', js_code: 'en', english_name: 'icelandic'),
            new Language('eo', 'Esperanto', js_code: 'en', english_name: 'esperanto'),
            new Language('es_CL', 'Español chileno', english_name: 'spanish chilean'),
        ];
    }
}
