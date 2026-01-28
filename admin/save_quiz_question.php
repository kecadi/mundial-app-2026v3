<?php
// admin/save_quiz_question.php
session_start();
require_once '../config/db.php'; 

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso denegado.");
}

$id = $_POST['id'] ?? null;
$question = $_POST['question'];

$options = [
    $_POST['option_a'], $_POST['option_b'], $_POST['option_c'], $_POST['option_d']
];
$correct_answer = $_POST['correct_answer'];
$date_available = $_POST['date_available']; // Capturamos la fecha

// 1. Array base de 7 parámetros (Question, Options A-D, Correct Answer, Date)
$params = [
    $question, $options[0], $options[1], $options[2], $options[3], $correct_answer, $date_available 
];

try {
    if ($id) {
        // CAMINO DE ACTUALIZACIÓN (UPDATE)
        // SQL: 7 placeholders para SET + 1 placeholder para WHERE = 8 en total
        $sql = "UPDATE daily_quiz_questions SET 
                question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, 
                correct_answer = ?, date_available = ? 
                WHERE id = ?";
        
        // Añadir el ID del registro al final del array para que sea el 8º parámetro
        $params[] = $id; 
    } else {
        // CAMINO DE INSERCIÓN (INSERT)
        // SQL: 7 placeholders
        $sql = "INSERT INTO daily_quiz_questions (question, option_a, option_b, option_c, option_d, correct_answer, date_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Location: quiz.php?msg=saved');
    exit;

} catch (PDOException $e) {
    die("Error SQL: " . $e->getMessage());
}