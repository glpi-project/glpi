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

   public function prepareInputForUpdate($input) {
      $max_depth = $input['max_depth'] ?? 0;

      if (intval($max_depth) <= 0) {
         // If value is not valid, reset to default
         $input['max_depth'] = Impact::DEFAULT_DEPTH;
      } else if ($max_depth >= Impact::MAX_DEPTH && $max_depth != Impact::NO_DEPTH_LIMIT) {
         // Set to no limit if greater than max
         $input['max_depth'] = Impact::NO_DEPTH_LIMIT;
      }

      return $input;
   }
}
