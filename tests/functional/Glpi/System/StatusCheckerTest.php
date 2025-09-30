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

namespace tests\units\Glpi\System;

use AuthLDAP;
use AuthMail;
use CronTask;
use Glpi\System\Status\StatusChecker;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class StatusCheckerTest extends GLPITestCase
{
    public function setUp(): void
    {
        parent::setUp();
        StatusChecker::resetInstance();
    }

    public function testStatusFormat()
    {
        $status = StatusChecker::getServiceStatus(service: null);
        $this->assertIsArray($status);
    }

    public function testDefaultStatus()
    {
        $status = StatusChecker::getServiceStatus(service: null);

        $known_services = ['db', 'cas', 'ldap', 'imap', 'mail_collectors', 'crontasks', 'filesystem', 'glpi', 'plugins'];

        //  Check we are getting all of the expected services and each service has a status value
        foreach ($known_services as $service) {
            $this->assertIsArray($status[$service]);
            $this->assertArrayHasKey('status', $status[$service]);
            $this->assertIsString($status[$service]['status']);
        }

        // Test overall status is OK
        $this->assertEquals(StatusChecker::STATUS_OK, $status['glpi']['status']);

        // Test the overall status matches the combined status of the top-level services
        // NO_DATA should not count against the overall status.
        // If an administrator expects something to have data, that should be a separate service check in their monitoring system.
        $statuses = array_column($status, 'status');
        $all_ok = !in_array(StatusChecker::STATUS_PROBLEM, $statuses, true);
        $this->assertTrue($all_ok);

        // We have no plugins. Verify the status is NO_DATA
        $this->assertEquals(StatusChecker::STATUS_NO_DATA, $status['plugins']['status']);
        // We should have a main DB for tests. Verify status is OK.
        $this->assertEquals(StatusChecker::STATUS_OK, $status['db']['main']['status']);
        // We have no DB replicas. Verify the status is NO_DATA
        $this->assertEquals(StatusChecker::STATUS_NO_DATA, $status['db']['replicas']['status']);
        // We have no CAS. Verify the status is NO_DATA
        $this->assertEquals(StatusChecker::STATUS_NO_DATA, $status['cas']['status']);
        // We have no LDAP servers. Verify the status is NO_DATA
        $this->assertEquals(StatusChecker::STATUS_NO_DATA, $status['ldap']['status']);
        // We have no IMAP servers. Verify the status is NO_DATA
        $this->assertEquals(StatusChecker::STATUS_NO_DATA, $status['imap']['status']);
        // We have no mail collectors. Verify the status is NO_DATA
        $this->assertEquals(StatusChecker::STATUS_NO_DATA, $status['mail_collectors']['status']);

        // Make sure no stuck cron tasks are reported
        $this->assertEquals(StatusChecker::STATUS_OK, $status['crontasks']['status']);
        $this->assertEmpty($status['crontasks']['stuck']);

        // Check filesystem and session_dir are OK
        $this->assertEquals(StatusChecker::STATUS_OK, $status['filesystem']['status']);
        $this->assertEquals(StatusChecker::STATUS_OK, $status['filesystem']['session_dir']['status']);
    }

    public function testBadStatus()
    {
        global $DB;

        // Run a DB check first so the status checker knows the DB is available.
        // Future checks won't re-run that check then, so the DB changes aren't lost.
        StatusChecker::getDBStatus();

        $DB->beginTransaction();

        // Add a bunch of bad service items
        $auth_mail = new AuthMail();
        $authmail_id = $auth_mail->add([
            'name'            => 'testmail',
            'connect_string'  => '{smtp.localhost/imap/ssl/validate-cert/tls/secure}',
            'host'            => 'localhost',
            'is_active'       => 1,
        ]);
        $this->assertGreaterThan(0, $authmail_id);

        $auth_ldap = new AuthLDAP();
        $authlap_id = $auth_ldap->add([
            'name'            => 'testldap',
            'host'            => 'localhost',
            'is_active'       => 1,
            'rootdn'          => 'cn=Manager,dc=glpi,dc=org',
            'rootdn_passwd'   => md5(mt_rand()),
        ]);
        $this->assertGreaterThan(0, $authlap_id);

        $crontask = new CronTask();
        // A definitely stuck crontask running for over an hour.
        $crontask->add([
            'itemtype'     => 'Ticket',
            'name'         => 'stucktest1',
            'frequency'    => 60,
            'lastrun'      => date('Y-m-d 00:00:00', strtotime('-61 minute')),
            'state'        => CronTask::STATE_RUNNING,
        ]);
        // A probably stuck crontask running for longer than the run interval.
        $crontask->add([
            'itemtype'     => 'Ticket',
            'name'         => 'stucktest2',
            'frequency'    => 60,
            'lastrun'      => date('Y-m-d 00:00:00', strtotime('-5 minute')),
            'state'        => CronTask::STATE_RUNNING,
        ]);

        // We have a non-existent LDAP server. Verify the status is PROBLEM.
        $this->assertEquals(StatusChecker::STATUS_PROBLEM, StatusChecker::getLDAPStatus()['status']);
        // We have a non-existent IMAP server. Verify the status is PROBLEM.
        $this->assertEquals(StatusChecker::STATUS_PROBLEM, StatusChecker::getIMAPStatus()['status']);

        // Make sure there are two stuck tasks
        $crontask_status = StatusChecker::getCronTaskStatus();
        $this->assertEquals(StatusChecker::STATUS_PROBLEM, $crontask_status['status']);
        $this->assertCount(2, $crontask_status['stuck']);

        $DB->rollBack();

        // Check the overall status without forcing a re-check in a separate call after individual status checks to avoid an issue where a full status check would show everything is OK
        $status = StatusChecker::getServiceStatus(service: null);
        $this->assertEquals(StatusChecker::STATUS_PROBLEM, $status['glpi']['status']);
    }

    public static function getCalculatedGlobalStatusProvider()
    {
        return [
            [
                [
                    'db'  => ['status' => StatusChecker::STATUS_OK],
                    'cas'  => ['status' => StatusChecker::STATUS_OK],
                    'ldap'  => ['status' => StatusChecker::STATUS_OK],
                ],
                StatusChecker::STATUS_OK,
            ],
            [
                [
                    'db'  => ['status' => StatusChecker::STATUS_OK],
                    'cas'  => ['status' => StatusChecker::STATUS_WARNING],
                    'ldap'  => ['status' => StatusChecker::STATUS_OK],
                ],
                StatusChecker::STATUS_WARNING,
            ],
            [
                [
                    'db'  => ['status' => StatusChecker::STATUS_OK],
                    'cas'  => ['status' => StatusChecker::STATUS_OK],
                    'ldap'  => ['status' => StatusChecker::STATUS_PROBLEM],
                ],
                StatusChecker::STATUS_PROBLEM,
            ],
            [
                [
                    'db'  => ['status' => StatusChecker::STATUS_NO_DATA],
                    'cas'  => ['status' => StatusChecker::STATUS_OK],
                    'ldap'  => ['status' => StatusChecker::STATUS_OK],
                ],
                StatusChecker::STATUS_OK,
            ],
            [
                [
                    'db'  => ['status' => StatusChecker::STATUS_NO_DATA],
                    'cas'  => ['status' => StatusChecker::STATUS_WARNING],
                    'ldap'  => ['status' => StatusChecker::STATUS_PROBLEM],
                ],
                StatusChecker::STATUS_PROBLEM,
            ],
        ];
    }

    #[DataProvider('getCalculatedGlobalStatusProvider')]
    public function testGetCalculateGlobalStatus($status, $expected)
    {
        $this->assertEquals($expected, StatusChecker::calculateGlobalStatus($status));
    }

    public function testGetServiceStatus()
    {
        $services = StatusChecker::getServices();
        $this->assertIsArray($services);

        foreach ($services as $name => $callback) {
            $this->assertIsString($name);
            $this->assertIsCallable($callback);

            $status = StatusChecker::getServiceStatus(service: $name);
            $this->assertIsArray($status);
            $this->assertArrayHasKey('status', $status);

            $status = StatusChecker::getServiceStatus(service: $name, public_only: false);
            $this->assertIsArray($status);
            $this->assertArrayHasKey('status', $status);
        }
    }
}
