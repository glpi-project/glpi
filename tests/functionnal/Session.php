<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
*/

namespace tests\units;

/* Test for inc/session.class.php */

class Session extends \GLPITestCase {

   public function testAddMessageAfterRedirect() {
      $err_msg = 'Something is broken. Weird.';
      $warn_msg = 'There was a warning. Be carefull.';
      $info_msg = 'All goes well. Or not... Who knows ;)';

      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      //test add message in cron mode
      $_SESSION['glpicronuserrunning'] = 'cron_phpunit';
      \Session::addMessageAfterRedirect($err_msg, false, ERROR);
      //adding a message in "cron mode" does not add anything in the session
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      //set not running from cron
      unset($_SESSION['glpicronuserrunning']);

      //test all messages types
      \Session::addMessageAfterRedirect($err_msg, false, ERROR);
      \Session::addMessageAfterRedirect($warn_msg, false, WARNING);
      \Session::addMessageAfterRedirect($info_msg, false, INFO);

      $expected = [
        ERROR   => [$err_msg],
        WARNING => [$warn_msg],
        INFO    => [$info_msg]
      ];
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

      $this->output(
         function () {
            \Html::displayMessageAfterRedirect();
         }
      )
         ->matches('/' . str_replace('.', '\.', $err_msg)  . '/')
         ->matches('/' . str_replace('.', '\.', $warn_msg)  . '/')
         ->matches('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/');

      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      //test multiple messages of same type
      \Session::addMessageAfterRedirect($err_msg, false, ERROR);
      \Session::addMessageAfterRedirect($err_msg, false, ERROR);
      \Session::addMessageAfterRedirect($err_msg, false, ERROR);

      $expected = [
        ERROR   => [$err_msg, $err_msg, $err_msg]
      ];
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

      $this->output(
         function () {
            \Html::displayMessageAfterRedirect();
         }
      )->matches('/' . str_replace('.', '\.', $err_msg)  . '/');

      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      //test message deduplication
      $err_msg_bis = $err_msg . ' not the same';
      \Session::addMessageAfterRedirect($err_msg, true, ERROR);
      \Session::addMessageAfterRedirect($err_msg_bis, true, ERROR);
      \Session::addMessageAfterRedirect($err_msg, true, ERROR);
      \Session::addMessageAfterRedirect($err_msg, true, ERROR);

      $expected = [
        ERROR   => [$err_msg, $err_msg_bis]
      ];
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

      $this->output(
         function () {
            \Html::displayMessageAfterRedirect();
         }
      )
         ->matches('/' . str_replace('.', '\.', $err_msg)  . '/')
         ->matches('/' . str_replace('.', '\.', $err_msg_bis)  . '/');

      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();

      //test with reset
      \Session::addMessageAfterRedirect($err_msg, false, ERROR);
      \Session::addMessageAfterRedirect($warn_msg, false, WARNING);
      \Session::addMessageAfterRedirect($info_msg, false, INFO, true);

      $expected = [
         INFO   => [$info_msg]
      ];
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isIdenticalTo($expected);

      $this->output(
         function () {
            \Html::displayMessageAfterRedirect();
         }
      )->matches('/' . str_replace(['.', ')'], ['\.', '\)'], $info_msg)  . '/');

      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])->isEmpty();
   }

   public function testLocalI18n() {
      //load locales
      \Session::loadLanguage('en_GB');
      $this->string(__('Login'))->isIdenticalTo('Login');

      //create directory for local i18n
      if (!file_exists(GLPI_LOCAL_I18N_DIR.'/core')) {
         mkdir(GLPI_LOCAL_I18N_DIR.'/core');
      }

      //write local MO file with i18n override
      copy(
         __DIR__ . '/../local_en_GB.mo',
         GLPI_LOCAL_I18N_DIR.'/core/en_GB.mo'
      );
      \Session::loadLanguage('en_GB');

      $this->string(__('Login'))->isIdenticalTo('Login from local gettext');
      $this->string(__('Password'))->isIdenticalTo('Password');

      //write local PHP file with i18n override
      file_put_contents(
         GLPI_LOCAL_I18N_DIR.'/core/en_GB.php',
         "<?php\n\$lang['Login'] = 'Login from local PHP';\n\$lang['Password'] = 'Password from local PHP';\nreturn \$lang;"
      );
      \Session::loadLanguage('en_GB');

      $this->string(__('Login'))->isIdenticalTo('Login from local gettext');
      $this->string(__('Password'))->isIdenticalTo('Password from local PHP');

      //cleanup -- keep at the end
      unlink(GLPI_LOCAL_I18N_DIR.'/core/en_GB.php');
      unlink(GLPI_LOCAL_I18N_DIR.'/core/en_GB.mo');
   }
}
