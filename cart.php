<?php
require_once 'db.php';
session_start();

function calculateCartTotals($cart, $pdo) {
    $cartSubtotal = 0;
    foreach ($cart as $cartItem) {
        $priceQuery = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $priceQuery->execute([$cartItem['product_id']]);
        $itemPrice = $priceQuery->fetchColumn();
        $cartSubtotal += $itemPrice * $cartItem['quantity'];
    }

    $discountAmount = 0;
    if (isset($_SESSION['coupon']) && $cartSubtotal >= ($_SESSION['coupon']['min_order_value'] ?? 0)) {
        $coupon = $_SESSION['coupon'];
        $discountAmount = ($coupon['discount_type'] === 'fixed') 
            ? $coupon['discount_value'] 
            : $cartSubtotal * ($coupon['discount_value'] / 100);
    }

    $shippingCost = ($cartSubtotal >= 200) ? 0 : (($cartSubtotal >= 52 && $cartSubtotal <= 166.59) ? 15 : 20);
    $cartTotal = $cartSubtotal - $discountAmount + $shippingCost;

    return ['subtotal' => $cartSubtotal, 'discount' => $discountAmount, 'shipping' => $shippingCost, 'total' => $cartTotal];
}

if (isset($_POST['update_quantity']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $productId = $_POST['product_id'];
    $newQuantity = max(1, intval($_POST['quantity']));
    $stockQuery = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ?");
    $stockQuery->execute([$productId]);
    $availableStock = $stockQuery->fetchColumn();

    if ($newQuantity <= $availableStock) {
        foreach ($_SESSION['cart'] as &$cartItem) {
            if ($cartItem['product_id'] == $productId) {
                $cartItem['quantity'] = $newQuantity;
                break;
            }
        }
    }
    header('Location: cart.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['product_id'])) {
    $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($item) => $item['product_id'] != $_GET['product_id']);
    header('Location: cart.php');
    exit;
}

$couponError = '';
if (isset($_POST['apply_coupon']) && isset($_POST['coupon_code'])) {
    $couponCode = $_POST['coupon_code'];
    $couponQuery = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND valid_from <= CURDATE() AND valid_until >= CURDATE()");
    $couponQuery->execute([$couponCode]);
    $coupon = $couponQuery->fetch(PDO::FETCH_ASSOC);
    if ($coupon) {
        $_SESSION['coupon'] = $coupon;
    } else {
        unset($_SESSION['coupon']);
        $couponError = "Cupom inválido ou expirado.";
    }
    header('Location: cart.php');
    exit;
}

$cartTotals = calculateCartTotals($_SESSION['cart'] ?? [], $pdo);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Carrinho de Compras</title>
    <link rel="stylesheet" href="styles/custom.css">
</head>
<body>
    <div class="cart-container">
        <h1>Seu Carrinho</h1>
        <?php if ($couponError): ?>
            <p class="error-message"><?php echo $couponError; ?></p>
        <?php endif; ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <?php foreach ($_SESSION['cart'] as $cartItem): ?>
                        <?php
                        $productQuery = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
                        $productQuery->execute([$cartItem['product_id']]);
                        $product = $productQuery->fetch(PDO::FETCH_ASSOC);
                        $itemSubtotal = $product['price'] * $cartItem['quantity'];
                        ?>
                        <tr>
                            <td><?php echo $product['name']; ?></td>
                            <td>R$<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="product_id" value="<?php echo $cartItem['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $cartItem['quantity']; ?>" min="1">
                                    <button type="submit" name="update_quantity" class="update-btn">Atualizar</button>
                                </form>
                            </td>
                            <td>R$<?php echo number_format($itemSubtotal, 2); ?></td>
                            <td>
                                
                                    <button type="submit" name="remove_item" class="update-btn">Remover</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">Seu carrinho está vazio.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <form method="POST" class="coupon-form">
            <div class="form-field">
                <label for="coupon_code">Código do Cupom</label>
                <input type="text" id="coupon_code" name="coupon_code">
            </div>
            <button type="submit" name="apply_coupon" class="apply-coupon-btn">Aplicar Cupom</button>
        </form>
        <div class="totals-summary">
            <p>Subtotal: R$<?php echo number_format($cartTotals['subtotal'], 2); ?></p>
            <p>Desconto: R$<?php echo number_format($cartTotals['discount'], 2); ?></p>
            <p>Frete: R$<?php echo number_format($cartTotals['shipping'], 2); ?></p>
            <p><strong>Total: R$<?php echo number_format($cartTotals['total'], 2); ?></strong></p>
        </div>
        <a href="checkout.php" class="checkout-btn">Finalizar Compra</a>
    </div>
</body>
</html>