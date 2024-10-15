<?php

namespace App\Repositories;

use App\Models\Product;
use App\Services\ProductService;

class ProductRepository extends BaseRepository
{
    public function getAllProducts(string $category): ?array
    {
        $sql = "
            SELECT p.id, p.name, p.description, p.in_stock, p.brand, 
            pr.amount, c.id as currency_id, c.label as currency_label, c.symbol as currency_symbol, 
            GROUP_CONCAT(pi.image_url SEPARATOR '||') as image_urls, 
            cat.name as category_name
            FROM products p
            LEFT JOIN prices pr ON p.id = pr.product_id
            LEFT JOIN categories cat ON cat.id = p.category_id
            LEFT JOIN currencies c ON pr.currency_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id
        ";

        if ($category !== 'all') {
            $sql .= " WHERE cat.name = :category ";
        }

        $sql .= " GROUP BY p.id, pr.amount, c.id, c.label, c.symbol, cat.name ";

        $params = $category !== 'all' ? [':category' => $category] : [];
        $products = $this->fetchAll($sql, $params);

        return ProductService::groupProducts($products);
    }

    public function getProductById(string $id): ?Product
    {
        $stmt = $this->db->prepare("
        SELECT 
            p.id, 
            p.name, 
            p.description, 
            p.in_stock, 
            p.brand, 
            pr.amount, 
            c.id AS currency_id, 
            c.label AS currency_label, 
            c.symbol AS currency_symbol, 
            GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.id SEPARATOR '||') AS image_urls, 
            cat.name AS category_name,
            a.id AS attribute_id, 
            a.name AS attribute_name, 
            a.type AS attribute_type,
            ai.id AS attribute_item_id, 
            ai.value AS attribute_item_value, 
            ai.display_value AS attribute_item_display_value
        FROM products p 
        LEFT JOIN prices pr ON p.id = pr.product_id
        LEFT JOIN categories cat ON cat.id = p.category_id
        LEFT JOIN currencies c ON pr.currency_id = c.id
        LEFT JOIN product_images pi ON p.id = pi.product_id
        LEFT JOIN product_attributes pa ON p.id = pa.product_id
        LEFT JOIN attributes a ON pa.attribute_id = a.id
        LEFT JOIN attribute_items ai ON pa.attribute_item_id = ai.id
        WHERE p.id = :id
        GROUP BY 
            p.id, pr.amount, c.id, c.label, c.symbol, cat.name, 
            a.id, a.name, a.type, ai.id, ai.value, ai.display_value
        ORDER BY a.name, ai.value
    ");

        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $productData = $stmt->fetchAll();

        if ($productData) {
            return ProductService::mapRowToProduct($productData);
        }

        return null;
    }
}
