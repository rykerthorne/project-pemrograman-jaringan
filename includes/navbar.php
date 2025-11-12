 <?php
// includes/navbar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.navbar-custom {
    background: linear-gradient(135deg, #6BA3D7, #A8D0F0);
    padding: 1rem 0;
    box-shadow: 0 4px 15px rgba(107, 163, 215, 0.2);
}

.navbar-brand-custom {
    font-size: 1.5rem;
    font-weight: 800;
    color: white !important;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-nav-custom .nav-link {
    color: white !important;
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    margin: 0 0.3rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.navbar-nav-custom .nav-link:hover,
.navbar-nav-custom .nav-link.active {
    background: rgba(255, 255, 255, 0.2);
}

.user-dropdown {
    color: white !important;
}

.dropdown-menu-custom {
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: none;
}
</style>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand-custom" href="<?= isAdmin() ? '../admin/index.php' : '../user/index.php' ?>">
            <i class="fas fa-building"></i>
            <span><?= SITE_NAME ?></span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (isAdmin()): ?>
            <!-- Admin Menu -->
            <ul class="navbar-nav ms-auto navbar-nav-custom">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'ruangan.php' ? 'active' : '' ?>" href="ruangan.php">
                        <i class="fas fa-door-open"></i> Ruangan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'booking.php' ? 'active' : '' ?>" href="booking.php">
                        <i class="fas fa-calendar-check"></i> Booking
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>" href="users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'laporan.php' ? 'active' : '' ?>" href="laporan.php">
                        <i class="fas fa-chart-bar"></i> Laporan
                    </a>
                </li>
            </ul>
            <?php else: ?>
            <!-- User Menu -->
            <ul class="navbar-nav ms-auto navbar-nav-custom">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'booking.php' ? 'active' : '' ?>" href="booking.php">
                        <i class="fas fa-calendar-plus"></i> Booking Baru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'riwayat.php' ? 'active' : '' ?>" href="riwayat.php">
                        <i class="fas fa-history"></i> Riwayat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'profil.php' ? 'active' : '' ?>" href="profil.php">
                        <i class="fas fa-user"></i> Profil
                    </a>
                </li>
            </ul>
            <?php endif; ?>
            
            <!-- User Dropdown -->
            <ul class="navbar-nav ms-3">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-dropdown" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['nama']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                        <li>
                            <a class="dropdown-item" href="<?= isAdmin() ? '../admin/profil.php' : '../user/profil.php' ?>">
                                <i class="fas fa-user-cog"></i> Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
