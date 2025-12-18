<?php
/**
 * OrderRepository - Repository Pattern Implementation
 * 
 * Encapsulates all order-related database operations
 */
class OrderRepository {
    private $conn;
    
    public function __construct(mysqli $connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create a new order
     * @param int $userId
     * @param float $total
     * @param string $paymentMethod
     * @param string $status
     * @param string $momoNumber
     * @return int|false Order ID or false on failure
     */
    public function create(int $userId, float $total, string $paymentMethod, string $status, string $momoNumber) {
        $stmt = $this->conn->prepare("INSERT INTO orders (user_id, total, payment_method, status, momo_number) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param('idsss', $userId, $total, $paymentMethod, $status, $momoNumber);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $orderId = $this->conn->insert_id;
        $stmt->close();
        
        return $orderId;
    }
    
    /**
     * Find order by ID and user ID
     * @param int $orderId
     * @param int $userId
     * @return array|null Order data or null
     */
    public function findByIdAndUserId(int $orderId, int $userId): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        return $order ?: null;
    }
    
    /**
     * Update order status
     * @param int $orderId
     * @param string $status
     * @return bool Success status
     */
    public function updateStatus(int $orderId, string $status): bool {
        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('si', $status, $orderId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get today's revenue for approved orders
     * @param string $date Date in Y-m-d format
     * @return array Revenue and order count
     */
    public function getTodayRevenue(string $date): array {
        $has_grand = $this->hasColumn('orders', 'grand_total');
        $has_total = $this->hasColumn('orders', 'total');
        $amount_col = $has_grand ? 'grand_total' : 'total';
        
        $has_status = $this->hasColumn('orders', 'status');
        $statusWhere = $has_status ? " AND status IN ('completed','approved','paid')" : '';
        
        $sql = "SELECT IFNULL(SUM($amount_col),0) AS revenue, COUNT(*) AS orders 
                FROM orders 
                WHERE DATE(created_at) = ? $statusWhere";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['revenue' => 0, 'orders' => 0];
        }
        
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ?: ['revenue' => 0, 'orders' => 0];
    }
    
    /**
     * Check if a column exists in a table
     * @param string $table
     * @param string $column
     * @return bool
     */
    private function hasColumn(string $table, string $column): bool {
        $result = $this->conn->query("SHOW COLUMNS FROM $table LIKE '" . $this->conn->real_escape_string($column) . "'");
        return $result && $result->num_rows > 0;
    }
}



