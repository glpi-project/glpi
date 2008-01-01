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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class TrackingBusinessRuleCollection extends RuleCollection {

	function TrackingBusinessRuleCollection() {
		$this->rule_type = RULE_TRACKING_AUTO_ACTION;
		$this->rule_class_name="TrackingBusinessRule";
		$this->right="rule_tracking";
		$this->use_output_rule_process_as_next_input=true;
	}

	function getTitle() {
		global $LANG;
		return $LANG["rulesengine"][28];
	}

}

class TrackingBusinessRule extends Rule {

	function TrackingBusinessRule() {
		$this->table = "glpi_rules_descriptions";
		$this->type = -1;
		$this->rule_type = RULE_TRACKING_AUTO_ACTION;
		$this->right="rule_tracking";
		$this->can_sort=true;		

	}

	function maxActionsCount(){
		global $RULES_ACTIONS;
		return count($RULES_ACTIONS[RULE_TRACKING_AUTO_ACTION]);
	}
}



?>
