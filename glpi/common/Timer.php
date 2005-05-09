<?php 
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

class Script_Timer {
	var $timer=0;
  function Script_Timer ()
  {
    return true;
  }

  function Start_Timer ()
  {
    $this->timer=microtime ();

    return true;
  }

  function Get_Time ($decimals = 3)
  {
    // $decimals will set the number of decimals you want for your milliseconds.

    // format start time
    $start_time = explode (" ", $this->timer);
    $start_time = $start_time[1] + $start_time[0];
    // get and format end time
    $end_time = explode (" ", microtime ());
    $end_time = $end_time[1] + $end_time[0];

    return number_format ($end_time - $start_time, $decimals);
  }
}
?>