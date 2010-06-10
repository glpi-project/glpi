# phpMyAdmin MySQL-Dump
# version 2.2.6
# http://phpwizard.net/phpMyAdmin/
# http://www.phpmyadmin.net/ (download page)
#
# Serveur: localhost
# Généré le : Jeudi 18 Septembre 2003 à 00:57
# Version du serveur: 3.23.49
# Version de PHP: 4.2.0
# Base de données: `glpidb`
# --------------------------------------------------------

#
# Structure de la table `computers`
#

DROP TABLE IF EXISTS computers;
CREATE TABLE computers (
  ID int(11) NOT NULL auto_increment,
  name varchar(200) default NULL,
  type varchar(100) default NULL,
  flags_server tinyint(4) NOT NULL default '0',
  os varchar(100) default NULL,
  osver varchar(20) default NULL,
  processor varchar(30) default NULL,
  processor_speed varchar(30) default NULL,
  location varchar(200) NOT NULL default '',
  serial varchar(200) NOT NULL default '',
  otherserial varchar(200) NOT NULL default '',
  ramtype varchar(200) NOT NULL default '',
  ram varchar(6) NOT NULL default '',
  network varchar(200) NOT NULL default '',
  hdspace varchar(6) default NULL,
  contact varchar(90) default NULL,
  contact_num varchar(90) default NULL,
  comments text NOT NULL,
  date_mod datetime default NULL,
  gfxcard varchar(255) NOT NULL default '',
  moboard varchar(255) NOT NULL default '',
  sndcard varchar(255) NOT NULL default '',
  hdtype varchar(255) NOT NULL default '',
  achat_date date default NULL,
  date_fin_garantie date default NULL,
  maintenance int(2) default '0',
  PRIMARY KEY  (ID),
  KEY location (location),
  KEY flags (flags_server)
) TYPE=MyISAM;

#
# Contenu de la table `computers`
#

INSERT INTO computers VALUES (8, 'Dell Inspiron 450', 'Generic x86 PC', 0, 'Windows NT 4.0', '', 'Intel Pentium III', '750', '1 ier etage', '4586-sd6-fds', '', 'SDRAM DIMMs (<10ns)', '512', '3Com (100Mbps)', '10', 'Roger Rabbit', '5462', '', '2003-09-18 00:15:44', 'integrated', 'unknown', 'integrated', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO computers VALUES (10, 'Dell Inspiron 450', 'Generic x86 PC', 0, 'Windows NT 4.0', 'SP3', 'Intel Pentium III', '750', '1 ier etage', '4598-jhd-545', '', 'SDRAM DIMMs (<10ns)', '128', '3Com (100Mbps)', '20', 'Peter Pan', '8565', '', '2003-09-18 00:07:58', 'integrated', 'unknown', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);
INSERT INTO computers VALUES (15, 'Dell Inspiron 450', 'Generic x86 PC', 0, 'Windows 2000', 'SP2', 'Intel Pentium III', '750', '1 ier etage', '4561-hsub-dfsnj', '', 'SDRAM DIMMs (<10ns)', '512', 'Generic 100Mbps Card', '20', 'Poppins Marry', '6545', '', '2003-09-18 00:09:47', 'integrated', 'unknown', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);
INSERT INTO computers VALUES (18, 'IBM 945gx', 'Generic x86 PC', 0, 'Linux (Redhat 6.2)', NULL, 'Intel Pentium III', '750', '1 ier etage', '9854-5f-4s4f', '', 'SDRAM DIMMs (<10ns)', '128', '3Com (100Mbps)', '20', 'Jeannot Lapin', '5465', '', '2003-09-18 00:05:07', 'integrated', 'unknown', 'integrated', 'IBM DTTA 35101', '2001-09-24', '2002-09-27', 0);
# --------------------------------------------------------

#
# Structure de la table `connect_wire`
#

DROP TABLE IF EXISTS connect_wire;
CREATE TABLE connect_wire (
  ID int(11) NOT NULL auto_increment,
  end1 int(11) NOT NULL default '0',
  end2 int(11) NOT NULL default '0',
  type tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `connect_wire`
#

# --------------------------------------------------------

#
# Structure de la table `dropdown_gfxcard`
#

DROP TABLE IF EXISTS dropdown_gfxcard;
CREATE TABLE dropdown_gfxcard (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_gfxcard`
#

INSERT INTO dropdown_gfxcard VALUES ('ATI Rage Pro 3D AGP');
INSERT INTO dropdown_gfxcard VALUES ('Matrox Millenium G400DH');
INSERT INTO dropdown_gfxcard VALUES ('S3 Trio 64V+');
INSERT INTO dropdown_gfxcard VALUES ('integrated');
# --------------------------------------------------------

#
# Structure de la table `dropdown_hdtype`
#

DROP TABLE IF EXISTS dropdown_hdtype;
CREATE TABLE dropdown_hdtype (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_hdtype`
#

INSERT INTO dropdown_hdtype VALUES ('IBM DTTA 35101');
INSERT INTO dropdown_hdtype VALUES ('IBM DCAS 34330');
# --------------------------------------------------------

#
# Structure de la table `dropdown_iface`
#

DROP TABLE IF EXISTS dropdown_iface;
CREATE TABLE dropdown_iface (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_iface`
#

INSERT INTO dropdown_iface VALUES ('10Mbps Ethernet (UTP)');
INSERT INTO dropdown_iface VALUES ('100Mbps Ethernet (UTP)');
INSERT INTO dropdown_iface VALUES ('100Base FL');
INSERT INTO dropdown_iface VALUES ('100Mbps FDDI');
INSERT INTO dropdown_iface VALUES ('Frame Relay');
INSERT INTO dropdown_iface VALUES ('ISDN');
INSERT INTO dropdown_iface VALUES ('T1/E1 +');
INSERT INTO dropdown_iface VALUES ('Serial Link');
# --------------------------------------------------------

#
# Structure de la table `dropdown_locations`
#

DROP TABLE IF EXISTS dropdown_locations;
CREATE TABLE dropdown_locations (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_locations`
#

INSERT INTO dropdown_locations VALUES ('1 ier etage');
INSERT INTO dropdown_locations VALUES ('2nd etage');
# --------------------------------------------------------

#
# Structure de la table `dropdown_moboard`
#

DROP TABLE IF EXISTS dropdown_moboard;
CREATE TABLE dropdown_moboard (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_moboard`
#

INSERT INTO dropdown_moboard VALUES ('Asus T2P4S');
INSERT INTO dropdown_moboard VALUES ('Asus P2BX');
INSERT INTO dropdown_moboard VALUES ('unknown');
# --------------------------------------------------------

#
# Structure de la table `dropdown_network`
#

DROP TABLE IF EXISTS dropdown_network;
CREATE TABLE dropdown_network (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_network`
#

INSERT INTO dropdown_network VALUES ('3Com (100Mbps)');
INSERT INTO dropdown_network VALUES ('3Com (10Mbps)');
INSERT INTO dropdown_network VALUES ('Intel (100Mbps)');
INSERT INTO dropdown_network VALUES ('Intel (10Mbps)');
INSERT INTO dropdown_network VALUES ('Generic 100Mbps Card');
INSERT INTO dropdown_network VALUES ('Generic 10Mbps Card');
INSERT INTO dropdown_network VALUES ('None');
INSERT INTO dropdown_network VALUES ('AMD 10Mbps');
INSERT INTO dropdown_network VALUES ('Realtek 10Mbps');
INSERT INTO dropdown_network VALUES ('Realtek 100Mbps');
INSERT INTO dropdown_network VALUES ('integrated');
# --------------------------------------------------------

#
# Structure de la table `dropdown_os`
#

DROP TABLE IF EXISTS dropdown_os;
CREATE TABLE dropdown_os (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_os`
#

INSERT INTO dropdown_os VALUES ('Windows 2000');
INSERT INTO dropdown_os VALUES ('Linux (Redhat 6.2)');
INSERT INTO dropdown_os VALUES ('Linux (Slackware 7)');
INSERT INTO dropdown_os VALUES ('Solaris');
INSERT INTO dropdown_os VALUES ('Windows NT 4.0');
INSERT INTO dropdown_os VALUES ('Windows 95a');
INSERT INTO dropdown_os VALUES ('Other');
INSERT INTO dropdown_os VALUES ('Windows 98');
INSERT INTO dropdown_os VALUES ('MacOS');
INSERT INTO dropdown_os VALUES ('Windows 95 OSR2');
INSERT INTO dropdown_os VALUES ('Windows 98 SR2');
INSERT INTO dropdown_os VALUES ('Debian woody 3.0');
# --------------------------------------------------------

#
# Structure de la table `dropdown_processor`
#

DROP TABLE IF EXISTS dropdown_processor;
CREATE TABLE dropdown_processor (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_processor`
#

INSERT INTO dropdown_processor VALUES ('Intel Pentium');
INSERT INTO dropdown_processor VALUES ('Intel Pentium II');
INSERT INTO dropdown_processor VALUES ('AMD K6-1');
INSERT INTO dropdown_processor VALUES ('AMD K6-2');
INSERT INTO dropdown_processor VALUES ('AMD K6-3');
INSERT INTO dropdown_processor VALUES ('PowerPC G3');
INSERT INTO dropdown_processor VALUES ('Intel Pentium III');
INSERT INTO dropdown_processor VALUES ('AMD Athlon');
INSERT INTO dropdown_processor VALUES ('68k (Motorola)');
INSERT INTO dropdown_processor VALUES ('486 SX');
INSERT INTO dropdown_processor VALUES ('486 DX');
INSERT INTO dropdown_processor VALUES ('486 DX2/4');
INSERT INTO dropdown_processor VALUES ('Intel Itanium');
INSERT INTO dropdown_processor VALUES ('PowerPC G4');
INSERT INTO dropdown_processor VALUES ('RS3000');
INSERT INTO dropdown_processor VALUES ('RS10k');
INSERT INTO dropdown_processor VALUES ('Alpha EV6.7');
INSERT INTO dropdown_processor VALUES ('PowerPC 603ev');
INSERT INTO dropdown_processor VALUES ('PowerPC 603');
INSERT INTO dropdown_processor VALUES ('PowerPC 601');
INSERT INTO dropdown_processor VALUES ('68040');
INSERT INTO dropdown_processor VALUES ('68040');
INSERT INTO dropdown_processor VALUES ('ULTRASparc II');
INSERT INTO dropdown_processor VALUES ('Intel Pentium IV');
INSERT INTO dropdown_processor VALUES ('AMD Athlon');
INSERT INTO dropdown_processor VALUES ('AMD Duron');
# --------------------------------------------------------

#
# Structure de la table `dropdown_ram`
#

DROP TABLE IF EXISTS dropdown_ram;
CREATE TABLE dropdown_ram (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_ram`
#

INSERT INTO dropdown_ram VALUES ('36pin SIMMS');
INSERT INTO dropdown_ram VALUES ('72pin SIMMS (Fast Page)');
INSERT INTO dropdown_ram VALUES ('72pin SIMMS (EDO)');
INSERT INTO dropdown_ram VALUES ('Unbuffered DIMMs');
INSERT INTO dropdown_ram VALUES ('DIMMs w/EEPROM');
INSERT INTO dropdown_ram VALUES ('SDRAM DIMMs (<10ns)');
INSERT INTO dropdown_ram VALUES ('ECC DIMMs');
INSERT INTO dropdown_ram VALUES ('Other');
INSERT INTO dropdown_ram VALUES ('iMac DIMMS');
# --------------------------------------------------------

#
# Structure de la table `dropdown_sndcard`
#

DROP TABLE IF EXISTS dropdown_sndcard;
CREATE TABLE dropdown_sndcard (
  name varchar(255) NOT NULL default ''
) TYPE=MyISAM;

#
# Contenu de la table `dropdown_sndcard`
#

INSERT INTO dropdown_sndcard VALUES ('Soundblaster 128 PCI');
INSERT INTO dropdown_sndcard VALUES ('Soundblaster 16 PnP');
INSERT INTO dropdown_sndcard VALUES ('integrated');
# --------------------------------------------------------

#
# Structure de la table `event_log`
#

DROP TABLE IF EXISTS event_log;
CREATE TABLE event_log (
  ID int(11) NOT NULL auto_increment,
  item int(11) NOT NULL default '0',
  itemtype varchar(10) NOT NULL default '',
  date datetime NOT NULL default '0000-00-00 00:00:00',
  service varchar(20) default NULL,
  level tinyint(4) NOT NULL default '0',
  message text NOT NULL,
  PRIMARY KEY  (ID),
  KEY comp (item),
  KEY date (date)
) TYPE=MyISAM;

#
# Contenu de la table `event_log`
#

INSERT INTO event_log VALUES (181, -1, 'system', '2003-09-13 19:49:29', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (180, -1, 'system', '2003-09-13 19:48:33', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (179, -1, 'system', '2003-09-13 19:46:17', 'login', 1, 'failed login: fds');
INSERT INTO event_log VALUES (178, -1, 'system', '2003-09-13 19:46:10', 'login', 1, 'failed login: gfd');
INSERT INTO event_log VALUES (177, -1, 'system', '2003-09-13 19:32:36', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (176, -1, 'system', '2003-09-13 18:18:28', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (174, 0, 'Templates', '2003-09-13 17:54:01', 'setup', 5, 'glpi deleted template .');
INSERT INTO event_log VALUES (175, -1, 'system', '2003-09-13 17:54:13', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (173, 0, 'Templates', '2003-09-13 17:53:57', 'setup', 5, 'glpi added template test.');
INSERT INTO event_log VALUES (172, 0, 'Templates', '2003-09-13 17:53:53', 'setup', 5, 'glpi added template .');
INSERT INTO event_log VALUES (171, 0, 'computers', '2003-09-13 17:53:28', 'inventory', 4, 'glpi added .');
INSERT INTO event_log VALUES (170, -1, 'system', '2003-09-13 17:53:13', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (169, -1, 'system', '2003-09-13 17:52:52', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (168, -1, 'system', '2003-09-13 17:52:44', 'login', 1, 'failed login: glpi');
INSERT INTO event_log VALUES (167, -1, 'system', '2003-09-13 17:51:41', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (166, -1, 'system', '2003-09-13 17:51:23', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (182, -1, 'system', '2003-09-14 01:44:20', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (183, -1, 'system', '2003-09-14 03:01:19', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (184, -1, 'system', '2003-09-14 03:26:38', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (185, 0, 'Templates', '2003-09-14 03:29:00', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (186, 0, 'Templates', '2003-09-14 03:29:03', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (187, 0, 'Templates', '2003-09-14 03:29:06', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (188, 0, 'Templates', '2003-09-14 03:29:08', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (189, 0, 'Templates', '2003-09-14 03:29:10', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (190, 0, 'Templates', '2003-09-14 03:30:17', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (191, 0, 'Templates', '2003-09-14 03:30:18', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (192, 0, 'Templates', '2003-09-14 03:30:23', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (193, 0, 'Templates', '2003-09-14 03:30:24', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (194, 0, 'Templates', '2003-09-14 03:30:25', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (195, 0, 'Templates', '2003-09-14 03:30:27', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (196, 0, 'Templates', '2003-09-14 03:31:38', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (197, 0, 'Templates', '2003-09-14 03:31:40', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (198, 0, 'Templates', '2003-09-14 03:33:46', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (199, 0, 'Templates', '2003-09-14 03:34:28', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (200, 0, 'Templates', '2003-09-14 03:34:32', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (201, 0, 'Templates', '2003-09-14 03:34:34', 'setup', 5, 'baaz updated template 2.');
INSERT INTO event_log VALUES (202, 0, 'Templates', '2003-09-14 03:34:39', 'setup', 5, 'baaz deleted template .');
INSERT INTO event_log VALUES (203, 0, 'Templates', '2003-09-14 03:34:43', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (204, 0, 'Templates', '2003-09-14 03:34:46', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (205, 0, 'Templates', '2003-09-14 03:34:47', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (206, 0, 'Templates', '2003-09-14 03:34:47', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (207, 0, 'Templates', '2003-09-14 03:34:48', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (208, 0, 'Templates', '2003-09-14 03:34:52', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (209, 0, 'Templates', '2003-09-14 03:34:52', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (210, 0, 'Templates', '2003-09-14 03:34:53', 'setup', 5, 'baaz updated template 7.');
INSERT INTO event_log VALUES (211, -1, 'system', '2003-09-15 01:19:52', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (212, -1, 'system', '2003-09-15 01:57:59', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (213, -1, 'system', '2003-09-15 01:59:26', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (214, -1, 'system', '2003-09-15 02:02:22', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (215, 0, 'users', '2003-09-15 02:02:32', 'setup', 5, 'glpi updated user glpi.');
INSERT INTO event_log VALUES (216, -1, 'system', '2003-09-15 02:02:41', 'login', 1, 'failed login: glpi');
INSERT INTO event_log VALUES (217, -1, 'system', '2003-09-15 02:02:49', 'login', 1, 'failed login: glpi');
INSERT INTO event_log VALUES (218, -1, 'system', '2003-09-15 02:02:55', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (219, 0, 'users', '2003-09-15 02:03:05', 'setup', 5, 'baaz updated user glpi.');
INSERT INTO event_log VALUES (220, -1, 'system', '2003-09-15 02:04:18', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (221, 18, 'computers', '2003-09-15 02:06:03', 'tracking', 4, 'glpi added new job.');
INSERT INTO event_log VALUES (222, -1, 'system', '2003-09-15 02:51:43', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (223, -1, 'system', '2003-09-15 02:58:22', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (224, 0, 'users', '2003-09-15 03:36:54', 'setup', 5, 'baaz updated user glpi.');
INSERT INTO event_log VALUES (225, 0, 'users', '2003-09-15 03:36:57', 'setup', 5, 'baaz updated user baaz.');
INSERT INTO event_log VALUES (226, 0, 'users', '2003-09-15 03:40:45', 'setup', 5, 'baaz updated user .');
INSERT INTO event_log VALUES (227, 0, 'users', '2003-09-15 03:50:57', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (228, 0, 'users', '2003-09-15 03:51:01', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (229, 0, 'users', '2003-09-15 03:51:17', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (230, 0, 'users', '2003-09-15 03:51:18', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (231, 0, 'users', '2003-09-15 03:51:18', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (232, 0, 'users', '2003-09-15 03:51:19', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (233, 0, 'users', '2003-09-15 03:51:22', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (234, 0, 'users', '2003-09-15 03:51:26', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (235, 0, 'users', '2003-09-15 03:51:31', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (236, 0, 'users', '2003-09-15 03:51:33', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (237, 0, 'users', '2003-09-15 03:51:36', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (238, 0, 'users', '2003-09-15 03:51:38', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (239, 0, 'users', '2003-09-15 03:51:42', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (240, 0, 'users', '2003-09-15 03:51:44', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (241, 0, 'users', '2003-09-15 03:51:45', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (242, 0, 'users', '2003-09-15 03:51:59', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (243, 0, 'users', '2003-09-15 03:52:42', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (244, 0, 'users', '2003-09-15 03:52:46', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (245, 0, 'users', '2003-09-15 03:52:48', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (246, 0, 'users', '2003-09-15 03:52:51', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (247, 0, 'users', '2003-09-15 03:53:14', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (248, 0, 'users', '2003-09-15 03:54:46', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (249, 0, 'users', '2003-09-15 03:55:58', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (250, 0, 'users', '2003-09-15 03:56:20', 'setup', 4, 'baaz added user .');
INSERT INTO event_log VALUES (251, 0, 'users', '2003-09-15 03:56:23', 'setup', 4, 'baaz deleted user .');
INSERT INTO event_log VALUES (252, 0, 'users', '2003-09-15 03:56:30', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (253, 0, 'users', '2003-09-15 03:56:33', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (254, 0, 'users', '2003-09-15 03:56:35', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (255, 0, 'users', '2003-09-15 03:56:38', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (256, 0, 'users', '2003-09-15 04:01:20', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (257, 0, 'users', '2003-09-15 04:02:08', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (258, 0, 'users', '2003-09-15 04:04:57', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (259, 0, 'users', '2003-09-15 04:05:26', 'setup', 5, 'baaz updated user baaz.');
INSERT INTO event_log VALUES (260, -1, 'system', '2003-09-15 04:05:37', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (261, 0, 'users', '2003-09-15 04:05:52', 'setup', 5, 'glpi updated user baaz.');
INSERT INTO event_log VALUES (262, -1, 'system', '2003-09-15 04:06:00', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (263, 0, 'users', '2003-09-15 04:07:38', 'setup', 5, 'baaz updated user glpi.');
INSERT INTO event_log VALUES (264, 0, 'users', '2003-09-15 04:07:42', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (265, 0, 'users', '2003-09-15 04:07:45', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (266, 0, 'users', '2003-09-15 04:07:47', 'setup', 5, 'baaz updated user baaz.');
INSERT INTO event_log VALUES (267, 0, 'users', '2003-09-15 04:09:43', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (268, 0, 'users', '2003-09-15 04:09:46', 'setup', 5, 'baaz updated user glpi.');
INSERT INTO event_log VALUES (269, -1, 'system', '2003-09-15 04:10:07', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (270, -1, 'system', '2003-09-15 04:12:13', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (271, -1, 'system', '2003-09-15 21:18:33', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (272, -1, 'system', '2003-09-15 21:25:50', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (273, -1, 'system', '2003-09-15 21:57:27', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (274, -1, 'system', '2003-09-15 21:57:38', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (275, -1, 'system', '2003-09-15 22:01:31', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (276, -1, 'system', '2003-09-15 22:02:04', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (277, -1, 'system', '2003-09-15 22:02:40', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (278, -1, 'system', '2003-09-15 22:03:09', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (279, -1, 'system', '2003-09-16 01:07:10', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (280, -1, 'system', '2003-09-16 23:08:45', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (281, -1, 'system', '2003-09-16 23:33:18', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (282, 0, 'users', '2003-09-17 00:24:54', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (283, 0, 'users', '2003-09-17 00:29:28', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (284, 0, 'users', '2003-09-17 00:30:38', 'setup', 5, 'baaz updated user baaz.');
INSERT INTO event_log VALUES (285, 0, 'users', '2003-09-17 00:32:22', 'setup', 5, 'baaz updated user louis.');
INSERT INTO event_log VALUES (286, 0, 'users', '2003-09-17 00:32:27', 'setup', 5, 'baaz updated user baaz.');
INSERT INTO event_log VALUES (287, 0, 'users', '2003-09-17 00:32:29', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (288, 0, 'users', '2003-09-17 00:32:32', 'setup', 5, 'baaz updated user jean.');
INSERT INTO event_log VALUES (289, 18, 'computers', '2003-09-17 00:40:32', 'tracking', 4, 'baaz added new job.');
INSERT INTO event_log VALUES (290, -1, 'system', '2003-09-17 00:41:06', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (291, -1, 'system', '2003-09-17 00:49:27', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (292, -1, 'system', '2003-09-17 01:10:16', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (293, -1, 'system', '2003-09-17 01:44:00', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (294, -1, 'system', '2003-09-17 02:45:25', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (295, -1, 'system', '2003-09-17 03:43:44', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (296, -1, 'system', '2003-09-17 20:30:54', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (297, -1, 'system', '2003-09-17 23:01:11', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (298, -1, 'system', '2003-09-17 23:09:40', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (299, -1, 'system', '2003-09-17 23:19:21', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (300, -1, 'system', '2003-09-18 00:03:11', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (301, 18, 'computers', '2003-09-18 00:05:07', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (302, 8, 'computers', '2003-09-18 00:06:33', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (303, 10, 'computers', '2003-09-18 00:07:58', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (304, 15, 'computers', '2003-09-18 00:09:47', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (305, 8, 'computers', '2003-09-18 00:10:06', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (306, 7, 'networking', '2003-09-18 00:10:54', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (307, 6, 'networking', '2003-09-18 00:11:26', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (308, 8, 'networking', '2003-09-18 00:11:30', 'inventory', 4, 'baaz deleted item.');
INSERT INTO event_log VALUES (309, 1, 'printers', '2003-09-18 00:12:43', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (310, 2, 'printers', '2003-09-18 00:13:11', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (311, 3, 'monitors', '2003-09-18 00:14:14', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (312, 2, 'monitors', '2003-09-18 00:14:50', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (313, 8, 'computers', '2003-09-18 00:15:44', 'inventory', 4, 'baaz updated item.');
INSERT INTO event_log VALUES (314, 0, 'networking', '2003-09-18 00:17:37', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (315, 0, 'networking', '2003-09-18 00:18:16', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (316, 0, 'networking', '2003-09-18 00:20:56', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (317, 0, 'networking', '2003-09-18 00:21:55', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (318, 0, 'networking', '2003-09-18 00:23:31', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (319, 6, 'networking', '2003-09-18 00:24:55', 'inventory', 4, 'baaz deleted item.');
INSERT INTO event_log VALUES (320, 7, 'networking', '2003-09-18 00:25:00', 'inventory', 4, 'baaz deleted item.');
INSERT INTO event_log VALUES (321, -1, 'system', '2003-09-18 00:27:38', 'login', 3, 'baaz logged in.');
INSERT INTO event_log VALUES (322, 0, 'networking', '2003-09-18 00:28:17', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (323, 0, 'networking', '2003-09-18 00:29:08', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (324, 0, 'networking', '2003-09-18 00:29:52', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (325, 0, 'networking', '2003-09-18 00:30:34', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (326, 0, 'networking', '2003-09-18 00:31:03', 'inventory', 4, 'baaz added item name Dlink 450.');
INSERT INTO event_log VALUES (327, 0, 'networking', '2003-09-18 00:31:28', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (328, 0, 'networking', '2003-09-18 00:32:49', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (329, 0, 'networking', '2003-09-18 00:33:36', 'inventory', 5, 'baaz added networking port.');
INSERT INTO event_log VALUES (330, 0, 'users', '2003-09-18 00:35:17', 'setup', 4, 'baaz deleted user baaz.');
INSERT INTO event_log VALUES (331, -1, 'system', '2003-09-18 00:35:23', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (332, 0, 'users', '2003-09-18 00:35:33', 'setup', 4, 'glpi deleted user louis.');
INSERT INTO event_log VALUES (333, 0, 'users', '2003-09-18 00:35:39', 'setup', 4, 'glpi deleted user jean.');
INSERT INTO event_log VALUES (334, 0, 'users', '2003-09-18 00:35:58', 'setup', 4, 'glpi added user Tech.');
INSERT INTO event_log VALUES (335, 0, 'users', '2003-09-18 00:36:14', 'setup', 5, 'glpi updated user Tech.');
INSERT INTO event_log VALUES (336, 0, 'users', '2003-09-18 00:37:17', 'setup', 4, 'glpi added user normal.');
INSERT INTO event_log VALUES (337, 0, 'users', '2003-09-18 00:37:24', 'setup', 4, 'glpi deleted user Tech.');
INSERT INTO event_log VALUES (338, 0, 'users', '2003-09-18 00:37:35', 'setup', 4, 'glpi added user tech.');
INSERT INTO event_log VALUES (339, 0, 'users', '2003-09-18 00:37:44', 'setup', 5, 'glpi updated user tech.');
INSERT INTO event_log VALUES (340, 0, 'users', '2003-09-18 00:37:47', 'setup', 5, 'glpi updated user normal.');
INSERT INTO event_log VALUES (341, 0, 'users', '2003-09-18 00:38:17', 'setup', 4, 'glpi added user post-only.');
INSERT INTO event_log VALUES (342, 0, 'users', '2003-09-18 00:38:19', 'setup', 5, 'glpi updated user post-only.');
INSERT INTO event_log VALUES (343, 0, 'users', '2003-09-18 00:38:27', 'setup', 5, 'glpi updated user post-only.');
INSERT INTO event_log VALUES (344, 0, 'users', '2003-09-18 00:38:36', 'setup', 5, 'glpi updated user post-only.');
INSERT INTO event_log VALUES (345, 0, 'users', '2003-09-18 00:38:44', 'setup', 5, 'glpi updated user glpi.');
INSERT INTO event_log VALUES (346, 0, 'dropdowns', '2003-09-18 00:39:24', 'setup', 5, 'glpi added a value to a dropdown.');
INSERT INTO event_log VALUES (347, 0, 'dropdowns', '2003-09-18 00:39:43', 'setup', 5, 'glpi added a value to a dropdown.');
INSERT INTO event_log VALUES (348, 0, 'dropdowns', '2003-09-18 00:39:56', 'setup', 5, 'glpi added a value to a dropdown.');
INSERT INTO event_log VALUES (349, 0, 'networking', '2003-09-18 00:40:42', 'inventory', 4, 'glpi added item name test.');
INSERT INTO event_log VALUES (350, 0, 'networking', '2003-09-18 00:40:49', 'inventory', 5, 'glpi added networking port.');
INSERT INTO event_log VALUES (351, 10, 'networking', '2003-09-18 00:40:58', 'inventory', 4, 'glpi deleted item.');
INSERT INTO event_log VALUES (352, -1, 'system', '2003-09-18 00:41:33', 'login', 3, 'tech logged in.');
INSERT INTO event_log VALUES (353, 8, 'computers', '2003-09-18 00:46:40', 'tracking', 4, 'Helpdesk added new job.');
INSERT INTO event_log VALUES (354, 10, 'computers', '2003-09-18 00:48:19', 'tracking', 4, 'Helpdesk added new job.');
INSERT INTO event_log VALUES (355, 18, 'computers', '2003-09-18 00:49:29', 'tracking', 4, 'Helpdesk added new job.');
INSERT INTO event_log VALUES (356, -1, 'system', '2003-09-18 00:51:31', 'login', 3, 'glpi logged in.');
INSERT INTO event_log VALUES (357, -1, 'system', '2003-09-18 00:52:43', 'login', 3, 'tech logged in.');
INSERT INTO event_log VALUES (358, 8, 'computers', '2003-09-18 00:53:35', 'tracking', 4, 'tech added followup to job 1.');
INSERT INTO event_log VALUES (359, 8, 'computers', '2003-09-18 00:54:06', 'tracking', 4, 'tech added followup to job 1.');
INSERT INTO event_log VALUES (360, 8, 'computers', '2003-09-18 00:54:41', 'tracking', 4, 'tech added followup to job 1.');
INSERT INTO event_log VALUES (361, 18, 'computers', '2003-09-18 00:55:08', 'tracking', 4, 'tech added followup to job 3.');
INSERT INTO event_log VALUES (362, 10, 'computers', '2003-09-18 00:55:52', 'tracking', 4, 'tech added followup to job 2.');
# --------------------------------------------------------

#
# Structure de la table `followups`
#

DROP TABLE IF EXISTS followups;
CREATE TABLE followups (
  ID int(11) NOT NULL auto_increment,
  tracking int(11) default NULL,
  date datetime default NULL,
  author varchar(200) default NULL,
  contents text,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `followups`
#

INSERT INTO followups VALUES (1, 1, '2003-09-18 00:53:35', 'tech', 'J\\\'ai été voir, je pense que la carte mere a grillé.');
INSERT INTO followups VALUES (2, 1, '2003-09-18 00:54:06', 'tech', 'L\\\'alimentation est foutue, je vais tester la carte mere pour voir si elle est recuperable');
INSERT INTO followups VALUES (3, 1, '2003-09-18 00:54:40', 'tech', 'Probleme reglé j\\\'ai seulement changé l\\\'alimentation.\r\nLe reste fonctionne tres bien.');
INSERT INTO followups VALUES (4, 3, '2003-09-18 00:55:08', 'tech', 'Je pense que l\\\'on peux changer la souris.');
INSERT INTO followups VALUES (5, 2, '2003-09-18 00:55:52', 'tech', 'Je suis passé, il faudra faire une restauration de windows NT4.');
# --------------------------------------------------------

#
# Structure de la table `inst_software`
#

DROP TABLE IF EXISTS inst_software;
CREATE TABLE inst_software (
  ID int(11) NOT NULL auto_increment,
  cID int(11) NOT NULL default '0',
  license int(11) NOT NULL default '0',
  PRIMARY KEY  (ID),
  KEY cID (cID),
  KEY sID (license)
) TYPE=MyISAM;

#
# Contenu de la table `inst_software`
#

INSERT INTO inst_software VALUES (2, 10, 7);
INSERT INTO inst_software VALUES (1, 8, 8);
INSERT INTO inst_software VALUES (3, 8, 6);
INSERT INTO inst_software VALUES (4, 8, 9);
# --------------------------------------------------------

#
# Structure de la table `licenses`
#

DROP TABLE IF EXISTS licenses;
CREATE TABLE licenses (
  ID int(15) NOT NULL auto_increment,
  sID int(15) NOT NULL default '0',
  serial varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `licenses`
#

INSERT INTO licenses VALUES (7, 4, '12-aa-asd-12-aa');
INSERT INTO licenses VALUES (6, 4, 'asd-asdf-asdf-12');
INSERT INTO licenses VALUES (4, 4, 'au-23-as-23-cd');
INSERT INTO licenses VALUES (5, 4, 'qw-as-23-0k-23-dg');
INSERT INTO licenses VALUES (8, 3, 'free');
INSERT INTO licenses VALUES (9, 5, 'free');
# --------------------------------------------------------

#
# Structure de la table `monitors`
#

DROP TABLE IF EXISTS monitors;
CREATE TABLE monitors (
  ID int(10) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  type varchar(255) NOT NULL default '',
  location varchar(255) NOT NULL default '',
  contact varchar(255) NOT NULL default '',
  contact_num varchar(255) NOT NULL default '',
  comments text NOT NULL,
  serial varchar(255) NOT NULL default '',
  otherserial varchar(255) NOT NULL default '',
  size int(3) NOT NULL default '0',
  flags_micro tinyint(4) NOT NULL default '0',
  flags_speaker tinyint(4) NOT NULL default '0',
  flags_subd tinyint(4) NOT NULL default '0',
  flags_bnc tinyint(4) NOT NULL default '0',
  achat_date date default NULL,
  date_fin_garantie date default NULL,
  maintenance int(2) default '0',
  PRIMARY KEY  (ID),
  KEY ID (ID)
) TYPE=MyISAM;

#
# Contenu de la table `monitors`
#

INSERT INTO monitors VALUES (3, 'nokia 20\'', '2003-09-18 00:14:14', 'Nokia 445Xav', '1 ier etage', '', '', 'Ecran infographiste', '', '', 20, 1, 1, 1, 0, '0000-00-00', '0000-00-00', 0);
INSERT INTO monitors VALUES (2, 'Sony 19\'', '2003-09-18 00:14:50', 'Sony 200sf', '1 ier etage', '', '', 'ecran documentation', '', '', 19, 0, 0, 1, 1, '0000-00-00', '0000-00-00', 0);
# --------------------------------------------------------

#
# Structure de la table `networking`
#

DROP TABLE IF EXISTS networking;
CREATE TABLE networking (
  ID int(11) NOT NULL auto_increment,
  name varchar(30) NOT NULL default '',
  type varchar(30) NOT NULL default '',
  ram varchar(10) default NULL,
  location varchar(40) NOT NULL default '',
  serial varchar(50) default NULL,
  otherserial varchar(50) default NULL,
  contact varchar(30) NOT NULL default '',
  contact_num varchar(30) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  comments text NOT NULL,
  achat_date date default NULL,
  date_fin_garantie date default NULL,
  maintenance int(2) default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `networking`
#

INSERT INTO networking VALUES (9, 'Dlink 450', 'Dlink Switch', NULL, '1 ier etage', '4586-puis-kioe', NULL, '', '', '0000-00-00 00:00:00', '', '0000-00-00', '0000-00-00', 0);
# --------------------------------------------------------

#
# Structure de la table `networking_ports`
#

DROP TABLE IF EXISTS networking_ports;
CREATE TABLE networking_ports (
  ID int(11) NOT NULL auto_increment,
  on_device int(11) NOT NULL default '0',
  device_type tinyint(4) NOT NULL default '0',
  iface char(40) NOT NULL default '',
  logical_number int(11) NOT NULL default '0',
  name char(30) NOT NULL default '',
  ifaddr char(30) NOT NULL default '',
  ifmac char(30) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `networking_ports`
#

INSERT INTO networking_ports VALUES (1, 8, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.26', '');
INSERT INTO networking_ports VALUES (2, 10, 1, '100Mbps Ethernet (UTP)', 1, '3com', '10.10.0.27', '');
INSERT INTO networking_ports VALUES (3, 15, 1, '100Mbps Ethernet (UTP)', 1, 'Generic', '10.10.0.28', '');
INSERT INTO networking_ports VALUES (4, 18, 1, '100Mbps Ethernet (UTP)', 1, '3Com', '10.10.0.29', '');
INSERT INTO networking_ports VALUES (5, 9, 2, '100Mbps Ethernet (UTP)', 1, 'Dlink port', '10.10.0.1', '');
INSERT INTO networking_ports VALUES (6, 9, 2, '100Mbps Ethernet (UTP)', 2, 'Dlink port', '10.10.0.1', '');
INSERT INTO networking_ports VALUES (7, 9, 2, '100Mbps Ethernet (UTP)', 3, 'Dlink port', '10.10.0.1', '');
INSERT INTO networking_ports VALUES (8, 10, 2, 'Frame Relay', 0, '', '', '');
# --------------------------------------------------------

#
# Structure de la table `networking_wire`
#

DROP TABLE IF EXISTS networking_wire;
CREATE TABLE networking_wire (
  ID int(11) NOT NULL auto_increment,
  end1 int(11) NOT NULL default '0',
  end2 int(11) NOT NULL default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `networking_wire`
#

INSERT INTO networking_wire VALUES (1, 5, 1);
INSERT INTO networking_wire VALUES (2, 6, 2);
INSERT INTO networking_wire VALUES (3, 7, 3);
# --------------------------------------------------------

#
# Structure de la table `prefs`
#

DROP TABLE IF EXISTS prefs;
CREATE TABLE prefs (
  user varchar(80) NOT NULL default '',
  tracking_order enum('no','yes') NOT NULL default 'no',
  language varchar(255) NOT NULL default '',
  PRIMARY KEY  (user)
) TYPE=MyISAM;

#
# Contenu de la table `prefs`
#

INSERT INTO prefs VALUES ('glpi', 'yes', 'french');
INSERT INTO prefs VALUES ('Helpdesk', 'no', 'french');
INSERT INTO prefs VALUES ('normal', '', 'english');
INSERT INTO prefs VALUES ('tech', 'yes', 'french');
INSERT INTO prefs VALUES ('post-only', '', 'english');
# --------------------------------------------------------

#
# Structure de la table `printers`
#

DROP TABLE IF EXISTS printers;
CREATE TABLE printers (
  ID int(10) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  type varchar(255) NOT NULL default '',
  location varchar(255) NOT NULL default '',
  contact varchar(255) NOT NULL default '',
  contact_num varchar(255) NOT NULL default '',
  serial varchar(255) NOT NULL default '',
  otherserial varchar(255) NOT NULL default '',
  flags_serial tinyint(4) NOT NULL default '0',
  flags_par tinyint(4) NOT NULL default '0',
  comments text NOT NULL,
  achat_date date default NULL,
  date_fin_garantie date default NULL,
  maintenance int(2) default '0',
  PRIMARY KEY  (ID),
  KEY id (ID)
) TYPE=MyISAM;

#
# Contenu de la table `printers`
#

INSERT INTO printers VALUES (1, 'HP laser', '2003-09-18 00:12:43', 'HP Laserjet 4050N', '1 ier etage', '', '', 'hp-jsgsj-658', '', 0, 1, 'Imprimante bureau du directeur', '0000-00-00', '0000-00-00', 0);
INSERT INTO printers VALUES (2, 'HP deskjet', '2003-09-18 00:13:11', 'HP Deskjet 850c', '2nd etage', '', '', '45dskjs-ds', '', 0, 1, 'Imprimante documentation', '0000-00-00', '0000-00-00', 0);
# --------------------------------------------------------

#
# Structure de la table `software`
#

DROP TABLE IF EXISTS software;
CREATE TABLE software (
  ID int(11) NOT NULL auto_increment,
  name varchar(200) default NULL,
  platform varchar(200) default NULL,
  version varchar(20) default NULL,
  location varchar(200) default NULL,
  comments text,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `software`
#

INSERT INTO software VALUES (3, 'Acrobat PDF Viewer', 'Windows NT 4.0', '4', 'Admin', NULL);
INSERT INTO software VALUES (4, 'MS Windows NT', 'Windows NT 4.0', '4.0', 'Admin', NULL);
INSERT INTO software VALUES (5, 'Latex', 'Linux (Redhat 6.2)', '6.2', '1 ier etage', 'Latex');
# --------------------------------------------------------

#
# Structure de la table `templates`
#

DROP TABLE IF EXISTS templates;
CREATE TABLE templates (
  ID int(11) NOT NULL auto_increment,
  templname varchar(200) default NULL,
  name varchar(200) default NULL,
  type varchar(200) default NULL,
  os varchar(200) default NULL,
  osver varchar(20) default NULL,
  processor varchar(200) default NULL,
  processor_speed varchar(100) default NULL,
  location varchar(200) default NULL,
  serial varchar(200) default NULL,
  otherserial varchar(200) default NULL,
  ramtype varchar(200) default NULL,
  ram varchar(20) default NULL,
  network varchar(200) default NULL,
  hdspace varchar(10) default NULL,
  contact varchar(200) default NULL,
  contact_num varchar(200) default NULL,
  comments text,
  moboard varchar(255) NOT NULL default '',
  sndcard varchar(255) NOT NULL default '',
  gfxcard varchar(255) NOT NULL default '',
  hdtype varchar(255) NOT NULL default '',
  achat_date date default NULL,
  date_fin_garantie date default NULL,
  maintenance int(2) default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `templates`
#

INSERT INTO templates VALUES (1, 'Blank Template', '', 'Generic x86 PC', 'Linux (Redhat 6.2)', '', '486 DX', '', 'Admin', '', '', '36pin SIMMS', '', '3Com (100Mbps)', '', '', '', 'Empty Template', 'Asus P2BX', 'Soundblaster 128 PCI', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
INSERT INTO templates VALUES (3, 'iMac', '', 'iMac 2nd Generation', 'MacOS', '9', 'PowerPC G3', '333', 'Admin', '', '', 'iMac DIMMS', '128', 'Generic 100Mbps Card', '6', '', '', 'Standard iMac', 'unknown', 'integrated', 'integrated', 'IBM DTTA 35101', '0000-00-00', '0000-00-00', 0);
INSERT INTO templates VALUES (7, 'test', NULL, 'Generic x86 PC', 'Debian woody 3.0', NULL, '486 DX', NULL, '1 ier etage', NULL, NULL, '36pin SIMMS', NULL, '3Com (100Mbps)', NULL, NULL, NULL, NULL, 'Asus P2BX', 'integrated', 'ATI Rage Pro 3D AGP', 'IBM DCAS 34330', '0000-00-00', '0000-00-00', 0);
# --------------------------------------------------------

#
# Structure de la table `tracking`
#

DROP TABLE IF EXISTS tracking;
CREATE TABLE tracking (
  ID int(11) NOT NULL auto_increment,
  date datetime default NULL,
  closedate datetime NOT NULL default '0000-00-00 00:00:00',
  status enum('new','old') default NULL,
  author varchar(200) default NULL,
  assign varchar(200) default NULL,
  computer int(11) default NULL,
  contents text,
  priority tinyint(4) NOT NULL default '1',
  is_group enum('no','yes') NOT NULL default 'no',
  uemail varchar(100) default NULL,
  emailupdates varchar(4) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Contenu de la table `tracking`
#

INSERT INTO tracking VALUES (1, '2003-09-18 00:46:40', '2003-09-18 00:54:43', 'old', 'Helpdesk', 'tech', 8, 'Mon ordinateur ne s\\\'allume plus, et il ya des bruits byzarres', 3, 'no', '', '');
INSERT INTO tracking VALUES (2, '2003-09-18 00:48:19', '0000-00-00 00:00:00', 'new', 'Helpdesk', 'tech', 10, 'Un message en anglais s\\\'affiche, je n\\\'y comprend rien, je n\\\'ose plus toucher à rien de peur de tout casser.\r\nVenez vite !!!!', 4, 'no', '', '');
INSERT INTO tracking VALUES (3, '2003-09-18 00:49:29', '0000-00-00 00:00:00', 'new', 'Helpdesk', 'tech', 18, 'Ma souris se bloque sans arret, elle defile mal sur l\\\'ecran et elle glisse tres mal sur le tapis de souris.', 3, 'no', '', '');
# --------------------------------------------------------

#
# Structure de la table `type_computers`
#

DROP TABLE IF EXISTS type_computers;
CREATE TABLE type_computers (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_computers`
#

INSERT INTO type_computers VALUES ('Generic x86 PC');
INSERT INTO type_computers VALUES ('PowerMac G4');
INSERT INTO type_computers VALUES ('iMac 2nd Generation');
INSERT INTO type_computers VALUES ('PowerMac G3');
# --------------------------------------------------------

#
# Structure de la table `type_monitors`
#

DROP TABLE IF EXISTS type_monitors;
CREATE TABLE type_monitors (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_monitors`
#

INSERT INTO type_monitors VALUES ('Nokia 445Xav');
INSERT INTO type_monitors VALUES ('Sony 200GDM');
INSERT INTO type_monitors VALUES ('Sony 200sf');
INSERT INTO type_monitors VALUES ('integrated');
# --------------------------------------------------------

#
# Structure de la table `type_networking`
#

DROP TABLE IF EXISTS type_networking;
CREATE TABLE type_networking (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_networking`
#

INSERT INTO type_networking VALUES ('Dlink Switch');
# --------------------------------------------------------

#
# Structure de la table `type_printers`
#

DROP TABLE IF EXISTS type_printers;
CREATE TABLE type_printers (
  name varchar(255) default NULL
) TYPE=MyISAM;

#
# Contenu de la table `type_printers`
#

INSERT INTO type_printers VALUES ('HP Laserjet 4050N');
INSERT INTO type_printers VALUES ('HP Laserjet 4+');
INSERT INTO type_printers VALUES ('HP Deskjet 850c');
# --------------------------------------------------------

#
# Structure de la table `users`
#

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  name varchar(80) NOT NULL default '',
  password varchar(80) default NULL,
  email varchar(80) default NULL,
  location varchar(100) default NULL,
  phone varchar(100) default NULL,
  type enum('normal','admin','post-only') NOT NULL default 'normal',
  realname varchar(255) NOT NULL default '',
  can_assign_job enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (name),
  KEY type (type)
) TYPE=MyISAM;

#
# Contenu de la table `users`
#

INSERT INTO users VALUES ('Helpdesk', '14e43c2d31dcbdd1', NULL, 'Admin', NULL, 'post-only', 'Helpdesk Injector', 'no');
INSERT INTO users VALUES ('glpi', '5b9b1ee2216a5ffe', '', '2nd etage', '', 'admin', 'glpi', 'yes');
INSERT INTO users VALUES ('post-only', '3eb831c67be6aeda', NULL, '1 ier etage', NULL, 'post-only', 'post-only', 'no');
INSERT INTO users VALUES ('tech', '37bd7c4221e8a247', NULL, '2nd etage', NULL, 'admin', 'technicien', 'no');
INSERT INTO users VALUES ('normal', '109e7883561b4202', NULL, '1 ier etage', NULL, 'normal', 'utilisateur normal', 'no');




