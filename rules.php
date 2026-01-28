<?php
// rules.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reglas Oficiales - Mundial 2026 Quiniela</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
    $current_page = 'rules'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4 text-center text-primary"><i class="bi bi-patch-question-fill"></i> Reglas Oficiales de la Quiniela</h2>

    <div class="card shadow-lg mb-5">
        <div class="card-body">
            <h4 class="card-title text-dark">1. Reglas Generales y Bloqueo de Tiempo</h4>
            <hr>
            <ul>
                <li>**Bloqueo de Partidos:** Todas las predicciones de partido se bloquean **2 minutos antes** de la hora de inicio oficial del encuentro. Una vez bloqueado, el pronóstico es final.</li>
                <li>**Bloqueo Especial (Alto Valor):** Las predicciones de **Goleador, Portero, Campeón y Goles Totales** se cierran antes del **primer partido** del torneo.</li>
                <li>**Cálculo:** Los puntos se calculan automáticamente en el Panel Admin tras introducir el resultado real.</li>
            </ul>

            <h4 class="card-title text-dark mt-4">2. Comodín x2 y Desafío de Predicción (Duelo)</h4>
            <hr>
            <p class="fw-bold text-danger">¡Cada participante dispone de un único Comodín x2 para todo el torneo!</p>
            <ul>
                <li>**Comodín (Efecto):** Al activar el comodín en un partido, los puntos que obtengas por ese encuentro se **duplicarán (x2)**.</li>
                <li>**Comodín (Gasto):** Si obtienes 0 puntos en ese partido, el comodín se gasta sin efecto.</li>
                <li>**Comodín (Restricción):** Una vez fijado, es irreversible.</li>
                <hr>
                <li>**⚔️ Restricción del Duelo:** Solo puedes iniciar **UN Desafío de Predicción por cada fase del torneo** (Grupos, Octavos, Cuartos, etc.). Úsalo estratégicamente en el partido de mayor riesgo/recompensa.</li>
            </ul>

            <h4 class="card-title text-dark mt-4">3. Puntuación de Partidos (Fase de Grupos)</h4>
            <hr>
            <p class="fw-bold">Puntuación de FASE DE GRUPOS (No Acumulable - Se otorga la más alta):</p>
            <ul>
                <li>**25 puntos:** Acierto del Resultado Exacto.</li>
                <li>**15 puntos:** Acierto del Equipo Ganador o Empate (1X2).</li>
                <li>**5 puntos:** Acierto del Total de Goles de uno de los dos equipos.</li>
            </ul>

            <h4 class="card-title text-dark mt-4">4. Puntuación de Partidos (Fases Eliminatorias)</h4>
            <hr>
            <p class="fw-bold">Puntuación de ELIMINATORIAS (Dieciseisavos a la Final):</p>
            <ul>
                <li>**Acumulación Especial:** Si aciertas el **Resultado Exacto (30 pts)** Y el partido finalizó en **Empate**, los puntos del Clasificado (25 pts) se acumulan (máximo 55 pts).</li>
                <li>**30 puntos:** Acierto del Resultado Exacto.</li>
                <li>**25 puntos:** Acierto del Equipo que Clasifica (ganador final post-penaltis).</li>
                <li>**10 puntos:** Acierto del Total de Goles de uno de los dos equipos.</li>
            </ul>

            <h4 class="card-title text-dark mt-4">5. Bonificaciones Mayores y Acumuladas</h4>
            <hr>
            <table class="table table-sm table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Premio/Logro</th>
                        <th>Puntuación</th>
                        <th>Regla</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>🏆 Campeón del Torneo</td><td class="fw-bold">150 puntos</td><td>Acertar el ganador final del Mundial.</td></tr>
                    <tr><td>👟 Goleador/🛡️ Portero</td><td class="fw-bold">100 puntos c/u</td><td>Acertar al ganador oficial del premio.</td></tr>
                    <tr><td>⚽ Goles Totales</td><td>25 puntos</td><td>Acertar el total de goles del torneo con un margen de **± 5 goles**.</td></tr>
                    <tr><td>🎯 Quiz Diario</td><td>10 puntos</td><td>Acertar la pregunta diaria **en menos de 10 segundos**.</td></tr>
                    <tr><td colspan="3" class="table-light fw-bold">PUNTOS DE CLASIFICACIÓN DE GRUPO (No Acumulables)</td></tr>
                    <tr><td>Acierto de Orden Exacto (1º y 2º)</td><td>90 puntos</td><td></td></tr>
                    <tr><td>Acertar los 2 Clasificados (sin orden)</td><td>40 puntos</td><td></td></tr>
                    <tr><td>Acertar 1º o 2º (posición individual)</td><td>25 puntos</td><td></td></tr>
                    <tr><td>Acertar 1 Clasificado (sin orden)</td><td>20 puntos</td><td></td></tr>
                </tbody>
                    </table>

            <h4 class="card-title text-dark mt-4">6. Reconocimiento Social</h4>
            <hr>
            <p class="small text-muted">La aplicación incluye features sociales y de gestión:</p>
            <ul>
                <li>**Timeline:** Espacio de comentarios por partido (con seguridad XSS).</li>
                <li>**Consenso 1X2:** Gráfico que muestra el balance de predicciones de todos los usuarios.</li>
                <li>**Rivalidad:** Puedes fijar un rival en la página de Bonus para hacer seguimiento en el Ranking.</li>
                <li>**Medallas:** Se rastrean logros como **El Francotirador** (3 exactos consecutivos) para ser mostrados en tu Perfil (no otorgan puntos).</li>
            </ul>

        </div>
    </div>
    
    <a href="index.php" class="btn btn-secondary">Volver al Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>