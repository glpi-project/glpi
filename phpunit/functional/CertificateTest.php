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

/* Test for inc/alert.class.php */

class CertificateTest extends DbTestCase
{
    public function testAdd()
    {
        $this->login();
        $obj = new \Certificate();

       // Add
        $in = $this->getIn($this->getUniqueString());
        $id = $obj->add($in);
        $this->assertGreaterThan(0, $id);
        $this->assertTrue($obj->getFromDB($id));

        // getField methods
        $this->assertEquals($id, $obj->getField('id'));
        foreach ($in as $k => $v) {
            $this->assertEquals($v, $obj->getField($k));
        }
    }

    public function testUpdate()
    {
        $this->login();
        $obj = new \Certificate();

        // Add
        $id = $obj->add([
            'name'        => $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $id);

        // Update
        $id = $obj->getID();
        $in = array_merge(['id' => $id], $this->getIn($this->getUniqueString()));
        $this->assertTrue($obj->update($in));
        $this->assertTrue($obj->getFromDB($id));

        // getField methods
        foreach ($in as $k => $v) {
            $this->assertEquals($v, $obj->getField($k));
        }
    }

    public function testDelete()
    {
        $this->login();
        $obj = new \Certificate();

        // Add
        $id = $obj->add([
            'name' => $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $id);

        // Delete
        $in = [
            'id' => $obj->getID(),
        ];
        $this->assertTrue($obj->delete($in));
    }

    public function testClone()
    {
        $this->login();
        $certificate = new \Certificate();

        // Add
        $id = $certificate->add([
            'name'        => $this->getUniqueString(),
            'entities_id' => 0
        ]);
        $this->assertGreaterThan(0, $id);

        // Update
        $id = $certificate->getID();
        $in = array_merge(['id' => $id], $this->getIn($this->getUniqueString()));
        $this->assertTrue($certificate->update($in));
        $this->assertTrue($certificate->getFromDB($id));

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        // Test item cloning
        $added = $certificate->clone();
        $this->assertGreaterThan(0, $added);

        $clonedCertificate = new \Certificate();
        $this->assertTrue($clonedCertificate->getFromDB($added));

        $fields = $certificate->fields;

        // Check the certificate values. ID and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($certificate->getField($k), $clonedCertificate->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedCertificate->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertEquals("{$certificate->getField($k)} (copy)", $clonedCertificate->getField($k));
                    break;
                default:
                    $this->assertEquals($certificate->getField($k), $clonedCertificate->getField($k));
            }
        }
    }

    public function getIn($method = "")
    {
        return [
            'name'                => $method,
            'entities_id'         => 0,
            'serial'              => $this->getUniqueString(),
            'otherserial'         => $this->getUniqueString(),
            'comment'             => $this->getUniqueString(),
            'certificatetypes_id' => $this->getUniqueInteger(),
            'dns_name'            => $this->getUniqueString(),
            'dns_suffix'          => $this->getUniqueString(),
            'users_id_tech'       => $this->getUniqueInteger(),
            'groups_id_tech'      => $this->getUniqueInteger(),
            'locations_id'        => $this->getUniqueInteger(),
            'manufacturers_id'    => $this->getUniqueInteger(),
            'users_id'            => $this->getUniqueInteger(),
            'groups_id'           => $this->getUniqueInteger(),
            'is_autosign'         => 1,
            'date_expiration'     => date('Y-m-d', time() + MONTH_TIMESTAMP),
            'states_id'           => $this->getUniqueInteger(),
            'command'             => $this->getUniqueString(),
            'certificate_request' => $this->getUniqueString(),
            'certificate_item'    => $this->getUniqueString(),
        ];
    }

    public function testCronCertificate()
    {
        global $CFG_GLPI;

        $this->login();
        $obj = new \Certificate();

        // Add
        $id = $obj->add([
            'name'            => $this->getUniqueString(),
            'entities_id'     => 0,
            'date_expiration' => date('Y-m-d', time() - MONTH_TIMESTAMP)
        ]);
        $this->assertGreaterThan(0, $id);

        // set root entity config for certificates alerts
        $entity = new \Entity();
        $this->assertTrue(
            $entity->update([
                'id'                                   => 0,
                'use_certificates_alert'               => true,
                'send_certificates_alert_before_delay' => true,
                'certificates_alert_repeat_interval'   => 6 * HOUR_TIMESTAMP //60 minutes was not enough with phpunit...
            ])
        );

        // force usage of notification (no alert sent otherwise)
        $CFG_GLPI['use_notifications']  = true;
        $CFG_GLPI['notifications_ajax'] = 1;

        // launch glpi cron and force task certificate
        $crontask = new \CronTask();
        $force    = -1;
        $ret      = $crontask->launch($force, 1, 'certificate');
        $this->assertNotEquals(false, $ret);

        // check presence of the id in alerts table
        $alert  = new \Alert();
        $alerts = $alert->find();

        $this->assertCount(1, $alerts);
        $alert_certificate = array_pop($alerts);
        $this->assertEquals('Certificate', $alert_certificate['itemtype']);
        $this->assertEquals($id, $alert_certificate['items_id']);

        // No new alert if the last one is less than 1 hour old
        $alert_id = $alert_certificate['id'];
        $ret      = $crontask->launch($force, 1, 'certificate');
        $this->assertNotEquals(false, $ret);
        $alerts   = $alert->find();

        $this->assertCount(1, $alerts);
        $alert_certificate = array_pop($alerts);
        $this->assertEquals('Certificate', $alert_certificate['itemtype']);
        $this->assertEquals($id, $alert_certificate['items_id']);
        $this->assertEquals($alert_id, $alert_certificate['id']);

        // New alert if the last one is more than 1 hour old
        $alert_id = $alert_certificate['id'];
        $alert->update([
            'id'    => $alert_id,
            'date'  => date('Y-m-d', time() - DAY_TIMESTAMP)
        ]);
        $ret      = $crontask->launch($force, 1, 'certificate');
        $this->assertNotEquals(false, $ret);
        $alerts   = $alert->find();

        $this->assertCount(1, $alerts);
        $alert_certificate = array_pop($alerts);
        $this->assertEquals('Certificate', $alert_certificate['itemtype']);
        $this->assertEquals($id, $alert_certificate['items_id']);
        $this->assertEquals($alert_id + 1, $alert_certificate['id']);
    }
}
