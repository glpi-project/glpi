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


# phpMyAdmin SQL Dump
# version 2.5.7-pl1
# http://www.phpmyadmin.net
#
# Serveur: localhost
# Généré le : Mercredi 21 Juillet 2004 à 02:34
# Version du serveur: 4.0.20
# Version de PHP: 4.3.8
# 
# Base de données: `glpidb`
# 

# --------------------------------------------------------

#
# Structure de la table `glpi_computers`
#

DROP TABLE IF EXISTS `glpi_computers`;
CREATE TABLE `glpi_computers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `flags_server` tinyint(4) NOT NULL default '0',
  `osver` varchar(20) NOT NULL default '',
  `processor_speed` varchar(30) NOT NULL default '',
  `serial` varchar(200) NOT NULL default '',
  `otherserial` varchar(200) NOT NULL default '',
  `ram` varchar(6) NOT NULL default '',
  `hdspace` varchar(6) NOT NULL default '',
  `contact` varchar(90) NOT NULL default '',
  `contact_num` varchar(90) NOT NULL default '',
  `comments` text NOT NULL,
  `date_mod` datetime default NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  `os` int(11) default NULL,
  `hdtype` int(11) default NULL,
  `sndcard` int(11) default NULL,
  `moboard` int(11) default NULL,
  `gfxcard` int(11) default NULL,
  `network` int(11) default NULL,
  `ramtype` int(11) default NULL,
  `location` int(11) default NULL,
  `processor` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `flags` (`flags_server`)
) TYPE=MyISAM AUTO_INCREMENT=19 ;

#
# Contenu de la table `glpi_computers`
#

INSERT INTO `glpi_computers` VALUES (8, 'Dell Inspiron 450', 0, '', '750', '4586-sd6-fds', '', '512', '10', 'Roger Rabbit', '5462', '', '2003-09-18 00:15:44', '0000-00-00', '0000-00-00', 0, 5, 2, 3, 3, 4, 1, 6, 1, 7, 1);
INSERT INTO `glpi_computers` VALUES (10, 'Dell Inspiron 450', 0, 'SP3', '750', '4598-jhd-545', '', '128', '20', 'Peter Pan', '8565', '', '2003-09-18 00:07:58', '0000-00-00', '0000-00-00', 0, 5, 1, 3, 3, 4, 1, 6, 1, 7, 1);
INSERT INTO `glpi_computers` VALUES (15, 'Dell Inspiron 450', 0, 'SP2', '750', '4561-hsub-dfsnj', '', '512', '20', 'Poppins Marry', '6545', '', '2003-09-18 00:09:47', '0000-00-00', '0000-00-00', 0, 1, 1, 3, 3, 4, 5, 6, 1, 7, 1);
INSERT INTO `glpi_computers` VALUES (18, 'IBM 945gx', 0, '', '750', '9854-5f-4s4f', '', '128', '20', 'Jeannot Lapin', '5465', '', '2003-09-18 00:05:07', '2001-09-24', '2002-09-27', 0, 2, 1, 3, 3, 4, 1, 6, 1, 7, 1);

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
  `mailing_new_attrib` varchar(200) NOT NULL default '',
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

INSERT INTO `glpi_config` VALUES (1, '10', '1', '1', '80', '30', '15', ' 0.4-alpha', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', '', '', '', '', 'admin@xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '1', '1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber');

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
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Contenu de la table `glpi_connect_wire`
#

INSERT INTO `glpi_connect_wire` VALUES (1, 2, 8, 5);

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_gfxcard`
#

DROP TABLE IF EXISTS `glpi_dropdown_gfxcard`;
CREATE TABLE `glpi_dropdown_gfxcard` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Contenu de la table `glpi_dropdown_gfxcard`
#

INSERT INTO `glpi_dropdown_gfxcard` VALUES (1, 'ATI Rage Pro 3D AGP');
INSERT INTO `glpi_dropdown_gfxcard` VALUES (2, 'Matrox Millenium G400DH');
INSERT INTO `glpi_dropdown_gfxcard` VALUES (3, 'S3 Trio 64V+');
INSERT INTO `glpi_dropdown_gfxcard` VALUES (4, 'integrated');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_hdtype`
#

DROP TABLE IF EXISTS `glpi_dropdown_hdtype`;
CREATE TABLE `glpi_dropdown_hdtype` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Contenu de la table `glpi_dropdown_hdtype`
#

INSERT INTO `glpi_dropdown_hdtype` VALUES (1, 'IBM DTTA 35101');
INSERT INTO `glpi_dropdown_hdtype` VALUES (2, 'IBM DCAS 34330');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_iface`
#

DROP TABLE IF EXISTS `glpi_dropdown_iface`;
CREATE TABLE `glpi_dropdown_iface` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=9 ;

#
# Contenu de la table `glpi_dropdown_iface`
#

INSERT INTO `glpi_dropdown_iface` VALUES (1, '10Mbps Ethernet (UTP)');
INSERT INTO `glpi_dropdown_iface` VALUES (2, '100Mbps Ethernet (UTP)');
INSERT INTO `glpi_dropdown_iface` VALUES (3, '100Base FL');
INSERT INTO `glpi_dropdown_iface` VALUES (4, '100Mbps FDDI');
INSERT INTO `glpi_dropdown_iface` VALUES (5, 'Frame Relay');
INSERT INTO `glpi_dropdown_iface` VALUES (6, 'ISDN');
INSERT INTO `glpi_dropdown_iface` VALUES (7, 'T1/E1 +');
INSERT INTO `glpi_dropdown_iface` VALUES (8, 'Serial Link');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_locations`
#

DROP TABLE IF EXISTS `glpi_dropdown_locations`;
CREATE TABLE `glpi_dropdown_locations` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Contenu de la table `glpi_dropdown_locations`
#

INSERT INTO `glpi_dropdown_locations` VALUES (1, '1 ier etage');
INSERT INTO `glpi_dropdown_locations` VALUES (2, '2nd etage');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_moboard`
#

DROP TABLE IF EXISTS `glpi_dropdown_moboard`;
CREATE TABLE `glpi_dropdown_moboard` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_dropdown_moboard`
#

INSERT INTO `glpi_dropdown_moboard` VALUES (1, 'Asus T2P4S');
INSERT INTO `glpi_dropdown_moboard` VALUES (2, 'Asus P2BX');
INSERT INTO `glpi_dropdown_moboard` VALUES (3, 'unknown');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_network`
#

DROP TABLE IF EXISTS `glpi_dropdown_network`;
CREATE TABLE `glpi_dropdown_network` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=12 ;

#
# Contenu de la table `glpi_dropdown_network`
#

INSERT INTO `glpi_dropdown_network` VALUES (1, '3Com (100Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES (2, '3Com (10Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES (3, 'Intel (100Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES (4, 'Intel (10Mbps)');
INSERT INTO `glpi_dropdown_network` VALUES (5, 'Generic 100Mbps Card');
INSERT INTO `glpi_dropdown_network` VALUES (6, 'Generic 10Mbps Card');
INSERT INTO `glpi_dropdown_network` VALUES (7, 'None');
INSERT INTO `glpi_dropdown_network` VALUES (8, 'AMD 10Mbps');
INSERT INTO `glpi_dropdown_network` VALUES (9, 'Realtek 10Mbps');
INSERT INTO `glpi_dropdown_network` VALUES (10, 'Realtek 100Mbps');
INSERT INTO `glpi_dropdown_network` VALUES (11, 'integrated');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_os`
#

DROP TABLE IF EXISTS `glpi_dropdown_os`;
CREATE TABLE `glpi_dropdown_os` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=13 ;

#
# Contenu de la table `glpi_dropdown_os`
#

INSERT INTO `glpi_dropdown_os` VALUES (1, 'Windows 2000');
INSERT INTO `glpi_dropdown_os` VALUES (2, 'Linux (Redhat 6.2)');
INSERT INTO `glpi_dropdown_os` VALUES (3, 'Linux (Slackware 7)');
INSERT INTO `glpi_dropdown_os` VALUES (4, 'Solaris');
INSERT INTO `glpi_dropdown_os` VALUES (5, 'Windows NT 4.0');
INSERT INTO `glpi_dropdown_os` VALUES (6, 'Windows 95a');
INSERT INTO `glpi_dropdown_os` VALUES (7, 'Other');
INSERT INTO `glpi_dropdown_os` VALUES (8, 'Windows 98');
INSERT INTO `glpi_dropdown_os` VALUES (9, 'MacOS');
INSERT INTO `glpi_dropdown_os` VALUES (10, 'Windows 95 OSR2');
INSERT INTO `glpi_dropdown_os` VALUES (11, 'Windows 98 SR2');
INSERT INTO `glpi_dropdown_os` VALUES (12, 'Debian woody 3.0');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_processor`
#

DROP TABLE IF EXISTS `glpi_dropdown_processor`;
CREATE TABLE `glpi_dropdown_processor` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=27 ;

#
# Contenu de la table `glpi_dropdown_processor`
#

INSERT INTO `glpi_dropdown_processor` VALUES (1, 'Intel Pentium');
INSERT INTO `glpi_dropdown_processor` VALUES (2, 'Intel Pentium II');
INSERT INTO `glpi_dropdown_processor` VALUES (3, 'AMD K6-1');
INSERT INTO `glpi_dropdown_processor` VALUES (4, 'AMD K6-2');
INSERT INTO `glpi_dropdown_processor` VALUES (5, 'AMD K6-3');
INSERT INTO `glpi_dropdown_processor` VALUES (6, 'PowerPC G3');
INSERT INTO `glpi_dropdown_processor` VALUES (7, 'Intel Pentium III');
INSERT INTO `glpi_dropdown_processor` VALUES (8, 'AMD Athlon');
INSERT INTO `glpi_dropdown_processor` VALUES (9, '68k (Motorola)');
INSERT INTO `glpi_dropdown_processor` VALUES (10, '486 SX');
INSERT INTO `glpi_dropdown_processor` VALUES (11, '486 DX');
INSERT INTO `glpi_dropdown_processor` VALUES (12, '486 DX2/4');
INSERT INTO `glpi_dropdown_processor` VALUES (13, 'Intel Itanium');
INSERT INTO `glpi_dropdown_processor` VALUES (14, 'PowerPC G4');
INSERT INTO `glpi_dropdown_processor` VALUES (15, 'RS3000');
INSERT INTO `glpi_dropdown_processor` VALUES (16, 'RS10k');
INSERT INTO `glpi_dropdown_processor` VALUES (17, 'Alpha EV6.7');
INSERT INTO `glpi_dropdown_processor` VALUES (18, 'PowerPC 603ev');
INSERT INTO `glpi_dropdown_processor` VALUES (19, 'PowerPC 603');
INSERT INTO `glpi_dropdown_processor` VALUES (20, 'PowerPC 601');
INSERT INTO `glpi_dropdown_processor` VALUES (21, '68040');
INSERT INTO `glpi_dropdown_processor` VALUES (22, '68040');
INSERT INTO `glpi_dropdown_processor` VALUES (23, 'ULTRASparc II');
INSERT INTO `glpi_dropdown_processor` VALUES (24, 'Intel Pentium IV');
INSERT INTO `glpi_dropdown_processor` VALUES (25, 'AMD Athlon');
INSERT INTO `glpi_dropdown_processor` VALUES (26, 'AMD Duron');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_ram`
#

DROP TABLE IF EXISTS `glpi_dropdown_ram`;
CREATE TABLE `glpi_dropdown_ram` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=10 ;

#
# Contenu de la table `glpi_dropdown_ram`
#

INSERT INTO `glpi_dropdown_ram` VALUES (1, '36pin SIMMS');
INSERT INTO `glpi_dropdown_ram` VALUES (2, '72pin SIMMS (Fast Page)');
INSERT INTO `glpi_dropdown_ram` VALUES (3, '72pin SIMMS (EDO)');
INSERT INTO `glpi_dropdown_ram` VALUES (4, 'Unbuffered DIMMs');
INSERT INTO `glpi_dropdown_ram` VALUES (5, 'DIMMs w/EEPROM');
INSERT INTO `glpi_dropdown_ram` VALUES (6, 'SDRAM DIMMs (<10ns)');
INSERT INTO `glpi_dropdown_ram` VALUES (7, 'ECC DIMMs');
INSERT INTO `glpi_dropdown_ram` VALUES (8, 'Other');
INSERT INTO `glpi_dropdown_ram` VALUES (9, 'iMac DIMMS');

# --------------------------------------------------------

#
# Structure de la table `glpi_dropdown_sndcard`
#

DROP TABLE IF EXISTS `glpi_dropdown_sndcard`;
CREATE TABLE `glpi_dropdown_sndcard` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_dropdown_sndcard`
#

INSERT INTO `glpi_dropdown_sndcard` VALUES (1, 'Soundblaster 128 PCI');
INSERT INTO `glpi_dropdown_sndcard` VALUES (2, 'Soundblaster 16 PnP');
INSERT INTO `glpi_dropdown_sndcard` VALUES (3, 'integrated');

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
) TYPE=MyISAM AUTO_INCREMENT=375 ;

#
# Contenu de la table `glpi_event_log`
#

INSERT INTO `glpi_event_log` VALUES (363, -1, 'system', '2004-07-18 01:16:19', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (364, -1, 'system', '2004-07-18 01:17:52', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (365, -1, 'system', '2004-07-18 01:18:45', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (366, -1, 'system', '2004-07-18 01:20:23', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (367, -1, 'system', '2004-07-21 02:28:34', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (368, -1, 'system', '2004-07-21 02:29:06', 'login', 3, 'glpi logged in.');
INSERT INTO `glpi_event_log` VALUES (369, 0, 'dropdowns', '2004-07-21 02:29:28', 'setup', 5, 'glpi added a value to a dropdown.');
INSERT INTO `glpi_event_log` VALUES (370, 0, 'dropdowns', '2004-07-21 02:29:46', 'setup', 5, 'glpi added a value to a dropdown.');
INSERT INTO `glpi_event_log` VALUES (371, 0, 'Peripheral', '2004-07-21 02:30:35', 'inventory', 4, 'glpi added Mustek plat.');
INSERT INTO `glpi_event_log` VALUES (372, 0, 'Peripheral', '2004-07-21 02:31:19', 'inventory', 4, 'glpi added Ax704.');
INSERT INTO `glpi_event_log` VALUES (373, 2, 'Peripheral', '2004-07-21 02:31:56', 'inventory', 4, 'glpi updated item.');
INSERT INTO `glpi_event_log` VALUES (374, 2, 'Peripheral', '2004-07-21 02:32:17', 'inventory', 5, 'glpi connected item.');

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
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# Contenu de la table `glpi_followups`
#

INSERT INTO `glpi_followups` VALUES (1, 1, '2003-09-18 00:53:35', 'tech', 'J\\\'ai été voir, je pense que la carte mere a grillé.');
INSERT INTO `glpi_followups` VALUES (2, 1, '2003-09-18 00:54:06', 'tech', 'L\\\'alimentation est foutue, je vais tester la carte mere pour voir si elle est recuperable');
INSERT INTO `glpi_followups` VALUES (3, 1, '2003-09-18 00:54:40', 'tech', 'Probleme reglé j\\\'ai seulement changé l\\\'alimentation.\r\nLe reste fonctionne tres bien.');
INSERT INTO `glpi_followups` VALUES (4, 3, '2003-09-18 00:55:08', 'tech', 'Je pense que l\\\'on peux changer la souris.');
INSERT INTO `glpi_followups` VALUES (5, 2, '2003-09-18 00:55:52', 'tech', 'Je suis passé, il faudra faire une restauration de windows NT4.');

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
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Contenu de la table `glpi_inst_software`
#

INSERT INTO `glpi_inst_software` VALUES (2, 10, 7);
INSERT INTO `glpi_inst_software` VALUES (1, 8, 8);
INSERT INTO `glpi_inst_software` VALUES (3, 8, 6);
INSERT INTO `glpi_inst_software` VALUES (4, 8, 9);

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
  `location` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `ID` (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_monitors`
#

INSERT INTO `glpi_monitors` VALUES (3, 'nokia 20\'', '2003-09-18 00:14:14', '', '', 'Ecran infographiste', '', '', 20, 1, 1, 1, 0, '0000-00-00', '0000-00-00', 0, 1, 1);
INSERT INTO `glpi_monitors` VALUES (2, 'Sony 19\'', '2003-09-18 00:14:50', '', '', 'ecran documentation', '', '', 19, 0, 0, 1, 1, '0000-00-00', '0000-00-00', 0, 1, 3);

# --------------------------------------------------------

#
# Structure de la table `glpi_networking`
#

DROP TABLE IF EXISTS `glpi_networking`;
CREATE TABLE `glpi_networking` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(30) NOT NULL default '',
  `ram` varchar(10) NOT NULL default '',
  `serial` varchar(50) NOT NULL default '',
  `otherserial` varchar(50) NOT NULL default '',
  `contact` varchar(30) NOT NULL default '',
  `contact_num` varchar(30) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments` text NOT NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  `location` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=10 ;

#
# Contenu de la table `glpi_networking`
#

INSERT INTO `glpi_networking` VALUES (9, 'Dlink 450', '', '4586-puis-kioe', '', '', '', '0000-00-00 00:00:00', '', '0000-00-00', '0000-00-00', 0, 1, 1);

# --------------------------------------------------------

#
# Structure de la table `glpi_networking_ports`
#

DROP TABLE IF EXISTS `glpi_networking_ports`;
CREATE TABLE `glpi_networking_ports` (
  `ID` int(11) NOT NULL auto_increment,
  `on_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  `logical_number` int(11) NOT NULL default '0',
  `name` char(30) NOT NULL default '',
  `ifaddr` char(30) NOT NULL default '',
  `ifmac` char(30) NOT NULL default '',
  `iface` int(11) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=9 ;

#
# Contenu de la table `glpi_networking_ports`
#

INSERT INTO `glpi_networking_ports` VALUES (1, 8, 1, 1, '3Com', '10.10.0.26', '', 2);
INSERT INTO `glpi_networking_ports` VALUES (2, 10, 1, 1, '3com', '10.10.0.27', '', 2);
INSERT INTO `glpi_networking_ports` VALUES (3, 15, 1, 1, 'Generic', '10.10.0.28', '', 2);
INSERT INTO `glpi_networking_ports` VALUES (4, 18, 1, 1, '3Com', '10.10.0.29', '', 2);
INSERT INTO `glpi_networking_ports` VALUES (5, 9, 2, 1, 'Dlink port', '10.10.0.1', '', 2);
INSERT INTO `glpi_networking_ports` VALUES (6, 9, 2, 2, 'Dlink port', '10.10.0.1', '', 2);
INSERT INTO `glpi_networking_ports` VALUES (7, 9, 2, 3, 'Dlink port', '10.10.0.1', '', 2);
INSERT INTO `glpi_networking_ports` VALUES (8, 10, 2, 0, '', '', '', 5);

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
# Structure de la table `glpi_peripherals`
#

DROP TABLE IF EXISTS `glpi_peripherals`;
CREATE TABLE `glpi_peripherals` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
  `contact` varchar(255) NOT NULL default '',
  `contact_num` varchar(255) NOT NULL default '',
  `comments` text NOT NULL,
  `serial` varchar(255) NOT NULL default '',
  `otherserial` varchar(255) NOT NULL default '',
  `date_fin_garantie` date default NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `maintenance` int(2) default '0',
  `location` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `brand` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Contenu de la table `glpi_peripherals`
#

INSERT INTO `glpi_peripherals` VALUES (1, 'Mustek plat', '0000-00-00 00:00:00', 'Rose', '', '', '132-12465-4564', '', '0000-00-00', '2004-07-21', 0, 1, 1, 'Mustek');
INSERT INTO `glpi_peripherals` VALUES (2, 'Ax704', '2004-07-21 02:31:56', 'Eloise', '', '-', '1321-5465-4564', '', '0000-00-00', '2004-07-21', 1, 1, 2, 'Sony');

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
  `location` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `id` (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Contenu de la table `glpi_printers`
#

INSERT INTO `glpi_printers` VALUES (1, 'HP laser', '2003-09-18 00:12:43', '', '', 'hp-jsgsj-658', '', 0, 1, 'Imprimante bureau du directeur', '0000-00-00', '0000-00-00', 0, '', 1, 1);
INSERT INTO `glpi_printers` VALUES (2, 'HP deskjet', '2003-09-18 00:13:11', '', '', '45dskjs-ds', '', 0, 1, 'Imprimante documentation', '0000-00-00', '0000-00-00', 0, '', 2, 3);

# --------------------------------------------------------

#
# Structure de la table `glpi_software`
#

DROP TABLE IF EXISTS `glpi_software`;
CREATE TABLE `glpi_software` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `version` varchar(20) NOT NULL default '',
  `comments` text,
  `location` int(11) default NULL,
  `platform` int(11) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# Contenu de la table `glpi_software`
#

INSERT INTO `glpi_software` VALUES (3, 'Acrobat PDF Viewer', '4', NULL, NULL, 5);
INSERT INTO `glpi_software` VALUES (4, 'MS Windows NT', '4.0', NULL, NULL, 5);
INSERT INTO `glpi_software` VALUES (5, 'Latex', '6.2', 'Latex', 1, 2);

# --------------------------------------------------------

#
# Structure de la table `glpi_templates`
#

DROP TABLE IF EXISTS `glpi_templates`;
CREATE TABLE `glpi_templates` (
  `ID` int(11) NOT NULL auto_increment,
  `templname` varchar(200) NOT NULL default '',
  `name` varchar(200) NOT NULL default '',
  `osver` varchar(20) NOT NULL default '',
  `processor_speed` varchar(100) NOT NULL default '',
  `serial` varchar(200) NOT NULL default '',
  `otherserial` varchar(200) NOT NULL default '',
  `ram` varchar(20) NOT NULL default '',
  `hdspace` varchar(10) NOT NULL default '',
  `contact` varchar(200) NOT NULL default '',
  `contact_num` varchar(200) NOT NULL default '',
  `comments` text NOT NULL,
  `achat_date` date NOT NULL default '0000-00-00',
  `date_fin_garantie` date default NULL,
  `maintenance` int(2) default '0',
  `os` int(11) default NULL,
  `hdtype` int(11) default NULL,
  `sndcard` int(11) default NULL,
  `moboard` int(11) default NULL,
  `gfxcard` int(11) default NULL,
  `network` int(11) default NULL,
  `ramtype` int(11) default NULL,
  `location` int(11) default NULL,
  `processor` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=8 ;

#
# Contenu de la table `glpi_templates`
#

INSERT INTO `glpi_templates` VALUES (1, 'Blank Template', '', '', '', '', '', '', '', '', '', 'Empty Template', '0000-00-00', '0000-00-00', 0, 2, 2, 1, 2, 1, 1, 1, NULL, 11, 1);
INSERT INTO `glpi_templates` VALUES (3, 'iMac', '', '9', '333', '', '', '128', '6', '', '', 'Standard iMac', '0000-00-00', '0000-00-00', 0, 9, 1, 3, 3, 4, 5, 9, NULL, 6, 3);
INSERT INTO `glpi_templates` VALUES (7, 'test', '', '', '', '', '', '', '', '', '', '', '0000-00-00', '0000-00-00', 0, 12, 2, 3, 2, 1, 1, 1, 1, 11, 1);

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
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_tracking`
#

INSERT INTO `glpi_tracking` VALUES (1, '2003-09-18 00:46:40', '2003-09-18 00:54:43', 'old', 'Helpdesk', 'tech', 8, 'Mon ordinateur ne s\\\'allume plus, et il ya des bruits byzarres', 3, 'no', '', '');
INSERT INTO `glpi_tracking` VALUES (2, '2003-09-18 00:48:19', '0000-00-00 00:00:00', 'new', 'Helpdesk', 'tech', 10, 'Un message en anglais s\\\'affiche, je n\\\'y comprend rien, je n\\\'ose plus toucher à rien de peur de tout casser.\r\nVenez vite !!!!', 4, 'no', '', '');
INSERT INTO `glpi_tracking` VALUES (3, '2003-09-18 00:49:29', '0000-00-00 00:00:00', 'new', 'Helpdesk', 'tech', 18, 'Ma souris se bloque sans arret, elle defile mal sur l\\\'ecran et elle glisse tres mal sur le tapis de souris.', 3, 'no', '', '');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_computers`
#

DROP TABLE IF EXISTS `glpi_type_computers`;
CREATE TABLE `glpi_type_computers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Contenu de la table `glpi_type_computers`
#

INSERT INTO `glpi_type_computers` VALUES (1, 'Generic x86 PC');
INSERT INTO `glpi_type_computers` VALUES (2, 'PowerMac G4');
INSERT INTO `glpi_type_computers` VALUES (3, 'iMac 2nd Generation');
INSERT INTO `glpi_type_computers` VALUES (4, 'PowerMac G3');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_monitors`
#

DROP TABLE IF EXISTS `glpi_type_monitors`;
CREATE TABLE `glpi_type_monitors` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=5 ;

#
# Contenu de la table `glpi_type_monitors`
#

INSERT INTO `glpi_type_monitors` VALUES (1, 'Nokia 445Xav');
INSERT INTO `glpi_type_monitors` VALUES (2, 'Sony 200GDM');
INSERT INTO `glpi_type_monitors` VALUES (3, 'Sony 200sf');
INSERT INTO `glpi_type_monitors` VALUES (4, 'integrated');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_networking`
#

DROP TABLE IF EXISTS `glpi_type_networking`;
CREATE TABLE `glpi_type_networking` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

#
# Contenu de la table `glpi_type_networking`
#

INSERT INTO `glpi_type_networking` VALUES (1, 'Dlink Switch');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_peripherals`
#

DROP TABLE IF EXISTS `glpi_type_peripherals`;
CREATE TABLE `glpi_type_peripherals` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

#
# Contenu de la table `glpi_type_peripherals`
#

INSERT INTO `glpi_type_peripherals` VALUES (1, 'scanner');
INSERT INTO `glpi_type_peripherals` VALUES (2, 'Vidéo projecteur');

# --------------------------------------------------------

#
# Structure de la table `glpi_type_printers`
#

DROP TABLE IF EXISTS `glpi_type_printers`;
CREATE TABLE `glpi_type_printers` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

#
# Contenu de la table `glpi_type_printers`
#

INSERT INTO `glpi_type_printers` VALUES (1, 'HP Laserjet 4050N');
INSERT INTO `glpi_type_printers` VALUES (2, 'HP Laserjet 4+');
INSERT INTO `glpi_type_printers` VALUES (3, 'HP Deskjet 850c');

# --------------------------------------------------------

#
# Structure de la table `glpi_users`
#

DROP TABLE IF EXISTS `glpi_users`;
CREATE TABLE `glpi_users` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(80) NOT NULL default '',
  `password` varchar(80) NOT NULL default '',
  `email` varchar(80) NOT NULL default '',
  `phone` varchar(100) default NULL,
  `type` enum('normal','admin','post-only') NOT NULL default 'normal',
  `realname` varchar(255) NOT NULL default '',
  `can_assign_job` enum('yes','no') NOT NULL default 'no',
  `location` int(11) default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `name_2` (`name`)
) TYPE=MyISAM AUTO_INCREMENT=6 ;

#
# Contenu de la table `glpi_users`
#

INSERT INTO `glpi_users` VALUES (1, 'Helpdesk', '14e43c2d31dcbdd1', '', NULL, 'post-only', 'Helpdesk Injector', 'no', NULL);
INSERT INTO `glpi_users` VALUES (2, 'glpi', '5b9b1ee2216a5ffe', '', '', 'admin', 'glpi', 'yes', 2);
INSERT INTO `glpi_users` VALUES (3, 'post-only', '3eb831c67be6aeda', '', NULL, 'post-only', 'post-only', 'no', 1);
INSERT INTO `glpi_users` VALUES (4, 'tech', '37bd7c4221e8a247', '', NULL, 'admin', 'technicien', 'yes', 2);
INSERT INTO `glpi_users` VALUES (5, 'normal', '109e7883561b4202', '', NULL, 'normal', 'utilisateur normal', 'no', 1);
