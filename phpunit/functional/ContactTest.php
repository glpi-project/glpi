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

namespace tests\units;

/* Test for src/Contact.php */

class ContactTest extends \DbTestCase
{
    public function testClone()
    {
        $this->login();

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $this->setEntity('_test_root_entity', true);

        // Create ContactType
        $contact_type_id = $this->createItem('ContactType', [
            'name' => 'Supplier Type',
        ])->getID();

        // Create UserTitle
        $user_title_id = $this->createItem('UserTitle', [
            'name' => 'User Title',
        ])->getID();

        // Create supplier
        $contact = $this->createItem('Contact', [
            'name'                => 'contact name',
            'entities_id'         => 0,
            'is_recursive'        => 0,
            'firstname'           => 'contact firstname',
            'registration_number' => '0123456789',
            'phone'               => '123',
            'phone2'              => '456',
            'mobile'              => '789',
            'fax'                 => '951',
            'email'               => 'contact@email',
            'contacttypes_id'     => $contact_type_id,
            'comment'             => 'comment',
            'usertitles_id'       => $user_title_id,
            'postcode'            => 'contact postcode',
            'town'                => 'contact town',
            'state'               => 'contact state',
            'country'             => 'contact country',
            'pictures'            => 'contact pictures',
        ]);

        // Test item cloning
        $added = $contact->clone();
        $this->assertGreaterThan(0, (int) $added);

        $clonedContact = new \Contact();
        $this->assertTrue($clonedContact->getFromDB($added));

        // Check the values. Id and dates must be different, everything else must be equal
        $expected = $contact->fields;
        $expected['id'] = $clonedContact->getID();
        $expected['date_creation'] = $date;
        $expected['date_mod'] = $date;
        $expected['name'] = "contact name (copy)";
        $this->assertEquals($expected, $clonedContact->fields);
    }
}
