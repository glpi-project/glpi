<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Toolbox;

use Toolbox;

/**
 * Helper class to build markdown content
 */
class MarkdownBuilder
{
    /**
     * Generated markdown content
     *
     * @var string
     */
    protected $content = "";

    /**
     * Get the generated markdown content
     *
     * @return string
     */
    public function getMarkdown(): string
    {
        return $this->content;
    }

    /**
     * Add a header to the markdown content
     *
     * @param string $prefix Header type (#, ##, ...)
     * @param string $content Header content
     * @param string|null $css_class Css class to add to this header
     */
    protected function addHeader(
        string $prefix,
        string $content,
        ?string $css_class = null
    ) {
        $css_class = !is_null($css_class) ? "{.$css_class}" : "";
        $this->content .= sprintf("%s %s %s \n", $prefix, $content, $css_class);
    }

    /**
     * Add a h1 header
     *
     * @param string $content Header content
     * @param string|null $css_class Css class to add to this header
     */
    public function addH1(string $content, ?string $css_class = null)
    {
        $this->addHeader("#", $content, $css_class);
    }

    /**
     * Add a h2 header
     *
     * @param string $content Header content
     * @param string|null $css_class Css class to add to this header
     */
    public function addH2(string $content, ?string $css_class = null)
    {
        $this->addHeader("##", $content, $css_class);
    }

    /**
     * Add a h3 header
     *
     * @param string $content Header content
     * @param string|null $css_class Css class to add to this header
     */
    public function addH3(string $content, ?string $css_class = null)
    {
        $this->addHeader("###", $content, $css_class);
    }

    /**
     * Add a h4 header
     *
     * @param string $content Header content
     * @param string|null $css_class Css class to add to this header
     */
    public function addH4(string $content, ?string $css_class = null)
    {
        $this->addHeader("####", $content, $css_class);
    }

    /**
     * Add a h5 header
     *
     * @param string $content Header content
     * @param string|null $css_class Css class to add to this header
     */
    public function addH5(string $content, ?string $css_class = null)
    {
        $this->addHeader("#####", $content, $css_class);
    }

    /**
     * Add a h6 header
     *
     * @param string $content Header content
     * @param string|null $css_class Css class to add to this header
     */
    public function addH6(string $content, ?string $css_class = null)
    {
        $this->addHeader("######", $content, $css_class);
    }

    /**
     * Add a table row
     *
     * @param array $values
     */
    public function addTableRow(array $values)
    {
        $this->content .= "|" . implode("|", $values) . "\n";
    }

    /**
     * Add a table header
     *
     * @param array $values
     */
    public function addTableHeader(array $headers)
    {
        $separator = array_fill(0, count($headers), '------');
        $this->addTableRow($headers);
        $this->addTableRow($separator);
    }

    /**
     * Helper function to encapsulate single line code
     *
     * @return string
     */
    public static function code($code): string
    {
        return sprintf("```%s```", $code);
    }

    /**
     * Helper function create a navigation link
     *
     * @return string
     */
    public static function navigationLink($label)
    {
        $link = Toolbox::slugify($label, '');
        return "[$label](#$link)";
    }

    /**
     * Helper function create a summary entry
     *
     * @return void
     */
    public function addSummaryEntry($label)
    {
        $link = self::navigationLink($label);
        $this->content .= "* $link\n";
    }
}
