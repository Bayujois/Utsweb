<?php
// api/products.php - API CRUD Produk

require_once '../config.php';

requireAuth();

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

// GET ALL PRODUCTS
if ($method === 'GET' && !isset($_GET['id']) && !isset($_GET['stats'])) {
    $sql = "SELECT * FROM products WHERE user_id = $userId ORDER BY created_at DESC";
    $result = $db->query($sql);
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'category' => $row['category'],
            'stock' => (int)$row['stock'],
            'price' => (float)$row['price'],
            'description' => $row['description'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    jsonResponse([
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ]);
}

// GET SINGLE PRODUCT
if ($method === 'GET' && isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    
    $result = $db->query("SELECT * FROM products WHERE id = $productId AND user_id = $userId");
    
    if ($result->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
    }
    
    $product = $result->fetch_assoc();
    
    jsonResponse([
        'success' => true,
        'data' => [
            'id' => (int)$product['id'],
            'code' => $product['code'],
            'name' => $product['name'],
            'category' => $product['category'],
            'stock' => (int)$product['stock'],
            'price' => (float)$product['price'],
            'description' => $product['description'],
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at']
        ]
    ]);
}

// GET STATISTICS
if ($method === 'GET' && isset($_GET['stats'])) {
    $totalProducts = $db->query("SELECT COUNT(*) as total FROM products WHERE user_id = $userId")->fetch_assoc()['total'];
    $totalStock = $db->query("SELECT SUM(stock) as total FROM products WHERE user_id = $userId")->fetch_assoc()['total'] ?? 0;
    $totalValue = $db->query("SELECT SUM(stock * price) as total FROM products WHERE user_id = $userId")->fetch_assoc()['total'] ?? 0;
    
    $categoriesResult = $db->query("SELECT category, COUNT(*) as count FROM products WHERE user_id = $userId GROUP BY category");
    $categories = [];
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
    
    jsonResponse([
        'success' => true,
        'data' => [
            'total_products' => (int)$totalProducts,
            'total_stock' => (int)$totalStock,
            'total_value' => (float)$totalValue,
            'categories' => $categories
        ]
    ]);
}

// CREATE PRODUCT
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $code = $db->escape_string($data['code'] ?? '');
    $name = $db->escape_string($data['name'] ?? '');
    $category = $db->escape_string($data['category'] ?? '');
    $stock = (int)($data['stock'] ?? 0);
    $price = (float)($data['price'] ?? 0);
    $description = $db->escape_string($data['description'] ?? '');
    
    if (empty($code) || empty($name) || empty($category)) {
        jsonResponse(['success' => false, 'message' => 'Kode, nama, dan kategori harus diisi'], 400);
    }
    
    if ($stock < 0 || $price < 0) {
        jsonResponse(['success' => false, 'message' => 'Stok dan harga tidak boleh negatif'], 400);
    }
    
    $checkCode = $db->query("SELECT id FROM products WHERE code = '$code' AND user_id = $userId");
    if ($checkCode->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Kode produk sudah digunakan'], 400);
    }
    
    $sql = "INSERT INTO products (user_id, code, name, category, stock, price, description) 
            VALUES ($userId, '$code', '$name', '$category', $stock, $price, '$description')";
    
    if ($db->query($sql)) {
        $productId = $db->insert_id;
        
        jsonResponse([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => [
                'id' => $productId,
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'stock' => $stock,
                'price' => $price,
                'description' => $description
            ]
        ], 201);
    } else {
        jsonResponse(['success' => false, 'message' => 'Gagal menambahkan produk'], 500);
    }
}

// UPDATE PRODUCT
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productId = (int)($data['id'] ?? 0);
    $code = $db->escape_string($data['code'] ?? '');
    $name = $db->escape_string($data['name'] ?? '');
    $category = $db->escape_string($data['category'] ?? '');
    $stock = (int)($data['stock'] ?? 0);
    $price = (float)($data['price'] ?? 0);
    $description = $db->escape_string($data['description'] ?? '');
    
    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID produk tidak valid'], 400);
    }
    
    if (empty($code) || empty($name) || empty($category)) {
        jsonResponse(['success' => false, 'message' => 'Kode, nama, dan kategori harus diisi'], 400);
    }
    
    if ($stock < 0 || $price < 0) {
        jsonResponse(['success' => false, 'message' => 'Stok dan harga tidak boleh negatif'], 400);
    }
    
    $checkProduct = $db->query("SELECT id FROM products WHERE id = $productId AND user_id = $userId");
    if ($checkProduct->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
    }
    
    $checkCode = $db->query("SELECT id FROM products WHERE code = '$code' AND user_id = $userId AND id != $productId");
    if ($checkCode->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Kode produk sudah digunakan'], 400);
    }
    
    $sql = "UPDATE products SET 
            code = '$code',
            name = '$name',
            category = '$category',
            stock = $stock,
            price = $price,
            description = '$description'
            WHERE id = $productId AND user_id = $userId";
    
    if ($db->query($sql)) {
        jsonResponse([
            'success' => true,
            'message' => 'Produk berhasil diupdate',
            'data' => [
                'id' => $productId,
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'stock' => $stock,
                'price' => $price,
                'description' => $description
            ]
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Gagal mengupdate produk'], 500);
    }
}

// DELETE PRODUCT
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($data['id'] ?? $_GET['id'] ?? 0);
    
    if ($productId <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID produk tidak valid'], 400);
    }
    
    $checkProduct = $db->query("SELECT id FROM products WHERE id = $productId AND user_id = $userId");
    if ($checkProduct->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Produk tidak ditemukan'], 404);
    }
    
    if ($db->query("DELETE FROM products WHERE id = $productId AND user_id = $userId")) {
        jsonResponse([
            'success' => true,
            'message' => 'Produk berhasil dihapus'
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Gagal menghapus produk'], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
?>