<?php

class Barcode extends CommonDropdown
{
    public static function generateQRCode(CommonDBTM $item)
    {
        $barcode = new \Com\Tecnick\Barcode\Barcode();
        $qrcode = $barcode->getBarcodeObj(
            'QRCODE,H',
            $_SERVER['SERVER_NAME'] . $item->getLinkURL(),
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
        $lowercaseArray = array_map('strtolower', $CFG_GLPI["asset_types"]);
        if (
            in_array(
                strtolower($item::$rightname),
                $lowercaseArray
            )
        ) {
            $qrcode = self::generateQRCode($item);
            return $qrcode->getHtmlDiv();
        }
    }
}
