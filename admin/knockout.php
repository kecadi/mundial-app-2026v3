<?php
// admin/knockout.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php'; 

// Seguridad Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$output = "";

// ==========================================================================================
// DEFINICIÓN FINAL DE ESTRUCTURAS DEL BRACKET (Basado en el input del usuario)
// ==========================================================================================

// A. Dieciseisavos (Round of 32) - Partidos 73 a 88 (Grupo a Knockout)
// NOTA: Mantenemos '3C', '3A', etc., como placeholder simplificado para el tercer lugar.
$round_of_32_cruces = [
    // [Phase, Match #, Home Source, Away Source, Stadium Name, Date]
    ['round_32', 73, '2A', '2B', 'SoFi Stadium, Los Ángeles', '2026-06-28'],
    ['round_32', 74, '1E', '3C', 'Gillette Stadium, Boston', '2026-06-29'],
    ['round_32', 75, '1F', '2C', 'Estadio Monterrey, Monterrey', '2026-06-29'],
    ['round_32', 76, '1C', '2F', 'NRG Stadium, Houston', '2026-06-29'],
    ['round_32', 77, '1I', '3C', 'MetLife Stadium, Nueva York/Nueva Jersey', '2026-06-30'],
    ['round_32', 78, '2E', '2I', 'AT&T Stadium, Dallas', '2026-06-30'],
    ['round_32', 79, '1A', '3C', 'Estadio Azteca, Ciudad de México', '2026-06-30'],
    ['round_32', 80, '1L', '3E', 'Mercedes-Benz Stadium, Atlanta', '2026-07-01'], 
    ['round_32', 81, '1D', '3B', 'Levi\'s Stadium, San Francisco', '2026-07-01'],
    ['round_32', 82, '1G', '3A', 'Lumen Field, Seattle', '2026-07-01'],
    ['round_32', 83, '2K', '2L', 'Estadio Nacional de Canadá, Toronto', '2026-07-02'],
    ['round_32', 84, '1H', '2J', 'SoFi Stadium, Los Ángeles', '2026-07-02'],
    ['round_32', 85, '1B', '3E', 'Estadio BC Place, Vancouver', '2026-07-02'],
    ['round_32', 86, '1J', '2H', 'Hard Rock Stadium, Miami', '2026-07-03'],
    ['round_32', 87, '1K', '3D', 'Arrowhead Stadium, Kansas City', '2026-07-03'],
    ['round_32', 88, '2D', '2G', 'AT&T Stadium, Dallas', '2026-07-03'],
];

// B. Octavos (R16) a Final/3er Puesto (Winner/Loser a Winner/Loser)
$all_knockout_cruces = [
    // R16 (Octavos): Partidos 89 a 96
    'round_16' => [
        ['round_16', 89, 'W74', 'W77', 'Lincoln Financial Field, Filadelfia', '2026-07-04'], 
        ['round_16', 90, 'W73', 'W75', 'Estadio NRG, Houston', '2026-07-04'],
        ['round_16', 91, 'W76', 'W78', 'MetLife Stadium, Nueva York/Nueva Jersey', '2026-07-05'],
        ['round_16', 92, 'W79', 'W80', 'Estadio Azteca, Ciudad de México', '2026-07-05'],
        ['round_16', 93, 'W83', 'W84', 'AT&T Stadium, Dallas', '2026-07-06'],
        ['round_16', 94, 'W81', 'W82', 'Lumen Field, Seattle', '2026-07-06'],
        ['round_16', 95, 'W86', 'W88', 'Mercedes-Benz Stadium, Atlanta', '2026-07-07'],
        ['round_16', 96, 'W85', 'W87', 'Estadio BC Place, Vancouver', '2026-07-07'],
    ],
    // Quarter (Cuartos): Partidos 97 a 100
    'quarter' => [
        ['quarter', 97, 'W89', 'W90', 'Gillette Stadium, Boston', '2026-07-09'],
        ['quarter', 98, 'W93', 'W94', 'SoFi Stadium, Los Ángeles', '2026-07-10'],
        ['quarter', 99, 'W91', 'W92', 'Hard Rock Stadium, Miami', '2026-07-11'],
        ['quarter', 100, 'W95', 'W96', 'Arrowhead Stadium, Kansas City', '2026-07-11'],
    ],
    // Semi (Semifinales): Partidos 101 y 102
    'semi' => [
        ['semi', 101, 'W97', 'W98', 'AT&T Stadium, Dallas', '2026-07-14'],
        ['semi', 102, 'W99', 'W100', 'Mercedes-Benz Stadium, Atlanta', '2026-07-15'],
    ],
    // Finales: Partidos 103 (3er puesto) y 104 (Final)
    'final' => [
        ['final', 104, 'W101', 'W102', 'MetLife Stadium, Nueva York/Nueva Jersey', '2026-07-19'], // Final
        ['third_place', 103, 'L101', 'L102', 'Hard Rock Stadium, Miami', '2026-07-18'], // Tercer Puesto
    ]
];


// Array de orden de fases para lógica de avance
$phases_order = ['group', 'round_32', 'round_16', 'quarter', 'semi', 'final'];
$nombres_fases = [
    'group' => 'Fase de Grupos',
    'round_32' => 'Dieciseisavos',
    'round_16' => 'Octavos',
    'quarter' => 'Cuartos',
    'semi' => 'Semifinales',
    'final' => 'Gran Final',
    'third_place' => 'Tercer Puesto'
];

// Función para obtener el ganador/perdedor de un partido (WXX o LXX)
function get_match_result_id($source, $match_results_map) {
    $match_num = substr($source, 1);
    $is_winner = (strpos($source, 'W') === 0);
    
    // Obtener la fila de resultados del partido anterior (usando la clave MySQL ID)
    if (!isset($match_results_map[$match_num])) return null;

    $match_data = $match_results_map[$match_num];
    $qualifier_id = $match_data['real_qualifier_id'];
    
    // 1. Lógica del Ganador (WXX)
    if ($is_winner) {
        if ($qualifier_id) return $qualifier_id; // Si hay ID de clasificado (penaltis), lo usamos.
        
        // Si no hay ID de clasificado (no hubo penaltis o no se guardó), inferimos del score.
        if ($match_data['home_score'] > $match_data['away_score']) {
            return $match_data['team_home_id'];
        } else {
            return $match_data['team_away_id'];
        }
    } else {
        // 2. Lógica del Perdedor (LXX)
        $home = $match_data['team_home_id'];
        $away = $match_data['team_away_id'];
        
        // Si hay clasificado, el perdedor es el otro.
        if ($qualifier_id) {
            return ($qualifier_id === $home) ? $away : $home;
        } else {
             // Si el score no es draw, inferimos del score.
             if ($match_data['home_score'] > $match_data['away_score']) {
                 return $away;
             } else {
                 return $home;
             }
        }
    }
    return null; 
}


// ==========================================================================================
// INICIO DE LA LÓGICA
// ==========================================================================================

try {
    // 1. OBTENER DATOS DE GRUPOS Y PARTIDOS
    $stmt = $pdo->query("SELECT id, name, flag, group_name FROM teams ORDER BY group_name, id");
    $all_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $teams_by_group = [];
    $team_id_to_data = [];
    foreach ($all_teams as $t) {
        $teams_by_group[$t['group_name']][] = $t;
        $team_id_to_data[$t['id']] = $t;
    }

    $stmt = $pdo->query("SELECT id, team_home_id, team_away_id, home_score, away_score, phase, real_qualifier_id FROM matches");
    $all_matches_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $real_matches_group = array_filter($all_matches_data, fn($m) => $m['phase'] === 'group');

    // 2. CÁLCULO DE STANDINGS (para saber si los grupos están listos)
    $all_standings = [];
    $all_third_places = [];
    $all_groups_closed = true;

    foreach ($teams_by_group as $group_name => $group_teams) {
        if (count($group_teams) < 4) continue;
        
        $group_matches = array_filter($real_matches_group, function($m) use ($group_teams) {
            return in_array($m['team_home_id'], array_column($group_teams, 'id'));
        });
        
        // Verificación de cierre
        if (count($group_matches) < 6) { $all_groups_closed = false; }
        else {
            foreach($group_matches as $m) { if (is_null($m['home_score'])) $all_groups_closed = false; }
        }
        
        $standings = calcularTablaGrupo($group_teams, $group_matches);
        $all_standings[$group_name] = $standings;
        
        if (isset($standings[2])) {
             $all_third_places[] = $standings[2]; 
             $all_third_places[count($all_third_places) - 1]['group_name'] = $group_name;
        }
    }
    
    // 3. IDENTIFICAR LOS 8 MEJORES TERCEROS
    usort($all_third_places, function($a, $b) {
        if ($a['pts'] != $b['pts']) return $b['pts'] - $a['pts'];
        if ($a['dg'] != $b['dg']) return $b['dg'] - $a['dg'];
        return $b['gf'] - $a['gf'];
    });
    
    $top_eight_thirds = array_slice($all_third_places, 0, 8);
    $qualifying_groups_thirds = array_column($top_eight_thirds, 'group_name');
    
    
    // 4. IDENTIFICAR LA FASE ACTUAL Y PRÓXIMA
    $next_phase_to_generate = null;
    $last_phase_generated = 'group';
    $last_phase_has_winners = true;
    $match_results_map = [];

    // Buscar la última fase con partidos generados y si tiene ganadores
    foreach ($phases_order as $index => $phase) {
        if ($phase === 'group') continue; 
        
        $current_phase_matches = array_filter($all_matches_data, fn($m) => $m['phase'] === $phase || ($phase === 'final' && $m['phase'] === 'third_place'));

        if (count($current_phase_matches) > 0) {
            $last_phase_generated = $phase;
            
            // Mapear resultados (ID MySQL -> Fila)
            foreach($current_phase_matches as $m) {
                 $match_results_map[$m['id']] = $m;
            }
            
            // Verificar si todos tienen ganador (sólo para fases que avanzan)
            if (in_array($phase, ['round_32', 'round_16', 'quarter', 'semi'])) {
                foreach($current_phase_matches as $m) {
                    if (!$m['real_qualifier_id'] && ($m['home_score'] !== $m['away_score'])) {
                        // Si no hay qualifier_id, pero el score es diferente, asumimos que el ganador existe.
                    } elseif (!$m['real_qualifier_id'] && ($m['home_score'] === $m['away_score'])) {
                        // Si hay empate, REQUIERE qualifier_id para avanzar.
                        $last_phase_has_winners = false;
                        break;
                    }
                }
            }

            if (!$last_phase_has_winners) {
                $next_phase_to_generate = $phase; // Generada pero necesita resultados
                break;
            }
        } else {
            $next_phase_to_generate = $phase;
            break; 
        }
    }


    // 5. GESTIÓN DE ACCIONES (Generación de Partidos)
    if (isset($_POST['action']) && strpos($_POST['action'], 'generate_') === 0) {
        
        $target_phase = substr($_POST['action'], 9); 
        $source_phase = $phases_order[array_search($target_phase, $phases_order) - 1] ?? 'group';
        
        $cruces = ($target_phase === 'round_32') ? $round_of_32_cruces : $all_knockout_cruces[$target_phase];

        try {
            // ... (Se asume que la lógica de verificación y generación está implementada aquí) ...
            
            if ($target_phase === 'round_32') {
                 if (!$all_groups_closed) throw new Exception("Grupos incompletos.");
                 // (Aquí iría la lógica de inserción de R32)
            } elseif ($last_phase_has_winners === false) {
                 throw new Exception("Faltan resultados en la fase anterior.");
            }
            
            // ----------------------------------------------------
            // SIMULACIÓN DE LA INSERCIÓN DE PARTIDOS
            // ----------------------------------------------------
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE phase = ?");
            $stmt_check->execute([$target_phase]);
            if ($stmt_check->fetchColumn() > 0) {
                 $output = "<p class='alert alert-warning'>Los partidos de $target_phase ya existen. Borra los partidos manualmente si deseas regenerarlos.</p>";
                 throw new Exception("Partidos ya generados.");
            }
            
            $qualifiers_map = [];
            foreach ($all_standings as $group => $standings) {
                $qualifiers_map['1' . $group] = $standings[0]['id']; 
                $qualifiers_map['2' . $group] = $standings[1]['id']; 
            }
            
            $stmt_insert = $pdo->prepare("INSERT INTO matches (team_home_id, team_away_id, match_date, stadium, phase) VALUES (?, ?, ?, ?, ?)");
            $match_date = date('Y-m-d H:i:s', strtotime('2026-06-28 12:00:00'));
            
            foreach ($cruces as $match_data) {
                $source_home = $match_data[2]; $source_away = $match_data[3];
                $phase_name = $match_data[0]; $match_num = $match_data[1];
                $stadium = $match_data[4]; $date_ref = $match_data[5];
                
                $home_team_id = null;
                $away_team_id = null;
                
                // Lógica de asignación de ID local
                if (strpos($source_home, 'W') === 0 || strpos($source_home, 'L') === 0) {
                    $home_team_id = get_match_result_id($source_home, $match_results_map);
                } elseif (strpos($source_home, '3') === 0) { /* Terceros */
                    $home_team_id = $team_id_to_data[($top_eight_thirds[0]['id'] ?? 0)]['id'] ?? 0;
                } else {
                    $home_team_id = $qualifiers_map[$source_home];
                }
                
                // Lógica de asignación de ID visitante
                 if (strpos($source_away, 'W') === 0 || strpos($source_away, 'L') === 0) {
                    $away_team_id = get_match_result_id($source_away, $match_results_map);
                } elseif (strpos($source_away, '3') === 0) { /* Terceros */
                    $away_team_id = $team_id_to_data[($top_eight_thirds[1]['id'] ?? 0)]['id'] ?? 0;
                } else {
                    $away_team_id = $qualifiers_map[$source_away];
                }
                
                // Si falta algún ID, se asume que la fase anterior no se cerró bien o faltan terceros.
                if (!$home_team_id || !$away_team_id) {
                     $output = "<p class='alert alert-danger'>Error: No se pudo asignar el equipo para el partido $match_num. Falta resultado de fase anterior o Tercero. (HOME: $source_home, AWAY: $source_away)</p>";
                     throw new Exception("Faltan datos de la fase anterior.");
                }
                
                $stmt_insert->execute([
                    $home_team_id, $away_team_id, $date_ref . ' 12:00:00', $stadium, $phase_name
                ]);
            }

            $output = "<p class='alert alert-success'>✅ Partidos de **$target_phase** generados con éxito. ¡Revisa la página de Partidos!</p>";
        
        // ----------------------------------------------------
        // FIN SIMULACIÓN DE LA INSERCIÓN DE PARTIDOS
        // ----------------------------------------------------
        
        } catch (Exception $e) {
            if ($e->getMessage() !== "Partidos ya generados." && $e->getMessage() !== "Grupos incompletos.") {
                 $output = "<p class='alert alert-danger'>Error al generar partidos: " . $e->getMessage() . "</p>";
            }
        }
    }
} catch (Exception $e) {
    $output .= "<p class='alert alert-danger'>Error de procesamiento general: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Bracket</title>
    <link rel="icon" type="image/png" href="../favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
    $current_page = 'knockout';
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4">⚙️ Gestión de Fases Eliminatorias</h2>
    <?php echo $output; ?>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white fw-bold">
                    1. Estado de Clasificación Final (A-L)
                </div>
                <div class="card-body">
                    <?php if (!$all_groups_closed): ?>
                        <div class="alert alert-warning">⚠️ No todos los partidos de grupo están cerrados. Los datos pueden ser inexactos.</div>
                    <?php endif; ?>
                    
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Gpo</th>
                                    <th>1º</th>
                                    <th>2º</th>
                                    <th>3º</th>
                                    <th>Puntos 3º</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_standings as $group => $standings): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $group; ?></td>
                                    <td><?php echo $team_id_to_data[$standings[0]['id']]['flag'] . ' ' . $team_id_to_data[$standings[0]['id']]['name']; ?></td>
                                    <td><?php echo $team_id_to_data[$standings[1]['id']]['flag'] . ' ' . $team_id_to_data[$standings[1]['id']]['name']; ?></td>
                                    <td class="<?php echo in_array($group, $qualifying_groups_thirds) ? 'table-warning fw-bold' : ''; ?>">
                                        <?php echo $team_id_to_data[$standings[2]['id']]['flag'] . ' ' . $team_id_to_data[$standings[2]['id']]['name']; ?>
                                    </td>
                                    <td><?php echo $standings[2]['pts']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm mb-4 h-100">
                <div class="card-header bg-primary text-white fw-bold">
                    2. Generación de Bracket
                </div>
                <div class="card-body">
                    <p><strong>Última Fase Generada:</strong> <?php echo $nombres_fases[$last_phase_generated]; ?></p>
                    
                    <?php if ($next_phase_to_generate !== null): ?>
                         <p class="alert alert-info">Siguiente fase a generar: **<?php echo $nombres_fases[$next_phase_to_generate]; ?>**.</p>
                         <form method="POST" action="knockout.php">
                             <input type="hidden" name="action" value="generate_<?php echo $next_phase_to_generate; ?>">
                             <button type="submit" class="btn btn-success mt-3 w-100" 
                                 <?php echo (!$all_groups_closed && $next_phase_to_generate === 'round_32') ? 'disabled' : ''; ?>>
                                 ▶️ Generar Partidos de <?php echo $nombres_fases[$next_phase_to_generate]; ?>
                             </button>
                         </form>
                    <?php else: ?>
                         <p class="alert alert-success">
                            ¡El Mundial ha terminado! Todos los cruces están listos. 🏆
                         </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>