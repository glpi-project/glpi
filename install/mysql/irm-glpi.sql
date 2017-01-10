;
; Script (hem, a list of SQL instructions ;) that attempt to convert an IRM
; 1.4.X installation to a GLPI 0.5.
;
; (C) Marco Gaiarin, <gaio@linux.it>, under GNU GPL Copyleft
;
; CHANGELOG
;
; 1.0 (Fri May  6 14:55:53 CEST 2005)
;  + first public version.


; NOTE
; This is a quick script that import most of the data, but there's some
; differences from IRM to GLPI because GLPI came from IRMA, an IRM 1.0.X
; fork.
; Particularly expect trouble on licence handling.
;
; Also, i've not even try to convert some data, as templates and
; configuration data...


; STEP to achieve the conversion
;
; 1) dump the IRM database, i use:
;	mysqldump -p --add-drop-table irmpp > irmpp.sql

; 2) install GLPI 0.42, follow the installation instructions, i suppose to
;  install to the glpipp database

; 3) restore the irm database into the glpi database, there's no table name
;  clash so it is a safe operation
;	cat irmpp.sql | mysql glpipp

; 4) execute this scripts, apart the last instructions about knowledgabase
;  tables (look at comment)
;  It is better to execute manually the block of instructions, reading
;  comments. But if you want to try quickly, do:
;	cat irm-glpi.sql | grep -v "^;" | mysql glpipp

; 5) upgrade GLPI to 0.5X

; 6) if you have knowledgebase data, run the last command to import these

; 7) verify your installation, particularly the software and licenses, cleanup
;  (drop) manually the remaining irm tables

; 8) upgrade GLPI to latest version.


; THE SCRIPT
;
; Now the single command used to upgrade, please read the comment.

; table comp_group and groups not used in glpi, will be supported by future
; GLPI version? I don't drop it.
;
;drop table comp_group;
;drop table groups;

; configuration better to be restarted from scratch...
;
drop table config;

; moving dropdown_iface data...
;
delete from glpi_dropdown_iface;
insert into glpi_dropdown_iface select NULL, name from dropdown_iface;
drop table dropdown_iface;

; moving dropdown_locations data...
;
delete from glpi_dropdown_locations;
insert into glpi_dropdown_locations select NULL, name from dropdown_locations;
drop table dropdown_locations;

; moving dropdown_network data...
;
delete from glpi_dropdown_network;
insert into glpi_dropdown_network select NULL, name from dropdown_network;
drop table dropdown_network;

; moving dropdown_os data...
;
delete from glpi_dropdown_os;
insert into glpi_dropdown_os select NULL, name from dropdown_os;
drop table dropdown_os;

; moving dropdown_processor data...
;
delete from glpi_dropdown_processor;
insert into glpi_dropdown_processor select NULL, name from dropdown_processor;
drop table dropdown_processor;

; moving dropdown_ram data...
;
delete from glpi_dropdown_ram;
insert into glpi_dropdown_ram select NULL, name from dropdown_ram;
drop table dropdown_ram;

; moving dropdown_type data...
;
delete from glpi_type_computers;
insert into glpi_type_computers select NULL, name from dropdown_type;
drop table dropdown_type;

; moving event_log data...
;
delete from glpi_event_log;
insert into glpi_event_log select * from event_log;
drop table event_log;

; table fasttracktemplates not used in glpi... i don't think will be
; implemented, but i will not drop automagically...
;
;drop table fasttracktemplates;


; software_bundles table not used in glpi, and also by me, so i simply
; discard it... but refuse to drop. ;)
;
;drop table software_bundles;


; templates (tables templates and templ_inst_software) are discarded,
; recreate your template as needed or write the conversion. ;)
;
;drop table templates;
;drop table templ_inst_software;


; setup users and prefs, i don't know if password work, but could be.
; (sorry, but i use external auth)
; Look at italian, is my default language!!! ;-)))
;
insert into glpi_users select NULL, name, password, NULL, email,
	phone, 'post-only', fullname, 'no', NULL from users;
drop table users;
drop table usersbak;
insert into glpi_prefs select name, 'no', 'italian', ID from glpi_users;
drop table prefs;


; setup networking stuff
;
delete from glpi_type_networking;
insert into glpi_type_networking
	select NULL, type from networking group by type;
delete from glpi_networking;
insert into glpi_networking select networking.ID, networking.name, ram,
	serial, otherserial, contact, contact_num, datemod, comments, NULL,
	NULL, NULL, glpi_dropdown_locations.ID, glpi_type_networking.ID,
	NULL from networking, glpi_dropdown_locations, glpi_type_networking
	WHERE location=glpi_dropdown_locations.name AND
	type=glpi_type_networking.name;
delete from glpi_networking_ports;
insert into glpi_networking_ports select networking_ports.ID, device_on,
	device_type, logical_number, networking_ports.name, ifaddr, ifmac,
	glpi_dropdown_iface.ID, NULL from networking_ports,
	glpi_dropdown_iface WHERE networking_ports.iface=glpi_dropdown_iface.name;
delete from glpi_networking_wire;
insert into glpi_networking_wire select * from networking_wire;
drop table networking;
drop table networking_ports;
drop table networking_wire;


; setup computers... the query for me pop up some dupes, but are correctly
; discarded by mysql, so i've not bother...
;
delete from glpi_computers;
insert into glpi_computers select computers.ID, computers.name,
	flags_server, osver, processor_speed, serial, otherserial, ram,
	hdspace, contact, contact_num, comments, date_mod, NULL, NULL, NULL,
	glpi_dropdown_os.ID, NULL, NULL, NULL, NULL, glpi_dropdown_network.ID,
	glpi_dropdown_ram.ID, glpi_dropdown_locations.ID,
	glpi_dropdown_processor.ID, glpi_type_computers.ID from computers,
	glpi_type_computers, glpi_dropdown_processor, glpi_dropdown_locations,
	glpi_dropdown_ram, glpi_dropdown_network, glpi_dropdown_os WHERE
	type=glpi_type_computers.name AND
	processor=glpi_dropdown_processor.name AND
	location=glpi_dropdown_locations.name AND
	ramtype=glpi_dropdown_ram.name AND
	network=glpi_dropdown_network.name AND os=glpi_dropdown_os.name;
drop table computers;


; setup software... version have to be setup by hand because irm lost
; version after 1.3.0 release...
;
delete from glpi_software;
insert into glpi_software select software.ID, software.name, NULL, NULL,
	NULL, glpi_dropdown_os.ID from software, glpi_dropdown_os WHERE
	platform=glpi_dropdown_os.name;
delete from glpi_licenses;
insert into glpi_licenses select ID, sID, licensekey, NULL from
	software_licenses;
delete from glpi_inst_software;
insert into glpi_inst_software select ID, cID, lID from inst_software;
drop table software;
drop table software_licenses;
;
; some data slip away, because irm link directly instalation and software
; so a licence can miss. Look ad licences misses using:
;
;select glpi_computers.name, glpi_software.name from glpi_computers,
;	glpi_software, inst_software where glpi_computers.ID=cID and
;	glpi_software.ID=sID AND lID=0;
; 
; you can also add some sort of ``default licence'' using this query..
;
replace glpi_inst_software select inst_software.id, cid, max(glpi_licenses.id)
	from inst_software, glpi_licenses
	where inst_software.sid=glpi_licenses.sid and lid=0
	group by inst_software.id, cid
	order by glpi_licenses.id;

; we refuse to drop inst_software table, so you can do further
; investigation...
;
;drop table inst_software;


; setup tracking... mmmh... real data lost, here we have only three status.
; We can create some category and add missing information to category, but
; i leave this as an exercise... ;)
;
delete from glpi_tracking;
insert into glpi_tracking select ID, date, closedate, 'old', author,
	assign, 1, computer, contents, priority, is_group, uemail, emailupdates,
	NULL from tracking where status='old';
insert into glpi_tracking select ID, date, closedate, 'old', author,
	assign, 1, computer, contents, priority, is_group, uemail, emailupdates,
	NULL from tracking where status='complete';
insert into glpi_tracking select ID, date, closedate, 'new', author,
	assign, 1, computer, contents, priority, is_group, uemail, emailupdates,
	NULL from tracking where status='new';
insert into glpi_tracking select ID, date, closedate, 'new', author,
	assign, 1, computer, contents, priority, is_group, uemail, emailupdates,
	NULL from tracking where status='wait';
insert into glpi_tracking select ID, date, closedate, 'new', author,
	assign, 1, computer, contents, priority, is_group, uemail, emailupdates,
	NULL from tracking where status='assigned';
delete from glpi_followups;
insert into glpi_followups select * from followups;
drop table tracking;
drop table followups;


; Knowledgabase added to 0.5, the table, name apart, are the same.
;
insert into glpi_dropdown_kbcategories select * from kbcategories;
insert into glpi_kbitems select * from kbarticles;
;drop table kbcategories;
;drop table kbarticles;
