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
    <style>
        .rules-section-title { border-left: 5px solid #0d6efd; padding-left: 15px; margin-top: 30px; font-weight: bold; color: #333; }
        .achievement-badge-mini { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #ffc107; color: white; margin-right: 8px; font-size: 0.8rem; }
    </style>
</head>
<body class="bg-light">

<?php 
    $current_page = 'rules'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4 text-center text-primary fw-bold"><i class="bi bi-patch-question-fill"></i> Reglas Oficiales de la Quiniela</h2>

    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-body p-4 p-md-5">
            
            <h4 class="rules-section-title">1. Gestión de Pronósticos y Tiempos</h4>
            <hr>
            <ul>
                <li><strong>Bloqueo de Partidos:</strong> Las predicciones se cierran automáticamente <strong>2 minutos antes</strong> del pitido inicial.</li>
                <li><strong>Bloqueo de Torneo:</strong> Las elecciones de <strong>Campeón, Bota de Oro, Guante de Oro y Goles Totales</strong> son definitivas y se bloquean al comenzar el <strong>primer partido</strong> del Mundial.</li>
                <li><strong>Edición:</strong> Puedes cambiar tus resultados tantas veces como quieras mientras el cronómetro de bloqueo no llegue a cero.</li>
            </ul>

            <h4 class="rules-section-title">2. Comodín x2 y Duelos Directos</h4>
            <hr>
            <p class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill"></i> ¡Solo tienes UN Comodín x2 para todo el campeonato!</p>
            <ul>
                <li><strong>Efecto Comodín:</strong> Duplica los puntos obtenidos en el partido seleccionado (se aplica antes que cualquier reto).</li>
                <li><strong>⚔️ Duelos (Challenges):</strong> Puedes lanzar <strong>UN desafío por fase</strong>. 
                    <ul>
                        <li>Al ganar un duelo, sumas una <strong>bonificación de +15 puntos</strong> adicionales.</li>
                        <li>El ganador se decide por <strong>estricta jerarquía</strong>:</li>
                    </ul>
                    
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered text-center bg-white shadow-sm" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Prioridad</th>
                                    <th>Criterio</th>
                                    <th>Explicación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1ª</td>
                                    <td><strong>Signo (1X2)</strong></td>
                                    <td>Si tú aciertas quién gana/empata y tu rival no, ganas el duelo automáticamente.</td>
                                </tr>
                                <tr>
                                    <td>2ª</td>
                                    <td><strong>Precisión</strong></td>
                                    <td>Si ambos acertáis el signo, gana el que esté más cerca del marcador real (menos goles de diferencia).</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2"><i class="bi bi-info-circle"></i> Nota: El rival nunca pierde sus puntos base del partido; el duelo es una competición por un premio extra.</p>
                </li>
            </ul>

            <h4 class="rules-section-title">3. Sistema de Puntuación (Grupos vs Eliminatorias)</h4>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p class="fw-bold text-primary text-uppercase small">Fase de Grupos (No Acumulable)</p>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-primary">25 Pts</span> Resultado Exacto.</li>
                        <li><span class="badge bg-secondary">15 Pts</span> Tendencia (1X2).</li>
                        <li><span class="badge bg-light text-dark border">5 Pts</span> Goles de un equipo.</li>
                    </ul>
                </div>
                <div class="col-md-6 border-start">
                    <p class="fw-bold text-success text-uppercase small">Eliminatorias (Acumulable en Empates)</p>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-success">30 Pts</span> Resultado Exacto.</li>
                        <li><span class="badge bg-info text-dark">10 Pts</span> Equipo que Clasifica.</li>
                        <li><span class="badge bg-light text-dark border">5 Pts</span> Goles de un equipo.</li>
                        <li><small class="text-muted">* Si aciertas un empate exacto y el clasificado, sumas 30 + 10 = 40 Pts.</small></li>
                    </ul>
                </div>
            </div>

            <h4 class="rules-section-title">4. Bonificaciones Especiales</h4>
            <hr>
            <div class="table-responsive">
                <table class="table table-hover align-middle border">
                    <thead class="table-dark">
                        <tr>
                            <th>Concepto</th>
                            <th>Puntos</th>
                            <th>Requisito</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>🏆 Campeón del Mundo</td><td class="fw-bold text-success">150</td><td>Acertar el ganador de la gran final.</td></tr>
                        <tr><td>👟 Bota/🛡️ Guante de Oro</td><td class="fw-bold text-success">100 c/u</td><td>Acertar los premios oficiales de la FIFA.</td></tr>
                        <tr><td>⚽ Goles Totales</td><td class="fw-bold text-success">25</td><td>Margen de error de <strong>± 5 goles</strong> sobre el total.</td></tr>
                        <tr><td>🧠 Quiz Diario</td><td class="fw-bold text-success">10</td><td>Responder correctamente en <strong>menos de 10 segundos</strong>.</td></tr>
                    </tbody>
                </table>
            </div>

            <h4 class="rules-section-title">5. Perfil, Evolución y Logros</h4>
            <hr>
            <p>Hemos añadido nuevas herramientas para que sigas tu rendimiento de forma profesional:</p>
            <ul>
                <li><strong>📊 Gráfico de Evolución:</strong> En tu perfil puedes ver una gráfica con tu historial de puntos y posición en el ranking tras cada partido. ¡Analiza si estás en racha o necesitas remontar!</li>
                <li><strong>🏅 Sistema de Condecoraciones:</strong> Desbloquea medallas automáticas por tus hitos en el juego:
                    <ul>
                        <li><span class="text-primary fw-bold">Ojo de Halcón:</span> Por tu primer pleno (resultado exacto).</li>
                        <li><span class="text-warning fw-bold">Estratega:</span> Por sumar puntos importantes usando el Comodín x2.</li>
                        <li><span class="text-success fw-bold">Fiel Seguidor:</span> Por completar todos los pronósticos de la Fase de Grupos.</li>
                        <li><span class="text-info fw-bold">Maestro Quiz:</span> Por acertar 3 preguntas diarias de forma consecutiva.</li>
                        <li><span class="text-danger fw-bold">Caza-Gigantes:</span> Por ganar un Duelo Directo (Challenge).</li>
                    </ul>
                </li>
            </ul>

            <h4 class="rules-section-title">6. Herramientas de Análisis</h4>
            <hr>
            <ul>
                <li><strong>Timeline y Consenso:</strong> Consulta qué opina la mayoría antes de cerrar tu apuesta y comenta los partidos en tiempo real con el resto de jugadores.</li>
                <li><strong>Estadísticas Avanzadas:</strong> Consulta tu efectividad, promedio de goles y rendimiento específico en fases KO desde tu Dashboard de Estadísticas.</li>
            </ul>

        </div>
        <div class="card-footer bg-white border-0 text-center pb-4">
            <a href="index.php" class="btn btn-primary px-5 rounded-pill shadow-sm"><i class="bi bi-house-door me-2"></i>Volver al Dashboard</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>