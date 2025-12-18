<?php
/**
 * ProductRepository - Repository Pattern Implementation
 * 
 * This class implements the Repository Pattern to encapsulate the logic
 * needed to access product data sources. It provides a cleaner separation
 * between business logic and data access logic.
 */
class ProductRepository {
    private $conn;
    
    public function __construct(mysqli $connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get all products with stock > 0
     * @return array Array of product data
     */
    public function findAllAvailable(): array {
        $has_discount = $this->hasDiscountColumn();
        $selectCols = "id, name, brand, price, stock, image" . ($has_discount ? ", discount_percent" : "");
        
        $result = $this->conn->query("SELECT $selectCols FROM products WHERE stock > 0 ORDER BY name ASC");
        
        if ($result === false) {
            error_log("Product query failed: " . $this->conn->error);
            return [];
        }
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    }
    
    /**
     * Find a product by ID
     * @param int $productId
     * @return array|null Product data or null if not found
     */
    public function findById(int $productId): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        return $product ?: null;
    }
    
    /**
     * Update product stock
     * @param int $productId
     * @param int $quantity Quantity to subtract
     * @return bool Success status
     */
    public function updateStock(int $productId, int $quantity): bool {
        $stmt = $this->conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed for stock update: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('ii', $quantity, $productId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get current stock for a product
     * @param int $productId
     * @return int Stock quantity
     */
    public function getStock(int $productId): int {
        $stmt = $this->conn->prepare("SELECT stock FROM products WHERE id = ?");
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (int)$row['stock'] : 0;
    }
    
    /**
     * Check if discount_percent column exists
     * @return bool
     */
    private function hasDiscountColumn(): bool {
        $result = $this->conn->query("SHOW COLUMNS FROM products LIKE 'discount_percent'");
        return $result && $result->num_rows > 0;
    }
}



