UPDATE glpi_configs SET value = 'https://demo.glpi-project.org' WHERE name = 'url_base';
UPDATE glpi_configs SET value = 'Pick an account, choose a language and try GLPI!

Following accounts are available:

- Administrator (login admin, password admin),
- Standard user (login normal, password normal),
- Self-service helpdesk user (login post-only, password postonly)'
   WHERE name = 'text_login';
UPDATE glpi_configs SET value = 1 WHERE name = 'use_public_faq';
UPDATE glpi_configs SET value = 1 WHERE name = 'translate_dropdowns';
UPDATE glpi_configs SET value = 1 WHERE name = 'translate_kb';
-- all profile rights
UPDATE glpi_profilerights SET rights = 0 WHERE name = 'password_update';
-- admin profile rights
UPDATE glpi_profilerights SET rights = 2049 WHERE profiles_id = 3 AND name = 'user';
UPDATE glpi_profilerights SET rights = 1 WHERE profiles_id = 3 AND name = 'typedoc';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'slm';
UPDATE glpi_profilerights SET rights = 261151 WHERE profiles_id = 3 AND name = 'ticket';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'ticketrecurrent';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'itiltemplate';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'reminder_public';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'rssfeed_public';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'bookmark_public';
INSERT INTO glpi_users (name, password, authtype, language) VALUES ('admin', '$2y$10$ERFSQRmAVBzX9xNDtkV82.AixFN3ds6WKWQOwwUBcG2.7.U4c2hCa', 1, 'en_GB');
INSERT INTO glpi_profiles_users (users_id, profiles_id)
   SELECT id, 3 from glpi_users WHERE name = 'admin';
