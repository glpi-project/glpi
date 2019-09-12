<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0.
 */
class ImpactItem extends CommonDBTM {

   public static function findForItem(CommonDBTM $item) {
      global $DB;

      $it = $DB->request([
         'SELECT' => [
            'glpi_impactitems.id',
         ],
         'FROM' => self::getTable(),
         'WHERE'  => [
            'glpi_impactitems.itemtype' => get_class($item),
            'glpi_impactitems.items_id' => $item->getID(),
         ]
      ]);

      $res = $it->next();

      if (!$res) {
         return false;
      }

      $impactItem = new self();
      $impactItem->getFromDB($res['id']);

      return $impactItem;
   }
}
