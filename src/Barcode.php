<?php

class Barcode extends CommonDropdown
{
    public static function generateQRCode(CommonDBTM $item)
    {
        global $CFG_GLPI;
        $barcode = new \Com\Tecnick\Barcode\Barcode();
        $qrcode = $barcode->getBarcodeObj(
            'QRCODE,H',
            $CFG_GLPI["url_base"] . $item->getLinkURL(),
            -2,
            -2,
            'black',
            array(-2, -2, -2, -2)
        )->setBackgroundColor('white');
        return $qrcode;
    }

    public static function renderQRCode(CommonDBTM $item)
    {
        global $CFG_GLPI;
        $lowercase_array = array_map('strtolower', $CFG_GLPI["asset_types"]);
        if (
            in_array(
                strtolower($item::$rightname),
                $lowercase_array
            )
        ) {
            $qrcode = self::generateQRCode($item);
            return $qrcode->getHtmlDiv();
        }
    }
}
