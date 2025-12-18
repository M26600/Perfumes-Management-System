<?php
/**
 * OrderItemRepository - Repository Pattern Implementation
 * 
 * Handles order items database operations
 */
class OrderItemRepository {
    private $conn;
    
    public function __construct(mysqli $connection) {
        $this->conn = $connection;
    }
    
    /**
     * Add items to an order
     * @param int $orderId
     * @param array $items Array of items with product_id, quantity, price
     * @return bool Success status
     */
    public function addItems(int $orderId, array $items): bool {
        $stmt = $this->conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed for order items: " . $this->conn->error);
            return false;
        }
        
        foreach ($items as $item) {
            $stmt->bind_param('iiid', $orderId, $item['id'], $item['qty'], $item['price']);
            if (!$stmt->execute()) {
                error_log("Execute failed for order item: " . $stmt->error);
                $stmt->close();
                return false;
            }
        }
        
        $stmt->close();
        return true;
    }
    
    /**
     * Get all items for an order
     * @param int $orderId
     * @return array Array of order items
     */
    public function findByOrderId(int $orderId): array {
        $stmt = $this->conn->prepare("
            SELECT oi.order_id, oi.product_id, oi.quantity, oi.price, 
                   p.name as product_name 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['item_total'] = (float)$row['price'] * (int)$row['quantity'];
            $items[] = $row;
        }
        
        $stmt->close();
        return $items;
    }
}



