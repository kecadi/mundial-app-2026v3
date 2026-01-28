<?php
// save_quiz_response.php
session_start();
require_once 'config/db.php'; 

$user_id = $_SESSION['user_id'] ?? null;
$question_id = $_POST['question_id'] ?? null;
$user_answer = $_POST['answer'] ?? null;
$start_time = $_SESSION['quiz_start_time'] ?? 0;

// Requerimientos de seguridad
if (!$user_id || !$question_id || !$start_time) {
    header('Location: index.php?err=quiz_security');
    exit;
}

// 1. CHEQUEO DE TIEMPO (Seguridad Server-Side)
$time_taken = time() - $start_time;
$max_time = 10;
$points_awarded = 0;
$status_msg = "answered";

try {
    // 2. Verificar la respuesta
    if ($time_taken <= $max_time && $user_answer) {
        $stmt = $pdo->prepare("SELECT correct_answer FROM daily_quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $correct_answer = $stmt->fetchColumn();

        if ($user_answer === $correct_answer) {
            $points_awarded = 10;
            $status_msg = "correct";
        } else {
            $status_msg = "incorrect";
        }
    } else {
        $status_msg = "timeout";
    }

    // 3. Guardar la respuesta
    $sql = "INSERT INTO daily_quiz_responses (user_id, question_id, points_awarded, response_date, time_taken_sec) 
            VALUES (?, ?, ?, CURDATE(), ?)";
    $stmt_save = $pdo->prepare($sql);
    $stmt_save->execute([$user_id, $question_id, $points_awarded, $time_taken]);
    
    // 4. Limpiar sesión (para evitar que se use la misma hora de inicio)
    unset($_SESSION['quiz_start_time']);
    unset($_SESSION['quiz_question_id']);

    header('Location: index.php?msg=quiz_' . $status_msg . '&pts=' . $points_awarded);
    exit;

} catch (PDOException $e) {
    // Error 23000 es clave única duplicada (ya contestó hoy)
    if ($e->getCode() == 23000) {
        header('Location: index.php?msg=quiz_duplicate');
        exit;
    }
    die("Error de Base de Datos: " . $e->getMessage());
}