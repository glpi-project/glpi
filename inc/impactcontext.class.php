<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0.
 */
class ImpactContext extends CommonDBTM {

   /**
    * Get ImpactContext for the given ImpactItem
    *
    * @param ImpactItem $item
    * @return ImpactContext|false
    */
   public static function findForImpactItem(\ImpactItem $item) {
      $impactContext = new self();
      $exist = $impactContext->getFromDB($item->fields['impactcontexts_id']);

      return $exist ? $impactContext : false;
   }
}