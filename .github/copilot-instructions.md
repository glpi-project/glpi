Follow GLPI’s latest coding standards and best practices: naming, indentation, comments, and PSR-12 compliance.
Use the GLPI framework whenever possible.
Do not use deprecated code.
Do not use PHP features older than version 8.2.
Do not use GLPI code or APIs older than version 10.0.
Never create .md or .txt files to explain changes.
Never explain what you did.
Do not add unnecessary comments or TODO notes.
Follow the MVC pattern, routing, and controllers wherever possible.
Do not create /front/ files — always use controllers and routes.
Never output raw HTML with echo; always use Twig templates.
Never execute raw SQL — always use GLPI’s ORM and database abstraction layer.
Do not ask clarification questions, except when a real choice between two technical solutions must be made.
Do not generate tests unless requested.
When generating code, always ensure it is secure and free from vulnerabilities.