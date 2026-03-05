# Agent Context for BioGazelle

This file provides system context and instructions for AI agents interacting with the BioGazelle codebase.

## Codebase Architecture
- **Framework & Routing**: Uses the Flight router. A typical request starts in `public/index.php`. Routes correspond to `require_once` statements that load files from `/sections`.
- **Database**: Uses a custom PDO wrapper. All queries must use parameterized statements to prevent SQL injection.
- **Models**: Located in `/app/Models`, they extend Laravel `LazyCollection` and implement the JSON:API specification. Objects are immutable. When updating, use `$object->updateOrCreate($data)`.
- **Templates**: Uses Twig templates (`/templates`). **No mixed PHP and HTML.** Everything should extend the base HTML5 template.
- **Search Engine**: Manticore Search is heavily integrated.

## Coding Standards & Conventions
- **Strict Types**: Always use `declare(strict_types=1);` at the top of every PHP file.
- **Singleton Pattern**: The entire application state (database, cache, user, Twig, env) is available via `Gazelle\App::go()`.
- **Relationships**: Database relationships are flat and stored as `1:1` reciprocal links in link tables (e.g., `creators_links`). There is no concept of ownership or strict hierarchies.
- **Security**: The `Auth` class (`/app/Auth.php`) handles authentication. Use WebAuthn for hardware keys/FIDO. Passwords use `PASSWORD_DEFAULT`. Do not prehash passwords.
- **Data Minimization**: Avoid adding features that collect unnecessary user data (e.g., no IP logging history, no Bitcoin addresses).
- **JSON:API**: Core objects follow the JSON:API specification format from instantiation.

## Agent Instructions
- **Read carefully**: Start by referring to `README.md` and `CONTRIBUTING.md` for a deeper dive into the system structures and design philosophy.
- **Testing/Debugging**: Use the interactive shell via `php shell` at the project root to test PHP logic (based on Laravel Tinker).
- **Modern PHP**: Ensure compatibility with PHP 8+. Use modern syntax and avoid deprecated functions.
