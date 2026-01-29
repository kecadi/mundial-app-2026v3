    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'guardado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ ¡Tu pronóstico se ha guardado correctamente!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>