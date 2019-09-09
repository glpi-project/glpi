<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0.
 */
class ImpactItem extends CommonDBTM {

   function prepareInputForUpdate($input) {
      return $input;
   }

   // public function update(array $input, $options = [], $history = true) {
   //    global $DB;

   //    // Find id from itemtype and items_id
   //    $it = $DB->request([
   //       'FROM'   => 'glpi_impactitems',
   //       'WHERE'  => [
   //          'itemtype'   => $input['itemtype'],
   //          'items_id'   => $input['items_id'],
   //       ]
   //    ]);

   //    if (count($it) !== 1) {
   //       return false;
   //    }

   //    $input['id'] = $it->next()['id'];
   //    return parent::update($input, $options, $history);
   // }

   // public function delete(array $input, $options = [], $history = true) {
   //    global $DB;

   //    // Find id from itemtype and items_id
   //    $it = $DB->request([
   //       'FROM'   => 'glpi_impactitems',
   //       'WHERE'  => [
   //          'itemtype'   => $input['itemtype'],
   //          'items_id'   => $input['items_id'],
   //       ]
   //    ]);

   //    if (count($it) !== 1) {
   //       return false;
   //    }

   //    $input['id'] = $it->next()['id'];
   //    return parent::delete($input, $options, $history);
   // }

   public static function findForItem(CommonDBTM $item) {
      global $DB;

      $it = $DB->request([
         'SELECT' => [
            'glpi_impactitems.id',
         ],
         'FROM' => 'glpi_impactitems',
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
