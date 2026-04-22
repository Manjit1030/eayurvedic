<?php
require_once __DIR__ . '/../app/core/db.php';
require_once __DIR__ . '/../app/core/auth.php';
require_once __DIR__ . '/../app/core/functions.php';
require_once __DIR__ . '/../app/core/config.php';

/* Response from Khalti usually has: pidx, txnId, amount, total_amount, status, purchase_order_id, purchase_order_name */
$pidx = $_GET['pidx'] ?? '';
$status = $_GET['status'] ?? '';
$purchase_order_id = $_GET['purchase_order_id'] ?? '';

if (!$pidx) die("Invalid response from Khalti.");

/* Verify with Khalti */
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => KHALTI_LOOKUP_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(["pidx" => $pidx]),
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

/* Load Payment Record */
$stmt = db()->prepare("SELECT * FROM payments WHERE txn_ref=? LIMIT 1");
$stmt->execute([$pidx]);
$payment = $stmt->fetch();

if (!$payment) die("Payment record not found.");

$order_id = (int)$payment['order_id'];

/* Log Verification */
$log = db()->prepare("
    INSERT INTO payment_logs (payment_id, gateway, event_type, payload)
    VALUES (?, 'khalti', 'verify', ?)
");
$log->execute([$payment['id'], $response]);

if (isset($res_data['status']) && $res_data['status'] === 'Completed') {
    
    db()->beginTransaction();
    try {
        // Update Payment
        $updP = db()->prepare("UPDATE payments SET status='success', paid_at=NOW(), raw_response=? WHERE id=?");
        $updP->execute([$response, $payment['id']]);

        // Update Order
        $updO = db()->prepare("UPDATE orders SET payment_status='paid' WHERE id=?");
        $updO->execute([$order_id]);

        db()->commit();

        /* Get Order Code for redirect */
        $stmt = db()->prepare("SELECT order_code FROM orders WHERE id=?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        redirect('/public/order_success.php?code=' . urlencode($order['order_code']));
    } catch (Exception $e) {
        db()->rollBack();
        die("Verification Error: " . $e->getMessage());
    }

} else {
    db()->beginTransaction();
    try {
        // Update Payment as failed
        $updP = db()->prepare("UPDATE payments SET status='failed', raw_response=? WHERE id=?");
        $updP->execute([$response, $payment['id']]);

        // Keep the order history in sync for admin/user views
        $updO = db()->prepare("UPDATE orders SET payment_status='failed' WHERE id=?");
        $updO->execute([$order_id]);

        db()->commit();
    } catch (Exception $e) {
        db()->rollBack();
        die("Verification Error: " . $e->getMessage());
    }
    
    redirect('/public/order_failed.php?code=' . urlencode($purchase_order_id));
}
