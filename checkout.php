<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['street'] . ', ' . $_POST['number'] . ', ' . $_POST['city'] . ' - ' . $_POST['state'] . ', CEP: ' . $_POST['cep'];

    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $price = $stmt->fetchColumn();
        $subtotal += $price * $item['quantity'];
    }

    $discount = 0;
    if (isset($_SESSION['coupon']) && $subtotal >= ($_SESSION['coupon']['min_order_value'] ?? 0)) {
        $discount = ($_SESSION['coupon']['discount_type'] == 'fixed') ? $_SESSION['coupon']['discount_value'] : $subtotal * ($_SESSION['coupon']['discount_value'] / 100);
    }

    $shipping = ($subtotal >= 200) ? 0 : (($subtotal >= 52 && $subtotal <= 166.59) ? 15 : 20);
    $total = $subtotal - $discount + $shipping;

    $stock_ok = true;
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ?");
        $stmt->execute([$item['product_id']]);
        $stock = $stmt->fetchColumn();
        if ($stock < $item['quantity']) {
            $stock_ok = false;
            break;
        }
    }

    if ($stock_ok) {
        $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_email, customer_address, total, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$name, $email, $address, $total]);
        $order_id = $pdo->lastInsertId();

        foreach ($_SESSION['cart'] as $item) {
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $price = $stmt->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $price]);
            $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE product_id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }

        mail($email, "Order Confirmation", "Your order #$order_id has been placed.\nAddress: $address\nTotal: R$$total", "From: no-reply@mini-erp.com");

        unset($_SESSION['cart']);
        unset($_SESSION['coupon']);
        header('Location: order_success.php');
        exit;
    } else {
        header('Location: cart.php?error=stock');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
<div class="container">
    <h1>Checkout</h1>
    <form method="POST">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="cep">CEP</label>
            <input type="text" class="form-control" id="cep" name="cep" required>
        </div>
        <div class="form-group">
            <label for="street">Street</label>
            <input type="text" class="form-control" id="street" name="street" required>
        </div>
        <div class="form-group">
            <label for="number">Number</label>
            <input type="text" class="form-control" id="number" name="number" required>
        </div>
        <div class="form-group">
            <label for="city">City</label>
            <input type="text" class="form-control" id="city" name="city" required>
        </div>
        <div class="form-group">
            <label for="state">State</label>
            <input type="text" class="form-control" id="state" name="state" required>
        </div>
        <button type="submit" class="btn btn-primary">Place Order</button>
    </form>
</div>
<script>
    $('#cep').on('blur', function() {
        var cep = $(this).val().replace(/\D/g, '');
        if (cep.length == 8) {
            $.getJSON('https://viacep.com.br/ws/' + cep + '/json/', function(data) {
                if (!data.erro) {
                    $('#street').val(data.logradouro);
                    $('#city').val(data.localidade);
                    $('#state').val(data.uf);
                }
            });
        }
    });
</script>
</body>
</html>