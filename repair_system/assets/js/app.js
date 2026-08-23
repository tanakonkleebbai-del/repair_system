/**
 * Core Application JavaScript
 * Repair & Equipment Management System
 * Enhanced Mobile Responsive Drawer & Interactivity
 */

document.addEventListener('DOMContentLoaded', function () {
    // Auto Dismiss Alerts
    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Sidebar Mobile Drawer Controller
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        document.body.classList.add('sidebar-open');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (sidebar && sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function (e) {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            closeSidebar();
        });
    }

    // Auto-close sidebar on mobile when a nav link is tapped
    const sidebarNavLinks = document.querySelectorAll('.sidebar-menu .nav-link-custom');
    sidebarNavLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 991.98) {
                closeSidebar();
            }
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Image Preview Helper (File input upload preview)
    const imageInputs = document.querySelectorAll('.image-preview-input');
    imageInputs.forEach(function (input) {
        input.addEventListener('change', function (e) {
            const targetPreviewId = this.getAttribute('data-preview-target');
            const previewElem = document.getElementById(targetPreviewId);
            if (previewElem && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewElem.src = e.target.result;
                    previewElem.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Image Preview Lightbox / Modal Handler
    const imageModalElem = document.getElementById('imagePreviewModal');
    if (imageModalElem && typeof bootstrap !== 'undefined') {
        const modalImg = document.getElementById('imagePreviewModalImg');
        const modalTitle = document.getElementById('imagePreviewModalLabel');
        const modalNewTab = document.getElementById('imagePreviewModalNewTab');
        const bsImageModal = new bootstrap.Modal(imageModalElem);

        document.addEventListener('click', function (e) {
            const previewTrigger = e.target.closest('[data-preview-image], .img-previewable');
            if (previewTrigger) {
                e.preventDefault();
                e.stopPropagation();
                const imgSrc = previewTrigger.getAttribute('data-preview-image') || previewTrigger.getAttribute('src') || previewTrigger.getAttribute('href');
                const titleText = previewTrigger.getAttribute('data-title') || previewTrigger.getAttribute('alt') || 'ดูรูปภาพ';
                
                if (imgSrc && imgSrc !== '#' && !imgSrc.startsWith('javascript:')) {
                    if (modalImg) modalImg.src = imgSrc;
                    if (modalTitle) modalTitle.innerHTML = `<i class="fas fa-image me-2 text-primary"></i>${titleText}`;
                    if (modalNewTab) modalNewTab.href = imgSrc;
                    bsImageModal.show();
                }
            }
        });
    }
});

// SweetAlert2 Confirmation Dialog
function confirmDelete(url, title = 'ยืนยันการลบข้อมูล?', text = 'หากลบแล้วจะไม่สามารถกู้คืนได้!') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ใช่, ลบเลย',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    } else {
        if (confirm(title + '\n' + text)) {
            window.location.href = url;
        }
    }
}
