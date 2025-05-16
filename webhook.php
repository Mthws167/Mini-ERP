<?php
require_once 'db.php';

function logWebhookAction($orderId, $status, $action) {
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Order $orderId: $action (status: $status)\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'] ?? null;
    $newStatus = $_POST['status'] ?? null;

    if (!$orderId || !$newStatus) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        exit;
    }

    if ($newStatus === 'canceled') {
        $deleteOrder = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $deleteOrder->execute([$orderId]);
        $deleteItems = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $deleteItems->execute([$orderId]);
        logWebhookAction($orderId, $newStatus, 'Deleted');
    } else {
        $updateOrder = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateOrder->execute([$newStatus, $orderId]);
        logWebhookAction($orderId, $newStatus, 'Updated');
    }

    http_response_code(200);
    echo json_encode(['message' => 'Pedido atualizado com sucesso']);
}