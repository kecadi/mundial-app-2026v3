<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) exit;

$stmt = $pdo->query("SELECT c.*, u.nombre, u.id as u_id FROM global_chat c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 20");
$chat_messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach($chat_messages as $msg): 
    $is_me = ($msg['u_id'] == $_SESSION['user_id']);
?>
    <div class="d-flex <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?> mb-3">
        <div style="max-width: 85%;">
            <div class="d-flex align-items-center mb-1 <?php echo $is_me ? 'justify-content-end' : ''; ?>">
                <small class="fw-bold text-muted px-1" style="font-size: 0.65rem;">
                    <?php echo htmlspecialchars($msg['nombre']); ?> • <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                </small>
            </div>
            <div class="p-3 shadow-sm <?php echo $is_me ? 'bg-primary text-white rounded-start-4 rounded-bottom-4' : 'bg-white text-dark rounded-end-4 rounded-bottom-4'; ?>" style="font-size: 0.9rem; line-height: 1.4; border: 1px solid rgba(0,0,0,0.05);">
                <?php echo htmlspecialchars($msg['message']); ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>