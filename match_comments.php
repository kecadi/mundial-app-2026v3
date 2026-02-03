<?php
// match_comments.php
session_start();
require_once 'config/db.php'; 

if (!isset($_SESSION['user_id']) || !isset($_GET['match_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$match_id = (int)$_GET['match_id'];

// 1. Obtener información básica del partido
$stmt_match = $pdo->prepare("SELECT 
    t1.name AS home, t2.name AS away, m.match_date, m.stadium 
FROM matches m
JOIN teams t1 ON m.team_home_id = t1.id
JOIN teams t2 ON m.team_away_id = t2.id
WHERE m.id = ?");
$stmt_match->execute([$match_id]);
$match_info = $stmt_match->fetch(PDO::FETCH_ASSOC);

if (!$match_info) { die("Partido no encontrado."); }

// 2. Obtener todos los comentarios (Ordenados por fecha ASC para leer de arriba a abajo como un chat)
$stmt_comments = $pdo->prepare("SELECT 
    c.comment, c.created_at, u.nombre, u.id as user_id 
FROM match_comments c
JOIN users u ON c.user_id = u.id
WHERE c.match_id = ?
ORDER BY c.created_at ASC");
$stmt_comments->execute([$match_id]);
$comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

// 3. Marcar como leído
$sql_mark_read = "INSERT INTO user_read_status (user_id, match_id) 
                  VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE last_read_at = CURRENT_TIMESTAMP()";
$stmt_mark_read = $pdo->prepare($sql_mark_read);
$stmt_mark_read->execute([$user_id, $match_id]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline - <?php echo htmlspecialchars($match_info['home']); ?> vs <?php echo htmlspecialchars($match_info['away']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .chat-container { background: #f0f2f5; border-radius: 15px; padding: 20px; height: 500px; overflow-y: auto; display: flex; flex-direction: column; }
        .message { max-width: 75%; margin-bottom: 15px; padding: 10px 15px; border-radius: 18px; position: relative; font-size: 0.95rem; line-height: 1.4; }
        .message.sent { align-self: flex-end; background: #0084ff; color: white; border-bottom-right-radius: 4px; }
        .message.received { align-self: flex-start; background: white; color: #333; border-bottom-left-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .message-info { font-size: 0.7rem; margin-bottom: 3px; font-weight: bold; display: block; }
        .message-time { font-size: 0.65rem; opacity: 0.7; display: block; text-align: right; margin-top: 5px; }
        .match-header-box { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; border-radius: 15px; margin-bottom: 25px; }
    </style>
</head>
<body class="bg-light">

<?php $current_page = 'match'; include 'includes/navbar.php'; ?>

<div class="container my-4">
    <div class="match-header-box p-4 shadow-sm text-center">
        <h5 class="text-uppercase fw-bold text-info small mb-2">Timeline del Partido</h5>
        <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($match_info['home']); ?> vs <?php echo htmlspecialchars($match_info['away']); ?></h2>
        <p class="mb-0 opacity-75 small">
            <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($match_info['stadium']); ?> | 
            <i class="bi bi-calendar-event"></i> <?php echo date('d M, H:i', strtotime($match_info['match_date'])); ?>
        </p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="chat-container shadow-sm mb-4" id="chatWindow">
                <?php if (empty($comments)): ?>
                    <div class="text-center my-auto opacity-50">
                        <i class="bi bi-chat-dots fs-1"></i>
                        <p class="mt-2">Aún no hay comentarios. ¡Rómpelo tú!</p>
                    </div>
                <?php endif; ?>

                <?php foreach($comments as $c): 
                    $is_me = ($c['user_id'] === $user_id);
                ?>
                    <div class="message <?php echo $is_me ? 'sent' : 'received'; ?>">
                        <?php if(!$is_me): ?>
                            <span class="message-info text-primary"><?php echo htmlspecialchars($c['nombre']); ?></span>
                        <?php endif; ?>
                        
                        <?php echo nl2br(htmlspecialchars($c['comment'])); ?>
                        
                        <span class="message-time">
                            <?php echo date('H:i', strtotime($c['created_at'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-2">
                    <form action="save_comment.php" method="POST" class="d-flex gap-2">
                        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                        <input type="text" name="comment" class="form-control border-0 bg-light" 
                               placeholder="Escribe tu mensaje aquí..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary rounded-circle" style="width: 45px; height: 45px;">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-link text-muted text-decoration-none">
                    <i class="bi bi-chevron-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Hacer scroll automático al final del chat al cargar
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.scrollTop = chatWindow.scrollHeight;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>