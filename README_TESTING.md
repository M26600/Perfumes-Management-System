# Testing Setup Guide

This guide explains how to set up and run all testing tools for the Perfumes Management System.

## Quick Start

### 1. Install Dependencies
```bash
composer install --dev
```

### 2. Run All Tests
```bash
# Unit tests
composer test-unit

# Integration tests
composer test-integration

# Functional tests
composer test-functional

# Acceptance tests
composer test-acceptance
```

## Testing Tools Included

### 1. PHPUnit (Unit Testing)
- **Location**: `tests/Unit/`
- **Run**: `./vendor/bin/phpunit` or `composer test-unit`
- **Config**: `phpunit.xml`

### 2. Codeception (Functional & Acceptance)
- **Location**: `tests/functional/` and `tests/acceptance/`
- **Run**: `./vendor/bin/codecept run`
- **Config**: `codeception.yml`

### 3. Behat (BDD)
- **Location**: `features/`
- **Run**: `./vendor/bin/behat`
- **Config**: `behat.yml`

### 4. Static Analysis
- **PHPStan**: `./vendor/bin/phpstan analyse`
- **Psalm**: `./vendor/bin/psalm`
- **PHPMD**: `./vendor/bin/phpmd includes text phpmd.xml`
- **PHP CodeSniffer**: `./vendor/bin/phpcs --standard=PSR12 includes admin`

### 5. Mocking
- **Mockery**: Used in unit tests for mocking dependencies
- See `tests/Unit/ProductRepositoryTest.php` for examples

## Test Database Setup

Create a separate test database:
```sql
CREATE DATABASE perfume_db_test;
```

Update `codeception.yml` with test database credentials.

## Continuous Integration

Tests can be run automatically on GitHub Actions. See `.github/workflows/tests.yml` for configuration.

## Coverage Reports

After running PHPUnit, coverage reports are generated in `tests/_output/coverage/`.

## Need Help?

Refer to `TESTING_IMPLEMENTATION.md` for detailed documentation on each testing tool.



