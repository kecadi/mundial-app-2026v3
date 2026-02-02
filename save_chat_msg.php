<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];

    if (!empty($message) && strlen($message) <= 160) {
        $stmt = $pdo->prepare("INSERT INTO global_chat (user_id, message) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $message])) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
}
echo json_encode(['success' => false]);