<?php

class Item_Ola extends CommonDBRelation
{
    public static $itemtype_1 = 'itemtype'; // Only Ticket at the moment
    public static $items_id_1 = 'items_id';

    public static $itemtype_2 = OLA::class;
    public static $items_id_2 = 'olas_id';

    //    public static $rightname = 'device'; // @todoseb
    //      public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;  // @todoseb voir implications
    //      public static $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;  // @todoseb voir implications

    //    public static $mustBeAttached_1 = true; // @todoseb voir implications
    //    public static $mustBeAttached_2 = true; // @todoseb voir implications

    /**
     * Prepare the input for add
     *
     * add start_time and due_time values.
     * @param $input
     * @return array|false
     */
    public function prepareInputForAdd($input)
    {
        // @todoseb attention filter si TTO ou TTR ?
        if (in_array(['due_time', 'start_time'], array_keys($input))) {
            throw new \RuntimeException('due_time and start_time are not allowed in the input. Values are computed.');
        }

        // get the related ola (cannot use getConnexityItem() ou getOnePeer() because it is not in the database yet)
        $_ola = new OLA();
        if (!$_ola->getFromDB($input[static::$items_id_2])) {
            throw new \RuntimeException('OLA not found #' . $input[static::$items_id_2]);
        }

        return parent::prepareInputForAdd([
            'due_time' => $_ola->computeDate($_SESSION['glpi_currenttime']),
            'start_time' => $_SESSION['glpi_currenttime'],
        ] + $input);
    }
}
