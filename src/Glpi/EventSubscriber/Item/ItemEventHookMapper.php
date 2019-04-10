<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
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

namespace Glpi\EventSubscriber\Item;

use Glpi\Event\ItemEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin;

class ItemEventHookMapper implements EventSubscriberInterface
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public static function getSubscribedEvents()
    {
        return [
            ItemEvent::ITEM_GET_EMPTY        => 'doItemEmptyHook',
            ItemEvent::ITEM_POST_ADD         => 'doItemAddHook',
            ItemEvent::ITEM_POST_DELETE      => 'doItemDeleteHook',
            ItemEvent::ITEM_POST_PREPARE_ADD => 'doPostPrepareaddHook',
            ItemEvent::ITEM_POST_PURGE       => 'doItemPurgeHook',
            ItemEvent::ITEM_POST_RESTORE     => 'doItemRestoreHook',
            ItemEvent::ITEM_POST_UPDATE      => 'doItemUpdateHook',
            ItemEvent::ITEM_PRE_ADD          => 'doPreItemAddHook',
            ItemEvent::ITEM_PRE_DELETE       => 'doPreItemDeleteHook',
            ItemEvent::ITEM_PRE_PURGE        => 'doPreItemPurgeHook',
            ItemEvent::ITEM_PRE_RESTORE      => 'doPreItemRestoreHook',
            ItemEvent::ITEM_PRE_UPDATE       => 'doPreItemUpdateHook',
        ];
    }

    /**
     * Call 'item_add' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doItemAddHook(ItemEvent $event)
    {
        $this->plugin->doHook('item_add', $event->getItem());
    }

    /**
     * Call 'item_delete' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doItemDeleteHook(ItemEvent $event)
    {
        $this->plugin->doHook('item_delete', $event->getItem());
    }

    /**
     * Call 'item_empty' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doItemEmptyHook(ItemEvent $event)
    {
        $this->plugin->doHook('item_empty', $event->getItem());
    }

    /**
     * Call 'item_purge' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doItemPurgeHook(ItemEvent $event)
    {
        $this->plugin->doHook('item_purge', $event->getItem());
    }

    /**
     * Call 'item_restore' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doItemRestoreHook(ItemEvent $event)
    {
        $this->plugin->doHook('item_restore', $event->getItem());
    }

    /**
     * Call 'item_update' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doItemUpdateHook(ItemEvent $event)
    {
        $this->plugin->doHook('item_update', $event->getItem());
    }

    /**
     * Call 'post_prepareadd' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doPostPrepareaddHook(ItemEvent $event)
    {
        $this->plugin->doHook('post_prepareadd', $event->getItem());
    }

    /**
     * Call 'pre_item_add' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doPreItemAddHook(ItemEvent $event)
    {
        $this->plugin->doHook('pre_item_add', $event->getItem());
    }

    /**
     * Call 'pre_item_delete' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doPreItemDeleteHook(ItemEvent $event)
    {
        $this->plugin->doHook('pre_item_delete', $event->getItem());
    }

    /**
     * Call 'pre_item_purge' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doPreItemPurgeHook(ItemEvent $event)
    {
        $this->plugin->doHook('pre_item_purge', $event->getItem());
    }

    /**
     * Call 'pre_item_restore' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doPreItemRestoreHook(ItemEvent $event)
    {
        $this->plugin->doHook('pre_item_restore', $event->getItem());
    }

    /**
     * Call 'pre_item_update' hook.
     *
     * @param ItemEvent $event
     *
     * @return void
     */
    public function doPreItemUpdateHook(ItemEvent $event)
    {
        $this->plugin->doHook('pre_item_update', $event->getItem());
    }
}
