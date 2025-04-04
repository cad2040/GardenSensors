# Contributing to Garden Sensors

Thank you for your interest in contributing to Garden Sensors! This document provides guidelines and instructions for contributing to the project.

## Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/garden-sensors.git`
3. Create a new branch: `git checkout -b feature/your-feature-name`
4. Install dependencies:
   ```bash
   composer install
   python -m pip install -r requirements.txt
   ```
5. Copy the environment file:
   ```bash
   cp config/environment/.env.example config/environment/.env
   ```

## Development Workflow

1. Create a new branch for your feature/fix
2. Make your changes
3. Write tests for your changes
4. Ensure all tests pass
5. Update documentation if needed
6. Submit a pull request

## Coding Standards

### PHP
- Follow PSR-12 coding standards
- Use type hints and return type declarations
- Write PHPDoc blocks for classes and methods
- Keep methods small and focused
- Use meaningful variable and method names

### Python
- Follow PEP 8 style guide
- Use type hints
- Write docstrings for classes and functions
- Keep functions small and focused
- Use meaningful variable and function names

### JavaScript
- Use ES6+ features
- Follow Airbnb JavaScript Style Guide
- Use meaningful variable and function names
- Comment complex logic

## Testing

### PHP Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit --filter testName

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Python Tests
```bash
# Run all tests
pytest

# Run specific test
pytest tests/test_file.py::test_name

# Run with coverage
pytest --cov=python
```

## Documentation

- Update README.md if needed
- Add/update API documentation in `docs/api/`
- Add/update development documentation in `docs/development/`
- Add comments for complex code
- Update CHANGELOG.md

## Pull Request Process

1. Update the README.md with details of changes if needed
2. Update the CHANGELOG.md with details of changes
3. The PR will be merged once you have the sign-off of at least one maintainer
4. Ensure the CI pipeline passes

## Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

Types:
- feat: A new feature
- fix: A bug fix
- docs: Documentation only changes
- style: Changes that do not affect the meaning of the code
- refactor: A code change that neither fixes a bug nor adds a feature
- perf: A code change that improves performance
- test: Adding missing tests or correcting existing tests
- chore: Changes to the build process or auxiliary tools

## Release Process

1. Update version numbers
2. Update CHANGELOG.md
3. Create a new release on GitHub
4. Tag the release
5. Deploy to production

## Getting Help

- Open an issue for bugs or feature requests
- Join our [Discord community](https://discord.gg/garden-sensors)
- Check the [documentation](docs/)

## License

By contributing, you agree that your contributions will be licensed under the project's [MIT License](LICENSE). 