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
                ['id' => 1, 'name' => 'Test Perfume', 'price' => 50.00, 'stock' => 10],
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
            ->willReturn(['id' => 1, 'name' => 'Test', 'price' => 50.00, 'stock' => 10]);

        $mockStmt->method('get_result')->willReturn($mockResult);
        $mockStmt->expects($this->once())->method('bind_param')->with('i', $productId);
        $mockStmt->expects($this->once())->method('execute');
        $mockStmt->expects($this->once())->method('close');

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM products WHERE id = ?'))
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

    public function testGetStockReturnsCorrectValue()
    {
        $productId = 1;
        $expectedStock = 15;
        
        $mockStmt = $this->createMock(\mysqli_stmt::class);
        $mockResult = $this->createMock(\mysqli_result::class);
        
        $mockResult->method('fetch_assoc')
            ->willReturn(['stock' => $expectedStock]);

        $mockStmt->method('get_result')->willReturn($mockResult);
        $mockStmt->expects($this->once())->method('bind_param')->with('i', $productId);
        $mockStmt->expects($this->once())->method('execute');
        $mockStmt->expects($this->once())->method('close');

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT stock FROM products'))
            ->willReturn($mockStmt);

        $stock = $this->repository->getStock($productId);
        $this->assertEquals($expectedStock, $stock);
    }
}


