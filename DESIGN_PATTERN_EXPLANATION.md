# Repository Pattern Implementation

## Overview
The Repository Pattern has been implemented in the Perfumes Management System to separate data access logic from business logic. This provides better code organization, testability, and maintainability.

## Pattern Description
The Repository Pattern acts as a mediator between the domain/business logic and the data mapping layer. It provides a collection-like interface for accessing domain objects.

## Benefits
1. **Separation of Concerns**: Business logic is separated from data access logic
2. **Testability**: Easy to mock repositories for unit testing
3. **Maintainability**: Changes to database structure only affect repository classes
4. **Reusability**: Repository methods can be reused across different parts of the application
5. **Single Responsibility**: Each repository handles one entity type

## Implementation

### Repository Classes Created

#### 1. ProductRepository (`includes/Repository/ProductRepository.php`)
Handles all product-related database operations:
- `findAllAvailable()`: Get all products with stock > 0
- `findById($productId)`: Find a specific product
- `updateStock($productId, $quantity)`: Update product stock
- `getStock($productId)`: Get current stock level

#### 2. OrderRepository (`includes/Repository/OrderRepository.php`)
Manages order database operations:
- `create()`: Create a new order
- `findByIdAndUserId()`: Find order by ID and user
- `updateStatus()`: Update order status
- `getTodayRevenue()`: Calculate revenue for approved orders

#### 3. OrderItemRepository (`includes/Repository/OrderItemRepository.php`)
Handles order items:
- `addItems()`: Add items to an order
- `findByOrderId()`: Get all items for an order

## Usage Example

### Before (Without Repository Pattern):
```php
// Direct database access in checkout.php
$stmt = $conn->prepare("INSERT INTO orders (user_id, total, payment_method, status, momo_number) VALUES (?, ?, 'momo', 'pending_payment', ?)");
$stmt->bind_param('ids', $user_id, $grand_total, $merchant_momo_number);
$stmt->execute();
$order_id = $conn->insert_id;
```

### After (With Repository Pattern):
```php
// Clean, reusable code
require_once 'includes/Repository/OrderRepository.php';
require_once 'includes/Repository/OrderItemRepository.php';
require_once 'includes/Repository/ProductRepository.php';

$orderRepo = new OrderRepository($conn);
$orderItemRepo = new OrderItemRepository($conn);
$productRepo = new ProductRepository($conn);

// Create order
$order_id = $orderRepo->create($user_id, $grand_total, 'momo', 'pending_payment', $merchant_momo_number);

// Add items
$orderItemRepo->addItems($order_id, $items);

// Update stock
foreach ($cart as $item) {
    $productRepo->updateStock($item['id'], $item['qty']);
}
```

## Integration Points

To use repositories in existing code:

1. **Include repository files**:
```php
require_once 'includes/Repository/ProductRepository.php';
require_once 'includes/Repository/OrderRepository.php';
require_once 'includes/Repository/OrderItemRepository.php';
```

2. **Initialize repositories**:
```php
$productRepo = new ProductRepository($conn);
$orderRepo = new OrderRepository($conn);
```

3. **Use repository methods**:
```php
$products = $productRepo->findAllAvailable();
$order = $orderRepo->findByIdAndUserId($order_id, $user_id);
```

## Testing Benefits

With the Repository Pattern, you can easily create mock repositories for testing:

```php
class MockProductRepository extends ProductRepository {
    public function findAllAvailable(): array {
        return [
            ['id' => 1, 'name' => 'Test Product', 'price' => 50.00]
        ];
    }
}
```

## Future Enhancements

1. **Add more repositories**: UserRepository, PaymentRepository
2. **Add caching layer**: Cache frequently accessed data
3. **Add query builders**: For complex queries
4. **Add transaction management**: Handle multi-repository transactions

## Files Modified/Created

- ✅ Created: `includes/Repository/ProductRepository.php`
- ✅ Created: `includes/Repository/OrderRepository.php`
- ✅ Created: `includes/Repository/OrderItemRepository.php`
- ✅ Created: `DESIGN_PATTERN_EXPLANATION.md` (this file)

## Migration Path

To migrate existing code to use repositories:
1. Identify database operations in each file
2. Replace direct queries with repository method calls
3. Test thoroughly
4. Refactor incrementally (file by file)



