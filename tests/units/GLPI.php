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

/* Test for inc/glpi.class.php */

class GLPI extends \GLPITestCase
{
    public function testMissingLanguages()
    {
        global $CFG_GLPI;

        $know_languages = $CFG_GLPI['languages'];
        $list_languages = [];

        $diterator = new \DirectoryIterator(__DIR__ . '/../../locales');
        foreach ($diterator as $file) {
            if (!$file->isDot() && $file->getExtension() == 'po') {
                $lang = $file->getBasename('.' . $file->getExtension());
                $list_languages[$lang] = $lang;
            }
        }

        $po_missing = array_diff_key($know_languages, $list_languages);
        $this->array($po_missing)->isEmpty(
            "Referenced languages in configuration are missing in locales directory:\n" . print_r($po_missing, true)
        );

        $cfg_missing = array_diff_key($list_languages, $know_languages);
        $this->array($cfg_missing)->isEmpty(
            "Locales files present in directory are missing from configuration:\n" . print_r($cfg_missing, true)
        );
    }

    /**
     * Verify the value of the GLPI_YEAR const
     *
     * @return void
     */
    public function test_GlpiYear(): void
    {
        $this->string(GLPI_YEAR)->isEqualTo(date('Y'));
    }
}
