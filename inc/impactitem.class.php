<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0.
 */
class ImpactItem extends CommonDBTM {

   /**
    * Find ImpactItem for a given CommonDBTM item
    *
    * @param CommonDBTM $item                The given item
    * @param bool       $create_if_missing   Should we create a new ImpactItem
    *                                        if none found ?
    * @return ImpactItem|bool ImpactItem object or false if not found and
    *                         creation is disabled
    */
   public static function findForItem(
      CommonDBTM $item,
      bool $create_if_missing = true
   ) {
      global $DB;

      $it = $DB->request([
         'SELECT' => [
            'glpi_impactitems.id',
         ],
         'FROM' => self::getTable(),
         'WHERE'  => [
            'glpi_impactitems.itemtype' => get_class($item),
            'glpi_impactitems.items_id' => $item->fields['id'],
         ]
      ]);

      $res = $it->next();
      $impact_item = new self();

      if ($res) {
         $id = $res['id'];
      } else if (!$res && $create_if_missing) {
         $id = $impact_item->add([
            'itemtype' => get_class($item),
            'items_id' => $item->fields['id']
         ]);
      } else {
         return false;
      }

      $impact_item->getFromDB($id);
      return $impact_item;
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
