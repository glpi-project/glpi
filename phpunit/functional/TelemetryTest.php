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

/* Test for inc/telemetry.class.php NOT requiring the Web server*/

class TelemetryTest extends DbTestCase
{
    public function testGrabGlpiInfos()
    {
       //we do not want any error messages
        $_SESSION['glpicronuserrunning'] = "cron_phpunit";

        $expected = [
            'uuid'               => 'TO BE SET',
            'version'            => GLPI_VERSION,
            'plugins'            => [],
            'default_language'   => 'en_GB',
            'install_mode'       => GLPI_INSTALL_MODE,
            'usage'              => [
                'avg_entities'          => '0-500',
                'avg_computers'         => '0-500',
                'avg_networkequipments' => '0-500',
                'avg_tickets'           => '0-500',
                'avg_problems'          => '0-500',
                'avg_changes'           => '0-500',
                'avg_projects'          => '0-500',
                'avg_users'             => '0-500',
                'avg_groups'            => '0-500',
                'ldap_enabled'          => false,
                'mailcollector_enabled' => false,
                'notifications_modes'   => [],
            ]
        ];

        $result = \Telemetry::grabGlpiInfos();
        $this->assertEquals(40, strlen($result['uuid']));
        $expected['uuid'] = $result['uuid'];
        $expected['plugins'] = $result['plugins'];
        $this->assertSame($expected, $result);

        $plugins = new \Plugin();
        $this->assertGreaterThan(
            0,
            $plugins->add(['directory' => 'testplugin',
                'name'      => 'testplugin',
                'version'   => '0.x.z'
            ])
        );

        $expected['plugins'][] = [
            'key'       => 'testplugin',
            'version'   => '0.x.z'
        ];
        $this->assertSame($expected, \Telemetry::grabGlpiInfos());

        //enable ldap server
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $this->assertTrue(
            $ldap->update([
                'id'        => $ldap->getID(),
                'is_active' => true
            ])
        );

        $expected['usage']['ldap_enabled'] = true;
        $this->assertSame($expected, \Telemetry::grabGlpiInfos());

        $groups = new \Group();
        for ($i = 0; $i < 501; $i++) {
            $this->assertGreaterThan(
                0,
                $groups->add(['name' => 'Tele test'])
            );
        }

        $expected['usage']['avg_groups'] = '500-1000';
        $this->assertSame($expected, \Telemetry::grabGlpiInfos());

        global $CFG_GLPI;
        $CFG_GLPI['use_notifications'] = 1;

        $this->assertSame($expected, \Telemetry::grabGlpiInfos());

        $CFG_GLPI['notifications_mailing'] = 1;
        $CFG_GLPI['notifications_ajax']    = 1;
        $expected['usage']['notifications'] = ['mailing', 'ajax'];
        $this->assertSame($expected, \Telemetry::grabGlpiInfos());

        $collector = new \MailCollector();
        $this->assertGreaterThan(
            0,
            $collector->add([
                'name'        => 'Collector1',
                'is_active'   => 1
            ])
        );

        $expected['usage']['mailcollector_enabled'] = true;
        $this->assertSame($expected, \Telemetry::grabGlpiInfos());

        $this->assertTrue(
            $collector->update([
                'id'        => $collector->getID(),
                'is_active' => false
            ])
        );

        $expected['usage']['mailcollector_enabled'] = false;
        $this->assertSame($expected, \Telemetry::grabGlpiInfos());
    }

    public function testGrabDbInfos()
    {
        global $DB;

        $dbinfos = $DB->getInfo();

        $expected = [
            'engine'    => $dbinfos['Server Software'],
            'version'   => $dbinfos['Server Version'],
            'size'      => '',
            'log_size'  => '',
            'sql_mode'  => $dbinfos['Server SQL Mode']
        ];
        $infos = \Telemetry::grabDbInfos();
        $this->assertNotEmpty($infos['size']);
        $expected['size'] = $infos['size'];
        $this->assertSame($expected, $infos);
    }

    public function testGrabPhpInfos()
    {
        $expected = [
            'version'   => str_replace(PHP_EXTRA_VERSION, '', PHP_VERSION),
            'modules'   => get_loaded_extensions(),
            'setup'     => [
                'max_execution_time'    => ini_get('max_execution_time'),
                'memory_limit'          => ini_get('memory_limit'),
                'post_max_size'         => ini_get('post_max_size'),
                'safe_mode'             => ini_get('safe_mode'),
                'session'               => ini_get('session.save_handler'),
                'upload_max_filesize'   => ini_get('upload_max_filesize')
            ]
        ];

        $this->assertSame($expected, \Telemetry::grabPhpInfos());
    }

    public function testGrabOsInfos()
    {
        $osinfos = \Telemetry::grabOsInfos();
        $this->assertArrayHasKey('family', $osinfos);
        $this->assertArrayHasKey('distribution', $osinfos);
        $this->assertArrayHasKey('version', $osinfos);
    }
}
