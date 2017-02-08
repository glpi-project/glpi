<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/session.class.php */

class SessionTest extends PHPUnit\Framework\TestCase {

   /**
    * @covers Session::addMessageAfterRedirect
    * @covers Html::displayMessageAfterRedirect
    */
    public function testAddMessageAfterRedirect() {
      session_start();
      $err_msg = 'Something is broken. Weird.';
      $warn_msg = 'There was a warning. Be carefull.';
      $info_msg = 'All goes well. Or not... Who knows ;)';

      $this->assertEquals(false,isset($_SESSION['MESSAGE_AFTER_REDIRECT']));

      //test add message in cron mode
      $_SESSION['glpicronuserrunning'] = 'phpunit_cron';
      Session::addMessageAfterRedirect($err_msg, false, ERROR);
      //adding a message in "cron mode" does not add anything in the session
      $this->assertEquals(false, isset($_SESSION['MESSAGE_AFTER_REDIRECT']));

      //set not running from cron
      unset($_SESSION['glpicronuserrunning']);

      //test all messages types
      Session::addMessageAfterRedirect($err_msg, false, ERROR);
      Session::addMessageAfterRedirect($warn_msg, false, WARNING);
      Session::addMessageAfterRedirect($info_msg, false, INFO);

      $expected = [
        ERROR   => [$err_msg],
        WARNING => [$warn_msg],
        INFO    => [$info_msg]
      ];
      $this->assertEquals($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

      $this->expectOutputRegex('/' . str_replace('.', '\.', $err_msg)  . '/');
      $this->expectOutputRegex('/' . str_replace('.', '\.', $warn_msg)  . '/');
      $this->expectOutputRegex('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/');
      Html::displayMessageAfterRedirect();

      $this->assertEquals([], $_SESSION['MESSAGE_AFTER_REDIRECT']);

      //test multiple messages of same type
      Session::addMessageAfterRedirect($err_msg, false, ERROR);
      Session::addMessageAfterRedirect($err_msg, false, ERROR);
      Session::addMessageAfterRedirect($err_msg, false, ERROR);

      $expected = [
        ERROR   => [$err_msg, $err_msg, $err_msg]
      ];
      $this->assertEquals($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

      $this->expectOutputRegex('/' . str_replace('.', '\.', $err_msg)  . '/');
      Html::displayMessageAfterRedirect();

      $this->assertEquals([], $_SESSION['MESSAGE_AFTER_REDIRECT']);

      //test message deduplication
      $err_msg_bis = $err_msg . ' not the same';
      Session::addMessageAfterRedirect($err_msg, true, ERROR);
      Session::addMessageAfterRedirect($err_msg_bis, true, ERROR);
      Session::addMessageAfterRedirect($err_msg, true, ERROR);
      Session::addMessageAfterRedirect($err_msg, true, ERROR);

      $expected = [
        ERROR   => [$err_msg, $err_msg_bis]
      ];
      $this->assertEquals($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

      $this->expectOutputRegex('/' . str_replace('.', '\.', $err_msg)  . '/');
      $this->expectOutputRegex('/' . str_replace('.', '\.', $err_msg_bis)  . '/');
      Html::displayMessageAfterRedirect();

      $this->assertEquals([], $_SESSION['MESSAGE_AFTER_REDIRECT']);

      //test with reset
      Session::addMessageAfterRedirect($err_msg, false, ERROR);
      Session::addMessageAfterRedirect($warn_msg, false, WARNING);
      Session::addMessageAfterRedirect($info_msg, false, INFO, true);

      $expected = [
         INFO   => [$info_msg]
      ];
      $this->assertEquals($expected, $_SESSION['MESSAGE_AFTER_REDIRECT']);

      $this->expectOutputRegex('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/');
      Html::displayMessageAfterRedirect();

      $this->assertEquals([], $_SESSION['MESSAGE_AFTER_REDIRECT']);
    }
}
