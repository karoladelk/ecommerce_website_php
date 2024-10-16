

<?php
// Database connection details
$host = 'ecommerce-karol.wuaze.com';  // e.g., localhost
$db = 'if0_37521547_data';
$user = 'if0_37521547';
$pass = 'tpyM1BskmW';
$port = '8000';  // Confirm if needed


try {
    // Connect to MySQL database
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Load JSON data from file
    $json = file_get_contents('data.json');
    $data = json_decode($json, true)['data'];

    // Populate Categories
    $stmt = $pdo->prepare("INSERT INTO categories (id, name) VALUES (:id, :name)");
    foreach ($data['categories'] as $category) {
        $stmt->execute([
            ':id' => $category['id'],
            ':name' => $category['name']
        ]);
    }

    // Populate Products
    $stmtProduct = $pdo->prepare("INSERT INTO products (id, name, description, category_id, inStock, brand) VALUES (:id, :name, :description, :category_id, :inStock, :brand)");
    $stmtGallery = $pdo->prepare("INSERT INTO galleries (product_id, image_url) VALUES (:product_id, :image_url)");
    $stmtAttribute = $pdo->prepare("INSERT INTO attributes (name) VALUES (:name)");
    $stmtAttributeItem = $pdo->prepare("INSERT INTO attribute_items (attribute_id, value, displayValue) VALUES (:attribute_id, :value, :displayValue)");
    $stmtPrice = $pdo->prepare("INSERT INTO prices (product_id, amount, currency_id) VALUES (:product_id, :amount, :currency_id)");

    foreach ($data['products'] as $product) {
        // Insert the product
        $stmtProduct->execute([
            ':id' => $product['id'],
            ':name' => $product['name'],
            ':description' => $product['description'],
            ':category_id' => $product['category_id'],
            ':inStock' => $product['inStock'],
            ':brand' => $product['brand']
        ]);

        // Insert gallery images
        foreach ($product['gallery'] as $image) {
            $stmtGallery->execute([
                ':product_id' => $product['id'],
                ':image_url' => $image
            ]);
        }

        // Insert attributes and their items
        foreach ($product['attributes'] as $attribute) {
            $stmtAttribute->execute([':name' => $attribute['name']]);
            $attributeId = $pdo->lastInsertId();

            foreach ($attribute['items'] as $item) {
                $stmtAttributeItem->execute([
                    ':attribute_id' => $attributeId,
                    ':value' => $item['value'],
                    ':displayValue' => $item['displayValue']
                ]);
            }
        }

        // Insert prices
        foreach ($product['prices'] as $price) {
            $currencyStmt = $pdo->prepare("SELECT id FROM currencies WHERE label = :label");
            $currencyStmt->execute([':label' => $price['currency']['label']]);
            $currencyId = $currencyStmt->fetchColumn();
            
            if (!$currencyId) {
                $stmtCurrency = $pdo->prepare("INSERT INTO currencies (label, symbol) VALUES (:label, :symbol)");
                $stmtCurrency->execute([
                    ':label' => $price['currency']['label'],
                    ':symbol' => $price['currency']['symbol']
                ]);
                $currencyId = $pdo->lastInsertId();
            }

            $stmtPrice->execute([
                ':product_id' => $product['id'],
                ':amount' => $price['amount'],
                ':currency_id' => $currencyId
            ]);
        }
    }

    echo "Data has been successfully populated into the database.";
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
