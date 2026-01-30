<?php
// groups.php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['nombre'];

// 1. Obtener TODOS los equipos
$stmt = $pdo->query("SELECT * FROM teams ORDER BY group_name, name");
$all_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar
$equipos_por_grupo = [];
foreach ($all_teams as $t) {
    $equipos_por_grupo[$t['group_name']][] = $t;
}

// 2. Partidos Reales
$sql_real = "SELECT team_home_id, team_away_id, home_score, away_score 
             FROM matches WHERE phase = 'group'";
$partidos_reales = $pdo->query($sql_real)->fetchAll(PDO::FETCH_ASSOC);

// 3. Predicciones
$sql_user = "SELECT m.team_home_id, m.team_away_id, 
                    p.predicted_home_score, p.predicted_away_score
             FROM matches m
             LEFT JOIN predictions p ON m.id = p.match_id AND p.user_id = :uid
             WHERE m.phase = 'group'";
$stmt = $pdo->prepare($sql_user);
$stmt->execute(['uid' => $user_id]);
$partidos_predichos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clasificación de Grupos</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Estilos para compactar la tabla y que quepa todo */
        .table-group th, .table-group td { 
            text-align: center; 
            vertical-align: middle; 
            font-size: 0.85rem;
            padding: 0.5rem 0.2rem;
        }
        .col-equipo { text-align: left !important; width: 35%; }
        .flag-mini { font-size: 1.2rem; margin-right: 4px; }
        .fw-heavy { font-weight: 800; } /* Puntos más gorditos */
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'groups'; 
    include 'includes/navbar.php'; 
?>

<div class="container mb-5">
    <h2 class="mb-4 text-center"><i class="bi bi-table"></i> Tablas de Posiciones</h2>

    <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-real-tab" data-bs-toggle="pill" data-bs-target="#pills-real">
                🌎 Clasificación OFICIAL
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-user-tab" data-bs-toggle="pill" data-bs-target="#pills-user">
                👤 Mi Clasificación
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        
        <div class="tab-pane fade show active" id="pills-real">
            <div class="row">
                <?php foreach ($equipos_por_grupo as $grupo => $equipos): 
                    $tabla = calcularTablaGrupo($equipos, $partidos_reales);
                ?>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-dark text-white">
                            <span class="fw-bold">GRUPO <?php echo $grupo; ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-group mb-0">
                                    <thead class="table-secondary text-uppercase">
                                        <tr>
                                            <th>Pos</th>
                                            <th class="col-equipo">Equipo</th>
                                            <th title="Puntos">Pts</th>
                                            <th title="Partidos Jugados">PJ</th>
                                            <th title="Ganados">G</th>
                                            <th title="Empatados">E</th>
                                            <th title="Perdidos">P</th>
                                            <th title="Goles a Favor">GF</th>
                                            <th title="Goles en Contra">GC</th>
                                            <th title="Diferencia de Goles">DG</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $pos = 1;
                                        foreach($tabla as $fila): 
                                            $class_pos = ($pos <= 2) ? 'table-success' : ''; 
                                            $dg_signo = ($fila['dg'] > 0) ? '+'.$fila['dg'] : $fila['dg'];
                                        ?>
                                        <tr class="<?php echo $class_pos; ?>">
                                            <td class="fw-bold"><?php echo $pos; ?></td>
                                            <td class="col-equipo text-truncate">
                                                <?php 
                                                    // Definimos la ruta de la bandera usando el campo 'bandera' de tu tabla de grupos
                                                    $flag_path = "assets/img/banderas/" . $fila['bandera'] . ".png";
                                                    
                                                    if (file_exists($flag_path)): 
                                                ?>
                                                    <img src="<?php echo $flag_path; ?>" 
                                                        alt="<?php echo $fila['bandera']; ?>" 
                                                        class="me-2 shadow-sm" 
                                                        style="width: 25px; height: auto; border-radius: 4px; border: 0px solid rgba(0,0,0,0.1); vertical-align: middle;">
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark me-2" style="font-size: 0.7rem;">
                                                        <?php echo htmlspecialchars($fila['bandera']); ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php echo htmlspecialchars($fila['nombre']); ?>
                                            </td>
                                            <td class="fw-heavy fs-6"><?php echo $fila['pts']; ?></td>
                                            <td><?php echo $fila['pj']; ?></td>
                                            <td><?php echo $fila['g']; ?></td>
                                            <td><?php echo $fila['e']; ?></td>
                                            <td><?php echo $fila['p']; ?></td>
                                            <td><?php echo $fila['gf']; ?></td>
                                            <td><?php echo $fila['gc']; ?></td>
                                            <td class="fw-bold text-muted small"><?php echo $dg_signo; ?></td>
                                        </tr>
                                        <?php $pos++; endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="pills-user">
             <div class="alert alert-info text-center mb-4">
                <i class="bi bi-magic"></i> Así quedarían los grupos según <strong>tus predicciones</strong>.
            </div>
            <div class="row">
                <?php foreach ($equipos_por_grupo as $grupo => $equipos): 
                    $tabla = calcularTablaGrupo($equipos, $partidos_predichos);
                ?>
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-primary h-100">
                        <div class="card-header bg-primary text-white">
                            <span class="fw-bold">GRUPO <?php echo $grupo; ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-group mb-0">
                                    <thead class="table-light text-primary text-uppercase">
                                        <tr>
                                            <th>Pos</th>
                                            <th class="col-equipo">Equipo</th>
                                            <th>Pts</th>
                                            <th>PJ</th>
                                            <th>G</th>
                                            <th>E</th>
                                            <th>P</th>
                                            <th>GF</th>
                                            <th>GC</th>
                                            <th>DG</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $pos = 1;
                                        foreach($tabla as $fila): 
                                            $class_pos = ($pos <= 2) ? 'table-primary' : ''; 
                                            $dg_signo = ($fila['dg'] > 0) ? '+'.$fila['dg'] : $fila['dg'];
                                        ?>
                                        <tr class="<?php echo $class_pos; ?>">
                                            <td class="fw-bold"><?php echo $pos; ?></td>
                                            <td class="col-equipo text-truncate">
                                                <?php 
                                                    // Definimos la ruta de la bandera usando el campo 'bandera' de tu tabla de grupos
                                                    $flag_path = "assets/img/banderas/" . $fila['bandera'] . ".png";
                                                    
                                                    if (file_exists($flag_path)): 
                                                ?>
                                                    <img src="<?php echo $flag_path; ?>" 
                                                        alt="<?php echo $fila['bandera']; ?>" 
                                                        class="me-2 shadow-sm" 
                                                        style="width: 25px; height: auto; border-radius: 4px; border: 0px solid rgba(0,0,0,0.1); vertical-align: middle;">
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark me-2" style="font-size: 0.7rem;">
                                                        <?php echo htmlspecialchars($fila['bandera']); ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php echo htmlspecialchars($fila['nombre']); ?>
                                            </td>
                                            <td class="fw-heavy fs-6"><?php echo $fila['pts']; ?></td>
                                            <td><?php echo $fila['pj']; ?></td>
                                            <td><?php echo $fila['g']; ?></td>
                                            <td><?php echo $fila['e']; ?></td>
                                            <td><?php echo $fila['p']; ?></td>
                                            <td><?php echo $fila['gf']; ?></td>
                                            <td><?php echo $fila['gc']; ?></td>
                                            <td class="fw-bold text-muted small"><?php echo $dg_signo; ?></td>
                                        </tr>
                                        <?php $pos++; endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>