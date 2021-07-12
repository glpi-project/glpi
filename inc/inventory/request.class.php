<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Inventory;

use Glpi\Agent\Communication\AbstractRequest;
use Glpi\Agent\Communication\Headers\Common;
use Unmanaged;

/**
 * Handle inventory request
 * Both XML (legacy) and JSON inventory formats are supported.
 *
 * @see https://github.com/glpi-project/inventory_format/blob/master/inventory.schema.json
 */
class Request extends AbstractRequest
{

   /** @var Inventory */
   private $inventory;


   protected function initHeaders(): Common {
      return new Common();
   }


    /**
     * Handle Query
     *
     * @param string $query   Query mode (one of self::*_QUERY)
     * @param mixed  $content Contents, optionnal
     *
     * @return boolean
     */
   protected function handleQuery($query, $content = null) :bool {
      switch ($query) {
         case self::CONTACT_ACTION:
            $this->contact($content);
            break;
         case self::PROLOG_QUERY:
            $this->prolog();
            break;
         case self::INVENT_QUERY:
         case self::INVENT_ACTION:
         case self::SNMP_QUERY:
         case self::OLD_SNMP_QUERY:
            $this->inventory($content);
            break;
         default:
            $this->addError("Query '$query' is not supported.", 400);
            return false;
      }
       return true;
   }

   /**
    * Handle agent prolog request
    *
    * @return void
    */
   public function prolog() {
      if ($this->headers->hasHeader('GLPI-Agent-ID')) {
          $this->setMode(self::JSON_MODE);
          $response = [
              'expiration'  => self::DEFAULT_FREQUENCY,
              'status'     => 'ok'
          ];
      } else {
         $response = [
            'PROLOG_FREQ'  => self::DEFAULT_FREQUENCY,
            'RESPONSE'     => 'SEND'
         ];
      }
       $this->addToResponse($response);
   }

    /**
     * Handle agent CONTACT request
     */
   public function contact($data) {
      $this->inventory = new Inventory();
      $this->inventory->contact($data);

      $response = [
         'expiration'  => self::DEFAULT_FREQUENCY,
         'status'     => 'ok'
      ];
      $this->addToResponse($response);
   }

   /**
    * Handle agent inventory request
    *
    * @param array $data Inventory input following specs
    *
    * @return void
    */
   public function inventory($data) {
      $this->inventory = new Inventory();
      $this->inventory->setData($data, $this->getMode());
      $this->inventory->doInventory($this->test_rules);

      if ($this->inventory->inError()) {
         foreach ($this->inventory->getErrors() as $error) {
            $this->addError($error, 500);
         }
      } else {
         if ($this->headers->hasHeader('GLPI-Agent-ID')) {
            $response = [
               'expiration'  => self::DEFAULT_FREQUENCY,
               'status'     => 'ok'
            ];
         } else {
            $response = ['RESPONSE' => 'SEND'];
         }

         $this->addToResponse($response);
      }
   }

   /**
    * Get inventory request status
    *
    * @return array
    */
   public function getInventoryStatus(): array {
      $items = $this->inventory->getItems();
      $status = [
         'metadata' => $this->inventory->getMetadata(),
         'items'    => $items
      ];

      if (count($items) == 1) {
         $item = $items[0];
         $status += [
            'itemtype' => $item->getType(),
            'items_id' => $item->fields['id']
         ];
      } else if (count($items)) {
         // Defines 'itemtype' only if all items has same type
         $itemtype = null;
         foreach ($items as $item) {
            if ($itemtype === null && $item->getType() != Unmanaged::class) {
               $itemtype = $item->getType();
            } else if ($itemtype !== $item->getType()) {
               $itemtype = false;
               break;
            }
         }
         if ($itemtype) {
            $status['itemtype'] = $itemtype;
         }
      }

      return $status;
   }

   public function getInventory(): Inventory {
      return $this->inventory;
   }
}
