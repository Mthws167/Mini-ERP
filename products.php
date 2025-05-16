<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $product_id = $_POST['product_id'] ?? null;

    if ($product_id) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $product_id]);
        $stmt = $pdo->prepare("UPDATE stock SET quantity = ? WHERE product_id = ?");
        $stmt->execute([$quantity, $product_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
        $stmt->execute([$name, $price]);
        $product_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO stock (product_id, quantity) VALUES (?, ?)");
        $stmt->execute([$product_id, $quantity]);
    }
    header('Location: products.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'add_to_cart') {
    $product_id = $_GET['product_id'];
    $quantity = 1;
    $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $stock = $stmt->fetchColumn();

    if ($stock >= $quantity) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                if ($item['quantity'] + $quantity <= $stock) {
                    $item['quantity'] += $quantity;
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = ['product_id' => $product_id, 'quantity' => $quantity];
        }
    }
    header('Location: products.php');
    exit;
}

$stmt = $pdo->query("SELECT p.id, p.name, p.price, s.quantity FROM products p JOIN stock s ON p.id = s.product_id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$edit_product = null;
if (isset($_GET['edit'])) {
    $product_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, s.quantity FROM products p JOIN stock s ON p.id = s.product_id WHERE p.id = ?");
    $stmt->execute([$product_id]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1>Products</h1>
    <form method="POST">
        <input type="hidden" name="product_id" value="<?php echo $edit_product['id'] ?? ''; ?>">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo $edit_product['name'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="quantity">Stock Quantity</label>
            <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $edit_product['quantity'] ?? ''; ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
    <table class="table mt-4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo $product['id']; ?></td>
                <td><?php echo $product['name']; ?></td>
                <td><?php echo $product['price']; ?></td>
                <td><?php echo $product['quantity']; ?></td>
                <td>
                    <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="?action=add_to_cart&product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-success">Buy</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="cart.php" class="btn btn-info">View Cart</a>
</div>
</body>
</html>