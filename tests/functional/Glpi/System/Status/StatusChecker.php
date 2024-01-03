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

namespace tests\units\Glpi\System\Status;

use AuthLDAP;
use AuthMail;
use CronTask;
use DbTestCase;
use Glpi\System\Status\StatusChecker as GlpiStatusChecker;

class StatusChecker extends DbTestCase
{
    public function testStatusFormats()
    {
        $status = GlpiStatusChecker::getServiceStatus(null, true);
        $this->boolean(is_array($status))->isTrue();

        $status = GlpiStatusChecker::getServiceStatus(null, true, false);
        $this->boolean(is_string($status))->isTrue();
    }

    public function testDefaultStatus()
    {
        $status = GlpiStatusChecker::getServiceStatus(null, true);

        $known_services = ['db', 'cas', 'ldap', 'imap', 'mail_collectors', 'crontasks', 'filesystem', 'glpi', 'plugins'];
       // Check we are getting all of the expected services
        $this->array($status)->hasKeys($known_services);

       // Check each service has a status value
        foreach ($known_services as $service) {
            $this->boolean(is_array($status[$service]))->isTrue();
            $this->array($status[$service])->hasKey('status');
            $this->boolean(is_string($status[$service]['status']))->isTrue();
        }

       // Test overall status is OK
        $this->string($status['glpi']['status'])->isEqualTo(GlpiStatusChecker::STATUS_OK);

       // Test the overall status matches the combined status of the top-level services
       // NO_DATA should not count against the overall status.
       // If an administrator expects something to have data, that should be a separate service check in their monitoring system.
        $statuses = array_column($status, 'status');
        $all_ok = !in_array(GlpiStatusChecker::STATUS_PROBLEM, $statuses, true);
        $this->boolean($all_ok)->isTrue();

       // We have no plugins. Verify the status is NO_DATA
        $this->string($status['plugins']['status'])->isEqualTo(GlpiStatusChecker::STATUS_NO_DATA);
       // We should have a master DB for tests. Verify status is OK.
        $this->string($status['db']['master']['status'])->isEqualTo(GlpiStatusChecker::STATUS_OK);
       // We have no DB slaves. Verify the status is NO_DATA
        $this->string($status['db']['slaves']['status'])->isEqualTo(GlpiStatusChecker::STATUS_NO_DATA);
       // We have no CAS. Verify the status is NO_DATA
        $this->string($status['cas']['status'])->isEqualTo(GlpiStatusChecker::STATUS_NO_DATA);
       // We have no LDAP servers. Verify the status is NO_DATA
        $this->string($status['ldap']['status'])->isEqualTo(GlpiStatusChecker::STATUS_NO_DATA);
       // We have no IMAP servers. Verify the status is NO_DATA
        $this->string($status['imap']['status'])->isEqualTo(GlpiStatusChecker::STATUS_NO_DATA);
       // We have no mail collectors. Verify the status is NO_DATA
        $this->string($status['mail_collectors']['status'])->isEqualTo(GlpiStatusChecker::STATUS_NO_DATA);

       // Make sure no stuck cron tasks are reported
        $this->string($status['crontasks']['status'])->isEqualTo(GlpiStatusChecker::STATUS_OK);
        $this->array($status['crontasks']['stuck'])->size->isEqualTo(0);

       // Check filesystem and session_dir are OK
        $this->string($status['filesystem']['status'])->isEqualTo(GlpiStatusChecker::STATUS_OK);
        $this->string($status['filesystem']['session_dir']['status'])->isEqualTo(GlpiStatusChecker::STATUS_OK);
    }

    public function testBadStatus()
    {
        global $DB;

       // Run a DB check first so the status checker knows the DB is available.
       // Future checks won't re-run that check then, so the DB changes aren't lost.
        GlpiStatusChecker::getDBStatus();

       // Manually start a transaction since this is a new connection
        $DB->beginTransaction();

       // Add a bunch of bad service items
        $auth_mail = new AuthMail();
        $authmail_id = $auth_mail->add([
            'name'            => 'testmail',
            'connect_string'  => '{smtp.localhost/imap/ssl/validate-cert/tls/secure}',
            'host'            => 'localhost',
            'is_active'       => 1
        ]);
        $this->integer($authmail_id)->isGreaterThan(0);

        $auth_ldap = new AuthLDAP();
        $authlap_id = $auth_ldap->add([
            'name'            => 'testldap',
            'host'            => 'localhost',
            'is_active'       => 1,
            'rootdn'          => 'cn=Manager,dc=glpi,dc=org',
            'rootdn_passwd'   => md5(mt_rand())
        ]);
        $this->integer($authlap_id)->isGreaterThan(0);

        $crontask = new CronTask();
       // A definitely stuck crontask running for over an hour.
        $crontask->add([
            'itemtype'     => 'Ticket',
            'name'         => 'stucktest1',
            'frequency'    => 60,
            'lastrun'      => date('Y-m-d 00:00:00', strtotime('-61 minute')),
            'state'        => \CronTask::STATE_RUNNING,
        ]);
       // A probably stuck crontask running for longer than the run interval.
        $crontask->add([
            'itemtype'     => 'Ticket',
            'name'         => 'stucktest2',
            'frequency'    => 60,
            'lastrun'      => date('Y-m-d 00:00:00', strtotime('-5 minute')),
            'state'        => \CronTask::STATE_RUNNING,
        ]);

        $status = GlpiStatusChecker::getServiceStatus(null, true, true);

       // Test overall status is PROBLEM
        $this->string($status['glpi']['status'])->isEqualTo(GlpiStatusChecker::STATUS_PROBLEM);

       // Test there is at least one service with a problem
        $statuses = array_column($status, 'status');
        $all_ok = !in_array(GlpiStatusChecker::STATUS_PROBLEM, $statuses, true);
        $this->boolean($all_ok)->isFalse();

       // We have a non-existent LDAP server. Verify the status is PROBLEM.
        $this->string($status['ldap']['status'])->isEqualTo(GlpiStatusChecker::STATUS_PROBLEM);
       // We have a non-existent IMAP server. Verify the status is PROBLEM.
        $this->string($status['imap']['status'])->isEqualTo(GlpiStatusChecker::STATUS_PROBLEM);

       // Make sure there are two stuck tasks
        $this->string($status['crontasks']['status'])->isEqualTo(GlpiStatusChecker::STATUS_PROBLEM);
        $this->array($status['crontasks']['stuck'])->size->isEqualTo(2);

       // afterTestMethod will rollback the DB changes for us
    }

    protected function getCalculatedGlobalStatusProvider()
    {
        return [
            [
                [
                    'db'  => ['status' => GlpiStatusChecker::STATUS_OK],
                    'cas'  => ['status' => GlpiStatusChecker::STATUS_OK],
                    'ldap'  => ['status' => GlpiStatusChecker::STATUS_OK]
                ],
                GlpiStatusChecker::STATUS_OK
            ],
            [
                [
                    'db'  => ['status' => GlpiStatusChecker::STATUS_OK],
                    'cas'  => ['status' => GlpiStatusChecker::STATUS_WARNING],
                    'ldap'  => ['status' => GlpiStatusChecker::STATUS_OK]
                ],
                GlpiStatusChecker::STATUS_WARNING
            ],
            [
                [
                    'db'  => ['status' => GlpiStatusChecker::STATUS_OK],
                    'cas'  => ['status' => GlpiStatusChecker::STATUS_OK],
                    'ldap'  => ['status' => GlpiStatusChecker::STATUS_PROBLEM]
                ],
                GlpiStatusChecker::STATUS_PROBLEM
            ],
            [
                [
                    'db'  => ['status' => GlpiStatusChecker::STATUS_NO_DATA],
                    'cas'  => ['status' => GlpiStatusChecker::STATUS_OK],
                    'ldap'  => ['status' => GlpiStatusChecker::STATUS_OK]
                ],
                GlpiStatusChecker::STATUS_OK
            ],
            [
                [
                    'db'  => ['status' => GlpiStatusChecker::STATUS_NO_DATA],
                    'cas'  => ['status' => GlpiStatusChecker::STATUS_WARNING],
                    'ldap'  => ['status' => GlpiStatusChecker::STATUS_PROBLEM]
                ],
                GlpiStatusChecker::STATUS_PROBLEM
            ]
        ];
    }

    /**
     * @dataProvider getCalculatedGlobalStatusProvider
     * @param $status
     */
    public function testGetCalculateGlobalStatus($status, $expected)
    {
        $this->string(GlpiStatusChecker::calculateGlobalStatus($status))->isEqualTo($expected);
    }

    public function testGetServiceStatus()
    {
        $services = GlpiStatusChecker::getServices();
        $this->boolean(is_array($services))->isTrue();

        foreach ($services as $name => $callback) {
            $this->boolean(is_string($name))->isTrue();

            $this->boolean(is_array($callback))->isTrue();
            $this->integer(count($callback))->isEqualTo(2);
            $this->boolean(method_exists($callback[0], $callback[1]))->isTrue();

            $status = GlpiStatusChecker::getServiceStatus($name, true);
            $this->boolean(is_array($status))->isTrue();
            $this->array($status)->hasKey('status');

            $status = GlpiStatusChecker::getServiceStatus($name, false);
            $this->boolean(is_array($status))->isTrue();
            $this->array($status)->hasKey('status');

            $status = GlpiStatusChecker::getServiceStatus($name, true, false);
            $this->boolean(is_string($status))->isTrue();
            $this->string($status)->isNotEmpty();

            $status = GlpiStatusChecker::getServiceStatus($name, false, false);
            $this->boolean(is_string($status))->isTrue();
            $this->string($status)->isNotEmpty();
        }
    }
}
