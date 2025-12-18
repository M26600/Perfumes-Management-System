# Testing Plan for Perfumes Management System

## 1. Overview
This document outlines the comprehensive testing strategy for the Perfumes Management System, covering functional, integration, security, and user acceptance testing.

## 2. Testing Objectives
- Ensure all features work as expected
- Verify data integrity and security
- Validate user workflows
- Test system performance under load
- Ensure cross-browser compatibility

## 3. Test Environment Setup
- **PHP Version**: 7.4 or higher
- **Database**: MySQL 5.7+
- **Web Server**: Apache/Nginx
- **Browsers**: Chrome, Firefox, Safari, Edge (latest versions)
- **Testing Tools**: PHPUnit, Selenium WebDriver, Postman

## 4. Test Cases

### 4.1 User Authentication & Registration

#### TC-001: User Registration - Valid Data
- **Objective**: Verify successful user registration
- **Preconditions**: User is on registration page
- **Test Steps**:
  1. Enter valid username (min 3 characters)
  2. Enter valid email (format: user@example.com)
  3. Enter password (min 6 characters)
  4. Click "Register"
- **Expected Result**: User is registered and redirected to login page
- **Priority**: High

#### TC-002: User Registration - Duplicate Email
- **Objective**: Prevent duplicate email registration
- **Preconditions**: Email already exists in database
- **Test Steps**:
  1. Enter existing email address
  2. Fill other required fields
  3. Click "Register"
- **Expected Result**: Error message displayed "Email already registered"
- **Priority**: High

#### TC-003: User Registration - Invalid Email Format
- **Objective**: Validate email format
- **Test Steps**:
  1. Enter invalid email (e.g., "invalid-email")
  2. Fill other fields
  3. Click "Register"
- **Expected Result**: Error message "Please enter a valid email address"
- **Priority**: Medium

#### TC-004: User Login - Valid Credentials
- **Objective**: Verify successful login
- **Preconditions**: User account exists
- **Test Steps**:
  1. Enter correct email
  2. Enter correct password
  3. Click "Login"
- **Expected Result**: User logged in, redirected to products page
- **Priority**: High

#### TC-005: User Login - Invalid Credentials
- **Objective**: Prevent unauthorized access
- **Test Steps**:
  1. Enter incorrect email or password
  2. Click "Login"
- **Expected Result**: Error message "Invalid email or password"
- **Priority**: High

### 4.2 Product Management

#### TC-006: View Products List
- **Objective**: Display all available products
- **Preconditions**: User is logged in, products exist in database
- **Test Steps**:
  1. Navigate to products page
- **Expected Result**: All products with stock > 0 are displayed
- **Priority**: High

#### TC-007: Add Product to Cart
- **Objective**: Add product to shopping cart
- **Preconditions**: User logged in, product available
- **Test Steps**:
  1. Click "Add to Cart" on a product
  2. Navigate to cart page
- **Expected Result**: Product appears in cart with correct details
- **Priority**: High

#### TC-008: Add Product to Cart - Out of Stock
- **Objective**: Prevent adding out-of-stock products
- **Preconditions**: Product stock = 0
- **Test Steps**:
  1. Try to add out-of-stock product
- **Expected Result**: Product disabled or error message shown
- **Priority**: Medium

#### TC-009: Update Cart Quantity
- **Objective**: Modify product quantity in cart
- **Preconditions**: Product in cart
- **Test Steps**:
  1. Change quantity in cart
  2. Click update
- **Expected Result**: Quantity updated, total recalculated
- **Priority**: Medium

#### TC-010: Remove Product from Cart
- **Objective**: Remove item from cart
- **Test Steps**:
  1. Click remove on cart item
- **Expected Result**: Item removed, cart updated
- **Priority**: Medium

### 4.3 Order Processing

#### TC-011: Checkout Process - Complete Order
- **Objective**: Create order successfully
- **Preconditions**: Cart has items, user logged in
- **Test Steps**:
  1. Go to cart
  2. Click "Checkout"
  3. Verify order details
  4. Complete checkout
- **Expected Result**: Order created, stock reduced, cart cleared
- **Priority**: High

#### TC-012: Checkout - Insufficient Stock
- **Objective**: Handle stock conflicts
- **Preconditions**: Product stock becomes 0 before checkout
- **Test Steps**:
  1. Add product to cart
  2. Another user purchases all stock
  3. Attempt checkout
- **Expected Result**: Error message, order not created
- **Priority**: High

#### TC-013: Payment Proof Upload
- **Objective**: Upload payment evidence
- **Preconditions**: Order created, status = awaiting_proof
- **Test Steps**:
  1. Navigate to payment proof page
  2. Upload valid image (JPG/PNG/WebP)
  3. Submit
- **Expected Result**: Proof uploaded, status = pending_cashier_review
- **Priority**: High

#### TC-014: Payment Proof Upload - Invalid File
- **Objective**: Reject invalid file types
- **Test Steps**:
  1. Try to upload non-image file
- **Expected Result**: Error message "Invalid file"
- **Priority**: Medium

### 4.4 Cashier Functions

#### TC-015: Cashier Approve Payment
- **Objective**: Approve valid payment proof
- **Preconditions**: Cashier logged in, pending payment exists
- **Test Steps**:
  1. View pending payments
  2. Click "Approve" on valid proof
- **Expected Result**: Order status = completed, loyalty points awarded
- **Priority**: High

#### TC-016: Cashier Reject Payment
- **Objective**: Reject invalid payment proof
- **Test Steps**:
  1. View pending payment
  2. Click "Reject"
- **Expected Result**: Order status = rejected, no loyalty points, stock restored
- **Priority**: High

### 4.5 Loyalty Points System

#### TC-017: Earn Loyalty Points
- **Objective**: Award points on approved order
- **Preconditions**: Order approved by cashier
- **Test Steps**:
  1. Complete order
  2. Cashier approves payment
- **Expected Result**: User receives 1 loyalty point
- **Priority**: High

#### TC-018: Redeem Free Perfume
- **Objective**: Redeem free perfume with 6+ points
- **Preconditions**: User has 6+ loyalty points
- **Test Steps**:
  1. Navigate to redeem page
  2. Select free perfume
  3. Confirm redemption
- **Expected Result**: Perfume added to account, 6 points deducted
- **Priority**: High

#### TC-019: Redeem Free Perfume - Insufficient Points
- **Objective**: Prevent redemption with < 6 points
- **Preconditions**: User has < 6 points
- **Test Steps**:
  1. Try to access redeem page
- **Expected Result**: Error or redirect, redemption not allowed
- **Priority**: Medium

### 4.6 Admin Functions

#### TC-020: View Today Revenue
- **Objective**: Display revenue for approved orders only
- **Preconditions**: Admin logged in, approved orders exist
- **Test Steps**:
  1. Navigate to Reports page
- **Expected Result**: Revenue shows only completed/approved orders
- **Priority**: High

#### TC-021: Add New Product
- **Objective**: Admin adds product to catalog
- **Preconditions**: Admin logged in
- **Test Steps**:
  1. Go to Perfumes management
  2. Fill product details
  3. Upload image
  4. Save
- **Expected Result**: Product added successfully
- **Priority**: High

#### TC-022: Update Product Stock
- **Objective**: Modify product inventory
- **Test Steps**:
  1. Edit product
  2. Update stock quantity
  3. Save
- **Expected Result**: Stock updated in database
- **Priority**: Medium

### 4.7 Security Testing

#### TC-023: SQL Injection Prevention
- **Objective**: Prevent SQL injection attacks
- **Test Steps**:
  1. Enter SQL injection string in login form: `' OR '1'='1`
- **Expected Result**: Query fails safely, no data exposed
- **Priority**: Critical

#### TC-024: XSS Prevention
- **Objective**: Prevent cross-site scripting
- **Test Steps**:
  1. Enter script tag in form: `<script>alert('XSS')</script>`
- **Expected Result**: Script sanitized/escaped in output
- **Priority**: Critical

#### TC-025: Session Management
- **Objective**: Verify secure session handling
- **Test Steps**:
  1. Login
  2. Check session ID changes on login
  3. Logout
  4. Try to access protected page
- **Expected Result**: Session invalidated, redirect to login
- **Priority**: High

#### TC-026: Authorization Check
- **Objective**: Prevent unauthorized access
- **Test Steps**:
  1. Regular user tries to access admin page
- **Expected Result**: Redirect to products page
- **Priority**: High

### 4.8 Integration Testing

#### TC-027: Complete Order Flow
- **Objective**: Test end-to-end order process
- **Test Steps**:
  1. User registers
  2. User logs in
  3. Adds products to cart
  4. Checks out
  5. Uploads payment proof
  6. Cashier approves
  7. Verify order completion
- **Expected Result**: All steps complete successfully
- **Priority**: High

#### TC-028: Database Transaction Integrity
- **Objective**: Ensure data consistency
- **Test Steps**:
  1. Create order
  2. Simulate failure during stock update
- **Expected Result**: Transaction rolled back, no partial data
- **Priority**: High

### 4.9 Performance Testing

#### TC-029: Page Load Time
- **Objective**: Ensure pages load within acceptable time
- **Test Steps**:
  1. Measure load time for products page
  2. Measure load time with 100+ products
- **Expected Result**: Page loads < 3 seconds
- **Priority**: Medium

#### TC-030: Concurrent Users
- **Objective**: Test system under load
- **Test Steps**:
  1. Simulate 50 concurrent users
  2. Monitor response times
- **Expected Result**: System handles load gracefully
- **Priority**: Medium

### 4.10 User Interface Testing

#### TC-031: Responsive Design - Mobile
- **Objective**: Verify mobile compatibility
- **Test Steps**:
  1. Access site on mobile device (320px width)
  2. Test navigation, forms, cart
- **Expected Result**: All features accessible and usable
- **Priority**: Medium

#### TC-032: Browser Compatibility
- **Objective**: Test across browsers
- **Test Steps**:
  1. Test in Chrome, Firefox, Safari, Edge
- **Expected Result**: Consistent appearance and functionality
- **Priority**: Medium

## 5. Test Execution Schedule

### Phase 1: Unit Testing (Week 1)
- Test individual functions and classes
- Repository pattern classes
- Authentication functions

### Phase 2: Integration Testing (Week 2)
- Database operations
- Order processing flow
- Payment workflow

### Phase 3: System Testing (Week 3)
- End-to-end scenarios
- Security testing
- Performance testing

### Phase 4: User Acceptance Testing (Week 4)
- Real user scenarios
- Feedback collection
- Bug fixes

## 6. Bug Tracking
- **Severity Levels**: Critical, High, Medium, Low
- **Status**: New, In Progress, Fixed, Closed
- **Tool**: GitHub Issues or Jira

## 7. Test Coverage Goals
- **Code Coverage**: Minimum 70%
- **Function Coverage**: 100% of critical functions
- **Branch Coverage**: Minimum 60%

## 8. Regression Testing
After each bug fix or feature addition:
- Run all high-priority test cases
- Verify related functionality
- Check for new issues introduced

## 9. Test Deliverables
1. Test Plan Document (this document)
2. Test Cases Documentation
3. Test Execution Reports
4. Bug Reports
5. Test Summary Report

## 10. Sign-off Criteria
- All critical and high-priority test cases passed
- No critical bugs remaining
- Performance benchmarks met
- Security vulnerabilities addressed
- User acceptance testing completed



