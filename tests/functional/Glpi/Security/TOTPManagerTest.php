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

namespace tests\units\Glpi\Security;

use Glpi\Security\TOTPManager;
use RobThree\Auth\Algorithm;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

class TOTPManagerTest extends \DbTestCase
{
    public function testCreateSecret()
    {
        $tfa = new TOTPManager();
        $this->assertEquals(32, strlen($tfa->createSecret()));
    }

    public function testSetSecretForUser()
    {
        global $DB;

        $tfa = new TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');

        $tfa_config = $DB->request([
            'SELECT' => '2fa',
            'FROM' => 'glpi_users',
            'WHERE' => ['id' => $users_id],
        ])->current()['2fa'];

        $this->assertNotNull($tfa_config);
        $tfa_config = json_decode($tfa_config, true);
        $this->assertEquals('G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB', (new \GLPIKey())->decrypt($tfa_config['secret']));
        $this->assertEquals('totp', $tfa_config['algorithm']);
        $this->assertEquals($tfa::CODE_ALGORITHM[0], $tfa_config['digest']);
        $this->assertEquals($tfa::CODE_VALIDITY_SECONDS, $tfa_config['period']);
        $this->assertEquals($tfa::CODE_LENGTH_DIGITS, $tfa_config['digits']);
    }

    public function testDisable2FAForUser()
    {
        global $DB;

        $tfa = new TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');

        $tfa_config = $DB->request([
            'SELECT' => '2fa',
            'FROM' => 'glpi_users',
            'WHERE' => ['id' => $users_id],
        ])->current()['2fa'];

        $this->assertNotNull($tfa_config);

        $tfa->disable2FAForUser($users_id);

        $tfa_config = $DB->request([
            'SELECT' => '2fa',
            'FROM' => 'glpi_users',
            'WHERE' => ['id' => $users_id],
        ])->current()['2fa'];
        $this->assertNull($tfa_config);
    }

    public function testIs2FAEnabled()
    {
        global $DB;

        $tfa = new TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $DB->update('glpi_users', [
            '2fa' => json_encode([
                'secret' => (new \GLPIKey())->encrypt('G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB'),
            ]),
        ], ['id' => $users_id]);
        $this->assertTrue($tfa->is2FAEnabled($users_id));

        $DB->update('glpi_users', ['2fa' => json_encode([])], ['id' => $users_id]);
        $this->assertFalse($tfa->is2FAEnabled($users_id));

        $DB->update('glpi_users', ['2fa' => null], ['id' => $users_id]);
        $this->assertFalse($tfa->is2FAEnabled($users_id));
    }

    public function testVerifyCode()
    {
        $tfa = new TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');

        $tfa_internal = new TwoFactorAuth(
            new BaconQrCodeProvider(),
            '',
            $tfa::CODE_LENGTH_DIGITS,
            $tfa::CODE_VALIDITY_SECONDS,
            Algorithm::Sha1,
        );

        $code = $tfa_internal->getCode('G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');
        $this->assertTrue($tfa->verifyCodeForUser($code, $users_id));
        $this->assertNotFalse($tfa->verifyCodeForSecret($code, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB'));
    }

    public function testRegenerateBackupCodes()
    {
        $tfa = new TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');
        $codes = $tfa->regenerateBackupCodes($users_id);
        $this->assertCount($tfa::BACKUP_CODES_COUNT, $codes);
        foreach ($codes as $code) {
            $this->assertEquals($tfa::BACKUP_CODES_LENGTH, strlen($code));
        }
    }

    public function testVerifyBackupCodeForUser()
    {
        $tfa = new TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');
        $codes = $tfa->regenerateBackupCodes($users_id);
        foreach ($codes as $code) {
            // Verify but don't invalidate the code
            $this->assertTrue($tfa->verifyBackupCodeForUser($code, $users_id, false));
            // Verify and invalidate the code (default behavior)
            $this->assertTrue($tfa->verifyBackupCodeForUser($code, $users_id));
            // Third time should fail
            $this->assertFalse($tfa->verifyBackupCodeForUser($code, $users_id));
        }
    }

    public function testGet2FAEnforcement()
    {
        global $CFG_GLPI, $DB;

        $tfa = new TOTPManager();
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $users_id = getItemByTypeName('User', TU_USER, true);

        $CFG_GLPI['2fa_enforced'] = 1;
        $this->assertEquals($tfa::ENFORCEMENT_MANDATORY, $tfa->get2FAEnforcement($users_id));
        $DB->update('glpi_users', ['2fa_unenforced' => 1], ['id' => $users_id]);
        $this->assertEquals($tfa::ENFORCEMENT_OPTIONAL, $tfa->get2FAEnforcement($users_id));
        $DB->update('glpi_users', ['2fa_unenforced' => 0], ['id' => $users_id]);

        $CFG_GLPI['2fa_enforced'] = 1;
        $CFG_GLPI['2fa_grace_date_start'] = date('Y-m-d H:i:s', strtotime('-1 day'));
        $CFG_GLPI['2fa_grace_days'] = 3;
        $this->assertEquals($tfa::ENFORCEMENT_MANDATORY_GRACE_PERIOD, $tfa->get2FAEnforcement($users_id));

        $CFG_GLPI['2fa_grace_date_start'] = date('Y-m-d H:i:s', strtotime('-5 day'));
        $CFG_GLPI['2fa_grace_days'] = 2;
        $this->assertEquals($tfa::ENFORCEMENT_MANDATORY, $tfa->get2FAEnforcement($users_id));

        $CFG_GLPI['2fa_enforced'] = 0;
        $CFG_GLPI['2fa_grace_days'] = 0;
        $DB->update('glpi_entities', ['2fa_enforcement_strategy' => 1], ['id' => $entities_id]);
        $this->assertEquals($tfa::ENFORCEMENT_MANDATORY, $tfa->get2FAEnforcement($users_id));
        $DB->update('glpi_entities', ['2fa_enforcement_strategy' => 0], ['id' => $entities_id]);

        $DB->update('glpi_profiles', ['2fa_enforced' => 1], ['id' => 4]);
        $this->assertEquals($tfa::ENFORCEMENT_MANDATORY, $tfa->get2FAEnforcement($users_id));
        $DB->update('glpi_profiles', ['2fa_enforced' => 0], ['id' => 4]);

        $group = new \Group();
        $groups_id = $group->add([
            'name' => __FUNCTION__,
            'entities_id' => $entities_id,
        ]);
        $this->assertGreaterThan(0, $groups_id);
        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            $group_user->add([
                'groups_id' => $groups_id,
                'users_id' => $users_id,
            ])
        );

        $DB->update('glpi_groups', ['2fa_enforced' => 1], ['id' => $groups_id]);
        $this->assertEquals($tfa::ENFORCEMENT_MANDATORY, $tfa->get2FAEnforcement($users_id));
    }

    public function testGetGracePeriodDaysLeft()
    {
        global $CFG_GLPI;

        $tfa = new TOTPManager();

        $CFG_GLPI['2fa_grace_date_start'] = date('Y-m-d H:i:s', strtotime('-1 day'));
        $CFG_GLPI['2fa_grace_days'] = 2;
        $this->assertEquals(0, $tfa->getGracePeriodDaysLeft());

        $CFG_GLPI['2fa_grace_days'] = 3;
        $this->assertEquals(1, $tfa->getGracePeriodDaysLeft());

        $CFG_GLPI['2fa_grace_days'] = 1;
        $this->assertEquals(0, $tfa->getGracePeriodDaysLeft());

        $CFG_GLPI['2fa_grace_days'] = 0;
        $this->assertEquals(0, $tfa->getGracePeriodDaysLeft());
    }

    public function testGet2faIssuer()
    {
        global $CFG_GLPI;

        $tfa = new TOTPManager();

        // No custom suffix
        $this->assertEquals('GLPI', $tfa->getIssuer());

        $CFG_GLPI['2fa_suffix'] = 'test';
        $this->assertEquals('GLPI (test)', $tfa->getIssuer());
    }
}
