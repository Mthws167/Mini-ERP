<?php
include 'db.php';
session_start();

if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = $_POST['quantity'];
    $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $stock = $stmt->fetchColumn();

    if ($new_quantity <= $stock) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] = $new_quantity;
                break;
            }
        }
    }
    header('Location: cart.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $product_id = $_GET['product_id'];
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
        return $item['product_id'] != $product_id;
    });
    header('Location: cart.php');
    exit;
}

if (isset($_POST['apply_coupon'])) {
    $coupon_code = $_POST['coupon_code'];
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND valid_from <= CURDATE() AND valid_until >= CURDATE()");
    $stmt->execute([$coupon_code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($coupon) {
        $_SESSION['coupon'] = $coupon;
    } else {
        unset($_SESSION['coupon']);
    }
    header('Location: cart.php');
    exit;
}

$subtotal = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $price = $stmt->fetchColumn();
        $subtotal += $price * $item['quantity'];
    }
}

$discount = 0;
if (isset($_SESSION['coupon']) && $subtotal >= ($_SESSION['coupon']['min_order_value'] ?? 0)) {
    if ($_SESSION['coupon']['discount_type'] == 'fixed') {
        $discount = $_SESSION['coupon']['discount_value'];
    } else {
        $discount = $subtotal * ($_SESSION['coupon']['discount_value'] / 100);
    }
}

$shipping = ($subtotal >= 200) ? 0 : (($subtotal >= 52 && $subtotal <= 166.59) ? 15 : 20);
$total = $subtotal - $discount + $shipping;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cart</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1>Cart</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
                    $stmt->execute([$item['product_id']]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    $item_subtotal = $product['price'] * $item['quantity'];
                    ?>
                    <tr>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['price']; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="submit" name="update_quantity" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                        <td><?php echo $item_subtotal; ?></td>
                        <td>
                            <a href="?action=remove&product_id=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-danger">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">Your cart is empty.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <form method="POST">
        <div class="form-group">
            <label for="coupon_code">Coupon Code</label>
            <input type="text" class="form-control" id="coupon_code" name="coupon_code">
        </div>
        <button type="submit" name="apply_coupon" class="btn btn-primary">Apply Coupon</button>
    </form>
    <div class="mt-3">
        <p>Subtotal: R$<?php echo number_format($subtotal, 2); ?></p>
        <p>Discount: R$<?php echo number_format($discount, 2); ?></p>
        <p>Shipping: R$<?php echo number_format($shipping, 2); ?></p>
        <p>Total: R$<?php echo number_format($total, 2); ?></p>
    </div>
    <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
</div>
</body>
</html>