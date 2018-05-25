UPDATE glpi_configs SET url_base = 'http://demo.glpi-project.org';
UPDATE glpi_configs SET text_login = 'Pick an account, choose a language and try GLPI!

Following accounts are available:

- Administrator (login admin, password admin),
- Standard user (login normal, password normal),
- Self-service helpdesk user (login post-only, password postonly)';
UPDATE glpi_configs SET use_public_faq = 1;
UPDATE glpi_configs SET translate_dropdowns = 1;
UPDATE glpi_configs SET translate_kb = 1;
UPDATE glpi_configs SET use_rich_text = 1;
-- all profile rights
UPDATE glpi_profilerights SET rights = 0 WHERE name = 'password_update';
-- admin profile rights
UPDATE glpi_profilerights SET rights = 2049 WHERE profiles_id = 3 AND name = 'user';
UPDATE glpi_profilerights SET rights = 1 WHERE profiles_id = 3 AND name = 'typedoc';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'slm';
UPDATE glpi_profilerights SET rights = 261151 WHERE profiles_id = 3 AND name = 'ticket';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'ticketrecurrent';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'tickettemplate';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'reminder_public';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'rssfeed_public';
UPDATE glpi_profilerights SET rights = 23 WHERE profiles_id = 3 AND name = 'bookmark_public';
REPLACE INTO glpi_users (name, password, language) VALUES ('admin', MD5('admin'), 'en_GB');
REPLACE INTO glpi_profiles_users (users_id, profiles_id)
   SELECT id, 3 from glpi_users WHERE name = 'admin';
