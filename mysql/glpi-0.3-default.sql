# phpMyAdmin SQL Dump
# version 2.5.7-pl1
# http://www.phpmyadmin.net
#
# Serveur: localhost
# Généré le : Mardi 13 Juillet 2004 à 00:25
# Version du serveur: 4.0.20
# Version de PHP: 4.3.4
# 
# Base de données: `riliz`
# 

# --------------------------------------------------------

#
# Structure de la table `computers`
#

DROP TABLE IF EXISTS `computers`;
CREATE TABLE `computers` (
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
) TYPE=MyISAM;

#
# Contenu de la table `computers`
#

INSERT DELAYED INTO `computers` (`ID`, `name`, `type`, `flags_server`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `date_mod`, `gfxcard`, `moboard`, `sndcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (23, '', 'Generic x86 PC', 0, 'Debian woody 3.0', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', '', '2004-07-12 17:52:31', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'integrated', 'IBM DCAS 34330', '2004-07-22', '2004-07-28', 0),
(19, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:12', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0),
(20, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:14', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0),
(21, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:16', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0),
(22, '', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', '', '486 DX', '', '1 ier etage', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', '2004-07-11 16:20:17', 'ATI Rage Pro 3D AGP', 'Asus P2BX', 'Soundblaster 128 PCI', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `connect_wire`
#

DROP TABLE IF EXISTS `connect_wire`;
CREATE TABLE `connect_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

#
# Contenu de la table `connect_wire`
#


# --------------------------------------------------------

#
# Structure de la table `dropdown_gfxcard`
#

DROP TABLE IF EXISTS `dropdown_gfxcard`;
CREATE TABLE `dropdown_gfxcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_gfxcard`
#

INSERT DELAYED INTO `dropdown_gfxcard` (`name`) VALUES ('ATI Rage Pro 3D AGP'),
('Matrox Millenium G400DH'),
('S3 Trio 64V+'),
('integrated');

# --------------------------------------------------------

#
# Structure de la table `dropdown_hdtype`
#

DROP TABLE IF EXISTS `dropdown_hdtype`;
CREATE TABLE `dropdown_hdtype` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_hdtype`
#

INSERT DELAYED INTO `dropdown_hdtype` (`name`) VALUES ('IBM DTTA 35101'),
('IBM DCAS 34330');

# --------------------------------------------------------

#
# Structure de la table `dropdown_iface`
#

DROP TABLE IF EXISTS `dropdown_iface`;
CREATE TABLE `dropdown_iface` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_iface`
#

INSERT DELAYED INTO `dropdown_iface` (`name`) VALUES ('10Mbps Ethernet (UTP)'),
('100Mbps Ethernet (UTP)'),
('100Base FL'),
('100Mbps FDDI'),
('Frame Relay'),
('ISDN'),
('T1/E1 +'),
('Serial Link');

# --------------------------------------------------------

#
# Structure de la table `dropdown_locations`
#

DROP TABLE IF EXISTS `dropdown_locations`;
CREATE TABLE `dropdown_locations` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_locations`
#

INSERT DELAYED INTO `dropdown_locations` (`name`) VALUES ('1 ier etage'),
('2nd etage'),
('1er etage');

# --------------------------------------------------------

#
# Structure de la table `dropdown_moboard`
#

DROP TABLE IF EXISTS `dropdown_moboard`;
CREATE TABLE `dropdown_moboard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_moboard`
#

INSERT DELAYED INTO `dropdown_moboard` (`name`) VALUES ('Asus T2P4S'),
('Asus P2BX'),
('unknown');

# --------------------------------------------------------

#
# Structure de la table `dropdown_network`
#

DROP TABLE IF EXISTS `dropdown_network`;
CREATE TABLE `dropdown_network` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_network`
#

INSERT DELAYED INTO `dropdown_network` (`name`) VALUES ('3Com (100Mbps)'),
('3Com (10Mbps)'),
('Intel (100Mbps)'),
('Intel (10Mbps)'),
('Generic 100Mbps Card'),
('Generic 10Mbps Card'),
('None'),
('AMD 10Mbps'),
('Realtek 10Mbps'),
('Realtek 100Mbps'),
('integrated');

# --------------------------------------------------------

#
# Structure de la table `dropdown_os`
#

DROP TABLE IF EXISTS `dropdown_os`;
CREATE TABLE `dropdown_os` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_os`
#

INSERT DELAYED INTO `dropdown_os` (`name`) VALUES ('Windows 2000'),
('Linux (Redhat 6.2)'),
('Linux (Slackware 7)'),
('Solaris'),
('Windows NT 4.0'),
('Windows 95a'),
('Other'),
('Windows 98'),
('MacOS'),
('Windows 95 OSR2'),
('Windows 98 SR2'),
('Debian woody 3.0');

# --------------------------------------------------------

#
# Structure de la table `dropdown_processor`
#

DROP TABLE IF EXISTS `dropdown_processor`;
CREATE TABLE `dropdown_processor` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_processor`
#

INSERT DELAYED INTO `dropdown_processor` (`name`) VALUES ('Intel Pentium'),
('Intel Pentium II'),
('AMD K6-1'),
('AMD K6-2'),
('AMD K6-3'),
('PowerPC G3'),
('Intel Pentium III'),
('AMD Athlon'),
('68k (Motorola)'),
('486 SX'),
('486 DX'),
('486 DX2/4'),
('Intel Itanium'),
('PowerPC G4'),
('RS3000'),
('RS10k'),
('Alpha EV6.7'),
('PowerPC 603ev'),
('PowerPC 603'),
('PowerPC 601'),
('68040'),
('68040'),
('ULTRASparc II'),
('Intel Pentium IV'),
('AMD Athlon'),
('AMD Duron');

# --------------------------------------------------------

#
# Structure de la table `dropdown_ram`
#

DROP TABLE IF EXISTS `dropdown_ram`;
CREATE TABLE `dropdown_ram` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_ram`
#

INSERT DELAYED INTO `dropdown_ram` (`name`) VALUES ('36pin SIMMS'),
('72pin SIMMS (Fast Page)'),
('72pin SIMMS (EDO)'),
('Unbuffered DIMMs'),
('DIMMs w/EEPROM'),
('SDRAM DIMMs (<10ns)'),
('ECC DIMMs'),
('Other'),
('iMac DIMMS');

# --------------------------------------------------------

#
# Structure de la table `dropdown_sndcard`
#

DROP TABLE IF EXISTS `dropdown_sndcard`;
CREATE TABLE `dropdown_sndcard` (
  `name` varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_sndcard`
#

INSERT DELAYED INTO `dropdown_sndcard` (`name`) VALUES ('Soundblaster 128 PCI'),
('Soundblaster 16 PnP'),
('integrated');

# --------------------------------------------------------

#
# Structure de la table `event_log`
#

DROP TABLE IF EXISTS `event_log`;
CREATE TABLE `event_log` (
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
) TYPE=MyISAM;

#
# Contenu de la table `event_log`
#

INSERT DELAYED INTO `event_log` (`ID`, `item`, `itemtype`, `date`, `service`, `level`, `message`) VALUES (363, -1, 'system', '2004-07-11 16:08:11', 'login', 3, 'glpi logged in.'),
(364, 8, 'computers', '2004-07-11 16:08:19', 'inventory', 4, 'glpiupdated item.'),
(365, 8, 'computers', '2004-07-11 16:08:21', 'inventory', 4, 'glpi deleted item.'),
(366, 8, 'computers', '2004-07-11 16:12:17', 'inventory', 4, 'glpiupdated item.'),
(367, 18, 'computers', '2004-07-11 16:12:25', 'inventory', 4, 'glpi deleted item.'),
(368, -1, 'system', '2004-07-11 16:15:36', 'login', 3, 'glpi logged in.'),
(369, 15, 'computers', '2004-07-11 16:15:47', 'inventory', 4, 'glpi deleted item.'),
(370, -1, 'system', '2004-07-11 16:19:55', 'login', 3, 'glpi logged in.'),
(371, 10, 'computers', '2004-07-11 16:20:02', 'inventory', 4, 'glpi deleted item.'),
(372, 0, 'computers', '2004-07-11 16:20:12', 'inventory', 4, 'glpi added .'),
(373, 0, 'computers', '2004-07-11 16:20:14', 'inventory', 4, 'glpi added .'),
(374, 0, 'computers', '2004-07-11 16:20:16', 'inventory', 4, 'glpi added .'),
(375, 0, 'computers', '2004-07-11 16:20:17', 'inventory', 4, 'glpi added .'),
(376, 19, 'computers', '2004-07-11 16:20:29', 'tracking', 4, 'glpi added new job.'),
(377, 19, 'computers', '2004-07-11 16:20:35', 'tracking', 4, 'glpi added new job.'),
(378, -1, 'system', '2004-07-11 16:21:41', 'login', 3, 'glpi logged in.'),
(379, -1, 'system', '2004-07-12 14:41:42', 'login', 3, 'glpi logged in.'),
(380, 19, 'computers', '2004-07-12 14:43:10', 'tracking', 4, 'glpi added followup to job 4.'),
(381, 19, 'computers', '2004-07-12 14:43:21', 'tracking', 4, 'glpi added followup to job 4.'),
(382, 19, 'computers', '2004-07-12 14:44:09', 'inventory', 5, 'glpi installed software.'),
(383, 0, 'dropdowns', '2004-07-12 14:45:31', 'setup', 5, 'glpi added a value to a dropdown.'),
(384, -1, 'system', '2004-07-12 14:48:56', 'login', 3, 'glpi logged in.'),
(385, 0, 'networking', '2004-07-12 14:49:06', 'inventory', 5, 'glpi added networking port.'),
(386, 0, 'networking', '2004-07-12 14:49:11', 'inventory', 5, 'glpi added networking port.'),
(387, -1, 'system', '2004-07-12 15:24:42', 'login', 3, 'glpi logged in.'),
(388, -1, 'system', '2004-07-12 15:50:04', 'login', 3, 'glpi logged in.'),
(389, -1, 'system', '2004-07-12 17:50:28', 'login', 3, 'glpi logged in.'),
(390, 0, 'Templates', '2004-07-12 17:52:24', 'setup', 5, 'glpi added template bazou.'),
(391, 0, 'computers', '2004-07-12 17:52:31', 'inventory', 4, 'glpi added .'),
(392, 0, 'Templates', '2004-07-12 17:53:15', 'setup', 5, 'glpi updated template 8.'),
(393, 0, 'Templates', '2004-07-12 17:53:25', 'setup', 5, 'glpi updated template 8.');

# --------------------------------------------------------

#
# Structure de la table `followups`
#

DROP TABLE IF EXISTS `followups`;
CREATE TABLE `followups` (
  `ID` int(11) NOT NULL auto_increment,
  `tracking` int(11) default NULL,
  `date` datetime default NULL,
  `author` varchar(200) default NULL,
  `contents` text,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

#
# Contenu de la table `followups`
#

INSERT DELAYED INTO `followups` (`ID`, `tracking`, `date`, `author`, `contents`) VALUES (6, 4, '2004-07-12 14:43:10', 'glpi', 'salut ad'),
(7, 4, '2004-07-12 14:43:21', 'glpi', 'salut ad');

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
) TYPE=MyISAM;

#
# Contenu de la table `glpi_config`
#

INSERT DELAYED INTO `glpi_config` (`config_id`, `num_of_events`, `jobs_at_login`, `sendexpire`, `cut`, `expire_events`, `list_limit`, `version`, `logotxt`, `root_doc`, `event_loglevel`, `mailing`, `imap_auth_server`, `imap_host`, `ldap_host`, `ldap_basedn`, `ldap_rootdn`, `ldap_pass`, `admin_email`, `mailing_signature`, `mailing_new_admin`, `mailing_attrib_admin`, `mailing_followup_admin`, `mailing_finish_admin`, `mailing_new_all_admin`, `mailing_attrib_all_admin`, `mailing_followup_all_admin`, `mailing_finish_all_admin`, `mailing_new_all_normal`, `mailing_attrib_all_normal`, `mailing_followup_all_normal`, `mailing_finish_all_normal`, `mailing_attrib_attrib`, `mailing_followup_attrib`, `mailing_finish_attrib`, `mailing_new_user`, `mailing_attrib_user`, `mailing_followup_user`, `mailing_finish_user`, `ldap_field_name`, `ldap_field_email`, `ldap_field_location`, `ldap_field_realname`, `ldap_field_phone`) VALUES (1, '10', '10', '1', '80', '30', '15', ' 0.3', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', 'ldap://localhost/', 'dc=melnibone', '', '', 'admsys@sic.sp2mi.xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '1', '1', '1', '1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber');

# --------------------------------------------------------

#
# Structure de la table `inst_software`
#

DROP TABLE IF EXISTS `inst_software`;
CREATE TABLE `inst_software` (
  `ID` int(11) NOT NULL auto_increment,
  `cID` int(11) NOT NULL default '0',
  `license` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `cID` (`cID`),
  KEY `sID` (`license`)
) TYPE=MyISAM;

#
# Contenu de la table `inst_software`
#

INSERT DELAYED INTO `inst_software` (`ID`, `cID`, `license`) VALUES (5, 19, 8);

# --------------------------------------------------------

#
# Structure de la table `licenses`
#

DROP TABLE IF EXISTS `licenses`;
CREATE TABLE `licenses` (
  `ID` int(15) NOT NULL auto_increment,
  `sID` int(15) NOT NULL default '0',
  `serial` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

#
# Contenu de la table `licenses`
#

INSERT DELAYED INTO `licenses` (`ID`, `sID`, `serial`) VALUES (7, 4, '12-aa-asd-12-aa'),
(6, 4, 'asd-asdf-asdf-12'),
(4, 4, 'au-23-as-23-cd'),
(5, 4, 'qw-as-23-0k-23-dg'),
(8, 3, 'free'),
(9, 5, 'free');

# --------------------------------------------------------

#
# Structure de la table `monitors`
#

DROP TABLE IF EXISTS `monitors`;
CREATE TABLE `monitors` (
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
) TYPE=MyISAM;

#
# Contenu de la table `monitors`
#

INSERT DELAYED INTO `monitors` (`ID`, `name`, `date_mod`, `type`, `location`, `contact`, `contact_num`, `comments`, `serial`, `otherserial`, `size`, `flags_micro`, `flags_speaker`, `flags_subd`, `flags_bnc`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (3, 'nokia 20\'', '2003-09-18 00:14:14', 'Nokia 445Xav', '1 ier etage', '', '', 'Ecran infographiste', '', '', 20, 1, 1, 1, 0, '0000-00-00', '0000-00-00', 0),
(2, 'Sony 19\'', '2003-09-18 00:14:50', 'Sony 200sf', '1 ier etage', '', '', 'ecran documentation', '', '', 19, 0, 0, 1, 1, '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `networking`
#

DROP TABLE IF EXISTS `networking`;
CREATE TABLE `networking` (
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
) TYPE=MyISAM;

#
# Contenu de la table `networking`
#

INSERT DELAYED INTO `networking` (`ID`, `name`, `type`, `ram`, `location`, `serial`, `otherserial`, `contact`, `contact_num`, `date_mod`, `comments`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (9, 'Dlink 450', 'Dlink Switch', '', '1 ier etage', '4586-puis-kioe', '', '', '', '0000-00-00 00:00:00', '', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `networking_ports`
#

DROP TABLE IF EXISTS `networking_ports`;
CREATE TABLE `networking_ports` (
  `ID` int(11) NOT NULL auto_increment,
  `on_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  `iface` char(40) NOT NULL default '',
  `logical_number` int(11) NOT NULL default '0',
  `name` char(30) NOT NULL default '',
  `ifaddr` char(30) NOT NULL default '',
  `ifmac` char(30) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

#
# Contenu de la table `networking_ports`
#

INSERT DELAYED INTO `networking_ports` (`ID`, `on_device`, `device_type`, `iface`, `logical_number`, `name`, `ifaddr`, `ifmac`) VALUES (1, 8, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.26', ''),
(2, 10, 1, '100Mbps Ethernet (UTP)', 1, '3com', '10.10.0.27', ''),
(3, 15, 1, '100Mbps Ethernet (UTP)', 1, 'Generic', '10.10.0.28', ''),
(4, 18, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.29', ''),
(5, 9, 2, '100Mbps Ethernet (UTP)', 1, 'Dlink port', '10.10.0.1', ''),
(6, 9, 2, '100Mbps Ethernet (UTP)', 2, 'Dlink port', '10.10.0.1', ''),
(7, 9, 2, '100Mbps Ethernet (UTP)', 3, 'Dlink port', '10.10.0.1', ''),
(8, 10, 2, 'Frame Relay', 0, '', '', ''),
(9, 9, 2, '100Base FL', 0, '', '', ''),
(10, 9, 2, '100Mbps FDDI', 0, '', '', '');

# --------------------------------------------------------

#
# Structure de la table `networking_wire`
#

DROP TABLE IF EXISTS `networking_wire`;
CREATE TABLE `networking_wire` (
  `ID` int(11) NOT NULL auto_increment,
  `end1` int(11) NOT NULL default '0',
  `end2` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

#
# Contenu de la table `networking_wire`
#

INSERT DELAYED INTO `networking_wire` (`ID`, `end1`, `end2`) VALUES (1, 5, 1),
(2, 6, 2),
(3, 7, 3);

# --------------------------------------------------------

#
# Structure de la table `prefs`
#

DROP TABLE IF EXISTS `prefs`;
CREATE TABLE `prefs` (
  `user` varchar(80) NOT NULL default '',
  `tracking_order` enum('no','yes') NOT NULL default 'no',
  `language` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`user`)
) TYPE=MyISAM;

#
# Contenu de la table `prefs`
#

INSERT DELAYED INTO `prefs` (`user`, `tracking_order`, `language`) VALUES ('glpi', 'yes', 'french'),
('Helpdesk', 'no', 'french'),
('normal', '', 'english'),
('tech', 'yes', 'french'),
('post-only', '', 'english');

# --------------------------------------------------------

#
# Structure de la table `printers`
#

DROP TABLE IF EXISTS `printers`;
CREATE TABLE `printers` (
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
) TYPE=MyISAM;

#
# Contenu de la table `printers`
#

INSERT DELAYED INTO `printers` (`ID`, `name`, `date_mod`, `type`, `location`, `contact`, `contact_num`, `serial`, `otherserial`, `flags_serial`, `flags_par`, `comments`, `achat_date`, `date_fin_garantie`, `maintenance`, `ramSize`) VALUES (1, 'HP laser', '2003-09-18 00:12:43', 'HP Laserjet 4050N', '1 ier etage', '', '', 'hp-jsgsj-658', '', 0, 1, 'Imprimante bureau du directeur', '0000-00-00', '0000-00-00', 0, ''),
(2, 'HP deskjet', '2003-09-18 00:13:11', 'HP Deskjet 850c', '2nd etage', '', '', '45dskjs-ds', '', 0, 1, 'Imprimante documentation', '0000-00-00', '0000-00-00', 0, '');

# --------------------------------------------------------

#
# Structure de la table `software`
#

DROP TABLE IF EXISTS `software`;
CREATE TABLE `software` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `platform` varchar(200) NOT NULL default '',
  `version` varchar(20) NOT NULL default '',
  `location` varchar(200) NOT NULL default '',
  `comments` text,
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;

#
# Contenu de la table `software`
#

INSERT DELAYED INTO `software` (`ID`, `name`, `platform`, `version`, `location`, `comments`) VALUES (3, 'Acrobat PDF Viewer', 'Windows NT 4.0', '4', 'Admin', NULL),
(4, 'MS Windows NT', 'Windows NT 4.0', '4.0', 'Admin', NULL),
(5, 'Latex', 'Linux (Redhat 6.2)', '6.2', '1 ier etage', 'Latex');

# --------------------------------------------------------

#
# Structure de la table `templates`
#

DROP TABLE IF EXISTS `templates`;
CREATE TABLE `templates` (
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
) TYPE=MyISAM;

#
# Contenu de la table `templates`
#

INSERT DELAYED INTO `templates` (`ID`, `templname`, `name`, `type`, `os`, `osver`, `processor`, `processor_speed`, `location`, `serial`, `otherserial`, `ramtype`, `ram`, `network`, `hdspace`, `contact`, `contact_num`, `comments`, `moboard`, `sndcard`, `gfxcard`, `hdtype`, `achat_date`, `date_fin_garantie`, `maintenance`) VALUES (1, 'Blank Template', '', 'Generic x86 PC', 'Linux (Redhat 6.2)', '', '486 DX', '', 'Admin', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', 'Asus P2BX', 'Soundblaster 128 PCI', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0),
(3, 'iMac', '', 'iMac 2nd Generation', 'MacOS', '9', 'PowerPC G3', '333', 'Admin', '', '', 'iMac DIMMS', '128', 'Generic 100Mbps Card', '6', '', '', 'Standard iMac', 'unknown', 'integrated', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);

# --------------------------------------------------------

#
# Structure de la table `tracking`
#

DROP TABLE IF EXISTS `tracking`;
CREATE TABLE `tracking` (
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
) TYPE=MyISAM;

#
# Contenu de la table `tracking`
#

# --------------------------------------------------------

#
# Structure de la table `type_computers`
#

DROP TABLE IF EXISTS `type_computers`;
CREATE TABLE `type_computers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_computers`
#

INSERT DELAYED INTO `type_computers` (`name`) VALUES ('Generic x86 PC'),
('PowerMac G4'),
('iMac 2nd Generation'),
('PowerMac G3');

# --------------------------------------------------------

#
# Structure de la table `type_monitors`
#

DROP TABLE IF EXISTS `type_monitors`;
CREATE TABLE `type_monitors` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_monitors`
#

INSERT DELAYED INTO `type_monitors` (`name`) VALUES ('Nokia 445Xav'),
('Sony 200GDM'),
('Sony 200sf'),
('integrated');

# --------------------------------------------------------

#
# Structure de la table `type_networking`
#

DROP TABLE IF EXISTS `type_networking`;
CREATE TABLE `type_networking` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_networking`
#

INSERT DELAYED INTO `type_networking` (`name`) VALUES ('Dlink Switch');

# --------------------------------------------------------

#
# Structure de la table `type_printers`
#

DROP TABLE IF EXISTS `type_printers`;
CREATE TABLE `type_printers` (
  `name` varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_printers`
#

INSERT DELAYED INTO `type_printers` (`name`) VALUES ('HP Laserjet 4050N'),
('HP Laserjet 4+'),
('HP Deskjet 850c');

# --------------------------------------------------------

#
# Structure de la table `users`
#

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
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
# Contenu de la table `users`
#

INSERT DELAYED INTO `users` (`name`, `password`, `email`, `location`, `phone`, `type`, `realname`, `can_assign_job`) VALUES ('Helpdesk', '14e43c2d31dcbdd1', '', 'Admin', NULL, 'post-only', 'Helpdesk Injector', 'no'),
('glpi', '5b9b1ee2216a5ffe', '', '2nd etage', '', 'admin', 'glpi', 'yes'),
('post-only', '3eb831c67be6aeda', '', '1 ier etage', NULL, 'post-only', 'post-only', 'no'),
('tech', '37bd7c4221e8a247', '', '2nd etage', NULL, 'admin', 'technicien', 'yes'),
('normal', '109e7883561b4202', '', '1 ier etage', NULL, 'normal', 'utilisateur normal', 'no');
    