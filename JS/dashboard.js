// js/dashboard.js - Enhanced JavaScript untuk Dashboard

const API_BASE = '../api/';

// Utility Functions
function showAlert(elementId, message, type) {
    const alertDiv = document.getElementById(elementId);
    if (alertDiv) {
        alertDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        setTimeout(() => { 
            alertDiv.innerHTML = ''; 
        }, 5000);
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? '#48bb78' : type === 'error' ? '#f56565' : '#4299e1'};
        color: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideInRight 0.4s ease, fadeOut 0.3s ease 2.7s;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #e2e8f0;
        border-top-color: #667eea;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease;
    }
    
    .slide-in {
        animation: slideIn 0.5s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stat-card {
        animation: scaleIn 0.5s ease;
    }
    
    .stat-card:nth-child(2) {
        animation-delay: 0.1s;
    }
    
    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    tbody tr {
        animation: slideIn 0.3s ease;
    }
    
    tbody tr:nth-child(even) {
        animation-delay: 0.05s;
    }
`;
document.head.appendChild(style);

function showLoading() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.id = 'loadingOverlay';
    overlay.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(API_BASE + endpoint, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API Error:', error);
        showNotification('Terjadi kesalahan koneksi', 'error');
        return { success: false, message: 'Terjadi kesalahan koneksi' };
    }
}

async function logout() {
    if (confirm('Yakin ingin logout?')) {
        showLoading();
        const result = await apiCall('auth.php?action=logout', 'POST');
        hideLoading();
        
        if (result.success) {
            showNotification('Logout berhasil', 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1000);
        }
    }
}

function switchTab(tab) {
    // Hide all tabs
    const tabs = ['dashboardTab', 'productsTab', 'profileTab'];
    tabs.forEach(t => {
        const element = document.getElementById(t);
        if (element) {
            element.classList.add('hidden');
        }
    });

    // Remove active class from all sidebar links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });

    // Show selected tab with animation
    const tabMap = {
        'dashboard': { element: 'dashboardTab', linkIndex: 0 },
        'products': { element: 'productsTab', linkIndex: 1 },
        'profile': { element: 'profileTab', linkIndex: 2 }
    };

    if (tabMap[tab]) {
        const selectedTab = document.getElementById(tabMap[tab].element);
        if (selectedTab) {
            selectedTab.classList.remove('hidden');
            selectedTab.classList.add('fade-in');
        }
        
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        if (sidebarLinks[tabMap[tab].linkIndex]) {
            sidebarLinks[tabMap[tab].linkIndex].classList.add('active');
        }
        
        // Load data for products tab
        if (tab === 'products') {
            loadProducts();
        }
    }
}

async function loadProducts() {
    const tbody = document.getElementById('productsBody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Memuat data...</td></tr>';
    
    const result = await apiCall('products.php');
    
    if (result.success) {
        const products = result.data;
        tbody.innerHTML = '';

        if (products.length === 0) {
            document.getElementById('emptyProducts').classList.remove('hidden');
            document.getElementById('productsTable').classList.add('hidden');
        } else {
            document.getElementById('emptyProducts').classList.add('hidden');
            document.getElementById('productsTable').classList.remove('hidden');

            products.forEach((product, index) => {
                const row = document.createElement('tr');
                row.style.animationDelay = `${index * 0.05}s`;
                row.innerHTML = `
                    <td><strong>${escapeHtml(product.code)}</strong></td>
                    <td>${escapeHtml(product.name)}</td>
                    <td><span style="padding: 4px 12px; background: #e6fffa; color: #234e52; border-radius: 20px; font-size: 12px; font-weight: 600;">${escapeHtml(product.category)}</span></td>
                    <td><strong>${product.stock}</strong></td>
                    <td><strong>Rp ${parseFloat(product.price).toLocaleString('id-ID')}</strong></td>
                    <td class="action-buttons">
                        <button class="btn-warning" onclick="editProduct(${product.id})" title="Edit Produk"> Edit</button>
                        <button class="btn-danger" onclick="deleteProduct(${product.id})" title="Hapus Produk"> Hapus</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        await updateStats();
    } else {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #f56565;">Gagal memuat data</td></tr>';
        showNotification(result.message || 'Gagal memuat data', 'error');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function updateStats() {
    const result = await apiCall('products.php?stats=1');
    
    if (result.success) {
        animateNumber('productCount', result.data.total_products);
        animateNumber('totalStock', result.data.total_stock);
    }
}

function animateNumber(elementId, targetValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const duration = 1000;
    const steps = 30;
    const stepValue = targetValue / steps;
    let currentValue = 0;
    let currentStep = 0;
    
    const interval = setInterval(() => {
        currentValue += stepValue;
        currentStep++;
        
        if (currentStep >= steps) {
            element.textContent = targetValue;
            clearInterval(interval);
        } else {
            element.textContent = Math.floor(currentValue);
        }
    }, duration / steps);
}

function openProductModal(productId = null) {
    const modal = document.getElementById('productModal');
    modal.classList.add('show');
    document.getElementById('productForm').reset();
    
    if (productId) {
        loadProductData(productId);
    } else {
        document.getElementById('productModalTitle').textContent = 'Tambah Produk';
        document.getElementById('productId').value = '';
    }
    
    // Focus first input
    setTimeout(() => {
        document.getElementById('productCode').focus();
    }, 100);
}

async function loadProductData(productId) {
    showLoading();
    const result = await apiCall(`products.php?id=${productId}`);
    hideLoading();
    
    if (result.success) {
        const product = result.data;
        document.getElementById('productModalTitle').textContent = 'Edit Produk';
        document.getElementById('productId').value = product.id;
        document.getElementById('productCode').value = product.code;
        document.getElementById('productName').value = product.name;
        document.getElementById('productCategory').value = product.category;
        document.getElementById('productStock').value = product.stock;
        document.getElementById('productPrice').value = product.price;
        document.getElementById('productDescription').value = product.description || '';
    } else {
        showNotification('Gagal memuat data produk', 'error');
        closeProductModal();
    }
}

function closeProductModal() {
    document.getElementById('productModal').classList.remove('show');
}

async function editProduct(id) {
    openProductModal(id);
}

async function deleteProduct(id) {
    if (confirm('Yakin ingin menghapus produk ini?')) {
        showLoading();
        const result = await apiCall('products.php', 'DELETE', { id });
        hideLoading();
        
        if (result.success) {
            showNotification(result.message, 'success');
            await loadProducts();
        } else {
            showNotification(result.message, 'error');
        }
    }
}

// Form Handlers
document.getElementById('productForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('productId').value;
    const productData = {
        code: document.getElementById('productCode').value,
        name: document.getElementById('productName').value,
        category: document.getElementById('productCategory').value,
        stock: parseInt(document.getElementById('productStock').value),
        price: parseFloat(document.getElementById('productPrice').value),
        description: document.getElementById('productDescription').value
    };

    showLoading();
    let result;
    if (id) {
        productData.id = parseInt(id);
        result = await apiCall('products.php', 'PUT', productData);
    } else {
        result = await apiCall('products.php', 'POST', productData);
    }
    hideLoading();

    if (result.success) {
        showNotification(result.message, 'success');
        await loadProducts();
        closeProductModal();
    } else {
        showNotification(result.message, 'error');
    }
});

document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const name = document.getElementById('profileName').value;
    
    showLoading();
    const result = await apiCall('user.php', 'POST', {
        action: 'update-profile',
        name: name
    });
    hideLoading();

    if (result.success) {
        showAlert('profileAlert', result.message, 'success');
        showNotification('Profil berhasil diupdate', 'success');
    } else {
        showAlert('profileAlert', result.message, 'error');
    }
});

document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPasswordProfile').value;
    const confirmPassword = document.getElementById('confirmPasswordProfile').value;

    if (newPassword.length < 6) {
        showAlert('profileAlert', 'Password minimal 6 karakter!', 'error');
        return;
    }

    if (newPassword !== confirmPassword) {
        showAlert('profileAlert', 'Password baru tidak cocok!', 'error');
        return;
    }

    showLoading();
    const result = await apiCall('user.php', 'POST', {
        action: 'change-password',
        current_password: currentPassword,
        new_password: newPassword
    });
    hideLoading();

    if (result.success) {
        showAlert('profileAlert', result.message, 'success');
        showNotification('Password berhasil diubah', 'success');
        document.getElementById('changePasswordForm').reset();
    } else {
        showAlert('profileAlert', result.message, 'error');
    }
});

// Close modal when clicking outside
document.getElementById('productModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProductModal();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC to close modal
    if (e.key === 'Escape') {
        closeProductModal();
    }
    
    // Ctrl+K to open product modal
    if (e.ctrlKey && e.key === 'k') {
        e.preventDefault();
        openProductModal();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    updateStats();
    
    // Refresh stats every 30 seconds
    setInterval(updateStats, 30000);
    
    console.log('✅ Dashboard initialized successfully');
});