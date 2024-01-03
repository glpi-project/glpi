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

/* Test for inc/authldapreplicate.class.php */

class AuthLdapReplicate extends DbTestCase
{
    public function testCanCreate()
    {
        $this->login();
        $this->boolean((bool)\AuthLdapReplicate::canCreate())->isTrue();

        $_SESSION['glpiactiveprofile']['config'] = READ;
        $this->boolean((bool)\AuthLdapReplicate::canCreate())->isFalse();

        $_SESSION['glpiactiveprofile']['config'] = 0;
        $this->boolean((bool)\AuthLdapReplicate::canCreate())->isFalse();
    }

    public function testCanPurge()
    {
        $this->login();
        $this->boolean((bool)\AuthLdapReplicate::canPurge())->isTrue();

        $_SESSION['glpiactiveprofile']['config'] = READ;
        $this->boolean((bool)\AuthLdapReplicate::canCreate())->isFalse();

        $_SESSION['glpiactiveprofile']['config'] = 0;
        $this->boolean((bool)\AuthLdapReplicate::canCreate())->isFalse();
    }

    public function testGetForbiddenStandardMassiveAction()
    {
        $this->login();
        $replicate = new \AuthLdapReplicate();
        $result    = $replicate->getForbiddenStandardMassiveAction();
        $this->array($result)->isIdenticalTo([0 => 'update']);
    }

    public function testPrepareInputForAddAndUpdate()
    {
        $replicate = new \AuthLdapReplicate();

        foreach (['prepareInputForAdd', 'prepareInputForUpdate'] as $method) {
           //Do not set a port : no port added
            $result = $replicate->$method([
                'name' => 'test',
                'host' => 'host'
            ]);
            $this->array($result)->nothasKey('port');

           //Port=0, change value to 389
            $result = $replicate->$method([
                'name' => 'test',
                'host' => 'host',
                'port' => 0
            ]);
            $this->integer($result['port'])->isIdenticalTo(389);

           //Port set : do not change it's value
            $result = $replicate->$method([
                'name' => 'test',
                'host' => 'host',
                'port' => 3389
            ]);
            $this->integer($result['port'])->isIdenticalTo(3389);
        }
    }
}
