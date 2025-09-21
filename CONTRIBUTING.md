# Contributing to PHP Scaffold

Thank you for considering contributing! This project is a boilerplate for building modern PHP libraries. Contributions
that improve the developer experience, documentation, or tooling are very welcome.

## Ways to contribute

- Report bugs or suggest enhancements by opening an issue.
- Improve documentation (README, comments, examples).
- Submit pull requests for fixes and improvements.

## Code of conduct

Please be respectful and inclusive. Behave professionally and kindly toward others. If you experience or witness
unacceptable behavior, please contact the maintainer at dev.cndrsdrmn@gmail.com.

## Development setup

Prerequisites:

- PHP 8.2+
- Composer

Steps:

1. Fork the repository and clone your fork.
2. Install dependencies:
   ```shell
   composer install
   ```

3. Ensure the test suite and quality tools run locally:

   ```shell
   composer test
   ```

### Useful scripts

| Task                   | Command                  |
|------------------------|--------------------------|
| Run tests              | `composer test`          |
| Format code            | `composer lint`          |
| Check formatting only  | `composer test:lint`     |
| Run unit tests         | `composer test:unit`     |
| Run static analysis    | `composer test:types`    |
| Preview Rector changes | `composer test:refactor` |
| Apply Rector refactors | `composer refactor`      |

### Coding standards

- This project uses Laravel Pint. Configuration: pint.json.
- Follow PSR-12 and the configured Pint rules.
- Commit only code that passes the composer test.

### Static analysis

- PHPStan configuration: phpstan.neon.dist.
- Fix reported issues or justify with clear comments or baselines when appropriate.

### Tests

- Tests use PHPUnit with configuration in phpunit.xml.dist.
- Add tests for new features and bug fixes.
- Ensure tests are deterministic and independent.

## Pull requests

- Create a feature branch from master.
- Keep changes focused and small when possible.
- Update documentation when behavior or public APIs change.
- Ensure CI passes on your PR.
- Write clear descriptions with context and motivation.

### Commit messages

- Use clear, descriptive commit messages.
- Conventional Commits are welcome but not required (e.g., feat:, fix:, docs:, refactor:, test:, chore:).

## Release and changelog

- Changes are documented in CHANGELOG.md.
- Maintainers will handle versioning and tagging following semantic versioning when applicable.

## Security

- Do not open public issues for security vulnerabilities.
- Email dev.cndrsdrmn@gmail.com with details instead.

Thanks again for your contributions!