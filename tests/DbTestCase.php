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

// Generic test classe, to be extended for CommonDBTM Object

class DbTestCase extends \GLPITestCase
{
    public function beforeTestMethod($method)
    {
        global $DB;
        $DB->beginTransaction();
        parent::beforeTestMethod($method);
    }

    public function afterTestMethod($method)
    {
        global $DB;
        $DB->rollback();
        parent::afterTestMethod($method);
    }


    /**
     * Connect (using the test user per default)
     *
     * @param string $user_name User name (defaults to TU_USER)
     * @param string $user_pass user password (defaults to TU_PASS)
     * @param bool $noauto disable autologin (from CAS by example)
     * @param bool $expected bool result expected from login return
     *
     * @return \Auth
     */
    protected function login(
        string $user_name = TU_USER,
        string $user_pass = TU_PASS,
        bool $noauto = true,
        bool $expected = true
    ): \Auth {
        \Session::destroy();
        \Session::start();

        $auth = new Auth();
        $this->boolean($auth->login($user_name, $user_pass, $noauto))->isEqualTo($expected);

        return $auth;
    }

    /**
     * Log out current user
     *
     * @return void
     */
    protected function logOut()
    {
        \Session::destroy();
        \Session::start();
    }

    /**
     * Generic method to test if an added object is corretly inserted
     *
     * @param  CommonDBTM $object The object to test
     * @param  int        $id     The id of added object
     * @param  array      $input  the input used for add object (optionnal)
     *
     * @return void
     */
    protected function checkInput(CommonDBTM $object, $id = 0, $input = [])
    {
        $this->integer($id)->isGreaterThan($object instanceof Entity ? -1 : 0);
        $this->boolean($object->getFromDB($id))->isTrue();
        $this->variable($object->fields['id'])->isEqualTo($id);

        if (count($input)) {
            foreach ($input as $k => $v) {
                $obj_var = var_export($object->fields[$k], true);
                $input_var = var_export($v, true);
                $this->variable($object->fields[$k])->isEqualTo(
                    $v,
                    "
                '$k' key current value '{$obj_var}' (" . gettype($object->fields[$k]) . ")
                is not equal to '$input_var' (" . gettype($v) . ")"
                );
            }
        }
    }

    /**
     * Create an item of the given class
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @param array $input
     * @param array $skip_fields Fields that wont be checked after creation
     *
     * @return T
     */
    protected function createItem($itemtype, $input, $skip_fields = []): CommonDBTM
    {
        $item = new $itemtype();
        $id = $item->add($input);
        $this->integer($id)->isGreaterThan(0);

        // Remove special fields
        $skip_fields[] = 'id';
        $input = array_filter($input, function ($key) use ($skip_fields) {
            return !in_array($key, $skip_fields) && strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->checkInput($item, $id, $input);

        return $item;
    }

    /**
     * Create an item of the given class
     *
     * @param string $itemtype
     * @param array $input
     * @param array $skip_fields Fields that wont be checked after creation
     *
     * @return CommonDBTM The updated item
     */
    protected function updateItem($itemtype, $id, $input, $skip_fields = []): CommonDBTM
    {
        $item = new $itemtype();
        $input['id'] = $id;
        $success = $item->update($input);
        $this->boolean($success)->isTrue();

        // Remove special fields
        $input = array_filter($input, function ($key) use ($skip_fields) {
            return !in_array($key, $skip_fields) && strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->checkInput($item, $id, $input);

        return $item;
    }

    /**
     * Create multiples items of the given class
     *
     * @param string $itemtype
     * @param array $inputs
     *
     * @return array created items
     */
    protected function createItems($itemtype, $inputs): array
    {
        $items = [];
        foreach ($inputs as $input) {
            $items[] = $this->createItem($itemtype, $input);
        }

        return $items;
    }

    /**
     * Delete an item of the given class
     *
     * @param string $itemtype
     * @param int $id
     * @param bool $purge
     *
     * @return void
     */
    protected function deleteItem($itemtype, $id, bool $purge = false): void
    {
        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $input['id'] = $id;
        $success = $item->delete($input, $purge);
        $this->boolean($success)->isTrue();
    }
}
