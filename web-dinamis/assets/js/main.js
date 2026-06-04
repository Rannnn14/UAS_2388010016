/**
 * Script Interaksi Utama Web Perpustakaan
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Penanganan Menu Sidebar Responsif (Mobile)
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // 2. Konfirmasi Hapus Data
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-message') || 'Apakah Anda yakin ingin menghapus data ini?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // 3. Efek Input Focus Glow Efek (opsional, CSS sudah menangani, tetapi bisa ditambahkan interaksi micro)
    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            const label = this.previousElementSibling;
            if (label && label.classList.contains('form-label')) {
                label.style.color = 'var(--accent-primary)';
            }
        });
        
        input.addEventListener('blur', function() {
            const label = this.previousElementSibling;
            if (label && label.classList.contains('form-label')) {
                label.style.color = 'var(--text-secondary)';
            }
        });
    });
});
