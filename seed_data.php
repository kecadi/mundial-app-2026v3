<?php
// seed_data.php
require_once 'config/db.php';

try {
    // 1. Limpiamos datos viejos (opcional, para evitar duplicados al probar)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE teams");
    $pdo->exec("TRUNCATE TABLE matches");
    $pdo->exec("TRUNCATE TABLE predictions");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 2. Insertamos Equipos (Usamos Emojis como banderas por simplicidad ahora mismo)
    $sql_teams = "INSERT INTO teams (name, code, flag, group_name) VALUES 
    ('España', 'ESP', '🇪🇸', 'A'),
    ('Brasil', 'BRA', '🇧🇷', 'A'),
    ('Argentina', 'ARG', '🇦🇷', 'B'),
    ('Francia', 'FRA', '🇫🇷', 'B')";
    $pdo->exec($sql_teams);

    // Recuperamos IDs para estar seguros
    $stm = $pdo->query("SELECT id, code FROM teams");
    $teams = $stm->fetchAll(PDO::FETCH_KEY_PAIR); // Array [ESP => 1, BRA => 2, ...]
    // Nota: Si FETCH_KEY_PAIR falla en tu versión, asuiremos IDs 1,2,3,4 por el orden de inserción.
    
    // 3. Insertamos Partidos
    // Partido 1: España (1) vs Brasil (2) - Mañana
    $tomorrow = date('Y-m-d H:i:s', strtotime('+1 day 18:00:00'));
    $next_week = date('Y-m-d H:i:s', strtotime('+7 days 20:00:00'));

    $sql_matches = "INSERT INTO matches (team_home_id, team_away_id, match_date, stadium, phase) VALUES 
    (1, 2, '$tomorrow', 'Estadio Azteca', 'group'),
    (3, 4, '$next_week', 'MetLife Stadium', 'group')";
    
    $pdo->exec($sql_matches);

    echo "<h3>✅ Datos cargados correctamente</h3>";
    echo "<p>Equipos: España, Brasil, Argentina, Francia</p>";
    echo "<p>Partidos creados: 2</p>";
    echo "<a href='index.php'>Ir al Inicio</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>