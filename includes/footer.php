 <?php
// includes/footer.php
?>
<style>
.footer-custom {
    background: linear-gradient(135deg, #4A7BA7, #6BA3D7);
    color: white;
    padding: 3rem 0 1rem;
    margin-top: 4rem;
}

.footer-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
}

.footer-links a:hover {
    color: white;
    padding-left: 5px;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding-top: 1.5rem;
    margin-top: 2rem;
    text-align: center;
}

.social-icons a {
    display: inline-block;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    margin: 0 0.3rem;
    color: white;
    transition: all 0.3s ease;
}

.social-icons a:hover {
    background: white;
    color: #6BA3D7;
}
</style>

<footer class="footer-custom">
    <div class="container">
        <div class="row">
            <!-- About -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">
                    <i class="fas fa-building me-2"></i>Booking Ruangan UCA
                </h5>
                <p style="color: rgba(255, 255, 255, 0.8);">
                    Sistem informasi booking ruangan kampus Universitas Cendekia Abditama untuk memudahkan proses peminjaman ruangan secara online.
                </p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="../index.php"><i class="fas fa-angle-right me-2"></i>Beranda</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li><a href="../admin/index.php"><i class="fas fa-angle-right me-2"></i>Dashboard Admin</a></li>
                            <li><a href="../admin/ruangan.php"><i class="fas fa-angle-right me-2"></i>Kelola Ruangan</a></li>
                        <?php else: ?>
                            <li><a href="../user/index.php"><i class="fas fa-angle-right me-2"></i>Dashboard</a></li>
                            <li><a href="../user/booking.php"><i class="fas fa-angle-right me-2"></i>Booking Ruangan</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="../auth/login.php"><i class="fas fa-angle-right me-2"></i>Login</a></li>
                        <li><a href="../auth/register.php"><i class="fas fa-angle-right me-2"></i>Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">Kontak Kami</h5>
                <ul class="footer-links">
                    <li>
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Jl. Kampus UCA No. 123<br>
                        <span style="padding-left: 1.5rem;">Jakarta, Indonesia</span>
                    </li>
                    <li>
                        <i class="fas fa-phone me-2"></i>
                        (021) 1234-5678
                    </li>
                    <li>
                        <i class="fas fa-envelope me-2"></i>
                        <?= SITE_EMAIL ?>
                    </li>
                    <li>
                        <i class="fas fa-clock me-2"></i>
                        Senin - Jumat: 07:00 - 21:00
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="mb-0">
                &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved. | 
                Developed with <i class="fas fa-heart text-danger"></i> for UCA
            </p>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button onclick="topFunction()" id="backToTop" class="btn btn-primary" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 99; border-radius: 50%; width: 50px; height: 50px;">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Back to Top Button
let mybutton = document.getElementById("backToTop");

window.onscroll = function() {
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
        mybutton.style.display = "block";
    } else {
        mybutton.style.display = "none";
    }
};

function topFunction() {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
}
</script>
