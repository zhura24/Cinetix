<?php
// includes/footer.php
?>
</main>

<footer class="mt-auto py-4 bg-dark border-top border-secondary">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-6 text-center text-md-start">
                <h6 class="text-warning mb-1 fw-bold"><i class="bi bi-film me-2"></i>CineTix Cinema</h6>
                <p class="text-light opacity-75 small mb-0">
                    Sistem pemesanan tiket bioskop modern dengan kemudahan reservasi kursi dan berbagai metode pembayaran.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <ul class="list-inline mb-0 small text-light opacity-75">
                    <li class="list-inline-item"><a href="<?= $baseUrl ?>index.php" class="text-light text-decoration-none">Beranda</a></li>
                    <li class="list-inline-item">&bull;</li>
                    <li class="list-inline-item"><a href="<?= $baseUrl ?>login.php" class="text-light text-decoration-none">Masuk</a></li>
                    <li class="list-inline-item">&bull;</li>
                    <li class="list-inline-item"><a href="<?= $baseUrl ?>register.php" class="text-light text-decoration-none">Daftar</a></li>
                </ul>
            </div>
        </div>
        <div class="text-center text-light opacity-75 small mt-3 pt-3 border-top border-secondary">
            &copy; <?= date('Y') ?> CineTix Cinema. All rights reserved.
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= $baseUrl ?>assets/js/script.js"></script>
</body>
</html>
