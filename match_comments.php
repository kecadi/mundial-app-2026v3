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

// 2. Obtener todos los comentarios para este partido
$stmt_comments = $pdo->prepare("SELECT 
    c.comment, c.created_at, u.nombre, u.id as user_id 
FROM match_comments c
JOIN users u ON c.user_id = u.id
WHERE c.match_id = ?
ORDER BY c.created_at DESC");
$stmt_comments->execute([$match_id]);
$comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? null;

// --- 3. LÓGICA: MARCAR COMO LEÍDO ---
$sql_mark_read = "INSERT INTO user_read_status (user_id, match_id) 
                  VALUES (?, ?) 
                  ON DUPLICATE KEY UPDATE last_read_at = CURRENT_TIMESTAMP()";
$stmt_mark_read = $pdo->prepare($sql_mark_read);
$stmt_mark_read->execute([$user_id, $match_id]);
// --- FIN LÓGICA DE LECTURA ---

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline - <?php echo htmlspecialchars($match_info['home']); ?> vs <?php echo htmlspecialchars($match_info['away']); ?></title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php 
    $current_page = 'match'; 
    include 'includes/navbar.php'; 
?>

<div class="container my-5">
    <h2 class="mb-4">📢 Timeline de Comentarios</h2>
    <h3 class="text-primary mb-3">
        <?php echo htmlspecialchars($match_info['home']); ?> vs <?php echo htmlspecialchars($match_info['away']); ?>
    </h3>
    <p class="text-muted"><?php echo date('D d M H:i', strtotime($match_info['match_date'])); ?></p>

    <?php if($msg === 'sent'): ?>
        <div class="alert alert-success fw-bold">✅ Mensaje enviado correctamente.</div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-dark text-white fw-bold">
            Deja tu Mensaje
        </div>
        <div class="card-body">
            <form action="save_comment.php" method="POST">
                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                <div class="mb-3">
                    <textarea name="comment" class="form-control" rows="3" placeholder="¡Genera pique! ¿Quién ganará?" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-chat-dots-fill"></i> Enviar Comentario</button>
            </form>
        </div>
    </div>

    <h4 class="mb-3">Comentarios Recientes (<?php echo count($comments); ?>)</h4>
    <div class="list-group">
        <?php if (empty($comments)): ?>
            <div class="alert alert-light text-center">Sé el primero en comentar este partido.</div>
        <?php endif; ?>
        
        <?php foreach($comments as $c): ?>
            <div class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1 <?php echo ($c['user_id'] === $user_id) ? 'text-primary fw-bold' : 'text-dark fw-bold'; ?>">
                        <?php echo htmlspecialchars($c['nombre']); ?>
                    </h6>
                    <small class="text-muted"><?php echo date('d M, H:i', strtotime($c['created_at'])); ?></small>
                </div>
                <p class="mb-1">
                    <?php echo nl2br(htmlspecialchars($c['comment'])); // Seguridad XSS: Mostramos el comentario después de HTMLizarlo ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
    
    <a href="index.php" class="btn btn-secondary mt-4">Volver al Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>
</body>
</html>