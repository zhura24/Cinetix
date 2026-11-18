<?php
// includes/footer.php
?>
</main>

<footer class="mt-auto py-4 bg-dark border-top border-secondary">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-6 text-center text-md-start">
                <h6 class="text-warning mb-1 fw-bold"><i class="bi bi-film me-2"></i>CineTix - Tugas Kelompok PBO</h6>
                <p class="text-muted small mb-0">
                    Sistem Pemesanan Tiket Bioskop berbasis OOP PHP (16 Class, 3 Abstract Class, Polimorfisme, Pewarisan, Komposisi).
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted small me-2">Akun Demo Cepat:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= $baseUrl ?>login.php?quick=admin" class="btn btn-outline-danger">Admin</a>
                    <a href="<?= $baseUrl ?>login.php?quick=kasir" class="btn btn-outline-info">Kasir</a>
                    <a href="<?= $baseUrl ?>login.php?quick=pelanggan" class="btn btn-outline-warning">Budi (Pelanggan)</a>
                </div>
            </div>
        </div>
        <div class="text-center text-muted small mt-3 pt-3 border-top border-secondary">
            &copy; <?= date('Y') ?> CineTix Kelompok 8. Dibuat untuk Tugas Pemrograman Berorientasi Objek.
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= $baseUrl ?>assets/js/script.js"></script>
</body>
</html>
