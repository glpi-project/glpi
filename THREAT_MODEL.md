# Security report threat model

Read this alongside [SECURITY.md](SECURITY.md). A security report must show unauthorized access to data or actions.

## Assets and boundaries

GLPI protects accounts, sessions, tickets, documents, inventory, knowledge-base data, settings and integration credentials, along with its database, host files and internal services.

A report must show one of these failures:

- Access to protected data or actions without authentication.
- Access beyond the account's profile rights or entity scope.
- Attacker-controlled content running as code in another user's GLPI session, a database query or the server.
- Access to host files or internal services beyond the account's permissions.

Give the attacker's rights, active profile, entity assignments and recursive access. Name the data or action the attack makes accessible. Profile names alone are not enough.

## Trusted capabilities

Global Super-Admins and accounts with general-configuration update rights can manage sensitive settings and integration credentials. Authorized impersonation, recovering LDAP or mail credentials by changing connection settings, or running plugin code the attacker can already install do not by themselves add privileges. Explain what access the attack adds.

Administrators limited to an entity must still obey their profile rights and entity scope. A user assigned recursively to a parent entity can appear in its child entities. Show access outside those assignments or an action the account is not allowed to perform.

Database access or the ability to change server files is not a new capability if the attack already requires it.

## Deployment

Use the documented web root and access restrictions. If private files are exposed only because required web-server protections were omitted, that is a deployment issue. A bypass of those protections or an unsafe documented setup can still be a GLPI vulnerability.

Supported optional features and nondefault settings are in scope. List any protections disabled for testing and explain whether the attack depends on disabling them.

## Common report cases

| Reported behavior | What to show |
| --- | --- |
| An RSS feed or integration makes an outbound request | Fetching configured URLs is expected. Show unauthorized access or a bypass of URL restrictions (`GLPI_SERVERSIDE_URL_ALLOWED_PRIVATE_NETWORKS_CONTEXTS` lists the contexts allowed to make server-side requests to private networks). A callback alone is insufficient. |
| Reflected XSS requires a modified `Referer` header | Show how the attacker sets the header in the victim's browser or the page is cached and served to other users; editing your own request is insufficient. |
| An uploaded HTML or PDF document runs JavaScript | Show access to GLPI data or actions outside the viewer's sandbox. Script execution inside the sandbox is insufficient. |
| An upload accepts an executable file extension | Show how the supported runtime executes the file on the server. A missing blocklist entry does not prove code execution. |
| A SQL payload causes an error, or input appears in HTML | Show query manipulation or script execution despite quoting or escaping. Errors, reflected text and HTTP success statuses do not prove exploitation. |
| The login page can be framed or used for phishing | Show an unauthorized GLPI action or disclosure. Framing or redirecting alone does not give access to the page's data, and social engineering attacks are not considered vulnerabilities. |

Ask for missing evidence before deciding whether a report is valid. A fix may be hardening; closing a report without an advisory does not mean the reported behavior will not be addressed.
