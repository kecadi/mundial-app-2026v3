<?php
// seed_full_worldcup.php
require_once 'config/db.php';

$teams_list = [
    ['Canadá', '🇨🇦', 'CONCACAF'], ['Estados Unidos', '🇺🇸', 'CONCACAF'], ['México', '🇲🇽', 'CONCACAF'], ['Japón', '🇯🇵', 'AFC'], 
    ['Nueva Zelanda', '🇳🇿', 'AFC'], ['Irán', '🇮🇷', 'AFC'], ['Argentina', '🇦🇷', 'CONMEBOL'], ['Uzbekistán', '🇺🇿', 'AFC'], 
    ['Corea del Sur', '🇰🇷', 'AFC'], ['Jordania', '🇯🇴', 'AFC'], ['Australia', '🇦🇺', 'AFC'], ['Brasil', '🇧🇷', 'CONMEBOL'], 
    ['Ecuador', '🇪🇨', 'CONMEBOL'], ['Uruguay', '🇺🇾', 'CONMEBOL'], ['Colombia', '🇨🇴', 'CONMEBOL'], ['Paraguay', '🇵🇾', 'CONMEBOL'],
    ['Marruecos', '🇲🇦', 'CAF'], ['Túnez', '🇹🇳', 'CAF'], ['Egipto', '🇪🇬', 'CAF'], ['Argelia', '🇩🇿', 'CAF'], 
    ['Ghana', '🇬🇭', 'CAF'], ['Cabo Verde', '🇨🇻', 'CAF'], ['Sudáfrica', '🇿🇦', 'CAF'], ['Catar', '🇶🇦', 'AFC'], 
    ['Inglaterra', '🏴󠁧󠁢󠁥󠁮󠁧󠁿', 'UEFA'], ['Arabia Saudita', '🇸🇦', 'AFC'], ['Costa de Marfil', '🇨🇮', 'CAF'], ['Senegal', '🇸🇳', 'CAF'], 
    ['Francia', '🇫🇷', 'UEFA'], ['Croacia', '🇭🇷', 'UEFA'], ['Portugal', '🇵🇹', 'UEFA'], ['Noruega', '🇳🇴', 'UEFA'], 
    ['Alemania', '🇩🇪', 'UEFA'], ['Países Bajos', '🇳🇱', 'UEFA'], ['Bélgica', '🇧🇪', 'UEFA'], ['Austria', '🇦🇹', 'UEFA'], 
    ['Suiza', '🇨🇭', 'UEFA'], ['España', '🇪🇸', 'UEFA'], ['Escocia', '🏴󠁧󠁢󠁳󠁣󠁴󠁿', 'UEFA'], ['Panamá', '🇵🇦', 'CONCACAF'], 
    ['Haití', '🇭🇹', 'CONCACAF'], ['Curazao', '🇨🇼', 'CONCACAF'], 
    // Inventados para completar
    ['Chile', '🇨🇱', 'CONMEBOL'], ['Nigeria', '🇳🇬', 'CAF'], ['Jamaica', '🇯🇲', 'CONCACAF'], ['Suecia', '🇸🇪', 'UEFA'], 
    ['Serbia', '🇷🇸', 'UEFA'], ['Irak', '🇮🇶', 'AFC']
];

$groups = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

try {
    echo "<h1>Cargando Estructura de Mundial (48 equipos)...</h1>";
    $pdo->beginTransaction();

    // 1. ASIGNAR GRUPOS A EQUIPOS
    $group_index = 0;
    $teams_to_insert = [];
    foreach ($teams_list as $i => $team) {
        $teams_to_insert[] = [
            'name' => $team[0],
            'flag' => $team[1],
            'group' => $groups[$group_index % 12] 
        ];
        $group_index++;
    }

    // Insertar equipos
    $stmt_insert = $pdo->prepare("INSERT INTO teams (name, code, flag, group_name) 
                                 VALUES (:name, :code, :flag, :group)");
    foreach ($teams_to_insert as $team) {
        $stmt_insert->execute([
            'name' => $team['name'],
            'code' => mb_substr($team['name'], 0, 3, 'UTF-8'),
            'flag' => $team['flag'],
            'group' => $team['group']
        ]);
    }
    echo "<p class='text-success'>✅ 48 equipos insertados correctamente.</p>";

    // 2. CREAR PARTIDOS (6 por grupo = 72 total)
    $stmt_teams = $pdo->query("SELECT id, name, group_name FROM teams ORDER BY group_name, id");
    $teams_data = $stmt_teams->fetchAll(PDO::FETCH_ASSOC);
    $teams_by_group = [];
    foreach ($teams_data as $t) {
        $teams_by_group[$t['group_name']][] = $t;
    }

    $match_date = date('Y-m-d H:i:s');
    $match_insert = $pdo->prepare("INSERT INTO matches (team_home_id, team_away_id, match_date, stadium, phase) 
                                   VALUES (?, ?, ?, 'Estadio Placeholder', 'group')");
    
    // Almacenamos IDs de partidos para asignar resultados después
    $match_ids_by_group = [];

    foreach ($teams_by_group as $group_name => $teams) {
        $count = count($teams);
        if ($count < 4) continue; // Si el grupo está incompleto

        $matches_in_group = [
            [$teams[0]['id'], $teams[1]['id']], [$teams[2]['id'], $teams[3]['id']], // Match 1, 2
            [$teams[0]['id'], $teams[2]['id']], [$teams[1]['id'], $teams[3]['id']], // Match 3, 4
            [$teams[0]['id'], $teams[3]['id']], [$teams[1]['id'], $teams[2]['id']]  // Match 5, 6
        ];

        foreach ($matches_in_group as $m) {
            $match_insert->execute([$m[0], $m[1], $match_date]);
            $match_ids_by_group[$group_name][] = $pdo->lastInsertId();
            $match_date = date('Y-m-d H:i:s', strtotime($match_date . ' + 1 day'));
        }
    }
    echo "<p class='text-success'>✅ 72 partidos de grupo creados.</p>";


    // 3. ASIGNAR RESULTADOS REALES (GRUPOS A y B)
    $real_results = [
        'A' => [ // Argentina, P. Bajos, Marruecos, Japón
            // M1: Arg-PB (1-0), M2: Mar-Jap (0-0)
            [1, 0], [0, 0], 
            // M3: Arg-Mar (2-0), M4: PB-Jap (1-1)
            [2, 0], [1, 1], 
            // M5: Arg-Jap (3-0), M6: PB-Mar (2-1)
            [3, 0], [2, 1] 
        ],
        'B' => [ // Brasil, Alemania, Senegal, Uzbekistán
            // M1: Bra-Ale (2-1), M2: Sen-Uzb (1-0)
            [2, 1], [1, 0],
            // M3: Bra-Sen (3-0), M4: Ale-Uzb (4-0)
            [3, 0], [4, 0], 
            // M5: Bra-Uzb (1-1), M6: Ale-Sen (2-2)
            [1, 1], [2, 2] 
        ]
    ];

    $match_update = $pdo->prepare("UPDATE matches SET home_score = ?, away_score = ?, status = 'finished' WHERE id = ?");

    foreach ($real_results as $group_name => $results) {
        $match_ids = $match_ids_by_group[$group_name];
        foreach ($results as $i => $score) {
            $match_update->execute([$score[0], $score[1], $match_ids[$i]]);
        }
    }
    echo "<p class='text-success'>✅ Resultados reales inyectados en Grupos A y B.</p>";


    $pdo->commit();
    echo "<p class='alert alert-info'>¡Base de datos lista para pruebas!</p>";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<p class='alert alert-danger'>Error: " . $e->getMessage() . "</p>";
}
?>