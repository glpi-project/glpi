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

namespace tests\units\Glpi\Cache;

use Glpi\Cache\CacheManager;

/* Test for inc/cache/simplecache.class.php */

class SimpleCache extends \GLPITestCase
{
    /**
     * Test all possible cache operations.
     */
    public function testOperationsOnCache()
    {

        $cache_manager = new CacheManager();
        $instance = $cache_manager->getCoreCacheInstance();

       // Different scalar types to test.
        $values = [
            'null'         => null,
            'string'       => 'some value',
            'true'         => true,
            'false'        => false,
            'negative int' => -10,
            'positive int' => 15,
            'zero'         => 0,
            'float'        => 15.358,
            'simple array' => ['a', 'b', 'c'],
            'assoc array'  => ['some' => 'value', 'from' => 'assoc', 'array' => null],
            '{}()/\@:'     => 'reserved chars in key',
        ];

       // Test single set/get/has/delete
        foreach ($values as $key => $value) {
           // Not yet existing
            $this->boolean($instance->has($key))->isFalse();

           // Can be set if not existing
            $this->boolean($instance->set($key, $value))->isTrue();

           // Is existing after being set
            $this->boolean($instance->has($key))->isTrue();

           // Cached value is equal to value that was set
            $this->variable($instance->get($key))->isEqualTo($value);

           // Overwriting an existing value works
            $rand = mt_rand();
            $this->boolean($instance->set($key, $rand))->isTrue();
            $this->variable($instance->get($key))->isEqualTo($rand);

           // Can delete a value
            $this->boolean($instance->delete($key))->isTrue();
        }

       // Test multiple set/get
        $instance->setMultiple($values);
        foreach ($values as $key => $value) {
           // Cached value exists and is equal to value that was set
            $this->boolean($instance->has($key))->isTrue();
            $this->variable($instance->get($key))->isEqualTo($value);
        }

       // Test only on partial result to be sure that "*Multiple" methods acts only on targetted elements
        $some_keys = array_rand($values, 4);
        $some_values = array_intersect_key($values, array_fill_keys($some_keys, null));

        $this->array($instance->getMultiple($some_keys))->isEqualTo($some_values);

        $instance->deleteMultiple($some_keys);
        foreach ($some_keys as $key) {
           // Cached value should not exists as it has been deleted
            $this->boolean($instance->has($key))->isFalse();
        }

       // Test global clear
        $instance->clear();
        foreach (array_keys($values) as $key) {
           // Cached value should not exists as it has been deleted
            $this->boolean($instance->has($key))->isFalse();
        }
    }
}
