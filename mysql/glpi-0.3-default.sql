# ----------------------------------------------------------------------
# GLPI - Gestionnaire libre de parc informatique
# Copyright (C) 2002 by the INDEPNET Development Team.
# Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
# http://indepnet.net/   http://glpi.indepnet.org
# ----------------------------------------------------------------------
# Based on:
#IRMA, Information Resource-Management and Administration
#Christian Bauer, turin@incubus.de
#
# ----------------------------------------------------------------------
# LICENSE
#
#This file is part of GLPI.
#
#    GLPI is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; either version 2 of the License, or
#    (at your option) any later version.
#
#    GLPI is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with GLPI; if not, write to the Free Software
#    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ----------------------------------------------------------------------
###################### Base de données: GLPI ##########################
#########################################################################

# --------------------------------------------------------

#
# Structure de la table `glpi_computers`
#

DROP TABLE IF EXISTS `glpi_computers`;
CREATE TABLE `glpi_computers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `type` varchar(100) NOT NULL default '',
  `flags_server` tinyint(4) NOT NULL default '0',
  `os` varchar(100) NOT NULL default '',
  `osver` varchar(20) NOT NULL default '',
  `processor` varchar(30) NOT NULL default '',
  `processor_speed` varchar(30) NOT NULL default '',
  `location` varchar(200) NOT NULL default '',
  `serial` varchar(200) NOT NULL default '',
  `otherserial` varchar(200) NOT NULL default '',
  `ramtype` varchar(200) NOT NULL default '',
  `ram` varchar(6) NOT NULL default '',
  `network` varchar(200) NOT NULL default '',
  `hdspace` varchar(6) NOT NULL default '',
  `contact` varchar(90) NOT NULL default '',
  `contact_num` varchar(90) NOT NULL default '',
  `comments` text NOT NULL,
  `date_mod` datetime default NULL,
  `gfxcard` varchar(255) NOT NULL default '',
  `moboard` varchar(255) NOT NULL default '',
  `sndcard` varchar(255) NOT NULL default '',
  `hdtype` varchar(255) NOT NULL default '',
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`),
  KEY `location` (`location`),
  KEY `flags` (`flags_server`)
) TYPE=MyISAM AUTO_INCREMENT=24 ;

#
# Contenu de la table `glpi_computers`
#

INSERT INTO `glpi_computers` VALUES (23, '', 'Generic x86 PC', 0, 'Debian woody 3.0', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', '', '2004-07-12 17:52:31', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'integrated', 'IBM DCAS 34330', '2004-07-22', '2004-07-28', 0);
INSERT INTO `glpi_computers` VALUES (19, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:12', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO `glpi_computers` VALUES (20, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:14', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO `glpi_computers` VALUES (21, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:16', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO `glpi_computers` VALUES (22, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:17', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `glpi_config`
#

DROP TABLE IF EXISTS `glpi_config`;
CREATE TABLE `glpi_config` (
  `config_id` int(11) NOT NULL auto_increment,
  `num_of_events` varchar(200) NOT NULL default '',
  `jobs_at_login` varchar(200) NOT NULL default '',
  `sendexpire` varchar(200) NOT NULL default '',
  `cut` varchar(200) NOT NULL default '',
  `expire_events` varchar(200) NOT NULL default '',
  `list_limit` varchar(200) NOT NULL default '',
  `version` varchar(200) NOT NULL default '',
  `logotxt` varchar(200) NOT NULL default '',
  `root_doc` varchar(200) NOT NULL default '',
  `event_loglevel` varchar(200) NOT NULL default '',
  `mailing` varchar(200) NOT NULL default '',
  `imap_auth_server` varchar(200) NOT NULL default '',
  `imap_host` varchar(200) NOT NULL default '',
  `ldap_host` varchar(200) NOT NULL default '',
  `ldap_basedn` varchar(200) NOT NULL default '',
  `ldap_rootdn` varchar(200) NOT NULL default '',
  `ldap_pass` varchar(200) NOT NULL default '',
  `admin_email` varchar(200) NOT NULL default '',
  `mailing_signature` varchar(200) NOT NULL default '',
  `mailing_new_admin` varchar(200) NOT NULL default '',
  `mailing_attrib_admin` varchar(200) NOT NULL default '',
  `mailing_followup_admin` varchar(200) NOT NULL default '',
  `mailing_finish_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_admin` varchar(200) NOT NULL default '',
  `mailing_attrib_all_admin` varchar(200) NOT NULL default '',
  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_normal` varchar(200) NOT NULL default '',
  `mailing_attrib_all_normal` varchar(200) NOT NULL default '',
  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
  `mailing_attrib_attrib` varchar(200) NOT NULL default '',
  `mailing_followup_attrib` varchar(200) NOT NULL default '',
  `mailing_finish_attrib` varchar(200) NOT NULL default '',
  `mailing_new_user` varchar(200) NOT NULL default '',
  `mailing_attrib_user` varchar(200) NOT NULL default '',
  `mailing_followup_user` varchar(200) NOT NULL default '',
  `mailing_finish_user` varchar(200) NOT NULL default '',
  `ldap_field_name` varchar(200) NOT NULL default '',
  `ldap_field_email` varchar(200) NOT NULL default '',
  `ldap_field_location` varchar(200) NOT NULL default '',
  `ldap_field_realname` varchar(200) NOT NULL default '',
  `ldap_field_phone` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`config_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Contenu de la table `glpi_config`
#

INSERT INTO `glpi_config` VALUES (1, '10', '10', '1', '80', '30', '15', ' 0.31', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', '', '', '', '', 'admin@xxxxxxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber');

# --------------------------------------------------------

#
# Structure de la table `glpi_connect_wire`
#

DROP TABLE IF EXISTS `glpi_connect_wire`;
CREATE TABLE `glpi_connect_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `glpi_connect_wire`
#


# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_gfxcard`
#

DROP TABLE IF EXISTS `glpi_dropdown_gfxcard`;
CREATE TABLE `glpi_dropdown_gfxcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_gfxcard`
#

INSERT INTO `glpi_dropdown_gfxcard` VALUES ('ATI Rage Pro 3D AGP');
INSERT INTO `glpi_dropdown_gfxcard` VALUES ('Matrox Millenium G400DH');
INSERT INTO `glpi_dropdown_gfxcard` VALUES ('S3 Trio 64V+');
INSERT INTO `glpi_dropdown_gfxcard` VALUES ('integrated');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_hdtype`
#

DROP TABLE IF EXISTS `glpi_dropdown_hdtype`;
CREATE TABLE `glpi_dropdown_hdtype` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_hdtype`
#

INSERT INTO `glpi_dropdown_hdtype` VALUES ('IBM DTTA 35101');
INSERT INTO `glpi_dropdown_hdtype` VALUES ('IBM DCAS 34330');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_iface`
#

DROP TABLE IF EXISTS `glpi_dropdown_iface`;
CREATE TABLE `glpi_dropdown_iface` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_iface`
#

INSERT INTO `glpi_dropdown_iface` VALUES ('10Mbps Ethernet (UTP)');
INSERT INTO `glpi_dropdown_iface` VALUES ('100Mbps Ethernet (UTP)');
INSERT INTO `glpi_dropdown_iface` VALUES ('100Base FL');
INSERT INTO `glpi_dropdown_iface` VALUES ('100Mbps FDDI');
INSERT INTO `glpi_dropdown_iface` VALUES ('Frame Relay');
INSERT INTO `glpi_dropdown_iface` VALUES ('ISDN');
INSERT INTO `glpi_dropdown_iface` VALUES ('T1/E1 +');
INSERT INTO `glpi_dropdown_iface` VALUES ('Serial Link');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_locations`
#

DROP TABLE IF EXISTS `glpi_dropdown_locations`;
CREATE TABLE `glpi_dropdown_locations` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_locations`
#

INSERT INTO `glpi_dropdown_locations` VALUES ('1 ier etage');
INSERT INTO `glpi_dropdown_locations` VALUES ('2nd etage');
INSERT INTO `glpi_dropdown_locations` VALUES ('1er etage');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_moboard`
#

DROP TABLE IF EXISTS `glpi_dropdown_moboard`;
CREATE TABLE `glpi_dropdown_moboard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_moboard`
#

INSERT INTO `glpi_dropdown_moboard` VALUES ('Asus T2P4S');
INSERT INTO `glpi_dropdown_moboard` VALUES ('Asus P2BX');
INSERT INTO `glpi_dropdown_moboard` VALUES ('unknown');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_network`
#

DROP TABLE IF EXISTS `glpi_dropdown_network`;
CREATE TABLE `glpi_dropdown_network` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_network`
#

INSERT INTO `glpi_dropdown_network` VALUES ('3Com (100Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES ('3Com (10Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES ('Intel (100Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES ('Intel (10Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES ('Generic 100Mbps Card');
INSERT INTO `glpi_dropdown_network` VALUES ('Generic 10Mbps Card');
INSERT INTO `glpi_dropdown_network` VALUES ('None');
INSERT INTO `glpi_dropdown_network` VALUES ('AMD 10Mbps');
INSERT INTO `glpi_dropdown_network` VALUES ('Realtek 10Mbps');
INSERT INTO `glpi_dropdown_network` VALUES ('Realtek 100Mbps');
INSERT INTO `glpi_dropdown_network` VALUES ('integrated');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_os`
#

DROP TABLE IF EXISTS `glpi_dropdown_os`;
CREATE TABLE `glpi_dropdown_os` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_os`
#

INSERT INTO `glpi_dropdown_os` VALUES ('Windows 2000');
INSERT INTO `glpi_dropdown_os` VALUES ('Linux (Redhat 6.2)');
INSERT INTO `glpi_dropdown_os` VALUES ('Linux (Slackware 7)');
INSERT INTO `glpi_dropdown_os` VALUES ('Solaris');
INSERT INTO `glpi_dropdown_os` VALUES ('Windows NT 4.0');
INSERT INTO `glpi_dropdown_os` VALUES ('Windows 95a');
INSERT INTO `glpi_dropdown_os` VALUES ('Other');
INSERT INTO `glpi_dropdown_os` VALUES ('Windows 98');
INSERT INTO `glpi_dropdown_os` VALUES ('MacOS');
INSERT INTO `glpi_dropdown_os` VALUES ('Windows 95 OSR2');
INSERT INTO `glpi_dropdown_os` VALUES ('Windows 98 SR2');
INSERT INTO `glpi_dropdown_os` VALUES ('Debian woody 3.0');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_processor`
#

DROP TABLE IF EXISTS `glpi_dropdown_processor`;
CREATE TABLE `glpi_dropdown_processor` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_processor`
#

INSERT INTO `glpi_dropdown_processor` VALUES ('Intel Pentium');
INSERT INTO `glpi_dropdown_processor` VALUES ('Intel Pentium II');
INSERT INTO `glpi_dropdown_processor` VALUES ('AMD K6-1');
INSERT INTO `glpi_dropdown_processor` VALUES ('AMD K6-2');
INSERT INTO `glpi_dropdown_processor` VALUES ('AMD K6-3');
INSERT INTO `glpi_dropdown_processor` VALUES ('PowerPC G3');
INSERT INTO `glpi_dropdown_processor` VALUES ('Intel Pentium III');
INSERT INTO `glpi_dropdown_processor` VALUES ('AMD Athlon');
INSERT INTO `glpi_dropdown_processor` VALUES ('68k (Motorola)');
INSERT INTO `glpi_dropdown_processor` VALUES ('486 SX');
INSERT INTO `glpi_dropdown_processor` VALUES ('486 DX');
INSERT INTO `glpi_dropdown_processor` VALUES ('486 DX2/4');
INSERT INTO `glpi_dropdown_processor` VALUES ('Intel Itanium');
INSERT INTO `glpi_dropdown_processor` VALUES ('PowerPC G4');
INSERT INTO `glpi_dropdown_processor` VALUES ('RS3000');
INSERT INTO `glpi_dropdown_processor` VALUES ('RS10k');
INSERT INTO `glpi_dropdown_processor` VALUES ('Alpha EV6.7');
INSERT INTO `glpi_dropdown_processor` VALUES ('PowerPC 603ev');
INSERT INTO `glpi_dropdown_processor` VALUES ('PowerPC 603');
INSERT INTO `glpi_dropdown_processor` VALUES ('PowerPC 601');
INSERT INTO `glpi_dropdown_processor` VALUES ('68040');
INSERT INTO `glpi_dropdown_processor` VALUES ('68040');
INSERT INTO `glpi_dropdown_processor` VALUES ('ULTRASparc II');
INSERT INTO `glpi_dropdown_processor` VALUES ('Intel Pentium IV');
INSERT INTO `glpi_dropdown_processor` VALUES ('AMD Athlon');
INSERT INTO `glpi_dropdown_processor` VALUES ('AMD Duron');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_ram`
#

DROP TABLE IF EXISTS `glpi_dropdown_ram`;
CREATE TABLE `glpi_dropdown_ram` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_ram`
#

INSERT INTO `glpi_dropdown_ram` VALUES ('36pin SIMMS');
INSERT INTO `glpi_dropdown_ram` VALUES ('72pin SIMMS (Fast Page)');
INSERT INTO `glpi_dropdown_ram` VALUES ('72pin SIMMS (EDO)');
INSERT INTO `glpi_dropdown_ram` VALUES ('Unbuffered DIMMs');
INSERT INTO `glpi_dropdown_ram` VALUES ('DIMMs w/EEPROM');
INSERT INTO `glpi_dropdown_ram` VALUES ('SDRAM DIMMs (<10ns)');
INSERT INTO `glpi_dropdown_ram` VALUES ('ECC DIMMs');
INSERT INTO `glpi_dropdown_ram` VALUES ('Other');
INSERT INTO `glpi_dropdown_ram` VALUES ('iMac DIMMS');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_sndcard`
#

DROP TABLE IF EXISTS `glpi_dropdown_sndcard`;
CREATE TABLE `glpi_dropdown_sndcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `glpi_dropdown_sndcard`
#

INSERT INTO `glpi_dropdown_sndcard` VALUES ('Soundblaster 128 PCI');
INSERT INTO `glpi_dropdown_sndcard` VALUES ('Soundblaster 16 PnP');
INSERT INTO `glpi_dropdown_sndcard` VALUES ('integrated');

# --------------------------------------------------------

#
# Structure de la table `glpi_event_log`
#

DROP TABLE IF EXISTS `glpi_event_log`;
CREATE TABLE `glpi_event_log` (
  `ID` int(11) NOT NULL auto_increment,
  `item` int(11) NOT NULL default '0',
  `itemtype` varchar(10) NOT NULL default '',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `service` varchar(20) default NULL,
  `level` tinyint(4) NOT NULL default '0',
  `message` text NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY `comp` (`item`),
  KEY `date` (`date`)
) TYPE=MyISAM AUTO_INCREMENT=395 ;

#
# Contenu de la table `glpi_event_log`
#

INSERT INTO `glpi_event_log` VALUES (363, -1, 'system', '2004-07-11 16:08:11', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (364, 8, 'computers', '2004-07-11 16:08:19', 'inventory', 4, 'glpiupdated item.');
INSERT INTO `glpi_event_log` VALUES (365, 8, 'computers', '2004-07-11 16:08:21', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `glpi_event_log` VALUES (366, 8, 'computers', '2004-07-11 16:12:17', 'inventory', 4, 'glpiupdated item.');
INSERT INTO `glpi_event_log` VALUES (367, 18, 'computers', '2004-07-11 16:12:25', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `glpi_event_log` VALUES (368, -1, 'system', '2004-07-11 16:15:36', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (369, 15, 'computers', '2004-07-11 16:15:47', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `glpi_event_log` VALUES (370, -1, 'system', '2004-07-11 16:19:55', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (371, 10, 'computers', '2004-07-11 16:20:02', 'inventory', 4, 'glpi deleted item.');
INSERT INTO `glpi_event_log` VALUES (372, 0, 'computers', '2004-07-11 16:20:12', 'inventory', 4, 'glpi added .');
INSERT INTO `glpi_event_log` VALUES (373, 0, 'computers', '2004-07-11 16:20:14', 'inventory', 4, 'glpi added .');
INSERT INTO `glpi_event_log` VALUES (374, 0, 'computers', '2004-07-11 16:20:16', 'inventory', 4, 'glpi added .');
INSERT INTO `glpi_event_log` VALUES (375, 0, 'computers', '2004-07-11 16:20:17', 'inventory', 4, 'glpi added .');
INSERT INTO `glpi_event_log` VALUES (376, 19, 'computers', '2004-07-11 16:20:29', 'tracking', 4, 'glpi added new job.');
INSERT INTO `glpi_event_log` VALUES (377, 19, 'computers', '2004-07-11 16:20:35', 'tracking', 4, 'glpi added new job.');
INSERT INTO `glpi_event_log` VALUES (378, -1, 'system', '2004-07-11 16:21:41', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (379, -1, 'system', '2004-07-12 14:41:42', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (380, 19, 'computers', '2004-07-12 14:43:10', 'tracking', 4, 'glpi added followup to job 4.');
INSERT INTO `glpi_event_log` VALUES (381, 19, 'computers', '2004-07-12 14:43:21', 'tracking', 4, 'glpi added followup to job 4.');
INSERT INTO `glpi_event_log` VALUES (382, 19, 'computers', '2004-07-12 14:44:09', 'inventory', 5, 'glpi installed software.');
INSERT INTO `glpi_event_log` VALUES (383, 0, 'dropdowns', '2004-07-12 14:45:31', 'setup', 5, 'glpi added a value to a dropdown.');
INSERT INTO `glpi_event_log` VALUES (384, -1, 'system', '2004-07-12 14:48:56', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (385, 0, 'networking', '2004-07-12 14:49:06', 'inventory', 5, 'glpi added networking port.');
INSERT INTO `glpi_event_log` VALUES (386, 0, 'networking', '2004-07-12 14:49:11', 'inventory', 5, 'glpi added networking port.');
INSERT INTO `glpi_event_log` VALUES (387, -1, 'system', '2004-07-12 15:24:42', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (388, -1, 'system', '2004-07-12 15:50:04', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (389, -1, 'system', '2004-07-12 17:50:28', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (390, 0, 'Templates', '2004-07-12 17:52:24', 'setup', 5, 'glpi added template bazou.');
INSERT INTO `glpi_event_log` VALUES (391, 0, 'computers', '2004-07-12 17:52:31', 'inventory', 4, 'glpi added .');
INSERT INTO `glpi_event_log` VALUES (392, 0, 'Templates', '2004-07-12 17:53:15', 'setup', 5, 'glpi updated template 8.');
INSERT INTO `glpi_event_log` VALUES (393, 0, 'Templates', '2004-07-12 17:53:25', 'setup', 5, 'glpi updated template 8.');
INSERT INTO `glpi_event_log` VALUES (394, -1, 'system', '2004-07-13 23:50:42', 'login', 3, 'glpi logged in.');

# --------------------------------------------------------

#
# Structure de la table `glpi_followups`
#

DROP TABLE IF EXISTS `glpi_followups`;
CREATE TABLE `glpi_followups` (
  `ID` int(11) NOT NULL auto_increment,
  `tracking` int(11) default NULL,
  `date` datetime default NULL,
  `author` varchar(200) default NULL,
  `contents` text,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=8 ;

#
# Contenu de la table `glpi_followups`
#

INSERT INTO `glpi_followups` VALUES (6, 4, '2004-07-12 14:43:10', 'glpi', 'salut ad');
INSERT INTO `glpi_followups` VALUES (7, 4, '2004-07-12 14:43:21', 'glpi', 'salut ad');

# --------------------------------------------------------

#
# Structure de la table `glpi_inst_software`
#

DROP TABLE IF EXISTS `glpi_inst_software`;
CREATE TABLE `glpi_inst_software` (
  `ID` int(11) NOT NULL auto_increment,
  `cID` int(11) NOT NULL default '0',
  `license` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `cID` (`cID`),
  KEY `sID` (`license`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# Contenu de la table `glpi_inst_software`
#

INSERT INTO `glpi_inst_software` VALUES (5, 19, 8);

# --------------------------------------------------------

#
# Structure de la table `glpi_licenses`
#

DROP TABLE IF EXISTS `glpi_licenses`;
CREATE TABLE `glpi_licenses` (
  `ID` int(15) NOT NULL auto_increment,
  `sID` int(15) NOT NULL default '0',
  `serial` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=10 ;

#
# Contenu de la table `glpi_licenses`
#

INSERT INTO `glpi_licenses` VALUES (7, 4, '12-aa-asd-12-aa');
INSERT INTO `glpi_licenses` VALUES (6, 4, 'asd-asdf-asdf-12');
INSERT INTO `glpi_licenses` VALUES (4, 4, 'au-23-as-23-cd');
INSERT INTO `glpi_licenses` VALUES (5, 4, 'qw-as-23-0k-23-dg');
INSERT INTO `glpi_licenses` VALUES (8, 3, 'free');
INSERT INTO `glpi_licenses` VALUES (9, 5, 'free');

# --------------------------------------------------------

#
# Structure de la table `glpi_monitors`
#

DROP TABLE IF EXISTS `glpi_monitors`;
CREATE TABLE `glpi_monitors` (
  `ID` int(10) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `type` varchar(255) NOT NULL default '',
  `location` varchar(255) NOT NULL default '',
  `contact` varchar(255) NOT NULL default '',
  `contact_num` varchar(255) NOT NULL default '',
  `comments` text NOT NULL,
  `serial` varchar(255) NOT NULL default '',
  `otherserial` varchar(255) NOT NULL default '',
  `size` int(3) NOT NULL default '0',
  `flags_micro` tinyint(4) NOT NULL default '0',
  `flags_speaker` tinyint(4) NOT NULL default '0',
  `flags_subd` tinyint(4) NOT NULL default '0',
  `flags_bnc` tinyint(4) NOT NULL default '0',
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`),
  KEY `ID` (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_monitors`
#

INSERT INTO `glpi_monitors` VALUES (3, 'nokia 20\'', '2003-09-18 00:14:14', 'Nokia 445Xav', '1 ier etage', '', '', 'Ecran infographiste', '', '', 20, 1, 1, 1, 0, '0000-00-00', '0000-00-00', 0);
INSERT INTO `glpi_monitors` VALUES (2, 'Sony 19\'', '2003-09-18 00:14:50', 'Sony 200sf', '1 ier etage', '', '', 'ecran documentation', '', '', 19, 0, 0, 1, 1, '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `glpi_networking`
#

DROP TABLE IF EXISTS `glpi_networking`;
CREATE TABLE `glpi_networking` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(30) NOT NULL default '',
  `type` varchar(30) NOT NULL default '',
  `ram` varchar(10) NOT NULL default '',
  `location` varchar(40) NOT NULL default '',
  `serial` varchar(50) NOT NULL default '',
  `otherserial` varchar(50) NOT NULL default '',
  `contact` varchar(30) NOT NULL default '',
  `contact_num` varchar(30) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments` text NOT NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=10 ;

#
# Contenu de la table `glpi_networking`
#

INSERT INTO `glpi_networking` VALUES (9, 'Dlink 450', 'Dlink Switch', '', '1 ier etage', '4586-puis-kioe', '', '', '', '0000-00-00 00:00:00', '', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `glpi_networking_ports`
#

DROP TABLE IF EXISTS `glpi_networking_ports`;
CREATE TABLE `glpi_networking_ports` (
  `ID` int(11) NOT NULL auto_increment,
  `on_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  `iface` char(40) NOT NULL default '',
  `logical_number` int(11) NOT NULL default '0',
  `name` char(30) NOT NULL default '',
  `ifaddr` char(30) NOT NULL default '',
  `ifmac` char(30) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=11 ;

#
# Contenu de la table `glpi_networking_ports`
#

INSERT INTO `glpi_networking_ports` VALUES (1, 8, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.26', '');
INSERT INTO `glpi_networking_ports` VALUES (2, 10, 1, '100Mbps Ethernet (UTP)', 1, '3com', '10.10.0.27', '');
INSERT INTO `glpi_networking_ports` VALUES (3, 15, 1, '100Mbps Ethernet (UTP)', 1, 'Generic', '10.10.0.28', '');
INSERT INTO `glpi_networking_ports` VALUES (4, 18, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.29', '');
INSERT INTO `glpi_networking_ports` VALUES (5, 9, 2, '100Mbps Ethernet (UTP)', 1, 'Dlink port', '10.10.0.1', '');
INSERT INTO `glpi_networking_ports` VALUES (6, 9, 2, '100Mbps Ethernet (UTP)', 2, 'Dlink port', '10.10.0.1', '');
INSERT INTO `glpi_networking_ports` VALUES (7, 9, 2, '100Mbps Ethernet (UTP)', 3, 'Dlink port', '10.10.0.1', '');
INSERT INTO `glpi_networking_ports` VALUES (8, 10, 2, 'Frame Relay', 0, '', '', '');
INSERT INTO `glpi_networking_ports` VALUES (9, 9, 2, '100Base FL', 0, '', '', '');
INSERT INTO `glpi_networking_ports` VALUES (10, 9, 2, '100Mbps FDDI', 0, '', '', '');

# --------------------------------------------------------

#
# Structure de la table `glpi_networking_wire`
#

DROP TABLE IF EXISTS `glpi_networking_wire`;
CREATE TABLE `glpi_networking_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_networking_wire`
#

INSERT INTO `glpi_networking_wire` VALUES (1, 5, 1);
INSERT INTO `glpi_networking_wire` VALUES (2, 6, 2);
INSERT INTO `glpi_networking_wire` VALUES (3, 7, 3);

# --------------------------------------------------------

#
# Structure de la table `glpi_prefs`
#

DROP TABLE IF EXISTS `glpi_prefs`;
CREATE TABLE `glpi_prefs` (
  `user` varchar(80) NOT NULL default '',
  `tracking_order` enum('no','yes') NOT NULL default 'no',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`user`)
) TYPE=MyISAM;

#
# Contenu de la table `glpi_prefs`
#

INSERT INTO `glpi_prefs` VALUES ('glpi', 'yes', 'french');
INSERT INTO `glpi_prefs` VALUES ('Helpdesk', 'no', 'french');
INSERT INTO `glpi_prefs` VALUES ('normal', '', 'english');
INSERT INTO `glpi_prefs` VALUES ('tech', 'yes', 'french');
INSERT INTO `glpi_prefs` VALUES ('post-only', '', 'english');

# --------------------------------------------------------

#
# Structure de la table `glpi_printers`
#

DROP TABLE IF EXISTS `glpi_printers`;
CREATE TABLE `glpi_printers` (
  `ID` int(10) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `type` varchar(255) NOT NULL default '',
  `location` varchar(255) NOT NULL default '',
  `contact` varchar(255) NOT NULL default '',
  `contact_num` varchar(255) NOT NULL default '',
  `serial` varchar(255) NOT NULL default '',
  `otherserial` varchar(255) NOT NULL default '',
  `flags_serial` tinyint(4) NOT NULL default '0',
  `flags_par` tinyint(4) NOT NULL default '0',
  `comments` text NOT NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  `ramSize` varchar(6) NOT NULL default '',
  PRIMARY KEY  (`ID`),
  KEY `id` (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Contenu de la table `glpi_printers`
#

INSERT INTO `glpi_printers` VALUES (1, 'HP laser', '2003-09-18 00:12:43', 'HP Laserjet 4050N', '1 ier etage', '', '', 'hp-jsgsj-658', '', 0, 1, 'Imprimante bureau du directeur', '0000-00-00', '0000-00-00', 0, '');
INSERT INTO `glpi_printers` VALUES (2, 'HP deskjet', '2003-09-18 00:13:11', 'HP Deskjet 850c', '2nd etage', '', '', '45dskjs-ds', '', 0, 1, 'Imprimante documentation', '0000-00-00', '0000-00-00', 0, '');

# --------------------------------------------------------

#
# Structure de la table `glpi_software`
#

DROP TABLE IF EXISTS `glpi_software`;
CREATE TABLE `glpi_software` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `platform` varchar(200) NOT NULL default '',
  `version` varchar(20) NOT NULL default '',
  `location` varchar(200) NOT NULL default '',
  `comments` text,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# Contenu de la table `glpi_software`
#

INSERT INTO `glpi_software` VALUES (3, 'Acrobat PDF Viewer', 'Windows NT 4.0', '4', 'Admin', NULL);
INSERT INTO `glpi_software` VALUES (4, 'MS Windows NT', 'Windows NT 4.0', '4.0', 'Admin', NULL);
INSERT INTO `glpi_software` VALUES (5, 'Latex', 'Linux (Redhat 6.2)', '6.2', '1 ier etage', 'Latex');

# --------------------------------------------------------

#
# Structure de la table `glpi_templates`
#

DROP TABLE IF EXISTS `glpi_templates`;
CREATE TABLE `glpi_templates` (
  `ID` int(11) NOT NULL auto_increment,
  `templname` varchar(200) NOT NULL default '',
  `name` varchar(200) NOT NULL default '',
  `type` varchar(200) default NULL,
  `os` varchar(200) NOT NULL default '',
  `osver` varchar(20) NOT NULL default '',
  `processor` varchar(200) NOT NULL default '',
  `processor_speed` varchar(100) NOT NULL default '',
  `location` varchar(200) NOT NULL default '',
  `serial` varchar(200) NOT NULL default '',
  `otherserial` varchar(200) NOT NULL default '',
  `ramtype` varchar(200) NOT NULL default '',
  `ram` varchar(20) NOT NULL default '',
  `network` varchar(200) NOT NULL default '',
  `hdspace` varchar(10) NOT NULL default '',
  `contact` varchar(200) NOT NULL default '',
  `contact_num` varchar(200) NOT NULL default '',
  `comments` text NOT NULL,
  `moboard` varchar(255) NOT NULL default '',
  `sndcard` varchar(255) NOT NULL default '',
  `gfxcard` varchar(255) NOT NULL default '',
  `hdtype` varchar(255) NOT NULL default '',
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_templates`
#

INSERT INTO `glpi_templates` VALUES (1, 'Blank Template', '', 'Generic x86 PC', 'Linux (Redhat 6.2)', '', '486 DX', '', 'Admin', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', 'Asus P2BX', 'Soundblaster 128 PCI', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO `glpi_templates` VALUES (3, 'iMac', '', 'iMac 2nd Generation', 'MacOS', '9', 'PowerPC G3', '333', 'Admin', '', '', 'iMac DIMMS', '128', 'Generic 100Mbps Card', '6', '', '', 'Standard iMac', 'unknown', 'integrated', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `glpi_tracking`
#

DROP TABLE IF EXISTS `glpi_tracking`;
CREATE TABLE `glpi_tracking` (
  `ID` int(11) NOT NULL auto_increment,
  `date` datetime default NULL,
  `closedate` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` enum('new','old') default NULL,
  `author` varchar(200) default NULL,
  `assign` varchar(200) default NULL,
  `computer` int(11) default NULL,
  `contents` text,
  `priority` tinyint(4) NOT NULL default '1',
  `is_group` enum('no','yes') NOT NULL default 'no',
  `uemail` varchar(100) default NULL,
  `emailupdates` varchar(4) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

#
# Contenu de la table `glpi_tracking`
#


# --------------------------------------------------------

#
# Structure de la table `glpi_type_computers`
#

DROP TABLE IF EXISTS `glpi_type_computers`;
CREATE TABLE `glpi_type_computers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_type_computers`
#

INSERT INTO `glpi_type_computers` VALUES ('Generic x86 PC');
INSERT INTO `glpi_type_computers` VALUES ('PowerMac G4');
INSERT INTO `glpi_type_computers` VALUES ('iMac 2nd Generation');
INSERT INTO `glpi_type_computers` VALUES ('PowerMac G3');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_monitors`
#

DROP TABLE IF EXISTS `glpi_type_monitors`;
CREATE TABLE `glpi_type_monitors` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_type_monitors`
#

INSERT INTO `glpi_type_monitors` VALUES ('Nokia 445Xav');
INSERT INTO `glpi_type_monitors` VALUES ('Sony 200GDM');
INSERT INTO `glpi_type_monitors` VALUES ('Sony 200sf');
INSERT INTO `glpi_type_monitors` VALUES ('integrated');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_networking`
#

DROP TABLE IF EXISTS `glpi_type_networking`;
CREATE TABLE `glpi_type_networking` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_type_networking`
#

INSERT INTO `glpi_type_networking` VALUES ('Dlink Switch');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_printers`
#

DROP TABLE IF EXISTS `glpi_type_printers`;
CREATE TABLE `glpi_type_printers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `glpi_type_printers`
#

INSERT INTO `glpi_type_printers` VALUES ('HP Laserjet 4050N');
INSERT INTO `glpi_type_printers` VALUES ('HP Laserjet 4+');
INSERT INTO `glpi_type_printers` VALUES ('HP Deskjet 850c');

# --------------------------------------------------------

#
# Structure de la table `glpi_users`
#

DROP TABLE IF EXISTS `glpi_users`;
CREATE TABLE `glpi_users` (
  `name` varchar(80) NOT NULL default '',
  `password` varchar(80) NOT NULL default '',
  `email` varchar(80) NOT NULL default '',
  `location` varchar(100) NOT NULL default '',
  `phone` varchar(100) default NULL,
  `type` enum('normal','admin','post-only') NOT NULL default 'normal',
  `realname` varchar(255) NOT NULL default '',
  `can_assign_job` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`name`),
  KEY `type` (`type`)
) TYPE=MyISAM;

#
# Contenu de la table `glpi_users`
#

INSERT INTO `glpi_users` VALUES ('Helpdesk', '14e43c2d31dcbdd1', '', 'Admin', NULL, 'post-only', 'Helpdesk Injector', 'no');
INSERT INTO `glpi_users` VALUES ('glpi', '5b9b1ee2216a5ffe', '', '2nd etage', '', 'admin', 'glpi', 'yes');
INSERT INTO `glpi_users` VALUES ('post-only', '3eb831c67be6aeda', '', '1 ier etage', NULL, 'post-only', 'post-only', 'no');
INSERT INTO `glpi_users` VALUES ('tech', '37bd7c4221e8a247', '', '2nd etage', NULL, 'admin', 'technicien', 'yes');
INSERT INTO `glpi_users` VALUES ('normal', '109e7883561b4202', '', '1 ier etage', NULL, 'normal', 'utilisateur normal', 'no');
