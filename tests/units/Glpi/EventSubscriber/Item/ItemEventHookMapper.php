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

namespace tests\units\Glpi\EventSubscriber\Item;

/* Test for src/Glpi/EventSubscriber/Item/ItemEventHookMapper.php */

class ItemEventHookMapper extends \GLPITestCase {

   /**
    * Mapping between item events and hooks.
    *
    * @return string[][]
    */
   public function provideMapping() {
      return [
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_GET_EMPTY,
            'hook'  => 'item_empty',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_POST_ADD,
            'hook'  => 'item_add',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_POST_DELETE,
            'hook'  => 'item_delete',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_POST_PREPARE_ADD,
            'hook'  => 'post_prepareadd',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_POST_PURGE,
            'hook'  => 'item_purge',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_POST_RESTORE,
            'hook'  => 'item_restore',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_POST_UPDATE,
            'hook'  => 'item_update',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_PRE_ADD,
            'hook'  => 'pre_item_add',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_PRE_DELETE,
            'hook'  => 'pre_item_delete',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_PRE_PURGE,
            'hook'  => 'pre_item_purge',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_PRE_RESTORE,
            'hook'  => 'pre_item_restore',
         ],
         [
            'event' => \Glpi\Event\ItemEvent::ITEM_PRE_UPDATE,
            'hook'  => 'pre_item_update',
         ],
      ];
   }

   /**
    * Test that hook mapping is correct.
    *
    * @dataProvider provideMapping
    */
   public function testMapping($event, $expectedHook) {
      global $PLUGIN_HOOKS;

      // Force plugin 'test' to be considered as active
      \Plugin::setLoaded('test', 'test');

      $this->newTestedInstance(new \mock\Plugin());

      $dispatcher = new \mock\Glpi\EventDispatcher\EventDispatcher();
      $dispatcher->addSubscriber($this->testedInstance);

      $self = $this;
      $item = new \Computer();
      $callbackCalled = false;
      $callback = function($param) use ($self, $item, &$callbackCalled, $expectedHook) {
         // Retrieve called hook from backtrace.
         $calledHook = '';
         $backtrace = debug_backtrace();
         foreach ($backtrace as $call) {
            if ('doHook' === $call['function'] && 'Plugin' === $call['class']) {
               $calledHook = $call['args'][0];
               break;
            }
         }

         $self->string($calledHook)->isEqualTo($expectedHook);
         $self->object($param)->isEqualTo($item);

         $callbackCalled = true;
      };

      $PLUGIN_HOOKS[$expectedHook]['test'] = ['Computer' => $callback];
      $dispatcher->dispatch($event, new \Glpi\Event\ItemEvent($item));
      $this->boolean($callbackCalled)->isTrue();
   }
}
