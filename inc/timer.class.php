<?php 
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


/**
 *  Timer class for debug
 */
class Script_Timer {
	//! Timer value
	var $timer=0;


	/**
	 * Constructor
	**/
	function Script_Timer (){
		return true;
	}

	//! Start the Timer
	function Start_Timer (){
		$this->timer=microtime ();

		return true;
	}
	/**
	 * Get the current time of the timer
	 *
	 * @param $decimals number of decimal of the result
	 * @return time past from Start_Timer
	 *
	 */
	function Get_Time ($decimals = 3)
	{
		// $decimals will set the number of decimals you want for your milliseconds.

		// format start time
		$start_time = explode (" ", $this->timer);
		$start_time = $start_time[1] + $start_time[0];
		// get and format end time
		$end_time = explode (" ", microtime ());
		$end_time = $end_time[1] + $end_time[0];

		return formatNumber ($end_time - $start_time, false, $decimals);
	}
}
?>
