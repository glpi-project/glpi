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

namespace tests\units;

use Domain;
use DomainRecord;
use Glpi\Tests\DbTestCase;

/* Test for inc/software.class.php */

class DomainRecordTest extends DbTestCase
{
    public function testCanViewUnattached()
    {
        global $DB;
        $this->login();
        $record = new DomainRecord();
        // Unattached records are not allowed to be created anymore but may still exist in the DB. For the test, we need to directly add one to the DB.
        $DB->insert(
            'glpi_domainrecords',
            [
                'name' => __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
                'ttl' => 3600,
            ]
        );
        $this->assertTrue($record->getFromDB($DB->insertId()));

        $this->assertTrue($record->canViewItem());
    }

    public function testPrepareInput()
    {
        $this->login();
        $record = new DomainRecord();
        $this->assertFalse($record->prepareInputForAdd([]));
        $this->hasSessionMessages(ERROR, ['A domain is required']);

        $created_record = $this->createItem('DomainRecord', ['domains_id' => 1]);
        $this->assertEmpty($record->prepareInputForUpdate([]));
        $this->assertFalse($record->prepareInputForUpdate(['domains_id' => 0]));
        $this->hasSessionMessages(ERROR, ['A domain is required']);
        $this->assertFalse($record->prepareInputForUpdate(['domains_id' => '']));
        $this->hasSessionMessages(ERROR, ['A domain is required']);
    }

    public function testPrepareInputTtl()
    {
        $this->login();
        $record = new DomainRecord();

        // Explicit ttl = 0 on add must be kept as-is, not coerced to the default.
        $input = $record->prepareInputForAdd(['domains_id' => 1, 'ttl' => 0]);
        $this->assertSame(0, $input['ttl']);

        // Missing/empty ttl on add falls back to the default.
        $input = $record->prepareInputForAdd(['domains_id' => 1]);
        $this->assertSame(DomainRecord::DEFAULT_TTL, $input['ttl']);

        $input = $record->prepareInputForAdd(['domains_id' => 1, 'ttl' => '']);
        $this->assertSame(DomainRecord::DEFAULT_TTL, $input['ttl']);

        // A fresh (empty) item should reflect the real default ttl, not 0.
        $record->getEmpty();
        $this->assertSame(DomainRecord::DEFAULT_TTL, $record->fields['ttl']);
    }

    public function testGetDisplayName()
    {
        $domain = new Domain();
        $domain->fields['name'] = 'Test';

        // Record name only textually contains the domain name as a prefix,
        // it must not be stripped down to its trailing suffix.
        $this->assertSame('Test2', DomainRecord::getDisplayName($domain, 'Test2'));

        // Record name equal to the domain name is the DNS root.
        $this->assertSame('@', DomainRecord::getDisplayName($domain, 'Test'));

        // Record name is a genuine FQDN suffixed by the domain name.
        $this->assertSame('www', DomainRecord::getDisplayName($domain, 'www.Test'));
    }
}
