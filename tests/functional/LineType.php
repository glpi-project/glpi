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

use DbTestCase;

class LineType extends DbTestCase
{
    public function testAdd()
    {
        $this->login();
        $obj = new \LineType();

       // Add
        $in = [
            'name'                     => __METHOD__,
            'comment'                  => $this->getUniqueString(),
        ];
        $id = $obj->add($in);
        $this->integer((int)$id)->isGreaterThan(0);
        $this->boolean($obj->getFromDB($id))->isTrue();

       // getField methods
        $this->variable($obj->getField('id'))->isEqualTo($id);
        foreach ($in as $k => $v) {
            $this->variable($obj->getField($k))->isEqualTo($v, "The property $k does not match: expected $v, got " . $obj->getField($k));
        }
    }

    public function testUpdate()
    {
        $this->login();
        $obj = new \LineType();

       // Add
        $id = $obj->add([
            'name'                     => $this->getUniqueString(),
            'comment'                  => $this->getUniqueString(),
        ]);
        $this->integer($id)->isGreaterThan(0);

       // Update
        $id = $obj->getID();
        $in = [
            'id'                       => $id,
            'name'                     => __METHOD__,
            'comment'                  => $this->getUniqueString(),
        ];
        $this->boolean($obj->update($in))->isTrue();
        $this->boolean($obj->getFromDB($id))->isTrue();

       // getField methods
        foreach ($in as $k => $v) {
            $this->variable($obj->getField($k))->isEqualTo($v);
        }
    }

    public function testDelete()
    {
        $this->login();
        $obj = new \LineType();

       // Add
        $id = $obj->add([
            'name'                     => __METHOD__,
        ]);
        $this->integer($id)->isGreaterThan(0);

       // Delete
        $in = [
            'id'                       => $obj->getID(),
        ];
        $this->boolean($obj->delete($in))->isTrue();
    }
}
