<?php
require_once 'db.php';
session_start();

function calculateOrderTotals($cart, $pdo) {
    $orderSubtotal = 0;
    foreach ($cart as $cartItem) {
        $query = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $query->execute([$cartItem['product_id']]);
        $itemPrice = $query->fetchColumn();
        $orderSubtotal += $itemPrice * $cartItem['quantity'];
    }

    $discountAmount = 0;
    if (isset($_SESSION['coupon']) && $orderSubtotal >= ($_SESSION['coupon']['min_order_value'] ?? 0)) {
        $coupon = $_SESSION['coupon'];
        $discountAmount = ($coupon['discount_type'] === 'fixed') 
            ? $coupon['discount_value'] 
            : $orderSubtotal * ($coupon['discount_value'] / 100);
    }

    $shippingCost = ($orderSubtotal >= 200) ? 0 : (($orderSubtotal >= 52 && $orderSubtotal <= 166.59) ? 15 : 20);
    $orderTotal = $orderSubtotal - $discountAmount + $shippingCost;

    return ['subtotal' => $orderSubtotal, 'discount' => $discountAmount, 'shipping' => $shippingCost, 'total' => $orderTotal];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['name'] ?? '';
    $customerEmail = $_POST['email'] ?? '';
    $customerAddress = sprintf("%s, %s, %s - %s, CEP: %s",
        $_POST['street'], $_POST['number'], $_POST['city'], $_POST['state'], $_POST['cep']);

    $totals = calculateOrderTotals($_SESSION['cart'] ?? [], $pdo);

    $isStockValid = true;
    foreach ($_SESSION['cart'] as $cartItem) {
        $stockQuery = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ?");
        $stockQuery->execute([$cartItem['product_id']]);
        $availableStock = $stockQuery->fetchColumn();
        if ($availableStock < $cartItem['quantity']) {
            $isStockValid = false;
            break;
        }
    }

    if ($isStockValid) {
        $orderQuery = $pdo->prepare("INSERT INTO orders (customer_name, customer_email, customer_address, total, status) VALUES (?, ?, ?, ?, 'pending')");
        $orderQuery->execute([$customerName, $customerEmail, $customerAddress, $totals['total']]);
        $newOrderId = $pdo->lastInsertId();

        foreach ($_SESSION['cart'] as $cartItem) {
            $priceQuery = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $priceQuery->execute([$cartItem['product_id']]);
            $itemPrice = $priceQuery->fetchColumn();

            $itemQuery = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $itemQuery->execute([$newOrderId, $cartItem['product_id'], $cartItem['quantity'], $itemPrice]);

            $stockUpdate = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE product_id = ?");
            $stockUpdate->execute([$cartItem['quantity'], $cartItem['product_id']]);
        }

        $emailBody = "Obrigado pelo seu pedido #$newOrderId!\nEndereço: $customerAddress\nTotal: R$" . number_format($totals['total'], 2);
        mail($customerEmail, "Confirmação do Pedido", $emailBody, "From: no-reply@lojaonline.com");

        unset($_SESSION['cart'], $_SESSION['coupon']);
        header('Location: order_completed.php');
        exit;
    } else {
        header('Location: cart.php?error=insufficient_stock');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Finalizar Compra</title>
    <link rel="stylesheet" href="styles/custom.css">
</head>
<body>
    <div class="checkout-container">
        <h1>Finalizar Compra</h1>
        <form method="POST" class="checkout-form">
            <div class="form-field">
                <label for="customer_name">Nome Completo</label>
                <input type="text" id="customer_name" name="name" required>
            </div>
            <div class="form-field">
                <label for="customer_email">E-mail</label>
                <input type="email" id="customer_email" name="email" required>
            </div>
            <div class="form-field">
                <label for="postal_code">CEP</label>
                <input type="text" id="postal_code" name="cep" required>
            </div>
            <div class="form-field">
                <label for="address_street">Rua</label>
                <input type="text" id="address_street" name="street" required>
            </div>
            <div class="form-field">
                <label for="address_number">Número</label>
                <input type="text" id="address_number" name="number" required>
            </div>
            <div class="form-field">
                <label for="address_city">Cidade</label>
                <input type="text" id="address_city" name="city" required>
            </div>
            <div class="form-field">
                <label for="address_state">Estado</label>
                <input type="text" id="address_state" name="state" required>
            </div>
            <button type="submit" class="submit-btn">Confirmar Pedido</button>
        </form>
    </div>
    <script>
        document.getElementById('postal_code').addEventListener('blur', async () => {
            const cep = document.getElementById('postal_code').value.replace(/\D/g, '');
            if (cep.length === 8) {
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();
                    if (!data.erro) {
                        document.getElementById('address_street').value = data.logradouro;
                        document.getElementById('address_city').value = data.localidade;
                        document.getElementById('address_state').value = data.uf;
                    }
                } catch (error) {
                    console.error('Erro ao buscar CEP:', error);
                }
            }
        });
    </script>
</body>
</html>