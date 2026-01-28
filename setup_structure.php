<?php
// setup_structure.php
require_once 'config/db.php';

try {
    // 1. Tabla de EQUIPOS
    $sql_teams = "CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        code VARCHAR(3) NOT NULL, -- Ej: ESP, ARG, BRA
        flag VARCHAR(255) DEFAULT NULL, -- URL de la bandera o nombre de archivo
        group_name CHAR(1) DEFAULT NULL -- A, B, C, D...
    )";
    $pdo->exec($sql_teams);
    echo "<p>✅ Tabla 'teams' creada.</p>";

    // 2. Tabla de PARTIDOS
    // Incluye campos para fase, estadio y los goles reales
    $sql_matches = "CREATE TABLE IF NOT EXISTS matches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_home_id INT,
        team_away_id INT,
        match_date DATETIME NOT NULL,
        stadium VARCHAR(100),
        phase ENUM('group', 'round_32', 'round_16', 'quarter', 'semi', 'final') NOT NULL DEFAULT 'group',
        home_score INT DEFAULT NULL, -- NULL significa que no se ha jugado
        away_score INT DEFAULT NULL,
        status ENUM('scheduled', 'finished') DEFAULT 'scheduled',
        FOREIGN KEY (team_home_id) REFERENCES teams(id),
        FOREIGN KEY (team_away_id) REFERENCES teams(id)
    )";
    $pdo->exec($sql_matches);
    echo "<p>✅ Tabla 'matches' creada.</p>";

    // 3. Tabla de PREDICCIONES (La Quiniela)
    $sql_predictions = "CREATE TABLE IF NOT EXISTS predictions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        match_id INT NOT NULL,
        predicted_home_score INT NOT NULL,
        predicted_away_score INT NOT NULL,
        points_earned INT DEFAULT 0, -- Aquí guardaremos los puntos que gane (25, 15, 5...)
        prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (match_id) REFERENCES matches(id),
        UNIQUE KEY unique_prediction (user_id, match_id) -- Un usuario solo puede hacer 1 predicción por partido
    )";
    $pdo->exec($sql_predictions);
    echo "<p>✅ Tabla 'predictions' creada.</p>";

    echo "<h3>¡Estructura completa lista!</h3>";
    echo "<a href='index.php'>Volver al Inicio</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>