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

class Certificate extends DbTestCase
{
    private $method;

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);
       //to handle GLPI barbarian replacements.
        $this->method = str_replace(
            ['\\', 'beforeTestMethod'],
            ['', $method],
            __METHOD__
        );
    }

    public function testAdd()
    {
        $this->login();
        $obj = new \Certificate();

       // Add
        $in = $this->getIn($this->method);
        $id = $obj->add($in);
        $this->integer((int)$id)->isGreaterThan(0);
        $this->boolean($obj->getFromDB($id))->isTrue();

       // getField methods
        $this->variable($obj->getField('id'))->isEqualTo($id);
        foreach ($in as $k => $v) {
            $this->variable($obj->getField($k))->isEqualTo($v);
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
        $this->integer($id)->isGreaterThan(0);

       // Update
        $id = $obj->getID();
        $in = array_merge(['id' => $id], $this->getIn($this->method));
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
        $obj = new \Certificate();

       // Add
        $id = $obj->add([
            'name' => $this->method,
            'entities_id' => 0
        ]);
        $this->integer($id)->isGreaterThan(0);

       // Delete
        $in = [
            'id' => $obj->getID(),
        ];
        $this->boolean($obj->delete($in))->isTrue();
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
        $this->integer($id)->isGreaterThan(0);

       // Update
        $id = $certificate->getID();
        $in = array_merge(['id' => $id], $this->getIn($this->method));
        $this->boolean($certificate->update($in))->isTrue();
        $this->boolean($certificate->getFromDB($id))->isTrue();

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

       // Test item cloning
        $added = $certificate->clone();
        $this->integer((int)$added)->isGreaterThan(0);

        $clonedCertificate = new \Certificate();
        $this->boolean($clonedCertificate->getFromDB($added))->isTrue();

        $fields = $certificate->fields;

       // Check the certificate values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->variable($clonedCertificate->getField($k))->isNotEqualTo($certificate->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedCertificate->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->dateTime($dateClone)->isEqualTo($expectedDate);
                    break;
                case 'name':
                    $this->variable($clonedCertificate->getField($k))->isEqualTo("{$certificate->getField($k)} (copy)");
                    break;
                default:
                    $this->variable($clonedCertificate->getField($k))->isEqualTo($certificate->getField($k));
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
        $this->integer($id)->isGreaterThan(0);

       // set root entity config for certificates alerts
        $entity = new \Entity();
        $entity->update([
            'id'                                   => 0,
            'use_certificates_alert'               => true,
            'send_certificates_alert_before_delay' => true,
            'certificates_alert_repeat_interval'   => 60
        ]);

       // force usage of notification (no alert sent otherwise)
        $CFG_GLPI['use_notifications']  = true;
        $CFG_GLPI['notifications_ajax'] = 1;

       // lanch glpi cron and force task certificate
        $crontask = new \CronTask();
        $force    = -1;
        $ret      = $crontask->launch($force, 1, 'certificate');

       // check presence of the id in alerts table
        $alert  = new \Alert();
        $alerts = $alert->find();

        $this->array($alerts)
           ->hasSize(1);
        $alert_certificate = array_pop($alerts);
        $this->array($alert_certificate)
         ->string['itemtype']->isEqualTo('Certificate')
         ->integer['items_id']->isEqualTo($id);

        // No new alert if the last one is less than 1 hour old
        $alert_id = $alert_certificate['id'];
        $ret      = $crontask->launch($force, 1, 'certificate');
        $alerts   = $alert->find();

        $this->array($alerts)
            ->hasSize(1);
        $alert_certificate = array_pop($alerts);
        $this->array($alert_certificate)
            ->string['itemtype']->isEqualTo('Certificate')
            ->integer['items_id']->isEqualTo($id)
            ->integer['id']->isEqualTo($alert_id);

        // New alert if the last one is more than 1 hour old
        $alert_id = $alert_certificate['id'];
        $alert->update([
            'id'    => $alert_id,
            'date'  => date('Y-m-d', time() - DAY_TIMESTAMP)
        ]);
        $ret      = $crontask->launch($force, 1, 'certificate');
        $alerts   = $alert->find();

        $this->array($alerts)
            ->hasSize(1);
        $alert_certificate = array_pop($alerts);
        $this->array($alert_certificate)
            ->string['itemtype']->isEqualTo('Certificate')
            ->integer['items_id']->isEqualTo($id)
            ->integer['id']->isEqualTo($alert_id + 1);
    }
}
