<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Exception\Http\AccessDeniedHttpException;

/**
 * @since 9.1
 */

// here we are going to try to unlock the given object
// url should be of the form: 'http://.../.../unlockobject.php?unlock=1&id=xxxxxx'
// or url should be of the form 'http://.../.../unlockobject.php?requestunlock=1&id=xxxxxx'
// to send notification to locker of object

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$ret = 0;
if (isset($_POST['unlock']) && isset($_POST["id"])) {
    // then we may have something to unlock
    $ol = new ObjectLock();
    if ($ol->getFromDB($_POST["id"])) {
        $can_unlock = $ol->fields['users_id'] === Session::getLoginUserID()
            || Session::haveRight($ol->fields['itemtype']::$rightname, UNLOCK);
        if (!$can_unlock) {
            throw new AccessDeniedHttpException();
        }

        if ($ol->deleteFromDB(true)) {
            Log::history(
                $ol->fields['items_id'],
                $ol->fields['itemtype'],
                [0, '', ''],
                0,
                Log::HISTORY_UNLOCK_ITEM
            );
            $ret = 1;
        }
    }
} elseif (
    isset($_POST['requestunlock'])
           && isset($_POST["id"])
) {
    // the we must ask for unlock
    $ol = new ObjectLock();
    if ($ol->getFromDB($_POST["id"])) {
        NotificationEvent::raiseEvent('unlock', $ol);
        $ret = 1;
    }
} elseif (
    isset($_GET['lockstatus'])
           && isset($_GET["id"])
) {
    $ol = new ObjectLock();
    if ($ol->getFromDB($_GET["id"])) {
        $ret = 1; // found = still locked
    }
}

echo $ret;
