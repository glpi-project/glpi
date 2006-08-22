<?php
/*
	* @version $Id: HEADER 3576 2006-06-12 08:40:44Z moyo $
	-------------------------------------------------------------------------
	GLPI - Gestionnaire Libre de Parc Informatique
	Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

function testMail(){
	global $cfg_glpi,$lang;
	$mmail=new glpi_phpmailer();
	$mmail->From=$cfg_glpi["admin_email"];
	$mmail->FromName=$cfg_glpi["admin_email"];
	$mmail->AddAddress($cfg_glpi["admin_email"], "GLPI");
	$mmail->Subject="[GLPI] ".$lang["mailing"][32];  
	$mmail->Body=$lang["mailing"][31]."\n-- \n".$cfg_glpi["mailing_signature"];

	if(!$mmail->Send()){
		$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["setup"][206];
	} else $_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["setup"][205];
}

	/**
	* Determine if email is valid
	* @param $email email to check
	* @return boolean 
	*/
function isValidEmail($email="")
	{
		if( !eregi( "^" .
			"[a-zA-Z0-9]+([_\\.-][a-zA-Z0-9]+)*" .    //user
            "@" .
            "([a-zA-Z0-9]+([\.-][a-zA-Z0-9]+)*)+" .   //domain
            "\\.[a-zA-Z0-9]{2,}" .                    //sld, tld 
            "$", $email)
                        )
        {
        //echo "Erreur: '$email' n'est pas une adresse mail valide!<br>";
        return false;
        }
		else return true;
	}

?>