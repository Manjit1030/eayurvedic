<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/config.php';

require_login();
require_role('user');

$user = current_user();
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) die("Invalid order ID.");

/* Load order */
$stmt = db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1");
$stmt->execute([$order_id, $user['id']]);
$order = $stmt->fetch();

if (!$order) die("Order not found.");
if (($order['payment_method'] ?? '') !== 'khalti') die("Invalid payment method for Khalti payment.");

$stmt = db()->prepare("
    SELECT status
    FROM payments
    WHERE order_id=? AND gateway='khalti'
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$order_id]);
$latestPayment = $stmt->fetch();

if ($latestPayment && ($latestPayment['status'] ?? '') === 'success') {
    die("Order already paid.");
}

/* Prepare Khalti Request */
$amount_in_paisa = (int)($order['total_amount'] * 100); // Khalti expects paisa
$purchase_order_id = $order['order_code'];
$purchase_order_name = "Order #" . $order['order_code'];
$return_url = BASE_URL . "/public/khalti_verify.php";

$post_data = [
    "return_url" => $return_url,
    "website_url" => BASE_URL,
    "amount" => $amount_in_paisa,
    "purchase_order_id" => $purchase_order_id,
    "purchase_order_name" => $purchase_order_name,
    "customer_info" => [
        "name" => $user['full_name'],
        "email" => $user['email'],
        "phone" => $user['phone'] ?? "9800000000"
    ]
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => KHALTI_INITIATE_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($post_data),
    CURLOPT_HTTPHEADER => [
        "Authorization: Key " . KHALTI_SECRET_KEY,
        "Content-Type: application/json",
    ],
    CURLOPT_SSL_VERIFYPEER => false, // For local environment
]);

$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);

if ($response === false) {
    die("CURL Error: " . e($error));
}

$res_data = json_decode($response, true);

if (isset($res_data['payment_url'])) {
    
    /* Log Initiation */
    $stmt = db()->prepare("
        INSERT INTO payments (order_id, gateway, txn_ref, amount, status, raw_response)
        VALUES (?, 'khalti', ?, ?, 'initiated', ?)
    ");
    $stmt->execute([
        $order_id,
        $res_data['pidx'],
        $order['total_amount'],
        $response
    ]);

    $payment_id = db()->lastInsertId();

    $log = db()->prepare("
        INSERT INTO payment_logs (payment_id, gateway, event_type, payload)
        VALUES (?, 'khalti', 'init', ?)
    ");
    $log->execute([$payment_id, json_encode($post_data)]);

    /* Redirect to Khalti */
    header("Location: " . $res_data['payment_url']);
    exit;
} else {
    die("Khalti Initiation Failed: " . e($response));
}
