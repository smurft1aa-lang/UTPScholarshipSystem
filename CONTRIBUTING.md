# Contributing to UTP Scholarship System

Thank you for contributing! This guide helps maintain consistency across the codebase.

## Development Setup

```bash
# Clone and install
git clone https://github.com/smurft1aa-lang/UTPScholarshipSystem.git
cd UTPScholarshipSystem
composer install
cp .env.example .env
# Edit .env with your local database credentials

# Start services (Docker)
docker-compose up -d

# Run tests to verify setup
composer test
```

## Code Style

This project enforces **PSR-12** via PHP CodeSniffer.

```bash
# Check for violations
composer lint

# Auto-fix violations
composer lint:fix

# Run static analysis
composer analyse
```

## Commit Message Convention

We use [Conventional Commits](https://www.conventionalcommits.org/). Every commit message must follow this format:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

| Type | When to Use |
|------|-------------|
| `feat` | New feature or functionality |
| `fix` | Bug fix |
| `docs` | Documentation changes only |
| `style` | Code formatting (no logic change) |
| `refactor` | Code restructuring (no behavior change) |
| `perf` | Performance improvement |
| `test` | Adding or updating tests |
| `ci` | CI/CD configuration changes |
| `chore` | Maintenance tasks (deps, configs) |
| `security` | Security-related fixes or improvements |

### Scope (Optional)

Use the component being changed: `auth`, `admin`, `student`, `api`, `ai-engine`, `db`, `docker`.

### Examples

```
feat(admin): add bulk CSV import for student accounts
fix(ai-engine): resolve N+1 query in eligibility check
docs: add production deployment checklist
test: add data export unit tests
ci: add PHP CodeSniffer to CI pipeline
security(upload): harden file extension validation
```

## Branch Naming

```
feat/short-description
fix/bug-description
docs/what-changed
test/what-covered
```

Always branch from `main`. Use kebab-case.

## Pull Request Process

1. Create a feature branch from `main`
2. Make your changes with conventional commit messages
3. Ensure tests pass: `composer ci`
4. Push and create a Pull Request
5. CI pipeline will auto-run (lint, test, security scan, Docker build)
6. PR comment will show CI results
7. Request review from a maintainer

### PR Title Format

Same as commit convention: `feat(scope): description`

### PR Description Template

```markdown
## What Changed
Brief description of the change.

## Why
Context and motivation.

## Testing
How you verified this works.

## Screenshots (if UI change)
Before/after screenshots.
```

## Project Structure

```
├── src/                    # PSR-4 namespaced classes
│   ├── Contracts/          # Interfaces
│   ├── Core/               # Session management
│   ├── Security/           # CSRF, rate limiting, input validation
│   └── Services/           # AI engine, auth, mailer, telemetry
├── api/                    # REST API endpoints
├── admin/                  # Admin dashboard pages
├── student/                # Student portal pages
├── auth/                   # Authentication pages
├── config/                 # Database configuration
├── includes/               # Legacy bridge (procedural → OOP)
├── tests/                  # PHPUnit test suite
├── sql/                    # Database schema and indexes
├── db/migrations/          # Phinx migrations
├── docs/                   # Documentation
└── templates/              # CSV templates for bulk import
```

## Testing

```bash
# Run all tests
composer test

# Run with coverage report
composer test:coverage

# Run a specific test file
vendor/bin/phpunit tests/AIEngineTest.php

# Run a specific test method
vendor/bin/phpunit --filter test_all_A_plus_gives_100_percent_fit
```

All new features must include unit tests. Minimum code coverage: **80%**.

## Questions?

Open an issue on GitHub or contact the team at engineering@utp.edu.my.
