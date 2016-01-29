#GLPI Dump database on 2004-11-15 21:42
### Dump table glpi_computers

DROP TABLE IF EXISTS glpi_computers;
CREATE TABLE glpi_computers (
    ID int(11) NOT NULL auto_increment,
    name varchar(200) NOT NULL,
    flags_server tinyint(4) DEFAULT '0' NOT NULL,
    osver varchar(20) NOT NULL,
    processor_speed varchar(30) NOT NULL,
    serial varchar(200) NOT NULL,
    otherserial varchar(200) NOT NULL,
    ram varchar(6) NOT NULL,
    hdspace varchar(6) NOT NULL,
    contact varchar(90) NOT NULL,
    contact_num varchar(90) NOT NULL,
    comments text NOT NULL,
    date_mod datetime,
    achat_date date DEFAULT '0000-00-00' NOT NULL,
    date_fin_garantie date,
    maintenance int(2) DEFAULT '0',
    os int(11),
    hdtype int(11),
    sndcard int(11),
    moboard int(11),
    gfxcard int(11),
    network int(11),
    ramtype int(11),
    location int(11),
    processor int(11),
    type int(11),
   PRIMARY KEY (ID),
   KEY flags (flags_server),
   KEY location (location),
   KEY os (os),
   KEY type (type),
   KEY hdtype (hdtype),
   KEY moboard (moboard),
   KEY gfxcard (gfxcard),
   KEY processor (processor),
   KEY ramtype (ramtype),
   KEY network (network),
   KEY sndcard (sndcard),
   KEY maintenance (maintenance)
);

INSERT INTO glpi_computers VALUES ('8','Dell Inspiron 450','0','','750','4586-sd6-fds','','512','10','Roger Rabbit','5462','','2003-09-18 00:15:44','0000-00-00','0000-00-00','0','5','2','3','3','4','1','6','1','7','1');
INSERT INTO glpi_computers VALUES ('10','Dell Inspiron 450','0','SP3','750','4598-jhd-545','','128','20','Peter Pan','8565','','2003-09-18 00:07:58','0000-00-00','0000-00-00','0','5','1','3','3','4','1','6','1','7','1');
INSERT INTO glpi_computers VALUES ('15','Dell Inspiron 450','0','SP2','750','4561-hsub-dfsnj','','512','20','Poppins Marry','6545','','2003-09-18 00:09:47','0000-00-00','0000-00-00','0','1','1','3','3','4','5','6','1','7','1');
INSERT INTO glpi_computers VALUES ('18','IBM 945gx','0','','750','9854-5f-4s4f','','128','20','Jeannot Lapin','5465','','2003-09-18 00:05:07','2001-09-24','2002-09-27','0','2','1','3','3','4','1','6','1','7','1');
### Dump table glpi_config

DROP TABLE IF EXISTS glpi_config;
CREATE TABLE glpi_config (
    ID int(11) NOT NULL auto_increment,
    num_of_events varchar(200) NOT NULL,
    jobs_at_login varchar(200) NOT NULL,
    sendexpire varchar(200) NOT NULL,
    cut varchar(200) NOT NULL,
    expire_events varchar(200) NOT NULL,
    list_limit varchar(200) NOT NULL,
    version varchar(200) NOT NULL,
    logotxt varchar(200) NOT NULL,
    root_doc varchar(200) NOT NULL,
    event_loglevel varchar(200) NOT NULL,
    mailing varchar(200) NOT NULL,
    imap_auth_server varchar(200) NOT NULL,
    imap_host varchar(200) NOT NULL,
    ldap_host varchar(200) NOT NULL,
    ldap_basedn varchar(200) NOT NULL,
    ldap_rootdn varchar(200) NOT NULL,
    ldap_pass varchar(200) NOT NULL,
    admin_email varchar(200) NOT NULL,
    mailing_signature varchar(200) NOT NULL,
    mailing_new_admin varchar(200) NOT NULL,
    mailing_followup_admin varchar(200) NOT NULL,
    mailing_finish_admin varchar(200) NOT NULL,
    mailing_new_all_admin varchar(200) NOT NULL,
    mailing_followup_all_admin varchar(200) NOT NULL,
    mailing_finish_all_admin varchar(200) NOT NULL,
    mailing_new_all_normal varchar(200) NOT NULL,
    mailing_followup_all_normal varchar(200) NOT NULL,
    mailing_finish_all_normal varchar(200) NOT NULL,
    mailing_new_attrib varchar(200) NOT NULL,
    mailing_followup_attrib varchar(200) NOT NULL,
    mailing_finish_attrib varchar(200) NOT NULL,
    mailing_new_user varchar(200) NOT NULL,
    mailing_followup_user varchar(200) NOT NULL,
    mailing_finish_user varchar(200) NOT NULL,
    ldap_field_name varchar(200) NOT NULL,
    ldap_field_email varchar(200) NOT NULL,
    ldap_field_location varchar(200) NOT NULL,
    ldap_field_realname varchar(200) NOT NULL,
    ldap_field_phone varchar(200) NOT NULL,
    ldap_condition varchar(255) NOT NULL,
    permit_helpdesk varchar(200) NOT NULL,
    default_language varchar(255) DEFAULT 'french' NOT NULL,
   PRIMARY KEY (ID)
);

INSERT INTO glpi_config VALUES ('1','10','1','1','80','30','15',' 0.42','GLPI powered by indepnet','/glpi','5','0','','','','','','','admsys@xxxxx.fr','SIGNATURE','1','1','1','1','0','0','0','0','0','0','0','0','1','1','1','uid','mail','physicaldeliveryofficename','cn','telephonenumber','','','french');
### Dump table glpi_connect_wire

DROP TABLE IF EXISTS glpi_connect_wire;
CREATE TABLE glpi_connect_wire (
    ID int(11) NOT NULL auto_increment,
    end1 int(11) DEFAULT '0' NOT NULL,
    end2 int(11) DEFAULT '0' NOT NULL,
    type tinyint(4) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE end1_1 (end1, end2, type),
   KEY end1 (end1),
   KEY end2 (end2),
   KEY type (type)
);

### Dump table glpi_dropdown_firmware

DROP TABLE IF EXISTS glpi_dropdown_firmware;
CREATE TABLE glpi_dropdown_firmware (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
);

### Dump table glpi_dropdown_gfxcard

DROP TABLE IF EXISTS glpi_dropdown_gfxcard;
CREATE TABLE glpi_dropdown_gfxcard (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_gfxcard VALUES ('1','ATI Rage Pro 3D AGP');
INSERT INTO glpi_dropdown_gfxcard VALUES ('2','Matrox Millennium G400DH');
INSERT INTO glpi_dropdown_gfxcard VALUES ('3','S3 Trio 64V+');
INSERT INTO glpi_dropdown_gfxcard VALUES ('4','integrated');
### Dump table glpi_dropdown_hdtype

DROP TABLE IF EXISTS glpi_dropdown_hdtype;
CREATE TABLE glpi_dropdown_hdtype (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_hdtype VALUES ('1','IBM DTTA 35101');
INSERT INTO glpi_dropdown_hdtype VALUES ('2','IBM DCAS 34330');
### Dump table glpi_dropdown_iface

DROP TABLE IF EXISTS glpi_dropdown_iface;
CREATE TABLE glpi_dropdown_iface (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_iface VALUES ('1','10Mbps Ethernet (UTP)');
INSERT INTO glpi_dropdown_iface VALUES ('2','100Mbps Ethernet (UTP)');
INSERT INTO glpi_dropdown_iface VALUES ('3','100Base FL');
INSERT INTO glpi_dropdown_iface VALUES ('4','100Mbps FDDI');
INSERT INTO glpi_dropdown_iface VALUES ('5','Frame Relay');
INSERT INTO glpi_dropdown_iface VALUES ('6','ISDN');
INSERT INTO glpi_dropdown_iface VALUES ('7','T1/E1 +');
INSERT INTO glpi_dropdown_iface VALUES ('8','Serial Link');
### Dump table glpi_dropdown_locations

DROP TABLE IF EXISTS glpi_dropdown_locations;
CREATE TABLE glpi_dropdown_locations (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_locations VALUES ('1','1 ier etage');
INSERT INTO glpi_dropdown_locations VALUES ('2','2nd etage');
### Dump table glpi_dropdown_moboard

DROP TABLE IF EXISTS glpi_dropdown_moboard;
CREATE TABLE glpi_dropdown_moboard (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_moboard VALUES ('1','Asus T2P4S');
INSERT INTO glpi_dropdown_moboard VALUES ('2','Asus P2BX');
INSERT INTO glpi_dropdown_moboard VALUES ('3','unknown');
### Dump table glpi_dropdown_netpoint

DROP TABLE IF EXISTS glpi_dropdown_netpoint;
CREATE TABLE glpi_dropdown_netpoint (
    ID int(11) NOT NULL auto_increment,
    location int(11) DEFAULT '0' NOT NULL,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID),
   KEY location (location)
);

### Dump table glpi_dropdown_network

DROP TABLE IF EXISTS glpi_dropdown_network;
CREATE TABLE glpi_dropdown_network (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_network VALUES ('1','3Com (100Mbps)');
INSERT INTO glpi_dropdown_network VALUES ('2','3Com (10Mbps)');
INSERT INTO glpi_dropdown_network VALUES ('3','Intel (100Mbps)');
INSERT INTO glpi_dropdown_network VALUES ('4','Intel (10Mbps)');
INSERT INTO glpi_dropdown_network VALUES ('5','Generic 100Mbps Card');
INSERT INTO glpi_dropdown_network VALUES ('6','Generic 10Mbps Card');
INSERT INTO glpi_dropdown_network VALUES ('7','None');
INSERT INTO glpi_dropdown_network VALUES ('8','AMD 10Mbps');
INSERT INTO glpi_dropdown_network VALUES ('9','Realtek 10Mbps');
INSERT INTO glpi_dropdown_network VALUES ('10','Realtek 100Mbps');
INSERT INTO glpi_dropdown_network VALUES ('11','integrated');
### Dump table glpi_dropdown_os

DROP TABLE IF EXISTS glpi_dropdown_os;
CREATE TABLE glpi_dropdown_os (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_os VALUES ('1','Windows 2000');
INSERT INTO glpi_dropdown_os VALUES ('2','Linux (Redhat 6.2)');
INSERT INTO glpi_dropdown_os VALUES ('3','Linux (Slackware 7)');
INSERT INTO glpi_dropdown_os VALUES ('4','Solaris');
INSERT INTO glpi_dropdown_os VALUES ('5','Windows NT 4.0');
INSERT INTO glpi_dropdown_os VALUES ('6','Windows 95a');
INSERT INTO glpi_dropdown_os VALUES ('7','Other');
INSERT INTO glpi_dropdown_os VALUES ('8','Windows 98');
INSERT INTO glpi_dropdown_os VALUES ('9','MacOS');
INSERT INTO glpi_dropdown_os VALUES ('10','Windows 95 OSR2');
INSERT INTO glpi_dropdown_os VALUES ('11','Windows 98 SR2');
INSERT INTO glpi_dropdown_os VALUES ('12','Debian woody 3.0');
### Dump table glpi_dropdown_processor

DROP TABLE IF EXISTS glpi_dropdown_processor;
CREATE TABLE glpi_dropdown_processor (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_processor VALUES ('1','Intel Pentium');
INSERT INTO glpi_dropdown_processor VALUES ('2','Intel Pentium II');
INSERT INTO glpi_dropdown_processor VALUES ('3','AMD K6-1');
INSERT INTO glpi_dropdown_processor VALUES ('4','AMD K6-2');
INSERT INTO glpi_dropdown_processor VALUES ('5','AMD K6-3');
INSERT INTO glpi_dropdown_processor VALUES ('6','PowerPC G3');
INSERT INTO glpi_dropdown_processor VALUES ('7','Intel Pentium III');
INSERT INTO glpi_dropdown_processor VALUES ('8','AMD Athlon');
INSERT INTO glpi_dropdown_processor VALUES ('9','68k (Motorola)');
INSERT INTO glpi_dropdown_processor VALUES ('10','486 SX');
INSERT INTO glpi_dropdown_processor VALUES ('11','486 DX');
INSERT INTO glpi_dropdown_processor VALUES ('12','486 DX2/4');
INSERT INTO glpi_dropdown_processor VALUES ('13','Intel Itanium');
INSERT INTO glpi_dropdown_processor VALUES ('14','PowerPC G4');
INSERT INTO glpi_dropdown_processor VALUES ('15','RS3000');
INSERT INTO glpi_dropdown_processor VALUES ('16','RS10k');
INSERT INTO glpi_dropdown_processor VALUES ('17','Alpha EV6.7');
INSERT INTO glpi_dropdown_processor VALUES ('18','PowerPC 603ev');
INSERT INTO glpi_dropdown_processor VALUES ('19','PowerPC 603');
INSERT INTO glpi_dropdown_processor VALUES ('20','PowerPC 601');
INSERT INTO glpi_dropdown_processor VALUES ('21','68040');
INSERT INTO glpi_dropdown_processor VALUES ('22','68040');
INSERT INTO glpi_dropdown_processor VALUES ('23','ULTRASparc II');
INSERT INTO glpi_dropdown_processor VALUES ('24','Intel Pentium IV');
INSERT INTO glpi_dropdown_processor VALUES ('25','AMD Athlon');
INSERT INTO glpi_dropdown_processor VALUES ('26','AMD Duron');
### Dump table glpi_dropdown_ram

DROP TABLE IF EXISTS glpi_dropdown_ram;
CREATE TABLE glpi_dropdown_ram (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_ram VALUES ('1','36pin SIMMS');
INSERT INTO glpi_dropdown_ram VALUES ('2','72pin SIMMS (Fast Page)');
INSERT INTO glpi_dropdown_ram VALUES ('3','72pin SIMMS (EDO)');
INSERT INTO glpi_dropdown_ram VALUES ('4','Unbuffered DIMMs');
INSERT INTO glpi_dropdown_ram VALUES ('5','DIMMs w/EEPROM');
INSERT INTO glpi_dropdown_ram VALUES ('6','SDRAM DIMMs (<10ns)');
INSERT INTO glpi_dropdown_ram VALUES ('7','ECC DIMMs');
INSERT INTO glpi_dropdown_ram VALUES ('8','Other');
INSERT INTO glpi_dropdown_ram VALUES ('9','iMac DIMMS');
### Dump table glpi_dropdown_sndcard

DROP TABLE IF EXISTS glpi_dropdown_sndcard;
CREATE TABLE glpi_dropdown_sndcard (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
   PRIMARY KEY (ID)
);

INSERT INTO glpi_dropdown_sndcard VALUES ('1','Soundblaster 128 PCI');
INSERT INTO glpi_dropdown_sndcard VALUES ('2','Soundblaster 16 PnP');
INSERT INTO glpi_dropdown_sndcard VALUES ('3','integrated');
### Dump table glpi_event_log

DROP TABLE IF EXISTS glpi_event_log;
CREATE TABLE glpi_event_log (
    ID int(11) NOT NULL auto_increment,
    item int(11) DEFAULT '0' NOT NULL,
    itemtype varchar(20) NOT NULL,
    date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    service varchar(20),
    level tinyint(4) DEFAULT '0' NOT NULL,
    message text NOT NULL,
   PRIMARY KEY (ID),
   KEY comp (item),
   KEY date (date)
);

INSERT INTO glpi_event_log VALUES ('363','-1','system','2004-11-15 21:39:47','login','3','glpi logged in.');
INSERT INTO glpi_event_log VALUES ('364','0','software','2004-11-15 21:39:58','inventory','4','glpi added reservation item 1-10.');
INSERT INTO glpi_event_log VALUES ('365','0','software','2004-11-15 21:41:22','inventory','4','glpi update reservation comment.');
### Dump table glpi_followups

DROP TABLE IF EXISTS glpi_followups;
CREATE TABLE glpi_followups (
    ID int(11) NOT NULL auto_increment,
    tracking int(11),
    date datetime,
    author varchar(200),
    contents text,
   PRIMARY KEY (ID),
   KEY tracking (tracking),
   KEY author (author)
);

INSERT INTO glpi_followups VALUES ('1','1','2003-09-18 00:53:35','tech','J\'ai été voir, je pense que la carte mere a grillé.');
INSERT INTO glpi_followups VALUES ('2','1','2003-09-18 00:54:06','tech','L\'alimentation est foutue, je vais tester la carte mere pour voir si elle est recuperable');
INSERT INTO glpi_followups VALUES ('3','1','2003-09-18 00:54:40','tech','Probleme reglé j\'ai seulement changé l\'alimentation.
Le reste fonctionne tres bien.');
INSERT INTO glpi_followups VALUES ('4','3','2003-09-18 00:55:08','tech','Je pense que l\'on peux changer la souris.');
INSERT INTO glpi_followups VALUES ('5','2','2003-09-18 00:55:52','tech','Je suis passé, il faudra faire une restauration de windows NT4.');
### Dump table glpi_inst_software

DROP TABLE IF EXISTS glpi_inst_software;
CREATE TABLE glpi_inst_software (
    ID int(11) NOT NULL auto_increment,
    cID int(11) DEFAULT '0' NOT NULL,
    license int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY cID (cID),
   KEY sID (license)
);

INSERT INTO glpi_inst_software VALUES ('2','10','7');
INSERT INTO glpi_inst_software VALUES ('1','8','8');
INSERT INTO glpi_inst_software VALUES ('3','8','6');
INSERT INTO glpi_inst_software VALUES ('4','8','9');
### Dump table glpi_licenses

DROP TABLE IF EXISTS glpi_licenses;
CREATE TABLE glpi_licenses (
    ID int(15) NOT NULL auto_increment,
    sID int(15) DEFAULT '0' NOT NULL,
    serial varchar(255) NOT NULL,
    expire date,
   PRIMARY KEY (ID),
   KEY sID (sID)
);

INSERT INTO glpi_licenses VALUES ('7','4','12-aa-asd-12-aa',NULL);
INSERT INTO glpi_licenses VALUES ('6','4','asd-asdf-asdf-12',NULL);
INSERT INTO glpi_licenses VALUES ('4','4','au-23-as-23-cd',NULL);
INSERT INTO glpi_licenses VALUES ('5','4','qw-as-23-0k-23-dg',NULL);
INSERT INTO glpi_licenses VALUES ('8','3','free',NULL);
INSERT INTO glpi_licenses VALUES ('9','5','free',NULL);
### Dump table glpi_monitors

DROP TABLE IF EXISTS glpi_monitors;
CREATE TABLE glpi_monitors (
    ID int(10) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    contact varchar(255) NOT NULL,
    contact_num varchar(255) NOT NULL,
    comments text NOT NULL,
    serial varchar(255) NOT NULL,
    otherserial varchar(255) NOT NULL,
    size int(3) DEFAULT '0' NOT NULL,
    flags_micro tinyint(4) DEFAULT '0' NOT NULL,
    flags_speaker tinyint(4) DEFAULT '0' NOT NULL,
    flags_subd tinyint(4) DEFAULT '0' NOT NULL,
    flags_bnc tinyint(4) DEFAULT '0' NOT NULL,
    achat_date date DEFAULT '0000-00-00' NOT NULL,
    date_fin_garantie date,
    maintenance int(2) DEFAULT '0',
    location int(11),
    type int(11),
   PRIMARY KEY (ID),
   KEY ID (ID),
   KEY type (type),
   KEY location (location),
   KEY maintenance (maintenance)
);

INSERT INTO glpi_monitors VALUES ('3','nokia 20\'','2003-09-18 00:14:14','','','Ecran infographiste','','','20','1','1','1','0','0000-00-00','0000-00-00','0','1','1');
INSERT INTO glpi_monitors VALUES ('2','Sony 19\'','2003-09-18 00:14:50','','','ecran documentation','','','19','0','0','1','1','0000-00-00','0000-00-00','0','1','3');
### Dump table glpi_networking

DROP TABLE IF EXISTS glpi_networking;
CREATE TABLE glpi_networking (
    ID int(11) NOT NULL auto_increment,
    name varchar(30) NOT NULL,
    ram varchar(10) NOT NULL,
    serial varchar(50) NOT NULL,
    otherserial varchar(50) NOT NULL,
    contact varchar(30) NOT NULL,
    contact_num varchar(30) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    comments text NOT NULL,
    achat_date date DEFAULT '0000-00-00' NOT NULL,
    date_fin_garantie date,
    maintenance int(2) DEFAULT '0',
    location int(11),
    type int(11),
    firmware int(11),
   PRIMARY KEY (ID),
   KEY location (location),
   KEY type (type),
   KEY firmware (firmware)
);

INSERT INTO glpi_networking VALUES ('9','Dlink 450','','4586-puis-kioe','','','','0000-00-00 00:00:00','','0000-00-00','0000-00-00','0','1','1',NULL);
### Dump table glpi_networking_ports

DROP TABLE IF EXISTS glpi_networking_ports;
CREATE TABLE glpi_networking_ports (
    ID int(11) NOT NULL auto_increment,
    on_device int(11) DEFAULT '0' NOT NULL,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    logical_number int(11) DEFAULT '0' NOT NULL,
    name char(30) NOT NULL,
    ifaddr char(30) NOT NULL,
    ifmac char(30) NOT NULL,
    iface int(11),
    netpoint int(11),
   PRIMARY KEY (ID),
   KEY on_device (on_device, device_type),
   KEY netpoint (netpoint)
);

INSERT INTO glpi_networking_ports VALUES ('1','8','1','1','3Com','10.10.0.26','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('2','10','1','1','3com','10.10.0.27','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('3','15','1','1','Generic','10.10.0.28','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('4','18','1','1','3Com','10.10.0.29','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('5','9','2','1','Dlink port','10.10.0.1','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('6','9','2','2','Dlink port','10.10.0.1','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('7','9','2','3','Dlink port','10.10.0.1','','2',NULL);
INSERT INTO glpi_networking_ports VALUES ('8','10','2','0','','','','5',NULL);
### Dump table glpi_networking_wire

DROP TABLE IF EXISTS glpi_networking_wire;
CREATE TABLE glpi_networking_wire (
    ID int(11) NOT NULL auto_increment,
    end1 int(11) DEFAULT '0' NOT NULL,
    end2 int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   UNIQUE end1_1 (end1, end2),
   KEY end1 (end1),
   KEY end2 (end2)
);

INSERT INTO glpi_networking_wire VALUES ('1','5','1');
INSERT INTO glpi_networking_wire VALUES ('2','6','2');
INSERT INTO glpi_networking_wire VALUES ('3','7','3');
### Dump table glpi_peripherals

DROP TABLE IF EXISTS glpi_peripherals;
CREATE TABLE glpi_peripherals (
    ID int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    contact varchar(255) NOT NULL,
    contact_num varchar(255) NOT NULL,
    comments text NOT NULL,
    serial varchar(255) NOT NULL,
    otherserial varchar(255) NOT NULL,
    date_fin_garantie date,
    achat_date date DEFAULT '0000-00-00' NOT NULL,
    maintenance int(2) DEFAULT '0',
    location int(11) DEFAULT '0' NOT NULL,
    type int(11) DEFAULT '0' NOT NULL,
    brand varchar(255) NOT NULL,
   PRIMARY KEY (ID),
   KEY type (type),
   KEY location (location)
);

### Dump table glpi_prefs

DROP TABLE IF EXISTS glpi_prefs;
CREATE TABLE glpi_prefs (
    user varchar(80) NOT NULL,
    tracking_order enum('no','yes') DEFAULT 'no' NOT NULL,
    language varchar(255) NOT NULL,
    ID int(11) NOT NULL auto_increment,
   PRIMARY KEY (ID),
   UNIQUE user (user)
);

INSERT INTO glpi_prefs VALUES ('glpi','yes','french','1');
INSERT INTO glpi_prefs VALUES ('Helpdesk','no','french','2');
INSERT INTO glpi_prefs VALUES ('normal','yes','english','3');
INSERT INTO glpi_prefs VALUES ('tech','yes','french','4');
INSERT INTO glpi_prefs VALUES ('post-only','yes','english','5');
### Dump table glpi_printers

DROP TABLE IF EXISTS glpi_printers;
CREATE TABLE glpi_printers (
    ID int(10) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    date_mod datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    contact varchar(255) NOT NULL,
    contact_num varchar(255) NOT NULL,
    serial varchar(255) NOT NULL,
    otherserial varchar(255) NOT NULL,
    flags_serial tinyint(4) DEFAULT '0' NOT NULL,
    flags_par tinyint(4) DEFAULT '0' NOT NULL,
    flags_usb tinyint(4) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
    achat_date date DEFAULT '0000-00-00' NOT NULL,
    date_fin_garantie date,
    maintenance int(2) DEFAULT '0',
    ramSize varchar(6) NOT NULL,
    location int(11),
    type int(11),
   PRIMARY KEY (ID),
   KEY id (ID),
   KEY location (location),
   KEY type (type),
   KEY maintenance (maintenance)
);

INSERT INTO glpi_printers VALUES ('1','HP laser','2003-09-18 00:12:43','','','hp-jsgsj-658','','0','1','0','Imprimante bureau du directeur','0000-00-00','0000-00-00','0','','1','1');
INSERT INTO glpi_printers VALUES ('2','HP deskjet','2003-09-18 00:13:11','','','45dskjs-ds','','0','1','0','Imprimante documentation','0000-00-00','0000-00-00','0','','2','3');
### Dump table glpi_reservation_item

DROP TABLE IF EXISTS glpi_reservation_item;
CREATE TABLE glpi_reservation_item (
    ID int(11) NOT NULL auto_increment,
    device_type tinyint(4) DEFAULT '0' NOT NULL,
    id_device int(11) DEFAULT '0' NOT NULL,
    comments text NOT NULL,
   PRIMARY KEY (ID),
   KEY device_type (device_type)
);

INSERT INTO glpi_reservation_item VALUES ('1','1','10','Pile Poil');
### Dump table glpi_reservation_resa

DROP TABLE IF EXISTS glpi_reservation_resa;
CREATE TABLE glpi_reservation_resa (
    ID bigint(20) NOT NULL auto_increment,
    id_item int(11) DEFAULT '0' NOT NULL,
    begin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    end datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    id_user int(11) DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY id_item (id_item),
   KEY id_user (id_user),
   KEY begin (begin),
   KEY end (end)
);

### Dump table glpi_software

DROP TABLE IF EXISTS glpi_software;
CREATE TABLE glpi_software (
    ID int(11) NOT NULL auto_increment,
    name varchar(200) NOT NULL,
    version varchar(20) NOT NULL,
    comments text,
    location int(11),
    platform int(11),
   PRIMARY KEY (ID),
   KEY platform (platform),
   KEY location (location)
);

INSERT INTO glpi_software VALUES ('3','Acrobat PDF Viewer','4',NULL,NULL,'5');
INSERT INTO glpi_software VALUES ('4','MS Windows NT','4.0',NULL,NULL,'5');
INSERT INTO glpi_software VALUES ('5','Latex','6.2','Latex','1','2');
### Dump table glpi_templates

DROP TABLE IF EXISTS glpi_templates;
CREATE TABLE glpi_templates (
    ID int(11) NOT NULL auto_increment,
    templname varchar(200) NOT NULL,
    name varchar(200) NOT NULL,
    osver varchar(20) NOT NULL,
    processor_speed varchar(100) NOT NULL,
    serial varchar(200) NOT NULL,
    otherserial varchar(200) NOT NULL,
    ram varchar(20) NOT NULL,
    hdspace varchar(10) NOT NULL,
    contact varchar(200) NOT NULL,
    contact_num varchar(200) NOT NULL,
    comments text NOT NULL,
    achat_date date DEFAULT '0000-00-00' NOT NULL,
    date_fin_garantie date,
    maintenance int(2) DEFAULT '0',
    os int(11),
    hdtype int(11),
    sndcard int(11),
    moboard int(11),
    gfxcard int(11),
    network int(11),
    ramtype int(11),
    location int(11),
    processor int(11),
    type int(11),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_templates VALUES ('1','Blank Template','','','','','','','','','','Empty Template','0000-00-00','0000-00-00','0','2','2','1','2','1','1','1',NULL,'11','1');
INSERT INTO glpi_templates VALUES ('3','iMac','','9','333','','','128','6','','','Standard iMac','0000-00-00','0000-00-00','0','9','1','3','3','4','5','9',NULL,'6','3');
INSERT INTO glpi_templates VALUES ('7','test','','','','','','','','','','','0000-00-00','0000-00-00','0','12','2','3','2','1','1','1','1','11','1');
### Dump table glpi_tracking

DROP TABLE IF EXISTS glpi_tracking;
CREATE TABLE glpi_tracking (
    ID int(11) NOT NULL auto_increment,
    date datetime,
    closedate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
    status enum('new','old'),
    author varchar(200),
    assign varchar(200),
    device_type int(11) DEFAULT '1' NOT NULL,
    computer int(11),
    contents text,
    priority tinyint(4) DEFAULT '1' NOT NULL,
    is_group enum('no','yes') DEFAULT 'no' NOT NULL,
    uemail varchar(100),
    emailupdates varchar(4),
    realtime float DEFAULT '0' NOT NULL,
   PRIMARY KEY (ID),
   KEY computer (computer),
   KEY author (author),
   KEY assign (assign),
   KEY date (date),
   KEY closedate (closedate),
   KEY status (status)
);

INSERT INTO glpi_tracking VALUES ('1','2003-09-18 00:46:40','2003-09-18 00:54:43','old','Helpdesk','tech','1','8','Mon ordinateur ne s\'allume plus, et il ya des bruits byzarres','3','no','','','0');
INSERT INTO glpi_tracking VALUES ('2','2003-09-18 00:48:19','0000-00-00 00:00:00','new','Helpdesk','tech','1','10','Un message en anglais s\'affiche, je n\'y comprend rien, je n\'ose plus toucher à rien de peur de tout casser.
Venez vite !!!!','4','no','','','0');
INSERT INTO glpi_tracking VALUES ('3','2003-09-18 00:49:29','0000-00-00 00:00:00','new','Helpdesk','tech','1','18','Ma souris se bloque sans arret, elle defile mal sur l\'ecran et elle glisse tres mal sur le tapis de souris.','3','no','','','0');
### Dump table glpi_type_computers

DROP TABLE IF EXISTS glpi_type_computers;
CREATE TABLE glpi_type_computers (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_type_computers VALUES ('1','Generic x86 PC');
INSERT INTO glpi_type_computers VALUES ('2','PowerMac G4');
INSERT INTO glpi_type_computers VALUES ('3','iMac 2nd Generation');
INSERT INTO glpi_type_computers VALUES ('4','PowerMac G3');
### Dump table glpi_type_monitors

DROP TABLE IF EXISTS glpi_type_monitors;
CREATE TABLE glpi_type_monitors (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_type_monitors VALUES ('1','Nokia 445Xav');
INSERT INTO glpi_type_monitors VALUES ('2','Sony 200GDM');
INSERT INTO glpi_type_monitors VALUES ('3','Sony 200sf');
INSERT INTO glpi_type_monitors VALUES ('4','integrated');
### Dump table glpi_type_networking

DROP TABLE IF EXISTS glpi_type_networking;
CREATE TABLE glpi_type_networking (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_type_networking VALUES ('1','Dlink Switch');
### Dump table glpi_type_peripherals

DROP TABLE IF EXISTS glpi_type_peripherals;
CREATE TABLE glpi_type_peripherals (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

### Dump table glpi_type_printers

DROP TABLE IF EXISTS glpi_type_printers;
CREATE TABLE glpi_type_printers (
    ID int(11) NOT NULL auto_increment,
    name varchar(255),
   PRIMARY KEY (ID)
);

INSERT INTO glpi_type_printers VALUES ('1','HP Laserjet 4050N');
INSERT INTO glpi_type_printers VALUES ('2','HP Laserjet 4+');
INSERT INTO glpi_type_printers VALUES ('3','HP Deskjet 850c');
### Dump table glpi_users

DROP TABLE IF EXISTS glpi_users;
CREATE TABLE glpi_users (
    ID int(11) NOT NULL auto_increment,
    name varchar(80) NOT NULL,
    password varchar(80) NOT NULL,
    password_md5 varchar(80) NOT NULL,
    email varchar(80) NOT NULL,
    phone varchar(100),
    type enum('normal','admin','post-only','super-admin') DEFAULT 'normal' NOT NULL,
    realname varchar(255) NOT NULL,
    can_assign_job enum('yes','no') DEFAULT 'no' NOT NULL,
    location int(11),
   PRIMARY KEY (ID),
   UNIQUE name (name),
   KEY type (type),
   KEY name_2 (name)
);

INSERT INTO glpi_users VALUES ('1','Helpdesk','14e43c2d31dcbdd1','','',NULL,'post-only','Helpdesk Injector','no',NULL);
INSERT INTO glpi_users VALUES ('2','glpi','5b9b1ee2216a5ffe','41ece51526515624ff89973668497d00','','','super-admin','glpi','yes','2');
INSERT INTO glpi_users VALUES ('3','post-only','3eb831c67be6aeda','','',NULL,'post-only','post-only','no','1');
INSERT INTO glpi_users VALUES ('4','tech','37bd7c4221e8a247','','',NULL,'super-admin','technicien','yes','2');
INSERT INTO glpi_users VALUES ('5','normal','109e7883561b4202','','',NULL,'normal','utilisateur normal','no','1');
