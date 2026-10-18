# Expressions and `#[IsGranted]` closures check the logged-in user

Reproducer for [symfony/symfony#67131](https://github.com/symfony/symfony/issues/67131). It targets Symfony 7.4, the lowest maintained branch with `#[IsGranted]` closures; the issue lists the other branches.

`AccessDecisionManagerInterface::decide()` takes the token to decide for. `is_granted()` in an expression and `$context->isGranted()` in an `#[IsGranted]` closure ignore it: they go through `security.authorization_checker`, which reads the token storage. In the same expression, `role_names` reads the token being decided on.

```bash
composer install
vendor/bin/phpunit --testdox
```

The tests decide `ROLE_ADMIN` for a token that is not the one in the token storage. The 4 that fail show the bug, the 4 that pass are there for comparison.

```
 ✔ An admin token is granted while nobody is logged in with data set "ROLE_ADMIN"
 ✔ An admin token is granted while nobody is logged in with data set "'ROLE_ADMIN' in role_names, in an expression"
 ✘ An admin token is granted while nobody is logged in with data set "is_granted('ROLE_ADMIN'), in an expression"
 ✘ An admin token is granted while nobody is logged in with data set "$context->isGranted('ROLE_ADMIN'), in a closure"
 ✔ A user token is denied while an admin is logged in with data set "ROLE_ADMIN"
 ✔ A user token is denied while an admin is logged in with data set "'ROLE_ADMIN' in role_names, in an expression"
 ✘ A user token is denied while an admin is logged in with data set "is_granted('ROLE_ADMIN'), in an expression"
 ✘ A user token is denied while an admin is logged in with data set "$context->isGranted('ROLE_ADMIN'), in a closure"
```

- `src/Kernel.php` enables FrameworkBundle and SecurityBundle, with a single firewall.
- `tests/TokenPassedToDecideTest.php` calls `decide()` for an admin token while nobody is logged in (expected: granted), then for a user token while an admin is logged in (expected: denied).

Checked on 2026-10-18 against the 7.4.21 packages locked in `composer.lock`, with PHP 8.5.
