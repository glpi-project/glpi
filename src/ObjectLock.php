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

use Glpi\Application\View\TemplateRenderer;

/**
 * ObjectLock is dedicated to manage real-time locking of items in GLPI.
 *
 * Item locks are used to lock items like Ticket, Computer, Reminder, etc.
 *
 * @author Olivier Moron
 * @since 9.1
 * @see $CFG_GLPI['lock_lockable_objects']
 **/
class ObjectLock extends CommonDBTM
{
    private static $shutdownregistered = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Object Lock', 'Object Locks', $nb);
    }

    /**
     * @inheritDoc
     * @return integer Always 0 (Root entity)
     **/
    public function getEntityID()
    {
        return 0;
    }

    /**
     * @return array Array of lockable objects 'itemtype' => 'plural itemtype'
     * @used-by templates/pages/setup/general/general_setup.html.twig
     **/
    public static function getLockableObjects()
    {
        global $CFG_GLPI;

        $ret = [];
        foreach ($CFG_GLPI['lock_lockable_objects'] as $lo) {
            $ret[$lo] = $lo::getTypeName(Session::getPluralNumber());
        }
        asort($ret, SORT_STRING);
        return $ret;
    }

    /**
     * Checks if autolock is enabled and if the object is not yet locked.
     * In that case, the item should be viewed in readonly mode with the option to lock it for editing.
     *
     * @return bool
     **/
    private function isAutolockReadonlyMode()
    {
        if (isset($_POST['lockwrite'])) {
            // Edit mode is requested
            $_SESSION['glpilock_autolock_items'][$this->fields['itemtype']][$this->fields['items_id']] = 1;
        }

        $ret = isset($_SESSION['glpilock_autolock_items'][$this->fields['itemtype']][$this->fields['items_id']])
             || (int) $_SESSION['glpilock_autolock_mode'] === 1;
        $locked = $this->getLockedObjectInfo($this->fields['itemtype'], $this->fields['items_id']);
        return !$ret && !$locked;
    }

    /**
     * Tries to lock object and if yes output code to auto unlock it when leaving browser page.
     * If lock can't be set (i.e.: someone has already locked it), LockedBy message is shown accordingly,
     * and read-only profile is set
     * @return bool True if locked
     **/
    private function lockObject()
    {
        global $CFG_GLPI;

        $ret = false;
        $new_lock = false;
        $showAskUnlock = false;
        $user_data = [
            'name' => null,
            'comment' => null,
        ];
        $autolock = $this->isAutolockReadonlyMode();

        $user = new User();
        if (isset($this->fields['users_id']) && $this->fields['users_id'] > 0 && $user->getFromDB($this->fields['users_id'])) {
            $user_data = [
                'name' => $user->getName(),
                'comment' => $user->getInfoCard(),
            ];
            // should get locking user info
            $useremail = new UserEmail();
            $showAskUnlock = $useremail->getFromDBByCrit([
                'users_id' => $this->fields['users_id'],
                'is_default' => 1,
            ]) && ($CFG_GLPI['notifications_mailing'] == 1);
        }

        if (!$autolock) {
            if (
                !($gotIt = $this->getFromDBByCrit([
                    'itemtype' => $this->fields['itemtype'],
                    'items_id' => $this->fields['items_id'],
                ]))
                && $id = $this->add(['itemtype' => $this->fields['itemtype'],
                    'items_id' => $this->fields['items_id'],
                    'users_id' => Session::getLoginUserID(),
                ])
            ) {
                $new_lock = true;
                $ret = true;
            } else { // can't add a lock as another one is already existing
                if (!$gotIt) {
                    $this->getFromDBByCrit([
                        'itemtype' => $this->fields['itemtype'],
                        'items_id' => $this->fields['items_id'],
                    ]);
                }
                // open the object as read-only as it is already locked by someone
                self::setReadonlyProfile();
                // and if autolock was set for this item then unset it
                unset($_SESSION['glpilock_autolock_items'][$this->fields['itemtype']][$this->fields['items_id']]);
            }
        }

        TemplateRenderer::getInstance()->display('layout/parts/objectlock_message.html.twig', [
            'new_lock' => $new_lock,
            'item' => $this,
            'user_data' => $user_data,
            'show_ask_unlock' => $showAskUnlock,
            'autolock_readmode' => $autolock,
        ]);
        return $ret;
    }

    /**
     * @return bool True if item is locked. If the object is locked, the fields of this {@link ObjectLock} are replaced with the data from the DB.
     **/
    private function getLockedObjectInfo(string $itemtype, int $items_id)
    {
        global $CFG_GLPI;

        $ret = false;
        if (
            $CFG_GLPI["lock_use_lock_item"]
            && ($CFG_GLPI["lock_lockprofile_id"] > 0)
            && Session::getCurrentInterface() === 'central'
            && in_array($this->fields['itemtype'], $CFG_GLPI['lock_item_list'], true)
            && $this->getFromDBByCrit([
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ])
        ) {
            $ret = true;
        }
        return $ret;
    }

    /**
     * @param string $itemtype
     * @param integer $items_id
     *
     * @return false|ObjectLock returns ObjectLock if locked, else false
     **/
    public static function isLocked($itemtype, $items_id)
    {
        $ol = new self();
        return ($ol->getLockedObjectInfo($itemtype, $items_id) ? $ol : false);
    }

    /**
     * Switches current profile with read-only profile
     * Registers a shutdown function to be sure that even in case of a fatal error,
     * the switch back will be done: to ensure correct reset of normal profile
     **/
    public static function setReadOnlyProfile()
    {
        global $CFG_GLPI;

        // to prevent double set ReadOnlyProfile
        if (!isset($_SESSION['glpilocksavedprofile']) && isset($CFG_GLPI['lock_lockprofile'])) {
            if (!self::$shutdownregistered) {
                // this is a security in case of a fatal error that can prevent correct revert of profile
                register_shutdown_function([self::class,  'revertProfile']);
                self::$shutdownregistered = true;
            }
            $_SESSION['glpilocksavedprofile'] = $_SESSION['glpiactiveprofile'];
            $_SESSION['glpiactiveprofile']    = $CFG_GLPI['lock_lockprofile'];

            // this mask is mandatory to prevent read of information
            // that are not permitted to view by active profile
            $rights = ProfileRight::getAllPossibleRights();
            foreach ($rights as $key => $val) {
                if (isset($_SESSION['glpilocksavedprofile'][$key])) {
                    $_SESSION['glpiactiveprofile'][$key]
                    = (int) $_SESSION['glpilocksavedprofile'][$key]
                    & (isset($CFG_GLPI['lock_lockprofile'][$key])
                         ? (int) $CFG_GLPI['lock_lockprofile'][$key] : 0);
                }
            }
            // don't forget entities
            $_SESSION['glpiactiveprofile']['entities'] = $_SESSION['glpilocksavedprofile']['entities'];
        }
    }

    /**
     * Will revert normal user profile
     **/
    public static function revertProfile()
    {
        if (isset($_SESSION['glpilocksavedprofile'])) {
            $_SESSION['glpiactiveprofile'] = $_SESSION['glpilocksavedprofile'];
            unset($_SESSION['glpilocksavedprofile']);
        }
    }

    /**
     * Is the main function to be called in order to lock an item
     *
     * @param  string $itemtype
     * @param  array $options
     **/
    public static function manageObjectLock($itemtype, &$options)
    {
        global $CFG_GLPI;

        if (isset($options['id']) && ($options['id'] > 0)) {
            $ol       = new self();
            $ol->fields['itemtype'] = $itemtype;
            $ol->fields['items_id'] = $options['id'];
            $template = isset($options['withtemplate']) && ($options['withtemplate'] > 0);
            if (
                (Session::getCurrentInterface() === "central")
                && isset($CFG_GLPI["lock_use_lock_item"]) && $CFG_GLPI["lock_use_lock_item"]
                && ($CFG_GLPI["lock_lockprofile_id"] > 0)
                && in_array($itemtype, $CFG_GLPI['lock_item_list'], true)
                && Session::haveRightsOr($itemtype::$rightname, [UPDATE, DELETE, PURGE, UPDATENOTE])
                && !$template
            ) {
                if (!$ol->lockObject()) {
                    $options['locked'] = 1;
                }
            }
        }
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        foreach ($ids as $items_id) {
            $itemtype = get_class($item);
            $lo       = new self();
            if ($lo->getLockedObjectInfo($itemtype, $items_id)) {
                $lo->deleteFromDB();
                Log::history($items_id, $itemtype, [0, '', ''], 0, Log::HISTORY_UNLOCK_ITEM);
                $ma->itemDone($itemtype, $items_id, MassiveAction::ACTION_OK);
            }
        }
    }

    public static function rawSearchOptionsToAdd($itemtype)
    {
        global $CFG_GLPI;
        $tab = [];

        if (
            (Session::getCurrentInterface() === "central")
            && isset($CFG_GLPI["lock_use_lock_item"]) && $CFG_GLPI["lock_use_lock_item"]
            && ($CFG_GLPI["lock_lockprofile_id"] > 0)
            && in_array($itemtype, $CFG_GLPI['lock_item_list'], true)
        ) {
            $tab[] = [
                'id'            => '207',
                'table'         => 'glpi_users',
                'field'         => 'name',
                'datatype'      => 'dropdown',
                'right'         => 'all',
                'name'          => __('Locked by'),
                'forcegroupby'  => true,
                'massiveaction' => false,
                'joinparams'    => [
                    'jointype'   => '',
                    'beforejoin' => [
                        'table'      => self::getTable(),
                        'joinparams' => ['jointype' => "itemtype_item"],
                    ],
                ],
            ];

            $tab[] = [
                'id'            => '208',
                'table'         => self::getTable(),
                'field'         => 'date',
                'datatype'      => 'datetime',
                'name'          => __('Locked date'),
                'joinparams'    => ['jointype' => 'itemtype_item'],
                'massiveaction' => false,
                'forcegroupby'  => true,
            ];

            $tab[] = [
                'id'            => '209',
                'table'         => self::getTable(),
                'field'         => 'id',
                'datatype'      => 'specific',
                'name'          => __('Lock status'),
                'joinparams'    => ['jointype' => 'itemtype_item'],
                'massiveaction' => false,
                'forcegroupby'  => true,
                'additionalfields' => ['date', 'users_id'],
            ];
        }

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'id':
                $templateContent = <<<TWIG
                    {% set color = is_locked ? 'bg-red-lt' : 'bg-green-lt' %}
                    {% set icon = is_locked ? 'ti-lock' : 'ti-lock-open' %}
                    {% set text = is_locked ? locked_label : free_label %}
                    {% set tooltip = is_locked ? locked_by_label|format(user_name, date) : text %}

                    <span class="badge {{ color }}" data-bs-toggle="tooltip" title="{{ tooltip }}">
                        <i class="ti {{ icon }}"></i>
                        {{ text }}
                    </span>
TWIG;

                return TemplateRenderer::getInstance()->renderFromStringTemplate($templateContent, [
                    'is_locked'       => $values['id'] > 0,
                    'user_name'       => getUserName($values['users_id']),
                    'date'            => $values['date'],
                    'locked_label'    => __('Locked'),
                    'free_label'      => __('Free'),
                    'locked_by_label' => __('Locked by %s at %s'),
                ]);
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * @param  string $itemtype
     * @param  string $interface
     *
     * @return array empty array if itemtype is not lockable; else returns UNLOCK right
     **/
    public static function getRightsToAdd($itemtype, $interface = 'central')
    {
        global $CFG_GLPI;

        $ret = [];
        if (
            ($interface === "central")
            && isset($CFG_GLPI["lock_use_lock_item"]) && $CFG_GLPI["lock_use_lock_item"]
            && ($CFG_GLPI["lock_lockprofile_id"] > 0)
            && in_array($itemtype, $CFG_GLPI['lock_lockable_objects'], true)
        ) {
            $ret = [UNLOCK  => __('Unlock')];
        }
        return $ret;
    }

    /**
     * Give cron information
     *
     * @param $name Task's name
     *
     * @return array of information
     **/
    public static function cronInfo($name)
    {
        switch ($name) {
            case 'unlockobject':
                return [
                    'description' => __('Unlock forgotten locked objects'),
                    'parameter'   => __('Timeout to force unlock (hours)'),
                ];
        }
        return [];
    }

    /**
     * Cron for unlocking forgotten locks
     *
     * @param CronTask $task Crontask object
     *
     * @return integer  >0: done. -1: error, 0: nothing to do
     * @used-by CronTask
     **/
    public static function cronUnlockObject($task)
    {
        // here we have to delete old locks
        $actionCode = 0; // by default
        $task->setVolume(0); // start with zero

        $lockedItems = getAllDataFromTable(
            getTableForItemType(self::class),
            [
                'date' => ['<', date("Y-m-d H:i:s", time() - ($task->fields['param'] * HOUR_TIMESTAMP))],
            ]
        );

        foreach ($lockedItems as $row) {
            $ol = new self();
            if ($ol->delete($row)) {
                $actionCode++;
                $item = getItemForItemtype($row['itemtype']);
                $item->getFromDB($row['items_id']);
                $task->log($row['itemtype'] . " #" . $row['items_id'] . ": " . $item->getLink());
                $task->addVolume(1);
                Log::history(
                    $row['items_id'],
                    $row['itemtype'],
                    [0, '', ''],
                    0,
                    Log::HISTORY_UNLOCK_ITEM
                );
            } else {
                return -1;
            }
        }

        return $actionCode;
    }
}
