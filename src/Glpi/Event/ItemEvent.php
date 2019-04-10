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

namespace Glpi\Event;

use CommonDBTM;
use Symfony\Component\EventDispatcher\Event;

/**
 * @since 10.0.0
 */
class ItemEvent extends Event
{
    /**
     * Name of event trigerred when creating an empty item.
     */
    const ITEM_GET_EMPTY = 'item.get_empty';

    /**
     * Name of event trigerred in item creation process, after item creation in database.
     */
    const ITEM_POST_ADD = 'item.post_add';

    /**
     * Name of event trigerred in item deletion process, after marking item as deleted in database.
     */
    const ITEM_POST_DELETE = 'item.post_delete';

    /**
     * Name of event trigerred in item creation process, after processing of input data.
     */
    const ITEM_POST_PREPARE_ADD = 'item.post_prepare_add';

    /**
     * Name of event trigerred in item purge process, after item deletion from database.
     */
    const ITEM_POST_PURGE = 'item.post_purge';

    /**
     * Name of event trigerred in item restore process, after removing deleted mark in database.
     */
    const ITEM_POST_RESTORE = 'item.post_restore';

    /**
     * Name of event trigerred in item update process, after item update in database.
     */
    const ITEM_POST_UPDATE = 'item.post_update';

    /**
     * Name of event trigerred in item creation process, prior to altering input data.
     */
    const ITEM_PRE_ADD = 'item.pre_add';

    /**
     * Name of event trigerred in item deletion process, prior to marking item as deleted.
     */
    const ITEM_PRE_DELETE = 'item.pre_delete';

    /**
     * Name of event trigerred in item purge process, prior to item deletion from database.
     */
    const ITEM_PRE_PURGE = 'item.pre_purge';

    /**
     * Name of event trigerred in item restore process, prior to removing deleted mark in database.
     */
    const ITEM_PRE_RESTORE = 'item.pre_restore';

    /**
     * Name of event trigerred in item update process, prior to altering input data.
     */
    const ITEM_PRE_UPDATE = 'item.pre_update';

    /**
     * @var CommonDBTM
     */
    private $item;

    /**
     * @param CommonDBTM $item
     */
    public function __construct(CommonDBTM $item)
    {
        $this->item = $item;
    }

    /**
     * Item on which event applies.
     *
     * @return CommonDBTM
     */
    public function getItem(): CommonDBTM
    {
        return $this->item;
    }
}
