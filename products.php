<?php
require_once 'db.php';
session_start();

function addToCart($productId, $quantity, $pdo) {
    $stockQuery = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ?");
    $stockQuery->execute([$productId]);
    $availableStock = $stockQuery->fetchColumn();

    if ($availableStock >= $quantity) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $itemExists = false;
        foreach ($_SESSION['cart'] as &$cartItem) {
            if ($cartItem['product_id'] == $productId) {
                if ($cartItem['quantity'] + $quantity <= $availableStock) {
                    $cartItem['quantity'] += $quantity;
                }
                $itemExists = true;
                break;
            }
        }
        if (!$itemExists) {
            $_SESSION['cart'][] = ['product_id' => $productId, 'quantity' => $quantity];
        }
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = $_POST['name'] ?? '';
    $productPrice = floatval($_POST['price'] ?? 0);
    $stockQuantity = intval($_POST['quantity'] ?? 0);
    $productId = $_POST['product_id'] ?? null;

    if ($stockQuantity < 0) {
        $errorMessage = "Quantidade em estoque não pode ser negativa.";
    } else {
        if ($productId) {
            $updateQuery = $pdo->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
            $updateQuery->execute([$productName, $productPrice, $productId]);
            $stockQuery = $pdo->prepare("UPDATE stock SET quantity = ? WHERE product_id = ?");
            $stockQuery->execute([$stockQuantity, $productId]);
        } else {
            $insertQuery = $pdo->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
            $insertQuery->execute([$productName, $productPrice]);
            $newProductId = $pdo->lastInsertId();
            $stockQuery = $pdo->prepare("INSERT INTO stock (product_id, quantity) VALUES (?, ?)");
            $stockQuery->execute([$newProductId, $stockQuantity]);
        }
        header('Location: products.php?success=1');
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart' && isset($_GET['product_id'])) {
    if (!addToCart($_GET['product_id'], 1, $pdo)) {
        header('Location: products.php?error=stock');
        exit;
    }
    header('Location: products.php');
    exit;
}

$productQuery = $pdo->query("SELECT p.id, p.name, p.price, s.quantity FROM products p JOIN stock s ON p.id = s.product_id");
$productList = $productQuery->fetchAll(PDO::FETCH_ASSOC);

$editingProduct = null;
if (isset($_GET['edit'])) {
    $editQuery = $pdo->prepare("SELECT p.id, p.name, p.price, s.quantity FROM products p JOIN stock s ON p.id = s.product_id WHERE p.id = ?");
    $editQuery->execute([$_GET['edit']]);
    $editingProduct = $editQuery->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Gerenciar Produtos</title>
    <link rel="stylesheet" href="styles/custom.css">
</head>
<body>
    <div class="products-container">
        <h1>Gerenciar Produtos</h1>
        <?php if (isset($_GET['success'])): ?>
            <p class="success-message">Produto salvo com sucesso!</p>
        <?php elseif (isset($errorMessage)): ?>
            <p class="error-message"><?php echo $errorMessage; ?></p>
        <?php endif; ?>
        <form method="POST" class="product-form">
            <input type="hidden" name="product_id" value="<?php echo $editingProduct['id'] ?? ''; ?>">
            <div class="form-field">
                <label for="product_name">Nome do Produto</label>
                <input type="text" id="product_name" name="name" value="<?php echo $editingProduct['name'] ?? ''; ?>" required>
            </div>
            <div class="form-field">
                <label for="product_price">Preço</label>
                <input type="number" step="0.01" id="product_price" name="price" value="<?php echo $editingProduct['price'] ?? ''; ?>" required>
            </div>
            <div class="form-field">
                <label for="stock_quantity">Quantidade em Estoque</label>
                <input type="number" id="stock_quantity" name="quantity" value="<?php echo $editingProduct['quantity'] ?? ''; ?>" required>
            </div>
            <button type="submit" class="submit-btn">Salvar Produto</button>
        </form>
        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productList as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo $product['name']; ?></td>
                        <td>R$<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['quantity']; ?></td>
                        <td>
                            <a href="?edit=<?php echo $product['id']; ?>" class="action-btn edit-btn">Editar</a>
                            <a href="?action=add_to_cart&product_id=<?php echo $product['id']; ?>" class="action-btn buy-btn">Comprar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="cart.php" class="cart-link">Ir para o Carrinho</a>
    </div>
</body>
</html>