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
 * Immutable description of a GLPI language.
 *
 * Every piece of information GLPI needs about a language is exposed as a typed,
 * named property to no longer rely on `$CFG_GLPI['languages']` array.
 *
 * Most languages only need a `$code` and a `$nativeName`: the MO file name, the
 * jQuery/i18n code and the JS/page-lang code are derived from the code with
 * defaults, and only the exceptions have to be provided explicitly.
 */
final class Language
{
    /**
     * MO file name (e.g. `fr_FR.mo`). Defaults to `{code}.mo`.
     */
    public readonly string $mo_file;

    /**
     * jQuery / select2 / fullcalendar i18n code (e.g. `fr`, `en-GB`, `pt-BR`).
     * Defaults to the region-less part of the code.
     */
    public readonly string $jquery_code;

    /**
     * JS code, also used as the HTML page `lang` attribute (e.g. `fr`, `en`).
     * Defaults to the region-less part of the code.
     */
    public readonly string $js_code;

    /**
     * @param string      $code         Regionalized language code, e.g. `fr_FR` (the array key).
     * @param string      $native_name  Language name written in its own language.
     * @param string|null $mo_file      MO file name; defaults to `{code}.mo`.
     * @param string|null $jquery_code  jQuery/i18n code; defaults to the region-less code.
     * @param string|null $js_code      JS/page-lang code; defaults to the region-less code.
     * @param string      $english_name Language name written in english.
     * @param int         $plural_number Gettext plural rule number.
     */
    public function __construct(
        public readonly string $code,
        public readonly string $native_name,
        ?string $mo_file = null,
        ?string $jquery_code = null,
        ?string $js_code = null,
        public readonly string $english_name = '',
        public readonly int $plural_number = 2,
    ) {
        $regionless = self::regionlessCode($code);

        $this->mo_file     = $mo_file ?? ($code . '.mo');
        $this->jquery_code = $jquery_code ?? $regionless;
        $this->js_code     = $js_code ?? $regionless;
    }

    /**
     * Region-less part of the code (`fr_FR` -> `fr`).
     */
    public function getRegionlessCode(): string
    {
        return self::regionlessCode($this->code);
    }

    /**
     * Code to use as the HTML page `lang` attribute and in front-end JS.
     */
    public function getPageLang(): string
    {
        return $this->js_code;
    }

    /**
     * Whether the language is written right-to-left.
     */
    public function isRTL(): bool
    {
        return locale_is_right_to_left($this->code);
    }

    private static function regionlessCode(string $code): string
    {
        return explode('_', $code)[0];
    }
}
