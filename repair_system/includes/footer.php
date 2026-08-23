        </div><!-- /.content-body -->
        <footer class="footer py-3 text-center text-muted border-top bg-white mt-auto">
            <div class="container-fluid">
                <small>© <?= date('Y') + 543 ?> <?= SITE_NAME ?> • เวอร์ชัน <?= SITE_VERSION ?></small>
            </div>
        </footer>
    </div><!-- /.main-content -->
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
</div><!-- /.app-wrapper -->

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-md); overflow: hidden;">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h6 class="modal-title fw-bold text-white mb-0" id="imagePreviewModalLabel">
                    <i class="fas fa-image me-2 text-primary"></i>ดูรูปภาพ
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-dark d-flex justify-content-center align-items-center" style="min-height: 250px; max-height: 75vh; overflow: hidden;">
                <img id="imagePreviewModalImg" src="" alt="Image Preview" class="img-fluid" style="max-height: 75vh; width: auto; object-fit: contain; display: block; margin: auto;">
            </div>
            <div class="modal-footer bg-light border-0 py-2 d-flex justify-content-between">
                <a id="imagePreviewModalNewTab" href="#" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i>เปิดในแท็บใหม่
                </a>
                <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
<!-- Custom App JS -->
<script src="<?= base_url('assets/js/app.js?v=' . time()) ?>"></script>

<?php 
$flashMessage = $flash ?? (function_exists('get_flash') ? get_flash() : null);
if (!empty($flashMessage)): 
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: '<?= ($flashMessage['type'] === 'error') ? 'error' : (($flashMessage['type'] === 'warning') ? 'warning' : 'success') ?>',
        title: '<?= addslashes($flashMessage['message'] ?? '') ?>',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true
    });
});
</script>
<?php endif; ?>

</body>
</html>
