<?php
// includes/check_achievements.php

function checkUserAchievements($pdo, $user_id) {
    // 1. LOGRO: "FIEL SEGUIDOR" (Completar todos los grupos)
    $stmt_groups = $pdo->query("SELECT COUNT(DISTINCT group_name) FROM group_ranking_points WHERE group_name != 'Z'");
    $total_groups = $stmt_groups->fetchColumn();

    $stmt_user_groups = $pdo->prepare("SELECT COUNT(DISTINCT group_name) FROM group_ranking_points WHERE user_id = ? AND group_name != 'Z'");
    $stmt_user_groups->execute([$user_id]);
    $user_groups = $stmt_user_groups->fetchColumn();

    if ($total_groups > 0 && $user_groups >= $total_groups) {
        $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_key) VALUES (?, 'loyal_fan')")
            ->execute([$user_id]);
    }

    // 2. LOGRO: "MAESTRO DEL QUIZ" (3 aciertos seguidos)
    // FIX: Cambiamos created_at por id (que siempre existe y es cronológico) para evitar el error de columna inexistente
    $stmt_quiz = $pdo->prepare("SELECT points_awarded FROM daily_quiz_responses WHERE user_id = ? ORDER BY id DESC LIMIT 3");
    $stmt_quiz->execute([$user_id]);
    $last_quizzes = $stmt_quiz->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($last_quizzes) === 3 && !in_array(0, $last_quizzes)) {
        $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_key) VALUES (?, 'quiz_master')")
            ->execute([$user_id]);
    }

    // 3. LOGRO: "CAZADOR DE GIGANTES"
    $stmt_giant = $pdo->prepare("SELECT COUNT(*) FROM match_challenges WHERE challenger_user_id = ? AND points_seized > 10");
    $stmt_giant->execute([$user_id]);
    if ($stmt_giant->fetchColumn() > 0) {
        $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_key) VALUES (?, 'giant_hunter')")
            ->execute([$user_id]);
    }
}