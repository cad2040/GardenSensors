# Testing Guide

## Overview
This document outlines the testing procedures for the Garden Sensors project. Our testing strategy includes unit tests, integration tests, and end-to-end tests for both PHP and Python components.

## Test Structure
```
tests/
├── Models/          # Model unit tests
├── Services/        # Service layer tests
├── Controllers/     # Controller tests
├── Core/            # Core functionality tests
└── Integration/     # Integration tests
```

## Running Tests

### PHP Tests
1. **Setup Test Environment**
   ```bash
   # Copy test environment file
   cp .env.example .env.testing
   
   # Configure test database
   mysql -u root -p < SQLDeployScript.sql
   ```

2. **Run All Tests**
   ```bash
   composer test
   ```

3. **Run Specific Test Suites**
   ```bash
   # Run model tests
   ./vendor/bin/phpunit --testsuite Models
   
   # Run service tests
   ./vendor/bin/phpunit --testsuite Services
   
   # Run controller tests
   ./vendor/bin/phpunit --testsuite Controllers
   
   # Run core tests
   ./vendor/bin/phpunit --testsuite Core
   ```

4. **Generate Coverage Report**
   ```bash
   composer test-coverage
   ```
   Coverage reports will be available in `tests/coverage/`

### Python Tests

1. **Setup Python Test Environment**
   ```bash
   # Activate virtual environment
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   
   # Install test dependencies
   pip install -r requirements.txt
   ```

2. **Run Sensor Simulator Tests**
   ```bash
   python3 tests/test_sensor_simulator.py
   ```

3. **Generate Test Data**
   ```bash
   python3 tests/test_sensor_simulator.py --generate
   ```

4. **Clean Up Test Data**
   ```bash
   ./tests/cleanup_test_data.sh
   ```

## Writing Tests

### PHP Tests
1. **Naming Convention**
   - Test classes should end with `Test`
   - Test methods should start with `test`
   - Use descriptive names that indicate what is being tested

2. **Test Structure**
   ```php
   public function setUp(): void
   {
       // Setup test environment
   }

   public function tearDown(): void
   {
       // Clean up after test
   }

   public function testFeature(): void
   {
       // Arrange
       // Act
       // Assert
   }
   ```

3. **Best Practices**
   - One assertion per test method
   - Use data providers for multiple test cases
   - Mock external dependencies
   - Test edge cases and error conditions

### Python Tests
1. **Test Structure**
   ```python
   def setUp(self):
       """Set up test fixtures."""
       pass

   def tearDown(self):
       """Clean up after tests."""
       pass

   def test_feature(self):
       """Test specific feature."""
       # Arrange
       # Act
       # Assert
   ```

2. **Best Practices**
   - Use descriptive test names
   - Mock hardware dependencies
   - Test both success and failure cases
   - Clean up resources properly

## Continuous Integration
- Tests run automatically on GitHub Actions
- Pull requests require passing tests
- Coverage reports are generated automatically

## Test Data Management
1. **Generate Test Data**
   ```bash
   # Generate sensor readings
   python3 tests/test_sensor_simulator.py --generate
   
   # Generate plant data
   php tests/generate_plant_data.php
   ```

2. **Clean Up Test Data**
   ```bash
   # Clean up all test data
   ./tests/cleanup_test_data.sh
   ```

## Troubleshooting Tests
1. **Common Issues**
   - Database connection errors
   - Missing dependencies
   - File permission issues
   - Hardware simulation failures

2. **Solutions**
   - Check test environment configuration
   - Verify database credentials
   - Ensure proper file permissions
   - Check hardware simulation settings

## Code Coverage Requirements
- Minimum 80% code coverage for new code
- Critical paths require 100% coverage
- Integration tests for all API endpoints
- Unit tests for all model methods 