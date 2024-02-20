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

/* Test for inc/savedsearch_user.class.php */

class SavedSearch_User extends DbTestCase
{
    public function testGetDefault()
    {
       // needs a user
       // let's use TU_USER
        $this->login();
        $uid =  getItemByTypeName('User', TU_USER, true);

       // with no default bookmark
        $this->boolean(
            (bool)\SavedSearch_User::getDefault($uid, 'Ticket')
        )->isFalse();

       // now add a bookmark on Ticket view
        $bk = new \SavedSearch();
        $this->boolean(
            (bool)$bk->add(['name'         => 'All my tickets',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $uid,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'         => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $uid
            ])
        )->isTrue();

        $bk_id = $bk->fields['id'];

        $bk_user = new \SavedSearch_User();
        $this->boolean(
            (bool)$bk_user->add(['users_id' => $uid,
                'itemtype' => 'Ticket',
                'savedsearches_id' => $bk_id
            ])
        )->isTrue();

       // should get a default bookmark
        $bk = \SavedSearch_User::getDefault($uid, 'Ticket');
        $this->array(
            $bk
        )->isEqualTo(['itemtype'         => 'Ticket',
            'sort'             => '2',
            'order'            => 'DESC',
            'savedsearches_id' => $bk_id,
            'criteria'         => [0 => ['field' => '5',
                'searchtype' => 'equals',
                'value' => $uid
            ]
            ],
            'reset'            => 'reset',
        ]);
    }
}
