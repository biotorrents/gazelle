A function should run the same no matter how it's (legally) formatted.
Having arbitrary whitespace break things, and using lax equality checks, are bugs.
PHP is notorious for code that relies on edge cases in its abysmal concepts of things being "equal."

This repo contains file paths and snippets that indicate broken code when run through PHP CS Fixer or converted to strict equality.
According to Visual Studio Code with
(only this PHP CS Fixer extension installed)[https://marketplace.visualstudio.com/items?itemName=junstyle.php-cs-fixer]
and set to run without arguments.
