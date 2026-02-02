<?php
// Obtener los últimos 20 mensajes para el muro lateral
$stmt_chat = $pdo->query("SELECT c.*, u.nombre, u.id as u_id FROM global_chat c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 20");
$chat_messages = $stmt_chat->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="offcanvas offcanvas-end rounded-start-4 border-0 shadow-lg" tabindex="-1" id="offcanvasChat" aria-labelledby="offcanvasChatLabel" style="width: 380px;">
    <div class="offcanvas-header bg-dark text-white p-4">
        <h5 class="offcanvas-title fw-black" id="offcanvasChatLabel">
            <i class="bi bi-chat-left-text-fill text-info me-2"></i>MURO DE PIQUES
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-0 d-flex flex-column bg-light">
        <div id="chatMessagesBody" class="flex-grow-1 p-3" style="overflow-y: auto;">
            <?php if (empty($chat_messages)): ?>
                <div class="text-center mt-5 opacity-50">
                    <i class="bi bi-chat-quote fs-1"></i>
                    <p>Nadie ha dicho nada...<br>¡Rompe el hielo!</p>
                </div>
            <?php else: ?>
                <?php foreach(array_reverse($chat_messages) as $msg): 
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
            <?php endif; ?>
        </div>

        <div class="p-3 bg-white border-top shadow-sm">
            <form id="chatFormSidebar" class="input-group">
                <input type="text" id="chatMsgInput" class="form-control rounded-pill-start border-light bg-light px-3" 
                       placeholder="Escribe un pique..." maxlength="140" required>
                <button type="submit" class="btn btn-primary rounded-pill-end px-3">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="text-center mt-2">
                <small class="text-muted" style="font-size: 0.6rem;">Máximo 140 caracteres</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBody = document.getElementById('chatMessagesBody');
    const form = document.getElementById('chatFormSidebar');
    const input = document.getElementById('chatMsgInput');
    const badge = document.getElementById('chatNotificationBadge');
    const offcanvasEl = document.getElementById('offcanvasChat');
    
    let lastMessageCount = 0;
    let isChatOpen = false;

    // Función para verificar nuevos mensajes y actualizar contenido
    function updateChat(forceScroll = false) {
        fetch('get_chat_messages.php')
            .then(r => r.text())
            .then(html => {
                // Creamos un elemento temporal para contar los mensajes en el HTML recibido
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const currentMessageCount = tempDiv.querySelectorAll('.d-flex').length;

                // Si hay más mensajes que antes y el chat está cerrado, mostramos notificación
                if (lastMessageCount !== 0 && currentMessageCount > lastMessageCount && !isChatOpen) {
                    badge.style.display = 'block';
                }

                // Si el chat está abierto, actualizamos el contenido
                if (isChatOpen || lastMessageCount === 0) {
                    if (chatBody.innerHTML !== html) {
                        chatBody.innerHTML = html;
                        if (forceScroll) chatBody.scrollTop = chatBody.scrollHeight;
                    }
                }
                
                lastMessageCount = currentMessageCount;
            });
    }

    // Detectar apertura del chat
    offcanvasEl.addEventListener('shown.bs.offcanvas', () => {
        isChatOpen = true;
        badge.style.display = 'none'; // Quitamos la notificación
        updateChat(true); // Forzamos carga y scroll al fondo
    });

    // Detectar cierre del chat
    offcanvasEl.addEventListener('hidden.bs.offcanvas', () => {
        isChatOpen = false;
    });

    // Poll de actualización cada 5 segundos (Background check)
    setInterval(() => updateChat(false), 5000);

    // Envío de mensajes (Mejorado)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = input.value.trim();
        if(!msg) return;

        fetch('save_chat_msg.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(msg)
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                input.value = '';
                updateChat(true); // Actualizar y bajar scroll inmediatamente
            }
        });
    });

    // Carga inicial silenciosa para establecer el contador base
    updateChat(false);
});
</script>

<style>
.rounded-pill-start { border-top-left-radius: 50px; border-bottom-left-radius: 50px; }
.rounded-pill-end { border-top-right-radius: 50px; border-bottom-right-radius: 50px; }
.fw-black { font-weight: 900; }
</style>