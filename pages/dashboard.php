<?php
// pages/dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>manajemen gudang </title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard-layout">
        <div class="sidebar">
            <div>
                <h3> manajemen gudang </h3>
                <nav>
                    <a href="#" class="sidebar-link active" onclick="switchTab('dashboard')">Dashboard</a>
                    <a href="#" class="sidebar-link" onclick="switchTab('products')">Produk</a>
                    <a href="#" class="sidebar-link" onclick="switchTab('profile')">Profil</a>
                </nav>
            </div>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <span><?php echo htmlspecialchars($userEmail); ?></span>
                </div>
                <button class="btn-logout-sidebar" onclick="logout()">Logout</button>
            </div>
        </div>

        <div class="dashboard-main">
            <div id="dashboardTab">
                <h2>Dashboard Admin Gudang</h2>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-value" id="productCount">0</div>
                        <div class="stat-label">Total Produk</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="totalStock">0</div>
                        <div class="stat-label">Total Stok</div>
                    </div>
                </div>
            </div>

            <div id="productsTab" class="hidden">
                <h2>Manajemen Produk</h2>
                <button class="btn-small" onclick="openProductModal()"> Tambah Produk</button>
                
                <table id="productsTable">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="productsBody">
                        <tr><td colspan="6" style="text-align: center;">Loading...</td></tr>
                    </tbody>
                </table>
                <div id="emptyProducts" class="empty-state hidden">
                    <p>Belum ada produk. Klik "Tambah Produk" untuk memulai.</p>
                </div>
            </div>

            <div id="profileTab" class="hidden">
                <h2>Informasi Profil</h2>
                <div id="profileAlert"></div>
                
                <form id="profileForm">
                    <div class="form-group">
                        <label for="profileName">Nama Lengkap</label>
                        <input type="text" id="profileName" value="<?php echo htmlspecialchars($userName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="profileEmail">Email</label>
                        <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
                    </div>
                    <button type="submit" class="btn-small">Update Profil</button>
                </form>

                <h2>Ubah Password</h2>
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label for="currentPassword">Password Saat Ini</label>
                        <input type="password" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="newPasswordProfile">Password Baru</label>
                        <input type="password" id="newPasswordProfile" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirmPasswordProfile">Konfirmasi Password Baru</label>
                        <input type="password" id="confirmPasswordProfile" required>
                    </div>
                    <button type="submit" class="btn-small">Ubah Password</button>
                </form>
            </div>
        </div>
    </div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="productModalTitle">Tambah Produk</h2>
                <button class="modal-close" onclick="closeProductModal()">&times;</button>
            </div>
            <form id="productForm">
                <input type="hidden" id="productId">
                <div class="form-group">
                    <label for="productCode">Kode Produk</label>
                    <input type="text" id="productCode" required>
                </div>
                <div class="form-group">
                    <label for="productName">Nama Produk</label>
                    <input type="text" id="productName" required>
                </div>
                <div class="form-group">
                    <label for="productCategory">Kategori</label>
                    <input type="text" id="productCategory" required>
                </div>
                <div class="form-group">
                    <label for="productStock">Stok</label>
                    <input type="number" id="productStock" required min="0">
                </div>
                <div class="form-group">
                    <label for="productPrice">Harga (Rp)</label>
                    <input type="number" id="productPrice" required min="0">
                </div>
                <div class="form-group">
                    <label for="productDescription">Deskripsi</label>
                    <textarea id="productDescription"></textarea>
                </div>
                <button type="submit">Simpan</button>
                <button type="button" class="btn-secondary" onclick="closeProductModal()">Batal</button>
            </form>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>
</body>
</html>