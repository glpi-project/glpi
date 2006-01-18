<?php

/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/


include ("_relpos.php");

// Default location for based configuration
$cfg_install["config_dir"] = $phproot . "/glpi/config/";

// Default location for backup dump
$cfg_install["dump_dir"] = $phproot . "/backups/dump/";

// Path for documents storage
$cfg_install["doc_dir"] = $phproot . "/docs";


// If this file exists, it is load, allow to set configdir/dumpdir elsewhere
if(file_exists($phproot ."/glpi/config/config_path.php")) {
    include($phproot ."/glpi/config/config_path.php");
}

?>
