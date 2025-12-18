# Comprehensive Testing Implementation Guide

This document provides a complete testing setup for the Perfumes Management System using multiple testing frameworks and tools.

## Table of Contents
1. [PHPUnit - Unit Testing](#phpunit)
2. [Codeception - Functional & Acceptance Testing](#codeception)
3. [Behat - Behavior-Driven Development](#behat)
4. [Static Analysis Tools](#static-analysis)
5. [Performance Testing](#performance-testing)
6. [Mocking with Mockery](#mocking)
7. [Setup Instructions](#setup)

---

## 1. PHPUnit - Unit Testing {#phpunit}

### Installation
```bash
composer require --dev phpunit/phpunit
```

### Configuration: `phpunit.xml`
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">includes</directory>
            <directory suffix=".php">admin</directory>
        </include>
        <exclude>
            <directory>includes/vendor</directory>
        </exclude>
    </coverage>
</phpunit>
```

### Example Unit Test: `tests/Unit/ProductRepositoryTest.php`
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProductRepository;
use mysqli;

class ProductRepositoryTest extends TestCase
{
    private $mockConnection;
    private $repository;

    protected function setUp(): void
    {
        // Create a mock mysqli connection
        $this->mockConnection = $this->createMock(mysqli::class);
        $this->repository = new ProductRepository($this->mockConnection);
    }

    public function testFindAllAvailableReturnsArray()
    {
        // Mock query result
        $mockResult = $this->createMock(\mysqli_result::class);
        $mockResult->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'name' => 'Test Perfume', 'price' => 50.00],
                false
            );
        $mockResult->method('num_rows')->willReturn(1);

        $this->mockConnection->expects($this->once())
            ->method('query')
            ->willReturn($mockResult);

        $products = $this->repository->findAllAvailable();

        $this->assertIsArray($products);
        $this->assertCount(1, $products);
        $this->assertEquals('Test Perfume', $products[0]['name']);
    }

    public function testFindByIdReturnsProduct()
    {
        $productId = 1;
        $mockStmt = $this->createMock(\mysqli_stmt::class);
        $mockResult = $this->createMock(\mysqli_result::class);
        
        $mockResult->method('fetch_assoc')
            ->willReturn(['id' => 1, 'name' => 'Test', 'price' => 50.00]);

        $mockStmt->method('get_result')->willReturn($mockResult);
        $mockStmt->expects($this->once())->method('bind_param');
        $mockStmt->expects($this->once())->method('execute');
        $mockStmt->expects($this->once())->method('close');

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $product = $this->repository->findById($productId);

        $this->assertNotNull($product);
        $this->assertEquals(1, $product['id']);
    }

    public function testUpdateStockDecreasesQuantity()
    {
        $productId = 1;
        $quantity = 5;
        
        $mockStmt = $this->createMock(\mysqli_stmt::class);
        $mockStmt->expects($this->once())
            ->method('bind_param')
            ->with('ii', $quantity, $productId);
        $mockStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $mockStmt->expects($this->once())->method('close');

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE products SET stock'))
            ->willReturn($mockStmt);

        $result = $this->repository->updateStock($productId, $quantity);
        $this->assertTrue($result);
    }
}
```

### Example Integration Test: `tests/Integration/OrderFlowTest.php`
```php
<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use OrderRepository;
use OrderItemRepository;
use ProductRepository;
use mysqli;

class OrderFlowTest extends TestCase
{
    private $conn;
    private $orderRepo;
    private $orderItemRepo;
    private $productRepo;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../includes/db_connect.php';
        require_once __DIR__ . '/../../includes/Repository/OrderRepository.php';
        require_once __DIR__ . '/../../includes/Repository/OrderItemRepository.php';
        require_once __DIR__ . '/../../includes/Repository/ProductRepository.php';
        
        global $conn;
        $this->conn = $conn;
        $this->orderRepo = new OrderRepository($this->conn);
        $this->orderItemRepo = new OrderItemRepository($this->conn);
        $this->productRepo = new ProductRepository($this->conn);
    }

    public function testCompleteOrderCreationFlow()
    {
        // Start transaction
        $this->conn->begin_transaction();

        try {
            // Create order
            $orderId = $this->orderRepo->create(1, 100.00, 'momo', 'pending_payment', '+1234567890');
            $this->assertGreaterThan(0, $orderId);

            // Add items
            $items = [
                ['id' => 1, 'qty' => 2, 'price' => 50.00]
            ];
            $result = $this->orderItemRepo->addItems($orderId, $items);
            $this->assertTrue($result);

            // Verify order exists
            $order = $this->orderRepo->findByIdAndUserId($orderId, 1);
            $this->assertNotNull($order);
            $this->assertEquals(100.00, $order['total']);

        } finally {
            // Rollback for test isolation
            $this->conn->rollback();
        }
    }
}
```

---

## 2. Codeception - Functional & Acceptance Testing {#codeception}

### Installation
```bash
composer require --dev codeception/codeception codeception/module-webdriver codeception/module-db codeception/module-phpbrowser
```

### Configuration: `codeception.yml`
```yaml
namespace: Tests
actor_suffix: Tester
paths:
    tests: tests
    output: tests/_output
    data: tests/_data
    support: tests/_support
    envs: tests/_envs
actor: Tester
extensions:
    enabled:
        - Codeception\Extension\RunFailed
modules:
    config:
        Db:
            dsn: 'mysql:host=localhost;dbname=perfume_db_test'
            user: 'root'
            password: ''
            dump: tests/_data/dump.sql
        WebDriver:
            url: 'http://localhost/Perfumes Management System'
            browser: chrome
            window_size: 1920x1080
```

### Functional Test: `tests/functional/UserRegistrationCest.php`
```php
<?php

class UserRegistrationCest
{
    public function _before(FunctionalTester $I)
    {
        $I->amOnPage('/register.php');
    }

    public function testUserCanRegisterSuccessfully(FunctionalTester $I)
    {
        $I->fillField('username', 'testuser_' . time());
        $I->fillField('email', 'test_' . time() . '@example.com');
        $I->fillField('password', 'password123');
        $I->click('Register');
        
        $I->seeCurrentUrlEquals('/login.php');
        $I->see('Registration successful');
    }

    public function testUserCannotRegisterWithDuplicateEmail(FunctionalTester $I)
    {
        $email = 'duplicate@example.com';
        
        // First registration
        $I->fillField('username', 'user1');
        $I->fillField('email', $email);
        $I->fillField('password', 'password123');
        $I->click('Register');
        
        // Second registration with same email
        $I->amOnPage('/register.php');
        $I->fillField('username', 'user2');
        $I->fillField('email', $email);
        $I->fillField('password', 'password123');
        $I->click('Register');
        
        $I->see('Email already registered');
    }

    public function testUserCannotRegisterWithInvalidEmail(FunctionalTester $I)
    {
        $I->fillField('username', 'testuser');
        $I->fillField('email', 'invalid-email');
        $I->fillField('password', 'password123');
        $I->click('Register');
        
        $I->see('valid email address');
    }
}
```

### Acceptance Test: `tests/acceptance/OrderProcessCest.php`
```php
<?php

class OrderProcessCest
{
    public function testCompleteOrderWorkflow(AcceptanceTester $I)
    {
        // Login
        $I->amOnPage('/login.php');
        $I->fillField('email', 'test@example.com');
        $I->fillField('password', 'password123');
        $I->click('Login');
        
        // Add product to cart
        $I->amOnPage('/products.php');
        $I->click('Add to Cart', '.product-card:first-child');
        $I->see('added to cart');
        
        // Go to cart
        $I->amOnPage('/cart.php');
        $I->see('Cart');
        $I->seeElement('.cart-item');
        
        // Checkout
        $I->click('Checkout');
        $I->seeCurrentUrlEquals('/payment_proof.php');
        
        // Upload payment proof
        $I->attachFile('input[name="proof"]', 'tests/_data/payment_proof.jpg');
        $I->click('Submit');
        
        $I->see('Payment proof uploaded');
        $I->see('Waiting for cashier approval');
    }
}
```

---

## 3. Behat - Behavior-Driven Development {#behat}

### Installation
```bash
composer require --dev behat/behat
```

### Configuration: `behat.yml`
```yaml
default:
  suites:
    default:
      contexts:
        - FeatureContext
        - Behat\MinkExtension\Context\MinkContext
  extensions:
    Behat\MinkExtension:
      base_url: 'http://localhost/Perfumes Management System'
      sessions:
        default:
          selenium2:
            wd_host: 'http://localhost:4444/wd/hub'
            browser: chrome
```

### Feature File: `features/user_registration.feature`
```gherkin
Feature: User Registration
  As a new customer
  I want to register an account
  So that I can purchase perfumes

  Scenario: Successful registration
    Given I am on the registration page
    When I fill in "username" with "newuser123"
    And I fill in "email" with "newuser@example.com"
    And I fill in "password" with "securepass123"
    And I press "Register"
    Then I should be redirected to the login page
    And I should see "Registration successful"

  Scenario: Registration with duplicate email fails
    Given a user with email "existing@example.com" exists
    When I am on the registration page
    And I fill in "email" with "existing@example.com"
    And I fill in "username" with "anotheruser"
    And I fill in "password" with "password123"
    And I press "Register"
    Then I should see "Email already registered"

  Scenario: Registration with invalid password
    Given I am on the registration page
    When I fill in "username" with "testuser"
    And I fill in "email" with "test@example.com"
    And I fill in "password" with "123"
    And I press "Register"
    Then I should see "Password must be at least 6 characters"
```

### Context: `features/bootstrap/FeatureContext.php`
```php
<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;

class FeatureContext extends MinkContext implements Context
{
    /**
     * @Given a user with email :email exists
     */
    public function aUserWithEmailExists($email)
    {
        // Create test user in database
        $conn = new mysqli('localhost', 'root', '', 'perfume_db_test');
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $username = 'existing_user';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->bind_param('sss', $username, $email, $password);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}
```

---

## 4. Static Analysis Tools {#static-analysis}

### PHPStan Configuration: `phpstan.neon`
```neon
parameters:
    level: 5
    paths:
        - includes
        - admin
        - *.php
    excludePaths:
        - vendor
    ignoreErrors:
        - '#Call to an undefined method mysqli::.*#'
```

### Psalm Configuration: `psalm.xml`
```xml
<?xml version="1.0"?>
<psalm
    errorLevel="5"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd">
    <projectFiles>
        <directory name="includes" />
        <directory name="admin" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>
</psalm>
```

### PHPMD Configuration: `phpmd.xml`
```xml
<?xml version="1.0"?>
<ruleset name="Perfumes Management System"
         xmlns="http://pmd.sf.net/ruleset/1.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://pmd.sf.net/ruleset/1.0.0
                     http://pmd.sf.net/ruleset_xml_schema.xsd"
         xsi:type="ruleset">
    <description>Code quality rules for Perfumes Management System</description>
    
    <rule ref="rulesets/codesize.xml/TooManyFields" />
    <rule ref="rulesets/codesize.xml/TooManyMethods" />
    <rule ref="rulesets/codesize.xml/ExcessiveClassComplexity" />
    
    <rule ref="rulesets/design.xml/TooManyPublicMethods" />
    <rule ref="rulesets/design.xml/NumberOfChildren" />
    
    <rule ref="rulesets/naming.xml/ShortVariable" />
    <rule ref="rulesets/naming.xml/LongVariable" />
    
    <rule ref="rulesets/unusedcode.xml/UnusedPrivateField" />
    <rule ref="rulesets/unusedcode.xml/UnusedLocalVariable" />
</ruleset>
```

### PHP CodeSniffer Configuration: `.phpcs.xml`
```xml
<?xml version="1.0"?>
<ruleset name="Perfumes Management System">
    <description>Coding standard for Perfumes Management System</description>
    
    <file>includes</file>
    <file>admin</file>
    <file>*.php</file>
    
    <exclude-pattern>vendor</exclude-pattern>
    
    <rule ref="PSR12"/>
    <rule ref="Generic.Arrays.DisallowLongArraySyntax"/>
    <rule ref="Generic.Formatting.SpaceAfterNot"/>
</ruleset>
```

---

## 5. Performance Testing {#performance-testing}

### JMeter Test Plan: `tests/performance/perfumes_system.jmx`
```xml
<?xml version="1.0" encoding="UTF-8"?>
<jmeterTestPlan version="1.2">
  <hashTree>
    <TestPlan guiclass="TestPlanGui" testclass="TestPlan" testname="Perfumes System Load Test">
      <elementProp name="TestPlan.arguments" elementType="Arguments" guiclass="ArgumentsPanel">
        <collectionProp name="Arguments.arguments"/>
      </elementProp>
      <stringProp name="TestPlan.user_define_classpath"></stringProp>
      <boolProp name="TestPlan.functional_mode">false</boolProp>
      <boolProp name="TestPlan.serialize_threadgroups">false</boolProp>
      <elementProp name="TestPlan.arguments" elementType="Arguments">
        <collectionProp name="Arguments.arguments"/>
      </elementProp>
    </TestPlan>
    <hashTree>
      <ThreadGroup guiclass="ThreadGroupGui" testclass="ThreadGroup" testname="User Load Test">
        <stringProp name="ThreadGroup.on_sample_error">continue</stringProp>
        <elementProp name="ThreadGroup.main_controller" elementType="LoopController">
          <boolProp name="LoopController.continue_forever">false</boolProp>
          <intProp name="LoopController.loops">10</intProp>
        </elementProp>
        <stringProp name="ThreadGroup.num_threads">50</stringProp>
        <stringProp name="ThreadGroup.ramp_time">60</stringProp>
        <longProp name="ThreadGroup.start_time">1</longProp>
        <longProp name="ThreadGroup.end_time">1</longProp>
        <boolProp name="ThreadGroup.scheduler">false</boolProp>
        <stringProp name="ThreadGroup.duration"></stringProp>
        <stringProp name="ThreadGroup.delay"></stringProp>
      </ThreadGroup>
      <hashTree>
        <HTTPSamplerProxy guiclass="HttpTestSampleGui" testclass="HTTPSamplerProxy" testname="Home Page">
          <elementProp name="HTTPsampler.Arguments" elementType="Arguments">
            <collectionProp name="Arguments.arguments"/>
          </elementProp>
          <stringProp name="HTTPSampler.domain">localhost</stringProp>
          <stringProp name="HTTPSampler.port">80</stringProp>
          <stringProp name="HTTPSampler.path">/Perfumes Management System/index.html</stringProp>
          <stringProp name="HTTPSampler.method">GET</stringProp>
        </HTTPSamplerProxy>
        <hashTree/>
        <HTTPSamplerProxy guiclass="HttpTestSampleGui" testclass="HTTPSamplerProxy" testname="Products Page">
          <stringProp name="HTTPSampler.path">/Perfumes Management System/products.php</stringProp>
          <stringProp name="HTTPSampler.method">GET</stringProp>
        </HTTPSamplerProxy>
        <hashTree/>
      </hashTree>
    </hashTree>
  </hashTree>
</jmeterTestPlan>
```

---

## 6. Mocking with Mockery {#mocking}

### Installation
```bash
composer require --dev mockery/mockery
```

### Example: `tests/Unit/OrderServiceTest.php`
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use OrderRepository;
use ProductRepository;
use OrderService;

class OrderServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCreateOrderWithValidProducts()
    {
        // Create mocks
        $orderRepo = Mockery::mock(OrderRepository::class);
        $productRepo = Mockery::mock(ProductRepository::class);

        // Setup expectations
        $productRepo->shouldReceive('getStock')
            ->with(1)
            ->once()
            ->andReturn(10);

        $productRepo->shouldReceive('updateStock')
            ->with(1, 2)
            ->once()
            ->andReturn(true);

        $orderRepo->shouldReceive('create')
            ->once()
            ->with(1, 100.00, 'momo', 'pending_payment', '+1234567890')
            ->andReturn(123);

        // Create service with mocked dependencies
        $service = new OrderService($orderRepo, $productRepo);
        
        $result = $service->createOrder([
            ['id' => 1, 'qty' => 2, 'price' => 50.00]
        ], 1);

        $this->assertEquals(123, $result);
    }
}
```

---

## 7. Setup Instructions {#setup}

### Complete Setup Script: `setup_testing.sh`
```bash
#!/bin/bash

echo "Setting up testing environment..."

# Install Composer dependencies
composer install --dev

# Install PHPUnit
composer require --dev phpunit/phpunit

# Install Codeception
composer require --dev codeception/codeception codeception/module-webdriver

# Install Behat
composer require --dev behat/behat

# Install Static Analysis Tools
composer require --dev phpstan/phpstan
composer require --dev vimeo/psalm
composer require --dev phpmd/phpmd
composer require --dev squizlabs/php_codesniffer

# Install Mockery
composer require --dev mockery/mockery

# Create test database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS perfume_db_test;"

# Run migrations on test database
mysql -u root perfume_db_test < database/schema.sql

echo "Testing environment setup complete!"
```

### Running Tests

```bash
# Run PHPUnit tests
./vendor/bin/phpunit

# Run Codeception tests
./vendor/bin/codecept run

# Run Behat tests
./vendor/bin/behat

# Run PHPStan
./vendor/bin/phpstan analyse

# Run Psalm
./vendor/bin/psalm

# Run PHPMD
./vendor/bin/phpmd includes text phpmd.xml

# Run PHP CodeSniffer
./vendor/bin/phpcs --standard=PSR12 includes admin
```

---

## 8. Continuous Integration (CI) Configuration

### GitHub Actions: `.github/workflows/tests.yml`
```yaml
name: Tests

on: [push, pull_request]

jobs:
  unit-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
      - name: Install dependencies
        run: composer install
      - name: Run PHPUnit
        run: ./vendor/bin/phpunit
      - name: Run PHPStan
        run: ./vendor/bin/phpstan analyse
      - name: Run CodeSniffer
        run: ./vendor/bin/phpcs --standard=PSR12 includes admin
```

---

## Summary

This comprehensive testing setup provides:
- ✅ Unit testing with PHPUnit
- ✅ Functional & Acceptance testing with Codeception
- ✅ BDD with Behat
- ✅ Static analysis with PHPStan, Psalm, PHPMD
- ✅ Code quality with PHP CodeSniffer
- ✅ Performance testing with JMeter
- ✅ Mocking with Mockery
- ✅ CI/CD integration

All tools are configured and ready to use!


