<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 


// glpi  LDAP CONFIGURATION OPTIONS

// Backend configuration
class cfgLDAPConnect extends LDAPConnect {

	var $hostname	= "your_host";
	var $port	= "389";
	var $username	= "uid=manager,o=organization,c=tld";
	var $password	= "secret";
}
class cfgLDAPSearch extends LDAPSearch {

	var $searchbase	= "o=organization,c=tld";
}


// Schema configuration
//
// Pay attention to the naming of the classes and what they
// extend. As long as you keep this conventions, everything 
// in your schema should work. For every schema you need a
// Filter-definitaion and a general definition.


// Start posixGroup
class LDAPposixGroupFilter extends LDAPEntries {
	
	// Search for this returns all entries
	var $filter		= "objectclass=posixGroup";
}
class LDAPposixGroup extends LDAPEntry {

	// Build DN for entries with this attribute
	var $build_rdn		= "cn";				
	
	// This is the 2nd part of your DN
	var $base_dn		= "ou=groups,o=organization,c=tld";	

	// Objectclasses for new entries
	var $objectclasses	= array("top",
					"posixGroup");		

	// Required Attributes
	var $attributes_req	= array("cn",
					"gidnumber");

	// Allowed Attributes
	var $attributes_allow	= array("memberuid",
					"description");
}
// End posixGroup


// Start posixAccount 
class LDAPposixAccountFilter extends LDAPEntries {

	// Search for this returns all entries
	var $filter		= "objectclass=posixAccount";
}
class LDAPposixAccount extends LDAPEntry {

	// Build DN for entries with this attribute
	var $build_rdn		= "uid";
	
	// This is the 2nd part of your DN
	var $base_dn		= "ou=accounts,o=organization,c=tld";
	
	// Objectclasses for new entries
	var $objectclasses	= array("top",
					"account",
					"posixAccount",
					"shadowAccount");	
	
	// Required Attributes
	var $attributes_req	= array("objectclass",
					"uid",
					"uidnumber",
					"gidnumber",
					"homedirectory",
					"gecos",
					"loginshell",
					"userpassword");

	// Allowed Attributes
	var $attributes_allow	= array("seealso",
					"cn",
					"shadowLastChange",
					"shadowMin",
					"shadowMax",
					"shadowWarning",
					"shadowInactive",
					"shadowExpire",
					"shadowFlag",
					"description");
}
// End posixAccount


// Start qmailUser
class LDAPqmailUserFilter extends LDAPEntries {
	
	// Search for this returns all entries
	var $filter		= "objectclass=qmailUser";
}
class LDAPqmailUser extends LDAPEntry {

	// Build DN for entries with this attribute
	var $build_rdn		= "uid";

	// This is the 2nd part of your DN
	var $base_dn		= "ou=mailusers,o=organization,c=tld";
	
	// Objectclasses for new entries
	var $objectclasses	= array("top",
					"qmailUser");
	
	// Required Attributes
	var $attributes_req	= array("objectclass",
					"uid",
					"mail");

	// Allowed Attributes
	var $attributes_allow	= array("mailmessagestore",
					"homedirectory",
					"userpassword",
					"mailalternateaddress",
					"mailquota",
					"mailhost",
					"mailforwardingaddress",
					"deliveryprogrampath",
					"qmaildotmode",
					"deliverymode",
					"mailreplytext",
					"cn",
					"sn",
					"mailgroup",
					"accountstatus");

	// Special configuration for attributes
	// default -> input field, multivalues possible by adding again
	// multiline -> textarea
	// binary -> file upload, not supported yet
	var $attribute_types	= array("mailreplytext"=>"multiline");
}
// End qmailUser


// END OF CONFIGURATION
?>
