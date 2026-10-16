/**
 * ==========================================================================
 * SISTEM INFORMASI PERPUSTAKAAN - VANILLA JAVASCRIPT
 * Portal User (Peminjaman, Pengembalian dengan Denda Otomatis, Pendaftaran)
 * Panel Admin (Dashboard Analitik, Kelola Data Buku, Kelola Anggota, Sirkulasi)
 * ==========================================================================
 */

// 1. STATE GLOBAL APLIKASI
const API_BASE = 'api';
let currentRole = 'user';       // 'user' | 'admin'
let currentView = '';           // Current active view
let allMembers = [];            // Daftar seluruh anggota dari database
let activeMember = null;        // Anggota peminjam aktif saat ini { id_anggota, nama }
let allUserBooks = [];          // Katalog buku untuk user
let allAdminBooks = [];         // Data buku untuk tabel admin
let allAdminMembers = [];       // Data anggota untuk admin
let allCirculation = [];        // Data sirkulasi untuk admin
let pendingReturnLoan = null;   // Data pinjaman yang sedang dibuka di modal struk pengembalian
let editingBookId = null;       // ID buku yang sedang diedit di admin
let deleteTarget = null;        // { id, title, entity: 'buku' | 'anggota' }
let myTrendChart = null;        // Objek Chart.js untuk grafik tren
let myCategoryChart = null;     // Objek Chart.js untuk grafik kategori

// 2. INISIALISASI KETIKA HALAMAN SELESAI DIMUAT
document.addEventListener('DOMContentLoaded', async () => {
    // Muat daftar anggota terlebih dahulu
    await loadMembersList();

    // Pulihkan anggota peminjam aktif dari localStorage jika ada
    restoreActiveMember();

    // Default mulai di Mode User dan muat katalog peminjaman
    switchRole('user');
    switchView('user-pinjam');

    // Setup input pencarian katalog user
    setupUserCatalogSearch();
});

// ==========================================================================
// 3. ROLE SWITCHER (PORTAL USER <-> PANEL ADMIN)
// ==========================================================================
function switchRole(role) {
    currentRole = role;

    const btnUser = document.getElementById('btnRoleUser');
    const btnAdmin = document.getElementById('btnRoleAdmin');
    const memberChip = document.getElementById('activeMemberChip');
    const roleUserBadge = document.getElementById('roleUserBadge');
    const sidebarUserSec = document.getElementById('sidebarUserSection');
    const sidebarAdminSec = document.getElementById('sidebarAdminSection');

    if (role === 'user') {
        if (btnUser) btnUser.classList.add('active');
        if (btnAdmin) {
            btnAdmin.classList.remove('active');
            btnAdmin.classList.remove('admin-mode');
        }

        // Tampilkan chip anggota aktif di mode user
        if (memberChip) memberChip.style.display = 'inline-flex';
        if (roleUserBadge) roleUserBadge.style.display = 'none';

        // Tampilkan navigasi user di sidebar
        if (sidebarUserSec) sidebarUserSec.style.opacity = '1';
        if (sidebarAdminSec) sidebarAdminSec.style.opacity = '0.7';

        // Beralih ke view peminjaman jika sebelumnya di admin
        if (!currentView || !currentView.startsWith('user-')) {
            switchView('user-pinjam');
        }
    } else {
        if (btnAdmin) btnAdmin.classList.add('active', 'admin-mode');
        if (btnUser) btnUser.classList.remove('active');

        // Sembunyikan chip anggota di panel admin, tampilkan badge admin
        if (memberChip) memberChip.style.display = 'none';
        if (roleUserBadge) roleUserBadge.style.display = 'flex';

        // Tampilkan navigasi admin di sidebar
        if (sidebarAdminSec) sidebarAdminSec.style.opacity = '1';
        if (sidebarUserSec) sidebarUserSec.style.opacity = '0.7';

        // Beralih ke view dashboard admin jika sebelumnya di user
        if (!currentView || !currentView.startsWith('admin-')) {
            switchView('admin-dashboard');
        }
    }
}

// ==========================================================================
// 4. SISTEM PINDAH VIEW (NAVIGASI)
// ==========================================================================
function switchView(viewName) {
    currentView = viewName;

    // Daftar semua section
    const views = [
        'user-pinjam', 'user-kembali', 'user-daftar',
        'admin-dashboard', 'admin-buku', 'admin-anggota', 'admin-sirkulasi'
    ];

    // Sembunyikan semua view dan reset tombol nav
    views.forEach(v => {
        const secId = getViewSectionId(v);
        const navId = getNavButtonId(v);
        const secEl = document.getElementById(secId);
        const navEl = document.getElementById(navId);

        if (secEl) secEl.classList.add('hidden');
        if (navEl) navEl.classList.remove('active');
    });

    // Tampilkan view aktif
    const activeSecId = getViewSectionId(viewName);
    const activeNavId = getNavButtonId(viewName);
    const activeSec = document.getElementById(activeSecId);
    const activeNav = document.getElementById(activeNavId);

    if (activeSec) activeSec.classList.remove('hidden');
    if (activeNav) activeNav.classList.add('active');

    // Update Header Title & Subtitle
    updateHeaderTitles(viewName);

    // Muat data terkait view tersebut
    handleViewDataLoad(viewName);

    // Tutup sidebar di HP jika sedang terbuka
    const sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
    }
}

function getViewSectionId(name) {
    const map = {
        'user-pinjam':     'viewUserPinjam',
        'user-kembali':    'viewUserKembali',
        'user-daftar':     'viewUserDaftar',
        'admin-dashboard': 'viewAdminDashboard',
        'admin-buku':      'viewAdminBuku',
        'admin-anggota':   'viewAdminAnggota',
        'admin-sirkulasi': 'viewAdminSirkulasi',
    };
    return map[name] || name;
}

function getNavButtonId(name) {
    const map = {
        'user-pinjam':     'navUserPinjam',
        'user-kembali':    'navUserKembali',
        'user-daftar':     'navUserDaftar',
        'admin-dashboard': 'navAdminDashboard',
        'admin-buku':      'navAdminBuku',
        'admin-anggota':   'navAdminAnggota',
        'admin-sirkulasi': 'navAdminSirkulasi',
    };
    return map[name] || name;
}

function updateHeaderTitles(viewName) {
    const titleEl = document.getElementById('pageTitle');
    const subEl = document.getElementById('pageSubtitle');
    if (!titleEl || !subEl) return;

    switch (viewName) {
        case 'user-pinjam':
            titleEl.textContent = 'Peminjaman Buku';
            subEl.textContent = 'Jelajahi katalog buku dan pinjam sesuai batas waktu masing-masing buku';
            break;
        case 'user-kembali':
            titleEl.textContent = 'Pengembalian & Denda';
            subEl.textContent = 'Daftar buku dipinjam, hitung denda otomatis jika terlambat, dan konfirmasi pengembalian';
            break;
        case 'user-daftar':
            titleEl.textContent = 'Pendaftaran Anggota';
            subEl.textContent = 'Daftarkan anggota perpustakaan baru dan dapatkan Kartu Anggota Digital';
            break;
        case 'admin-dashboard':
            titleEl.textContent = 'Dashboard & Analitik';
            subEl.textContent = 'Ringkasan operasional perpustakaan, statistik sirkulasi, dan grafik analitik';
            break;
        case 'admin-buku':
            titleEl.textContent = 'Kelola Data Buku';
            subEl.textContent = 'Tambah, edit, hapus buku, dan atur batas hari peminjaman serta tarif denda';
            break;
        case 'admin-anggota':
            titleEl.textContent = 'Kelola Data Anggota';
            subEl.textContent = 'Daftar anggota perpustakaan terdaftar dan status pinjaman aktif';
            break;
        case 'admin-sirkulasi':
            titleEl.textContent = 'Sirkulasi & Log Transaksi';
            subEl.textContent = 'Monitoring seluruh transaksi peminjaman, batas kembali, dan audit denda keterlambatan';
            break;
    }
}

function handleViewDataLoad(viewName) {
    switch (viewName) {
        case 'user-pinjam':
            loadUserCatalog();
            break;
        case 'user-kembali':
            loadActiveLoans();
            loadReturnHistory();
            break;
        case 'user-daftar':
            updateLiveCard();
            break;
        case 'admin-dashboard':
            loadDashboardStats();
            break;
        case 'admin-buku':
            loadAdminBooks();
            break;
        case 'admin-anggota':
            loadAdminMembers();
            break;
        case 'admin-sirkulasi':
            loadAdminCirculation();
            break;
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

// ==========================================================================
// 5. MANAJEMEN ANGGOTA PEMINJAM AKTIF
// ==========================================================================
async function loadMembersList() {
    try {
        const res = await fetch(`${API_BASE}/library.php?action=daftar_anggota`);
        const json = await res.json();
        if (json.status === 'success' && json.data) {
            allMembers = json.data.items || [];
            populateAllMemberDropdowns();
        }
    } catch (err) {
        console.error('Gagal memuat anggota:', err);
    }
}

function populateAllMemberDropdowns() {
    // 1. Dropdown di modal pinjam buku
    const borrowSelect = document.getElementById('borrowMemberSelect');
    if (borrowSelect) {
        borrowSelect.innerHTML = allMembers.map(m => `
            <option value="${m.id_anggota}" ${activeMember && activeMember.id_anggota == m.id_anggota ? 'selected' : ''}>
                ${escapeHtml(m.nama)} (ID #${m.id_anggota}) - ${escapeHtml(m.no_hp || '-')}
            </option>
        `).join('');
    }

    // 2. Dropdown di filter pengembalian buku
    const returnSelect = document.getElementById('filterReturnMember');
    if (returnSelect) {
        const curVal = returnSelect.value || '0';
        returnSelect.innerHTML = '<option value="0">Semua Anggota (Seluruh Pinjaman Aktif)</option>' +
            allMembers.map(m => `
                <option value="${m.id_anggota}" ${curVal == m.id_anggota ? 'selected' : ''}>
                    ${escapeHtml(m.nama)} (ID #${m.id_anggota})
                </option>
            `).join('');
    }

    // 3. Dropdown di modal pemilih anggota aktif
    const pickerSelect = document.getElementById('memberPickerSelect');
    if (pickerSelect) {
        pickerSelect.innerHTML = allMembers.map(m => `
            <option value="${m.id_anggota}" ${activeMember && activeMember.id_anggota == m.id_anggota ? 'selected' : ''}>
                ${escapeHtml(m.nama)} — ID #${m.id_anggota} (${escapeHtml(m.no_hp || 'No HP -')})
            </option>
        `).join('');
    }
}

function restoreActiveMember() {
    try {
        const saved = localStorage.getItem('sim_perpus_active_member');
        if (saved) {
            activeMember = JSON.parse(saved);
        }
    } catch (e) {
        activeMember = null;
    }

    // Fallback ke anggota pertama jika belum ada
    if (!activeMember && allMembers.length > 0) {
        activeMember = {
            id_anggota: allMembers[0].id_anggota,
            nama: allMembers[0].nama
        };
    }

    updateActiveMemberChip();
}

function updateActiveMemberChip() {
    const chipName = document.getElementById('chipMemberName');
    const chipAvatar = document.getElementById('chipAvatar');
    if (!chipName || !chipAvatar) return;

    if (activeMember && activeMember.nama) {
        chipName.textContent = `${activeMember.nama} (#${activeMember.id_anggota})`;
        const initials = activeMember.nama.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        chipAvatar.textContent = initials || '👤';
    } else {
        chipName.textContent = 'Pilih Anggota...';
        chipAvatar.textContent = '👤';
    }
}

function openMemberPickerModal() {
    populateAllMemberDropdowns();
    const modal = document.getElementById('memberPickerModal');
    if (modal) modal.classList.add('active');
}

function closeMemberPickerModal() {
    const modal = document.getElementById('memberPickerModal');
    if (modal) modal.classList.remove('active');
}

function saveActiveMemberSelection() {
    const sel = document.getElementById('memberPickerSelect');
    if (!sel || !sel.value) return;

    const chosenId = parseInt(sel.value);
    const found = allMembers.find(m => parseInt(m.id_anggota) === chosenId);

    if (found) {
        activeMember = { id_anggota: found.id_anggota, nama: found.nama };
        localStorage.setItem('sim_perpus_active_member', JSON.stringify(activeMember));
        updateActiveMemberChip();
        showToast(`Peminjam aktif diatur ke: ${found.nama}`, 'success');
        closeMemberPickerModal();

        // Jika sedang di halaman pengembalian, perbarui filter
        const filterReturn = document.getElementById('filterReturnMember');
        if (filterReturn) {
            filterReturn.value = found.id_anggota;
            loadActiveLoans();
        }
    }
}

// ==========================================================================
// 6. BAGIAN USER: KATALOG PEMINJAMAN BUKU (DENGAN BATAS WAKTU BERAGAM)
// ==========================================================================
async function loadUserCatalog() {
    const grid = document.getElementById('userBooksGrid');
    if (!grid) return;

    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">Memuat katalog buku...</div>';

    try {
        const res = await fetch(`${API_BASE}/library.php?action=buku_tersedia`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            allUserBooks = json.data.items || [];
            populateUserCatalogCategories(allUserBooks);
            renderUserCatalog(allUserBooks);
        }
    } catch (err) {
        console.error('Gagal mengambil katalog buku:', err);
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--danger); padding: 40px;">Gagal memuat katalog buku dari server.</div>';
    }
}

function populateUserCatalogCategories(books) {
    const sel = document.getElementById('userCatalogFilterKategori');
    if (!sel) return;

    const categories = Array.from(new Set(books.map(b => b.nama_kategori).filter(Boolean))).sort();
    const curVal = sel.value;
    sel.innerHTML = '<option value="">Semua Kategori</option>' +
        categories.map(c => `<option value="${escapeHtml(c)}" ${c === curVal ? 'selected' : ''}>${escapeHtml(c)}</option>`).join('');
}

function setupUserCatalogSearch() {
    const searchInput = document.getElementById('userCatalogSearch');
    if (!searchInput) return;

    let timer = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(filterUserCatalog, 250);
    });
}

function filterUserCatalog() {
    const search = document.getElementById('userCatalogSearch')?.value.trim().toLowerCase() || '';
    const kat = document.getElementById('userCatalogFilterKategori')?.value || '';
    const durasi = document.getElementById('userCatalogFilterDurasi')?.value || '';

    const filtered = allUserBooks.filter(b => {
        const matchSearch = !search ||
            (b.judul && b.judul.toLowerCase().includes(search)) ||
            (b.penulis && b.penulis.toLowerCase().includes(search)) ||
            (b.penerbit && b.penerbit.toLowerCase().includes(search));

        const matchKat = !kat || (b.nama_kategori === kat);

        const limitDays = parseInt(b.batas_hari_pinjam) || 7;
        let matchDurasi = true;
        if (durasi === 'short') matchDurasi = limitDays <= 7;
        if (durasi === 'long')  matchDurasi = limitDays > 7;

        return matchSearch && matchKat && matchDurasi;
    });

    renderUserCatalog(filtered);
}

function renderUserCatalog(books) {
    const grid = document.getElementById('userBooksGrid');
    const countEl = document.getElementById('userCatalogCount');
    if (!grid) return;

    if (countEl) countEl.textContent = `Menampilkan ${books.length} buku`;

    if (books.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 48px; background: var(--bg-card); border-radius: 16px; border: 1px dashed var(--border);">
                <div style="font-size: 32px; margin-bottom: 8px;">🔍</div>
                <h3 style="color: var(--text-main); font-size: 15px;">Tidak Ada Buku yang Cocok</h3>
                <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">Coba gunakan kata kunci pencarian atau filter kategori yang berbeda.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = books.map(book => {
        const isTersedia = parseInt(book.is_tersedia) === 1;
        const durasiHari = parseInt(book.batas_hari_pinjam) || 7;
        const dendaPerHari = parseInt(book.denda) || 1000;
        const stars = '⭐'.repeat(book.jumlah_rating || 0);

        return `
            <div class="catalog-card">
                <div>
                    <div class="catalog-card-header">
                        <span class="book-badge-cat">${escapeHtml(book.nama_kategori || 'Umum')}</span>
                        <span class="book-availability ${isTersedia ? 'tersedia' : 'dipinjam'}">
                            ${isTersedia ? '● Tersedia' : '● Sedang Dipinjam'}
                        </span>
                    </div>

                    <h3 class="catalog-card-title">${escapeHtml(book.judul)}</h3>
                    
                    <div class="catalog-card-meta">
                        <div>✍️ <strong>Penulis:</strong> ${escapeHtml(book.penulis || '-')}</div>
                        <div>🏢 <strong>Penerbit:</strong> ${escapeHtml(book.penerbit || '-')} (${escapeHtml(book.eidisi || 'Edisi 1')})</div>
                        <div style="margin-top: 4px;">${stars}</div>
                    </div>

                    <!-- Batas Pengembalian Beragam Tiap Buku & Tarif Denda -->
                    <div class="catalog-rules-box">
                        <div class="rule-item">
                            <span class="rule-item-label">⏱️ Batas Pengembalian:</span>
                            <span class="rule-tag-limit">${durasiHari} Hari</span>
                        </div>
                        <div class="rule-item">
                            <span class="rule-item-label">⚠️ Denda Keterlambatan:</span>
                            <span class="rule-tag-fine">Rp ${dendaPerHari.toLocaleString('id-ID')} / hari</span>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 8px;">
                    ${isTersedia ? `
                        <button onclick="openBorrowModal(${book.id_buku})" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            📖 Pinjam Buku Ini
                        </button>
                    ` : `
                        <button disabled class="btn btn-secondary" style="width: 100%; justify-content: center; opacity: 0.6; cursor: not-allowed;">
                            ⏳ Sedang Dipinjam Orang Lain
                        </button>
                    `}
                </div>
            </div>
        `;
    }).join('');
}

// ==========================================================================
// 7. MODAL KONFIRMASI PINJAM BUKU (DENGAN HITUNG DUE DATE OTOMATIS)
// ==========================================================================
function openBorrowModal(bookId) {
    const book = allUserBooks.find(b => parseInt(b.id_buku) === bookId);
    if (!book) return;

    document.getElementById('borrowBookId').value = book.id_buku;
    document.getElementById('borrowModalTitle').textContent = book.judul;
    document.getElementById('borrowModalAuthor').textContent = `${book.penulis} • ${book.penerbit}`;
    document.getElementById('borrowModalCategory').textContent = book.nama_kategori || 'Umum';

    const durasiHari = parseInt(book.batas_hari_pinjam) || 7;
    const dendaPerHari = parseInt(book.denda) || 1000;

    document.getElementById('borrowModalLimitText').textContent = `${durasiHari} Hari Peminjaman`;
    document.getElementById('borrowModalFineRate').textContent = `Rp ${dendaPerHari.toLocaleString('id-ID')} / hari`;

    // Hitung tanggal pinjam & due date
    const today = new Date();
    const dueDate = new Date();
    dueDate.setDate(today.getDate() + durasiHari);

    const formatOpts = { day: 'numeric', month: 'short', year: 'numeric' };
    document.getElementById('borrowModalBorrowDate').textContent = today.toLocaleDateString('id-ID', formatOpts);
    document.getElementById('borrowModalDueDate').textContent = `${dueDate.toLocaleDateString('id-ID', formatOpts)} (Maksimal)`;

    // Pastikan dropdown anggota terisi dan anggota aktif terpilih
    populateAllMemberDropdowns();
    if (activeMember) {
        document.getElementById('borrowMemberSelect').value = activeMember.id_anggota;
    }

    document.getElementById('borrowModal').classList.add('active');
}

function closeBorrowModal() {
    document.getElementById('borrowModal').classList.remove('active');
}

async function submitBorrowBook(event) {
    event.preventDefault();

    const bookId = parseInt(document.getElementById('borrowBookId').value);
    const memberId = parseInt(document.getElementById('borrowMemberSelect').value);
    const btn = document.getElementById('btnConfirmBorrow');

    if (!bookId || !memberId) {
        showToast('Pilih buku dan peminjam terlebih dahulu', 'error');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Memproses Peminjaman...';

    try {
        const res = await fetch(`${API_BASE}/library.php?action=pinjam_buku`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_anggota: memberId,
                id_buku: bookId
            })
        });

        const json = await res.json();

        if (json.status === 'success') {
            showToast(json.message, 'success');
            closeBorrowModal();

            // Refresh katalog & reload active loans
            loadUserCatalog();
            loadActiveLoans();

            // Update active member jika berbeda
            const borrower = allMembers.find(m => parseInt(m.id_anggota) === memberId);
            if (borrower) {
                activeMember = { id_anggota: borrower.id_anggota, nama: borrower.nama };
                localStorage.setItem('sim_perpus_active_member', JSON.stringify(activeMember));
                updateActiveMemberChip();
            }
        } else {
            showToast(json.message || 'Gagal memproses peminjaman', 'error');
        }
    } catch (err) {
        console.error('Error pinjam buku:', err);
        showToast('Terjadi kesalahan koneksi', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '✅ Konfirmasi Pinjam Buku';
    }
}

// ==========================================================================
// 8. BAGIAN USER: PENGEMBALIAN BUKU & KALKULASI DENDA REAL-TIME
// ==========================================================================
async function loadActiveLoans() {
    const container = document.getElementById('activeLoansContainer');
    if (!container) return;

    container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">Menghitung status peminjaman &amp; denda...</div>';

    const filterMember = document.getElementById('filterReturnMember')?.value || '0';
    let url = `${API_BASE}/library.php?action=pinjam_aktif`;
    if (parseInt(filterMember) > 0) {
        url += `&id_anggota=${filterMember}`;
    }

    try {
        const res = await fetch(url);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            const items = json.data.items || [];
            renderActiveLoans(items);
            updateLoansSummary(items);
        }
    } catch (err) {
        console.error('Gagal mengambil data pinjaman aktif:', err);
        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--danger); padding: 40px;">Gagal memuat pinjaman dari server.</div>';
    }
}

function updateLoansSummary(loans) {
    const totalActive = loans.length;
    const totalOverdue = loans.filter(l => l.is_overdue || l.hari_terlambat > 0).length;
    const totalFines = loans.reduce((acc, l) => acc + (parseInt(l.denda_saat_ini) || 0), 0);

    const activeEl = document.getElementById('summaryActiveLoans');
    const overdueEl = document.getElementById('summaryOverdueLoans');
    const finesEl = document.getElementById('summaryTotalFines');

    if (activeEl) animateCounter('summaryActiveLoans', totalActive);
    if (overdueEl) animateCounter('summaryOverdueLoans', totalOverdue);
    if (finesEl) finesEl.textContent = `Rp ${totalFines.toLocaleString('id-ID')}`;
}

function renderActiveLoans(loans) {
    const container = document.getElementById('activeLoansContainer');
    if (!container) return;

    if (loans.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 48px; background: var(--bg-card); border-radius: 16px; border: 1px dashed var(--border);">
                <div style="font-size: 32px; margin-bottom: 8px;">🎉</div>
                <h3 style="color: var(--text-main); font-size: 15px;">Tidak Ada Pinjaman Aktif</h3>
                <p style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">Tidak ada buku yang sedang dipinjam untuk kriteria anggota ini. Semua koleksi rapi dan bebas denda!</p>
                <button onclick="switchView('user-pinjam')" class="btn btn-primary btn-sm" style="margin-top: 16px;">
                    Buka Katalog untuk Pinjam Buku →
                </button>
            </div>
        `;
        return;
    }

    container.innerHTML = loans.map(loan => {
        const isOverdue = loan.is_overdue || (parseInt(loan.hari_terlambat) > 0);
        const hariTerlambat = parseInt(loan.hari_terlambat) || 0;
        const dendaRate = parseInt(loan.denda_per_hari) || 1000;
        const dendaTotal = parseInt(loan.denda_saat_ini) || 0;
        const durasiBuku = parseInt(loan.batas_hari_pinjam) || 7;

        return `
            <div class="loan-card ${isOverdue ? 'overdue' : 'ontime'}">
                <div>
                    <div class="loan-card-top">
                        <span class="badge ${isOverdue ? 'badge-danger' : 'badge-success'}">
                            ${isOverdue ? `🚨 Terlambat ${hariTerlambat} Hari` : `✅ Tepat Waktu (Sisa ${loan.sisa_hari} Hari)`}
                        </span>
                        <span style="font-size: 11px; color: var(--text-muted);">
                            ID Pinjam #${loan.id_peminjaman}
                        </span>
                    </div>

                    <h3 class="loan-card-title">${escapeHtml(loan.judul_buku)}</h3>
                    <div class="loan-borrower">👤 Peminjam: ${escapeHtml(loan.nama_anggota)}</div>

                    <!-- Tabel Tanggal Sirkulasi -->
                    <table class="loan-dates-table">
                        <tr>
                            <td style="color: var(--text-muted);">Batas Pinjam Buku</td>
                            <td><span class="rule-tag-limit">${durasiBuku} Hari</span></td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Tanggal Pinjam</td>
                            <td>${formatDateIndo(loan.tgl_pinjam)}</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Jatuh Tempo (Due Date)</td>
                            <td style="color: ${isOverdue ? '#f87171' : '#34d399'}; font-weight: 700;">
                                ${formatDateIndo(loan.tgl_kembali_rencana)}
                            </td>
                        </tr>
                    </table>

                    <!-- Kotak Status Denda Real-Time -->
                    <div class="fine-callout-box ${isOverdue ? 'overdue' : 'ontime'}">
                        ${isOverdue ? `
                            <div class="fine-formula-text">
                                <span>Tagihan Denda Keterlambatan:</span>
                                <span>${hariTerlambat} hari &times; Rp ${dendaRate.toLocaleString('id-ID')}</span>
                            </div>
                            <div class="fine-total-display">
                                Rp ${dendaTotal.toLocaleString('id-ID')}
                            </div>
                            <div style="font-size: 11px; color: #fca5a5; margin-top: 4px;">
                                ⚠️ Wajib dilunasi saat pengembalian buku ini
                            </div>
                        ` : `
                            <div class="fine-safe-text">
                                Bebas Denda (Tepat Waktu)
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                Kembalikan sebelum ${formatDateIndo(loan.tgl_kembali_rencana)} untuk menghindari denda Rp ${dendaRate.toLocaleString('id-ID')}/hari.
                            </div>
                        `}
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <button onclick="openReturnModal(${JSON.stringify(loan).replace(/"/g, '&quot;')})" class="btn ${isOverdue ? 'btn-danger' : 'btn-primary'}" style="width: 100%; justify-content: center;">
                        🔄 Kembalikan Buku ${isOverdue ? `(+ Denda Rp ${dendaTotal.toLocaleString('id-ID')})` : ''}
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// ==========================================================================
// 9. MODAL STRUK PENGEMBALIAN & PELUNASAN DENDA
// ==========================================================================
function openReturnModal(loan) {
    pendingReturnLoan = loan;

    const isOverdue = loan.is_overdue || (parseInt(loan.hari_terlambat) > 0);
    const hariTerlambat = parseInt(loan.hari_terlambat) || 0;
    const dendaRate = parseInt(loan.denda_per_hari) || 1000;
    const dendaTotal = parseInt(loan.denda_saat_ini) || 0;

    document.getElementById('receiptBookTitle').textContent = loan.judul_buku;
    document.getElementById('receiptBorrowerName').textContent = loan.nama_anggota;
    document.getElementById('receiptBorrowDate').textContent = formatDateIndo(loan.tgl_pinjam);
    document.getElementById('receiptDueDate').textContent = formatDateIndo(loan.tgl_kembali_rencana);

    const todayStr = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    document.getElementById('receiptReturnDate').textContent = `${todayStr} (Hari Ini)`;

    const statusBadge = document.getElementById('receiptStatusBadge');
    if (isOverdue) {
        statusBadge.innerHTML = `<span class="badge badge-danger">Terlambat ${hariTerlambat} Hari</span>`;
    } else {
        statusBadge.innerHTML = `<span class="badge badge-success">Tepat Waktu (Bebas Denda)</span>`;
    }

    document.getElementById('receiptOverdueDays').textContent = `${hariTerlambat} Hari`;
    document.getElementById('receiptFineRate').textContent = `Rp ${dendaRate.toLocaleString('id-ID')} / hari`;
    document.getElementById('receiptTotalFine').textContent = `Rp ${dendaTotal.toLocaleString('id-ID')}`;

    const btnExecute = document.getElementById('btnExecuteReturn');
    const noticeBox = document.getElementById('receiptNoticeBox');

    if (dendaTotal > 0) {
        btnExecute.textContent = `💵 Bayar Denda Rp ${dendaTotal.toLocaleString('id-ID')} & Selesaikan`;
        btnExecute.className = 'btn btn-danger';
        noticeBox.innerHTML = `⚠️ Denda keterlambatan sebesar <strong>Rp ${dendaTotal.toLocaleString('id-ID')}</strong> akan dicatat lunas.`;
    } else {
        btnExecute.textContent = `✅ Selesaikan Pengembalian (Bebas Denda)`;
        btnExecute.className = 'btn btn-primary';
        noticeBox.innerHTML = `🎉 Pengembalian tepat waktu! Buku siap kembali ke rak koleksi.`;
    }

    document.getElementById('returnModal').classList.add('active');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('active');
    pendingReturnLoan = null;
}

async function executeReturnBook() {
    if (!pendingReturnLoan) return;

    const btn = document.getElementById('btnExecuteReturn');
    btn.disabled = true;

    try {
        const res = await fetch(`${API_BASE}/library.php?action=kembalikan_buku`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_peminjaman: pendingReturnLoan.id_peminjaman
            })
        });

        const json = await res.json();

        if (json.status === 'success') {
            showToast(json.message, 'success');
            closeReturnModal();

            // Refresh sirkulasi pengembalian & katalog
            loadActiveLoans();
            loadReturnHistory();
            loadUserCatalog();

            // Jika dashboard admin aktif, update stats
            if (currentView === 'admin-dashboard') {
                loadDashboardStats();
            }
        } else {
            showToast(json.message || 'Gagal memproses pengembalian', 'error');
        }
    } catch (err) {
        console.error('Error saat pengembalian:', err);
        showToast('Terjadi kesalahan saat memproses pengembalian', 'error');
    } finally {
        btn.disabled = false;
    }
}

async function loadReturnHistory() {
    const tbody = document.getElementById('returnHistoryTableBody');
    if (!tbody) return;

    try {
        const res = await fetch(`${API_BASE}/library.php?action=riwayat_kembali`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            const items = json.data.items || [];
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada riwayat pengembalian.</td></tr>';
                return;
            }

            tbody.innerHTML = items.map(item => {
                const denda = parseInt(item.denda_total) || 0;
                return `
                    <tr>
                        <td style="text-align: center;"><div class="id-chip">#${item.id_pengembalian}</div></td>
                        <td><strong>${escapeHtml(item.nama_anggota)}</strong></td>
                        <td>${escapeHtml(item.judul_buku)}</td>
                        <td style="color: var(--text-muted);">${formatDateIndo(item.tgl_pinjam)}</td>
                        <td style="color: var(--text-secondary);">${formatDateIndo(item.tgl_kembali)}</td>
                        <td>
                            ${denda > 0 ? `
                                <span style="color: #f87171; font-weight: 700;">Rp ${denda.toLocaleString('id-ID')}</span>
                            ` : `
                                <span style="color: #34d399;">Rp 0 (Tepat Waktu)</span>
                            `}
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-success">✓ Selesai / Lunas</span>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    } catch (err) {
        console.error('Gagal mengambil riwayat pengembalian:', err);
    }
}

// ==========================================================================
// 10. BAGIAN USER: PENDAFTARAN ANGGOTA & KARTU ANGGOTA DIGITAL
// ==========================================================================
function updateLiveCard() {
    const namaInput = document.getElementById('regNama')?.value.trim();
    const nameEl = document.getElementById('cardPreviewName');

    if (nameEl) {
        nameEl.textContent = namaInput ? namaInput.toUpperCase() : 'NAMA LENGKAP ANDA';
    }

    const curDate = new Date();
    const dateStr = curDate.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }).toUpperCase();
    const dateEl = document.getElementById('cardPreviewDate');
    if (dateEl) dateEl.textContent = dateStr;
}

function resetLiveCard() {
    setTimeout(updateLiveCard, 50);
}

async function handleMemberRegistration(event) {
    event.preventDefault();

    const btn = document.getElementById('btnSubmitRegister');
    btn.disabled = true;
    btn.textContent = 'Menerbitkan Kartu Anggota...';

    const nama = document.getElementById('regNama').value.trim();
    const noHp = document.getElementById('regNoHp').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const alamat = document.getElementById('regAlamat').value.trim();

    try {
        const res = await fetch(`${API_BASE}/library.php?action=daftar_anggota`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nama, no_hp: noHp, email, alamat })
        });

        const json = await res.json();

        if (json.status === 'success' && json.data) {
            const newId = json.data.id_anggota;

            // Update nomor ID di kartu digital
            document.getElementById('cardPreviewId').textContent = `ID #${newId}`;

            showToast(json.message, 'success');

            // Tambahkan ke state lokal dan jadikan anggota aktif
            activeMember = { id_anggota: newId, nama: nama };
            localStorage.setItem('sim_perpus_active_member', JSON.stringify(activeMember));
            updateActiveMemberChip();

            // Refresh daftar anggota di sistem
            await loadMembersList();

            // Berikan opsi langsung menuju katalog buku
            setTimeout(() => {
                showToast(`Kartu diterbitkan! Anda kini aktif sebagai peminjam #${newId}. Mengalihkan ke katalog buku...`, 'info');
                setTimeout(() => {
                    switchView('user-pinjam');
                }, 1200);
            }, 500);

            // Reset form
            document.getElementById('memberRegisterForm').reset();
        } else {
            showToast(json.message || 'Gagal mendaftarkan anggota', 'error');
        }
    } catch (err) {
        console.error('Error saat pendaftaran:', err);
        showToast('Terjadi kesalahan koneksi saat pendaftaran', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '✨ Daftarkan Anggota & Terbitkan Kartu';
    }
}

// ==========================================================================
// 11. BAGIAN ADMIN: DASHBOARD & ANALITIK
// ==========================================================================
async function loadDashboardStats() {
    try {
        const res = await fetch(`${API_BASE}/dashboard_stats.php`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            const { summary, charts, recent_books } = json.data;

            // Update counter metrik
            animateCounter('statTotalBuku',    summary.total_buku || 0);
            animateCounter('statTotalAnggota', summary.total_anggota || 0);
            animateCounter('statPinjamAktif',  summary.total_pinjam_aktif || 0);
            animateCounter('statTotalKembali', summary.total_pengembalian || 0);

            const dendaEl = document.getElementById('statTotalDenda');
            if (dendaEl) {
                const totalDenda = summary.total_denda || 0;
                dendaEl.textContent = `Rp ${totalDenda.toLocaleString('id-ID')}`;
            }

            // Render grafik Chart.js
            renderTrendChart(charts.trend_peminjaman);
            renderCategoryChart(charts.distribusi_kategori);

            // Render tabel buku terkini
            renderRecentBooks(recent_books);
        }
    } catch (err) {
        console.error('Gagal memuat dashboard stats:', err);
    }
}

function renderTrendChart(trendData) {
    const canvas = document.getElementById('myChart');
    if (!canvas || !trendData) return;

    if (myTrendChart) myTrendChart.destroy();

    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = "'Inter', sans-serif";

    myTrendChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: trendData.labels,
            datasets: [{
                label: 'Jumlah Peminjaman',
                data: trendData.data,
                backgroundColor: (ctx) => {
                    const grad = ctx.chart.ctx.createLinearGradient(0, 0, 0, 260);
                    grad.addColorStop(0, 'rgba(99, 102, 241, 0.85)');
                    grad.addColorStop(1, 'rgba(99, 102, 241, 0.15)');
                    return grad;
                },
                borderColor: '#818cf8',
                borderWidth: 1,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 800, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#171f33',
                    borderColor: 'rgba(99,102,241,0.3)',
                    borderWidth: 1,
                    titleColor: '#f1f5f9',
                    bodyColor: '#94a3b8',
                    padding: 10,
                    cornerRadius: 8,
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    border: { display: false },
                    ticks: { color: '#64748b', font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, color: '#64748b', font: { size: 11 } },
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    border: { display: false }
                }
            }
        }
    });
}

function renderCategoryChart(catData) {
    const canvas = document.getElementById('categoryChart');
    if (!canvas || !catData) return;

    if (myCategoryChart) myCategoryChart.destroy();

    myCategoryChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: catData.labels,
            datasets: [{
                data: catData.data,
                backgroundColor: [
                    '#6366f1', '#06b6d4', '#10b981', '#f59e0b',
                    '#ec4899', '#8b5cf6', '#f97316'
                ],
                borderColor: '#111625',
                borderWidth: 3,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 800, easing: 'easeOutQuart' },
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#94a3b8', padding: 12, font: { size: 11 }, boxWidth: 10, boxHeight: 10 }
                }
            }
        }
    });
}

function renderRecentBooks(books) {
    const tbody = document.getElementById('recentBooksTableBody');
    if (!tbody) return;

    if (!books || books.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada data buku</td></tr>';
        return;
    }

    tbody.innerHTML = books.map(b => {
        const stars = '⭐'.repeat(b.jumlah_rating || 0);
        return `
            <tr>
                <td><div style="font-weight:600; color:var(--text-main);">${escapeHtml(b.judul)}</div></td>
                <td>${escapeHtml(b.penulis)}</td>
                <td>${escapeHtml(b.penerbit)}</td>
                <td><span class="badge badge-primary">${escapeHtml(b.nama_kategori)}</span></td>
                <td style="text-align: right;">${stars}</td>
            </tr>
        `;
    }).join('');
}

// ==========================================================================
// 12. BAGIAN ADMIN: KELOLA DATA BUKU (CRUD LENGKAP & DURASI PINJAM)
// ==========================================================================
async function loadAdminBooks() {
    const tbody = document.getElementById('adminBooksTableBody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 28px; color: var(--text-muted);">Memuat data buku...</td></tr>';

    try {
        const res = await fetch(`${API_BASE}/read.php?entity=buku`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            allAdminBooks = json.data.items || [];
            populateAdminBookDropdowns(json.data.categories, json.data.years);
            renderAdminBooksTable(allAdminBooks);
        }
    } catch (err) {
        console.error('Gagal mengambil data buku admin:', err);
    }
}

function renderAdminBooksTable(books) {
    const tbody = document.getElementById('adminBooksTableBody');
    const countEl = document.getElementById('adminBookRecordCount');
    if (!tbody) return;

    if (countEl) countEl.textContent = `Menampilkan ${books.length} data buku`;

    if (books.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 28px; color: var(--text-muted);">Tidak ada data buku yang sesuai</td></tr>';
        return;
    }

    tbody.innerHTML = books.map(b => {
        const thn = b.tahun_terbit ? new Date(b.tahun_terbit).getFullYear() : '-';
        const denda = parseInt(b.denda) || 1000;
        const durasi = parseInt(b.batas_hari_pinjam) || 7;
        const stars = '⭐'.repeat(b.jumlah_rating || 0);

        return `
            <tr>
                <td style="text-align: center;"><div class="id-chip">${b.id_buku}</div></td>
                <td>
                    <div style="font-weight:600; color:var(--text-main);">${escapeHtml(b.judul)}</div>
                    <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(b.eidisi || '-')}</div>
                </td>
                <td>${escapeHtml(b.penulis)}</td>
                <td>${escapeHtml(b.penerbit)}</td>
                <td><span class="badge badge-primary">${escapeHtml(b.nama_kategori || 'Umum')}</span></td>
                <td style="color: var(--text-muted);">${thn}</td>
                <td>
                    <span class="rule-tag-limit">${durasi} Hari</span>
                </td>
                <td>
                    <span style="font-weight: 600; color: #fbbf24;">Rp ${denda.toLocaleString('id-ID')}</span>
                </td>
                <td style="text-align: center;">${stars}</td>
                <td style="text-align: center;">
                    <button onclick="openEditModal(${b.id_buku})" class="btn-icon" title="Edit">✏️</button>
                    <button onclick="openDeleteModal(${b.id_buku}, '${escapeHtml(b.judul).replace(/'/g, "\\'")}', 'buku')" class="btn-icon btn-icon-danger" title="Hapus">🗑️</button>
                </td>
            </tr>
        `;
    }).join('');
}

function populateAdminBookDropdowns(categories, years) {
    const katSel = document.getElementById('adminBookFilterKategori');
    const thnSel = document.getElementById('adminBookFilterTahun');

    if (katSel && categories) {
        const cur = katSel.value;
        katSel.innerHTML = '<option value="">Semua Kategori</option>' +
            categories.map(c => `<option value="${escapeHtml(c)}" ${c === cur ? 'selected' : ''}>${escapeHtml(c)}</option>`).join('');
    }

    if (thnSel && years) {
        const cur = thnSel.value;
        thnSel.innerHTML = '<option value="">Semua Tahun</option>' +
            years.map(y => `<option value="${y}" ${String(y) === cur ? 'selected' : ''}>${y}</option>`).join('');
    }
}

function applyAdminBookFilters() {
    const search = document.getElementById('adminBookSearch')?.value.trim().toLowerCase() || '';
    const kat = document.getElementById('adminBookFilterKategori')?.value || '';
    const thn = document.getElementById('adminBookFilterTahun')?.value || '';

    const filtered = allAdminBooks.filter(b => {
        const matchSearch = !search ||
            (b.judul && b.judul.toLowerCase().includes(search)) ||
            (b.penulis && b.penulis.toLowerCase().includes(search)) ||
            (b.penerbit && b.penerbit.toLowerCase().includes(search));

        const matchKat = !kat || (b.nama_kategori === kat);

        const bYear = b.tahun_terbit ? String(new Date(b.tahun_terbit).getFullYear()) : '';
        const matchThn = !thn || (bYear === thn);

        return matchSearch && matchKat && matchThn;
    });

    renderAdminBooksTable(filtered);
}

// Preset Durasi Pinjam untuk Admin
function setPresetDuration(days) {
    const input = document.getElementById('formBatasHari');
    if (input) input.value = days;
}

function openCreateModal() {
    editingBookId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Buku Baru';
    document.getElementById('modalSubtitle').textContent = 'Isi data buku lengkap beserta durasi pinjam dan tarif denda';
    document.getElementById('bookForm').reset();
    document.getElementById('formIdBuku').value = '';
    document.getElementById('formBatasHari').value = 7;
    document.getElementById('formDenda').value = 1000;
    document.getElementById('submitBtnText').textContent = '💾 Simpan Buku';
    document.getElementById('bookModal').classList.add('active');
}

function openEditModal(id) {
    const book = allAdminBooks.find(b => parseInt(b.id_buku) === id);
    if (!book) return;

    editingBookId = id;
    document.getElementById('modalTitle').textContent = 'Edit Data Buku';
    document.getElementById('modalSubtitle').textContent = 'Perbarui informasi buku, durasi pinjam, dan denda';
    document.getElementById('formIdBuku').value = book.id_buku;
    document.getElementById('formJudul').value = book.judul || '';
    document.getElementById('formPenulis').value = book.penulis || '';
    document.getElementById('formPenerbit').value = book.penerbit || '';
    document.getElementById('formEidisi').value = book.eidisi || '';
    document.getElementById('formKategori').value = book.nama_kategori || '';
    document.getElementById('formBatasHari').value = book.batas_hari_pinjam || 7;
    document.getElementById('formDenda').value = book.denda || 1000;
    document.getElementById('formTahun').value = book.tahun_terbit || '';
    document.getElementById('formRating').value = book.jumlah_rating || 5;

    document.getElementById('submitBtnText').textContent = '💾 Perbarui Buku';
    document.getElementById('bookModal').classList.add('active');
}

function closeBookModal() {
    document.getElementById('bookModal').classList.remove('active');
}

async function handleBookFormSubmit(event) {
    event.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;

    const payload = {
        entity: 'buku',
        judul: document.getElementById('formJudul').value.trim(),
        penulis: document.getElementById('formPenulis').value.trim(),
        penerbit: document.getElementById('formPenerbit').value.trim(),
        eidisi: document.getElementById('formEidisi').value.trim() || 'Edisi 1',
        nama_kategori: document.getElementById('formKategori').value.trim() || 'Umum',
        batas_hari_pinjam: parseInt(document.getElementById('formBatasHari').value) || 7,
        denda: parseInt(document.getElementById('formDenda').value) || 1000,
        tahun_terbit: document.getElementById('formTahun').value,
        jumlah_rating: parseInt(document.getElementById('formRating').value) || 5,
    };

    try {
        let url = `${API_BASE}/create.php`;
        if (editingBookId) {
            url = `${API_BASE}/update.php`;
            payload.id_buku = editingBookId;
        }

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const json = await res.json();

        if (json.status === 'success') {
            showToast(json.message, 'success');
            closeBookModal();
            loadAdminBooks();
            loadUserCatalog();
        } else {
            showToast(json.message || 'Gagal menyimpan data buku', 'error');
        }
    } catch (err) {
        console.error('Error simpan buku:', err);
        showToast('Terjadi kesalahan koneksi', 'error');
    } finally {
        submitBtn.disabled = false;
    }
}

// ==========================================================================
// 13. BAGIAN ADMIN: KELOLA ANGGOTA
// ==========================================================================
async function loadAdminMembers() {
    const tbody = document.getElementById('adminMembersTableBody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 28px; color: var(--text-muted);">Memuat data anggota...</td></tr>';

    try {
        const res = await fetch(`${API_BASE}/library.php?action=daftar_anggota`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            allAdminMembers = json.data.items || [];
            renderAdminMembersTable(allAdminMembers);
        }
    } catch (err) {
        console.error('Gagal mengambil data anggota:', err);
    }
}

function renderAdminMembersTable(members) {
    const tbody = document.getElementById('adminMembersTableBody');
    const countEl = document.getElementById('adminMemberCount');
    if (!tbody) return;

    if (countEl) countEl.textContent = `Menampilkan ${members.length} anggota`;

    if (members.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 28px; color: var(--text-muted);">Belum ada data anggota</td></tr>';
        return;
    }

    tbody.innerHTML = members.map(m => {
        const activeLoans = parseInt(m.pinjam_aktif_count) || 0;
        return `
            <tr>
                <td style="text-align: center;"><div class="id-chip">#${m.id_anggota}</div></td>
                <td><strong style="color: var(--text-main);">${escapeHtml(m.nama)}</strong></td>
                <td>${escapeHtml(m.no_hp || '-')}</td>
                <td>${escapeHtml(m.email || '-')}</td>
                <td style="font-size: 12px; color: var(--text-muted);">${escapeHtml(m.alamat || '-')}</td>
                <td style="color: var(--text-secondary);">${formatDateIndo(m.tangga_daftar)}</td>
                <td style="text-align: center;">
                    ${activeLoans > 0 ? `
                        <span class="badge badge-warning">${activeLoans} Buku Dipinjam</span>
                    ` : `
                        <span class="badge badge-success">0 Aktif</span>
                    `}
                </td>
                <td style="text-align: center;">
                    <button onclick="openDeleteModal(${m.id_anggota}, '${escapeHtml(m.nama).replace(/'/g, "\\'")}', 'anggota')" class="btn-icon btn-icon-danger" title="Hapus Anggota">🗑️</button>
                </td>
            </tr>
        `;
    }).join('');
}

function filterAdminMembers() {
    const q = document.getElementById('adminMemberSearch')?.value.trim().toLowerCase() || '';
    const filtered = allAdminMembers.filter(m => {
        return !q ||
            (m.nama && m.nama.toLowerCase().includes(q)) ||
            (m.no_hp && m.no_hp.toLowerCase().includes(q)) ||
            (m.alamat && m.alamat.toLowerCase().includes(q));
    });
    renderAdminMembersTable(filtered);
}

// ==========================================================================
// 14. BAGIAN ADMIN: SIRKULASI & LOG TRANSAKSI
// ==========================================================================
async function loadAdminCirculation() {
    const tbody = document.getElementById('adminCirculationTableBody');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 28px; color: var(--text-muted);">Memuat sirkulasi perpustakaan...</td></tr>';

    try {
        const res = await fetch(`${API_BASE}/library.php?action=semua_peminjaman`);
        const json = await res.json();

        if (json.status === 'success' && json.data) {
            allCirculation = json.data.items || [];
            renderAdminCirculationTable(allCirculation);
        }
    } catch (err) {
        console.error('Gagal mengambil sirkulasi:', err);
    }
}

function renderAdminCirculationTable(items) {
    const tbody = document.getElementById('adminCirculationTableBody');
    const countEl = document.getElementById('adminCirculationCount');
    if (!tbody) return;

    if (countEl) countEl.textContent = `Menampilkan ${items.length} transaksi`;

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 28px; color: var(--text-muted);">Tidak ada transaksi sirkulasi yang sesuai</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(item => {
        const isDipinjam = item.status === 'Dipinjam';
        const isOverdue = item.is_overdue || (parseInt(item.hari_terlambat) > 0);
        const denda = parseInt(item.denda_total) || parseInt(item.denda_berjalan) || 0;

        let statusBadge = '';
        if (!isDipinjam) {
            statusBadge = `<span class="badge badge-success">✓ Dikembalikan</span>`;
        } else if (isOverdue) {
            statusBadge = `<span class="badge badge-danger">🚨 Terlambat (${item.hari_terlambat} Hari)</span>`;
        } else {
            statusBadge = `<span class="badge badge-primary">📖 Sedang Dipinjam</span>`;
        }

        return `
            <tr>
                <td style="text-align: center;"><div class="id-chip">#${item.id_peminjaman}</div></td>
                <td>
                    <strong>${escapeHtml(item.nama_anggota)}</strong>
                    <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(item.no_hp || '-')}</div>
                </td>
                <td>
                    ${escapeHtml(item.judul_buku)}
                    <div style="font-size: 11px; color: var(--text-muted);">${escapeHtml(item.nama_kategori || 'Umum')} • Batas: ${item.batas_hari_pinjam || 7} Hari</div>
                </td>
                <td style="color: var(--text-muted);">${formatDateIndo(item.tgl_pinjam)}</td>
                <td style="color: ${isOverdue ? '#f87171' : 'var(--text-main)'}; font-weight: 600;">
                    ${formatDateIndo(item.tgl_kembali_rencana)}
                </td>
                <td style="text-align: center;">${statusBadge}</td>
                <td>
                    ${denda > 0 ? `
                        <span style="color: #f87171; font-weight: 700;">Rp ${denda.toLocaleString('id-ID')}</span>
                        <div style="font-size: 10px; color: var(--text-muted);">${isDipinjam ? '(Berjalan)' : '(Lunas)'}</div>
                    ` : `
                        <span style="color: #34d399;">Rp 0</span>
                    `}
                </td>
                <td style="font-size: 12px; color: var(--text-muted);">${escapeHtml(item.nama_petugas || 'Petugas')}</td>
            </tr>
        `;
    }).join('');
}

function filterAdminCirculation() {
    const q = document.getElementById('adminCirculationSearch')?.value.trim().toLowerCase() || '';
    const st = document.getElementById('adminCirculationFilterStatus')?.value || '';

    const filtered = allCirculation.filter(item => {
        const matchQ = !q ||
            (item.nama_anggota && item.nama_anggota.toLowerCase().includes(q)) ||
            (item.judul_buku && item.judul_buku.toLowerCase().includes(q));

        let matchSt = true;
        if (st === 'Dipinjam') matchSt = (item.status === 'Dipinjam' && !item.is_overdue);
        if (st === 'Terlambat') matchSt = (item.status === 'Dipinjam' && item.is_overdue);
        if (st === 'Dikembalikan') matchSt = (item.status === 'Dikembalikan');

        return matchQ && matchSt;
    });

    renderAdminCirculationTable(filtered);
}

// ==========================================================================
// 15. MODAL HAPUS DATA (BUKU & ANGGOTA)
// ==========================================================================
function openDeleteModal(id, title, entity = 'buku') {
    deleteTarget = { id, title, entity };

    const header = document.getElementById('deleteModalHeader');
    const question = document.getElementById('deleteModalQuestion');
    const titleEl = document.getElementById('deleteItemTitle');

    if (entity === 'buku') {
        header.textContent = 'Konfirmasi Hapus Buku';
        question.textContent = 'Apakah Anda yakin ingin menghapus koleksi buku ini dari perpustakaan?';
    } else {
        header.textContent = 'Konfirmasi Hapus Anggota';
        question.textContent = 'Apakah Anda yakin ingin menghapus data anggota peminjam ini?';
    }

    titleEl.textContent = `"${title}"`;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteTarget = null;
}

async function executeDelete() {
    if (!deleteTarget) return;

    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;

    try {
        let res, json;

        if (deleteTarget.entity === 'buku') {
            res = await fetch(`${API_BASE}/delete.php?id=${deleteTarget.id}&entity=buku`, {
                method: 'DELETE'
            });
            json = await res.json();
            if (json.status === 'success') {
                showToast(json.message, 'success');
                closeDeleteModal();
                loadAdminBooks();
                loadUserCatalog();
            } else {
                showToast(json.message || 'Gagal menghapus buku', 'error');
            }
        } else {
            res = await fetch(`${API_BASE}/library.php?action=hapus_anggota`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_anggota: deleteTarget.id })
            });
            json = await res.json();
            if (json.status === 'success') {
                showToast(json.message, 'success');
                closeDeleteModal();
                loadAdminMembers();
                loadMembersList();
            } else {
                showToast(json.message || 'Gagal menghapus anggota', 'error');
            }
        }
    } catch (err) {
        console.error('Error hapus:', err);
        showToast('Terjadi kesalahan saat menghapus data', 'error');
    } finally {
        btn.disabled = false;
    }
}

// ==========================================================================
// 16. DATA SAMPEL HELPER (SEED)
// ==========================================================================
async function seedSampleData() {
    try {
        showToast('Mengisi data sampel dan mengatur variasi batas pinjam...', 'info');
        const res = await fetch(`${API_BASE}/seed.php`);
        const json = await res.json();

        if (json.status === 'success') {
            showToast('Data sampel berhasil diperbarui!', 'success');
            await loadMembersList();
            restoreActiveMember();
            handleViewDataLoad(currentView);
        } else {
            showToast(json.message || 'Gagal mengisi data sampel', 'error');
        }
    } catch (err) {
        console.error('Error seed:', err);
        showToast('Terjadi kesalahan saat mengisi sampel', 'error');
    }
}

// ==========================================================================
// 17. TOAST NOTIFIKASI FEEDBACK
// ==========================================================================
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    const dot = document.createElement('span');
    dot.className = 'toast-dot';

    const text = document.createElement('span');
    text.textContent = message;

    toast.appendChild(dot);
    toast.appendChild(text);
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'toastIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 280);
    }, 3200);
}

// ==========================================================================
// 18. UTILITAS FORMATTER & HELPER
// ==========================================================================
function animateCounter(elementId, targetValue) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const start = parseInt(el.textContent) || 0;
    const duration = 600;
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(start + (targetValue - start) * eased);
        if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
}

function formatDateIndo(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
