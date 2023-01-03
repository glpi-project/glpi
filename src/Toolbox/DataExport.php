<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\RichText\RichText;

class DataExport
{
    /**
     * Normalize a value for text export (PDF, CSV, SYLK, ...).
     * Assume value cames from DB and has been processed by GLPI sanitize process.
     *
     * @param string $value
     *
     * @return string
     */
    public static function normalizeValueForTextExport(string $value): string
    {
        $value = Sanitizer::unsanitize($value);

        if (RichText::isRichTextHtmlContent($value)) {
            libxml_use_internal_errors(true); // Silent errors
            $document = new \DOMDocument();
            $document->loadHTML(
                '<?xml encoding="utf-8" ?><div>' . $value . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            // Remove invisible contents (tooltips for instance)
            $xpath = new \DOMXPath($document);
            $invisible_elements = $xpath->query('//div[contains(@class, "invisible")]');
            foreach ($invisible_elements as $element) {
                $element->parentNode->removeChild($element);
            }

            // Remove FontAwesome and Table icons that does not contains any text
            $icons_elements = $xpath->query('//*[contains(@class, "fa-") or contains(@class, "ti-")]');
            foreach ($icons_elements as $element) {
                if (strlen(trim($element->textContent)) === 0) {
                    $element->parentNode->removeChild($element);
                }
            }

            $value = $document->saveHTML();

            // Transform into simple text
            $value = RichText::getTextFromHtml($value, true, true);

            // Remove extra spacing
            $nbsp = chr(0xC2) . chr(0xA0); // unicode value of decoded &nbsp;
            $value = trim($value, " \n\r\t" . $nbsp);
        }

        return $value;
    }
}
