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

namespace tests\units;

use DbTestCase;
use Generator;
use Glpi\Socket;
use Glpi\Toolbox\Sanitizer;
use Session;
use State;

class DropdownTranslation extends DbTestCase
{
    /**
     * @return void
     * @see https://github.com/glpi-project/glpi/issues/15801
     */
    public function testCompleteNameGeneration()
    {
        global $CFG_GLPI;
        $CFG_GLPI['translate_dropdowns'] = 1;

        $this->login();
        $itilcategory = new \ITILCategory();
        $this->integer($itilcategory->add([
            'name' => 'test',
        ]))->isGreaterThan(0);

        $this->string(\DropdownTranslation::getTranslatedValue($itilcategory->getID(), 'ITILCategory', 'completename'))
            ->isEqualTo('test');

        $trans = new \DropdownTranslation();
        $this->integer($trans->add([
            'language' => 'fr_FR',
            'itemtype' => 'ITILCategory',
            'items_id' => $itilcategory->getID(),
            'field'    => 'name',
            'value'     => 'test_FR',
        ]))->isGreaterThan(0);
        $_SESSION['glpilanguage'] = 'fr_FR';
        $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

        $this->string(\DropdownTranslation::getTranslatedValue($itilcategory->getID(), 'ITILCategory', 'name'))
            ->isEqualTo('test_FR');
        $this->string(\DropdownTranslation::getTranslatedValue($itilcategory->getID(), 'ITILCategory', 'completename'))
            ->isEqualTo('test_FR');

        $_SESSION['glpilanguage'] = 'en_GB';
        $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

        $this->string(\DropdownTranslation::getTranslatedValue($itilcategory->getID(), 'ITILCategory', 'completename'))
            ->isEqualTo('test');

        $this->integer($trans->add([
            'language' => 'fr_FR',
            'itemtype' => 'ITILCategory',
            'items_id' => $itilcategory->getID(),
            'field' => 'comment',
            'value' => 'comment_FR',
        ]))->isGreaterThan(0);

        $_SESSION['glpilanguage'] = 'fr_FR';

        $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
        $this->string(\DropdownTranslation::getTranslatedValue($itilcategory->getID(), 'ITILCategory', 'name'))
            ->isEqualTo('test_FR');
        $this->string(\DropdownTranslation::getTranslatedValue($itilcategory->getID(), 'ITILCategory', 'completename'))
            ->isEqualTo('test_FR');
        $this->string(\DropdownTranslation::getTranslatedValue($itilcategory->getID(), 'ITILCategory', 'comment'))
            ->isEqualTo('comment_FR');
    }
}
