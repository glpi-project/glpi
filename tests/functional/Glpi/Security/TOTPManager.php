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

namespace tests\units\Glpi\Security;

use RobThree\Auth\Algorithm;
use RobThree\Auth\TwoFactorAuth;

class TOTPManager extends \DbTestCase
{
    public function testCreateSecret()
    {
        $tfa = new \Glpi\Security\TOTPManager();
        $this->string($tfa->createSecret())->hasLength(32);
    }

    public function testSetSecretForUser()
    {
        global $DB;

        $tfa = new \Glpi\Security\TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');

        $tfa_config = $DB->request([
            'SELECT' => '2fa',
            'FROM' => 'glpi_users',
            'WHERE' => ['id' => $users_id]
        ])->current()['2fa'];

        $this->variable($tfa_config)->isNotNull();
        $tfa_config = json_decode($tfa_config, true);
        $this->string((new \GLPIKey())->decrypt($tfa_config['secret']))->isEqualTo('G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');
        $this->string($tfa_config['algorithm'])->isEqualTo('totp');
        $this->string($tfa_config['digest'])->isEqualTo($tfa::CODE_ALGORITHM[0]);
        $this->integer($tfa_config['period'])->isEqualTo($tfa::CODE_VALIDITY_SECONDS);
        $this->integer($tfa_config['digits'])->isEqualTo($tfa::CODE_LENGTH_DIGITS);
    }

    public function testDisable2FAForUser()
    {
        global $DB;

        $tfa = new \Glpi\Security\TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');

        $tfa_config = $DB->request([
            'SELECT' => '2fa',
            'FROM' => 'glpi_users',
            'WHERE' => ['id' => $users_id]
        ])->current()['2fa'];

        $this->variable($tfa_config)->isNotNull();

        $tfa->disable2FAForUser($users_id);

        $tfa_config = $DB->request([
            'SELECT' => '2fa',
            'FROM' => 'glpi_users',
            'WHERE' => ['id' => $users_id]
        ])->current()['2fa'];
        $this->variable($tfa_config)->isNull();
    }

    public function testIs2FAEnabled()
    {
        global $DB;

        $tfa = new \Glpi\Security\TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $DB->update('glpi_users', [
            '2fa' => json_encode([
                'secret' => (new \GLPIKey())->encrypt('G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB')
            ])
        ], ['id' => $users_id]);
        $this->boolean($tfa->is2FAEnabled($users_id))->isTrue();

        $DB->update('glpi_users', ['2fa' => json_encode([])], ['id' => $users_id]);
        $this->boolean($tfa->is2FAEnabled($users_id))->isFalse();

        $DB->update('glpi_users', ['2fa' => null], ['id' => $users_id]);
        $this->boolean($tfa->is2FAEnabled($users_id))->isFalse();
    }

    public function testVerifyCode()
    {
        $tfa = new \Glpi\Security\TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');

        $tfa_internal = new TwoFactorAuth(
            '',
            $tfa::CODE_LENGTH_DIGITS,
            $tfa::CODE_VALIDITY_SECONDS,
            Algorithm::Sha1,
        );

        $code = $tfa_internal->getCode('G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');
        $this->boolean($tfa->verifyCodeForUser($code, $users_id))->isTrue();
        $this->variable($tfa->verifyCodeForSecret($code, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB'))->isNotFalse();
    }

    public function testRegenerateBackupCodes()
    {
        $tfa = new \Glpi\Security\TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');
        $codes = $tfa->regenerateBackupCodes($users_id);
        $this->array($codes)->hasSize($tfa::BACKUP_CODES_COUNT);
        foreach ($codes as $code) {
            $this->string($code)->hasLength($tfa::BACKUP_CODES_LENGTH);
        }
    }

    public function testVerifyBackupCodeForUser()
    {
        $tfa = new \Glpi\Security\TOTPManager();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $tfa->setSecretForUser($users_id, 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');
        $codes = $tfa->regenerateBackupCodes($users_id);
        foreach ($codes as $code) {
            // Verify but don't invalidate the code
            $this->boolean($tfa->verifyBackupCodeForUser($code, $users_id, false))->isTrue();
            // Verify and invalidate the code (default behavior)
            $this->boolean($tfa->verifyBackupCodeForUser($code, $users_id))->isTrue();
            // Third time should fail
            $this->boolean($tfa->verifyBackupCodeForUser($code, $users_id))->isFalse();
        }
    }

    public function testGet2FAEnforcement()
    {
        global $CFG_GLPI, $DB;

        $tfa = new \Glpi\Security\TOTPManager();
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $users_id = getItemByTypeName('User', TU_USER, true);

        $CFG_GLPI['2fa_enforced'] = 1;
        $this->integer($tfa->get2FAEnforcement($users_id))->isEqualTo($tfa::ENFORCEMENT_MANDATORY);
        $DB->update('glpi_users', ['2fa_unenforced' => 1], ['id' => $users_id]);
        $this->integer($tfa->get2FAEnforcement($users_id))->isEqualTo($tfa::ENFORCEMENT_OPTIONAL);
        $DB->update('glpi_users', ['2fa_unenforced' => 0], ['id' => $users_id]);

        $CFG_GLPI['2fa_enforced'] = 1;
        $CFG_GLPI['2fa_grace_date_start'] = date('Y-m-d H:i:s', strtotime('-1 day'));
        $CFG_GLPI['2fa_grace_days'] = 3;
        $this->integer($tfa->get2FAEnforcement($users_id))->isEqualTo($tfa::ENFORCEMENT_MANDATORY_GRACE_PERIOD);

        $CFG_GLPI['2fa_grace_date_start'] = date('Y-m-d H:i:s', strtotime('-5 day'));
        $CFG_GLPI['2fa_grace_days'] = 2;
        $this->integer($tfa->get2FAEnforcement($users_id))->isEqualTo($tfa::ENFORCEMENT_MANDATORY);

        $CFG_GLPI['2fa_enforced'] = 0;
        $CFG_GLPI['2fa_grace_days'] = 0;
        $DB->update('glpi_entities', ['2fa_enforcement_strategy' => 1], ['id' => $entities_id]);
        $this->integer($tfa->get2FAEnforcement($users_id))->isEqualTo($tfa::ENFORCEMENT_MANDATORY);
        $DB->update('glpi_entities', ['2fa_enforcement_strategy' => 0], ['id' => $entities_id]);

        $DB->update('glpi_profiles', ['2fa_enforced' => 1], ['id' => 4]);
        $this->integer($tfa->get2FAEnforcement($users_id))->isEqualTo($tfa::ENFORCEMENT_MANDATORY);
        $DB->update('glpi_profiles', ['2fa_enforced' => 0], ['id' => 4]);

        $group = new \Group();
        $groups_id = $group->add([
            'name' => __FUNCTION__,
            'entities_id' => $entities_id,
        ]);
        $this->integer($groups_id)->isGreaterThan(0);
        $group_user = new \Group_User();
        $this->integer($group_user->add([
            'groups_id' => $groups_id,
            'users_id' => $users_id,
        ]))->isGreaterThan(0);

        $DB->update('glpi_groups', ['2fa_enforced' => 1], ['id' => $groups_id]);
        $this->integer($tfa->get2FAEnforcement($users_id))->isEqualTo($tfa::ENFORCEMENT_MANDATORY);
    }

    public function testGetGracePeriodDaysLeft()
    {
        global $CFG_GLPI;

        $tfa = new \Glpi\Security\TOTPManager();

        $CFG_GLPI['2fa_grace_date_start'] = date('Y-m-d H:i:s', strtotime('-1 day'));
        $CFG_GLPI['2fa_grace_days'] = 2;
        $this->integer($tfa->getGracePeriodDaysLeft())->isEqualTo(0);

        $CFG_GLPI['2fa_grace_days'] = 3;
        $this->integer($tfa->getGracePeriodDaysLeft())->isEqualTo(1);

        $CFG_GLPI['2fa_grace_days'] = 1;
        $this->integer($tfa->getGracePeriodDaysLeft())->isEqualTo(0);

        $CFG_GLPI['2fa_grace_days'] = 0;
        $this->integer($tfa->getGracePeriodDaysLeft())->isEqualTo(0);
    }
}
