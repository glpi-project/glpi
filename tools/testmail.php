<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (isset($_SERVER['argc'])) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it           = explode("=",$_SERVER['argv'][$i],2);
      $it[0]        = preg_replace('/^--/','',$it[0]);
      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}
$NEEDED_ITEMS = array("mailgate", "mailing");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (isset($_GET['from'])) {
   $from = $_GET['from'];
} else {
   $from = $CFG_GLPI['admin_email'];
}

if (isset($_GET['to'])) {
   $dest = $_GET['to'];
} else {
   die("--to option is mandatory\n");
}

if (isset($_GET['enc'])) {
   $enc = $_GET['enc'];
} else {
   // "7bit", "binary", "base64", and "quoted-printable".
   $enc = '';
}

if (isset($_GET['help'])) {
   die("usage php testmail.php  [ --from=email ] --to=email [ --enc=7bit|8bit|binary|base64|quoted-printable ]\n");
}

$dat     = date('r');
$secret  = "l'été, ça roule !";

echo "From : $from\n";
echo "To : $dest\n";
echo "Date : $dat\n";

$mmail = new NotificationMail();
$mmail->From=$from;
$mmail->FromName="GLPI test";
$mmail->isHTML(true);

if ($enc) {
   $mmail->Encoding = $enc;
}

$mmail->Subject="GLPI test mail" . ($enc ? " ($enc)" : '');
$mmail->Body="<html><body><h3>GLPI test mail</h3><p>Encoding = <b>$enc</b></p>".
             "<p>Date = <b>$dat</b></p><p>Secret = <b>$secret</b></p></body></html>";
$mmail->AltBody="GLPI test mail\nEncoding : $enc\nDate : $dat\nSecret=$secret";

$mmail->AddAddress($dest, "");

$logo=file_get_contents("../pics/logo-glpi-login.png");
$mmail->AddStringAttachment($logo,'glpi.png',($enc?$enc:'base64'),'image/png');

$mmail->AddStringAttachment($secret,'secret.txt',($enc?$enc:'base64'),'text/plain');

echo "Send : ". ($mmail->Send() ? "OK\n" : "Failed (". $mmail->ErrorInfo.")\n");
?>
