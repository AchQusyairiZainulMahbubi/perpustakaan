<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Perpustakaan - UTS SI</title>
    <meta name="description" content="Sistem Informasi Perpustakaan modern dengan portal User (Peminjaman, Pengembalian dengan Denda Otomatis, Pendaftaran) dan Panel Admin (Dashboard, Kelola Buku, Kelola Anggota, Sirkulasi).">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Premium CSS with Cache Buster -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>

    <!-- ============================================================== -->
    <!-- 1. LAYOUT UTAMA APLIKASI                                       -->
    <!-- ============================================================== -->
    <div class="app-layout">

        <!-- ============================================================== -->
        <!-- 2. SIDEBAR NAVIGASI                                            -->
        <!-- ============================================================== -->
        <aside id="sidebar" class="sidebar">
            <div>
                <!-- Brand / Logo Aplikasi -->
                <div class="sidebar-brand">
                    <div class="brand-icon">📚</div>
                    <div class="brand-text">
                        <h2>SIM Perpustakaan</h2>
                        <p>Project UTS Sistem Informasi</p>
                    </div>
                </div>

                <!-- Menu Navigasi -->
                <nav class="sidebar-menu">

                    <!-- SEKSI MENU USER -->
                    <div id="sidebarUserSection">
                        <span class="menu-title">Portal User / Anggota</span>

                        <!-- Menu User 1: Peminjaman (Katalog) -->
                        <button id="navUserPinjam" onclick="switchView('user-pinjam')" class="nav-btn active">
                            <div class="nav-icon">📖</div>
                            <span>Peminjaman Buku</span>
                        </button>

                        <!-- Menu User 2: Pengembalian & Denda -->
                        <button id="navUserKembali" onclick="switchView('user-kembali')" class="nav-btn">
                            <div class="nav-icon">🔄</div>
                            <span>Pengembalian &amp; Denda</span>
                        </button>

                        <!-- Menu User 3: Pendaftaran Anggota -->
                        <button id="navUserDaftar" onclick="switchView('user-daftar')" class="nav-btn">
                            <div class="nav-icon">📝</div>
                            <span>Pendaftaran Anggota</span>
                        </button>
                    </div>

                    <!-- SEKSI MENU ADMIN -->
                    <div id="sidebarAdminSection" style="margin-top: 10px;">
                        <span class="menu-title">Panel Admin / Petugas</span>

                        <!-- Menu Admin 1: Dashboard -->
                        <button id="navAdminDashboard" onclick="switchView('admin-dashboard')" class="nav-btn">
                            <div class="nav-icon">📊</div>
                            <span>Dashboard &amp; Analitik</span>
                        </button>

                        <!-- Menu Admin 2: Kelola Data Buku -->
                        <button id="navAdminBuku" onclick="switchView('admin-buku')" class="nav-btn">
                            <div class="nav-icon">📚</div>
                            <span>Kelola Data Buku</span>
                        </button>

                        <!-- Menu Admin 3: Kelola Anggota -->
                        <button id="navAdminAnggota" onclick="switchView('admin-anggota')" class="nav-btn">
                            <div class="nav-icon">👥</div>
                            <span>Kelola Anggota</span>
                        </button>

                        <!-- Menu Admin 4: Sirkulasi Global -->
                        <button id="navAdminSirkulasi" onclick="switchView('admin-sirkulasi')" class="nav-btn">
                            <div class="nav-icon">📋</div>
                            <span>Sirkulasi Transaksi</span>
                        </button>
                    </div>

                </nav>
            </div>

            <!-- Status Database di Bagian Bawah Sidebar -->
            <div class="sidebar-footer">
                <div class="db-status-box">
                    <p><span class="status-dot"></span><strong>Database MySQL Aktif</strong></p>
                    <p class="db-status-info">perpustakaan_sistem_informasi</p>
                    <button onclick="seedSampleData()" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                        🔄 Isi Data Sampel
                    </button>
                </div>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- 3. KONTEN UTAMA                                                -->
        <!-- ============================================================== -->
        <div class="main-wrapper">

            <!-- NAVBAR HEADER ATAS -->
            <header class="header-bar">
                <div class="header-left">
                    <button onclick="toggleSidebar()" class="btn-mobile-toggle" aria-label="Toggle Sidebar">☰</button>
                    <div class="header-title">
                        <h1 id="pageTitle">Peminjaman Buku</h1>
                        <p id="pageSubtitle">Jelajahi koleksi buku dan pinjam sesuai batas waktu masing-masing buku</p>
                    </div>
                </div>

                <div class="header-right" style="display: flex; align-items: center; gap: 14px;">
                    
                    <!-- ROLE SWITCHER (USER <-> ADMIN) -->
                    <div class="role-switcher" id="roleSwitcher">
                        <button type="button" class="role-btn active" id="btnRoleUser" onclick="switchRole('user')">
                            <span>👤</span> Portal User
                        </button>
                        <button type="button" class="role-btn" id="btnRoleAdmin" onclick="switchRole('admin')">
                            <span>🛡️</span> Panel Admin
                        </button>
                    </div>

                    <!-- ACTIVE MEMBER CHIP (MODE USER) -->
                    <div class="active-member-chip" id="activeMemberChip" onclick="openMemberPickerModal()" title="Klik untuk mengganti anggota peminjam aktif">
                        <div class="chip-avatar" id="chipAvatar">AF</div>
                        <div class="chip-info">
                            <span class="chip-label">Peminjam Aktif</span>
                            <strong class="chip-name" id="chipMemberName">Ahmad Fauzi (#1)</strong>
                        </div>
                        <span class="chip-arrow">▾</span>
                    </div>

                    <!-- USER AVATAR / ROLE BADGE -->
                    <div class="user-badge" id="roleUserBadge" style="display: none;">
                        <div class="user-avatar" style="background: linear-gradient(135deg, #0ea5e9, #0284c7);">AD</div>
                        <div>
                            <strong>Administrator</strong>
                            <p>Petugas Perpustakaan</p>
                        </div>
                    </div>

                </div>
            </header>

            <!-- ISI HALAMAN -->
            <main class="content-body">

                <!-- ============================================================== -->
                <!-- BAGIAN USER 1: PEMINJAMAN BUKU (KATALOG & BATAS WAKTU BUKU)     -->
                <!-- ============================================================== -->
                <section id="viewUserPinjam" class="view-section">
                    
                    <!-- BANNER PANDUAN PEMINJAMAN & BATAS WAKTU -->
                    <div class="portal-banner">
                        <div class="portal-banner-text">
                            <h2><span>📚</span> Katalog Peminjaman Buku</h2>
                            <p>
                                Setiap koleksi buku memiliki <strong>batas waktu pengembalian yang berbeda</strong> (mulai dari 3, 5, 7, 10, 14, hingga 21 hari) serta tarif denda keterlambatan per hari. Pilih buku dan klik <strong>Pinjam Buku</strong> untuk mengajukan peminjaman.
                            </p>
                        </div>
                        <button onclick="switchView('user-kembali')" class="btn btn-secondary btn-sm" style="white-space: nowrap;">
                            Lihat Pinjaman Saya →
                        </button>
                    </div>

                    <!-- TOOLBAR FILTER KATALOG USER -->
                    <div class="toolbar-card">
                        <div class="filter-group">
                            <div class="search-wrapper">
                                <span class="search-icon">🔍</span>
                                <input type="text" id="userCatalogSearch" placeholder="Cari judul buku, penulis, penerbit..." class="input-search">
                            </div>

                            <select id="userCatalogFilterKategori" onchange="filterUserCatalog()" class="select-filter">
                                <option value="">Semua Kategori</option>
                            </select>

                            <select id="userCatalogFilterDurasi" onchange="filterUserCatalog()" class="select-filter">
                                <option value="">Semua Batas Waktu</option>
                                <option value="short">Batas Singkat (&le; 7 Hari)</option>
                                <option value="long">Batas Panjang (&gt; 7 Hari)</option>
                            </select>
                        </div>

                        <div style="font-size: 13px; color: var(--text-muted);" id="userCatalogCount">
                            Menampilkan 0 buku
                        </div>
                    </div>

                    <!-- GRID KATALOG BUKU -->
                    <div id="userBooksGrid" class="book-catalog-grid">
                        <!-- Kartu buku akan di-render secara dinamis oleh JavaScript -->
                    </div>

                </section>

                <!-- ============================================================== -->
                <!-- BAGIAN USER 2: PENGEMBALIAN & HITUNG DENDA KETERLAMBATAN        -->
                <!-- ============================================================== -->
                <section id="viewUserKembali" class="view-section hidden">
                    
                    <!-- BANNER INFO PENGEMBALIAN & DENDA -->
                    <div class="portal-banner" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(245, 158, 11, 0.08) 100%); border-color: rgba(239, 68, 68, 0.25);">
                        <div class="portal-banner-text">
                            <h2><span>🔄</span> Pengembalian Buku &amp; Kalkulasi Denda</h2>
                            <p>
                                Sistem secara otomatis menghitung status pengembalian secara <em>real-time</em>. Jika pengembalian melewati batas tanggal jatuh tempo buku, denda akan dikalkulasikan berdasarkan rumus: <strong>Hari Terlambat &times; Tarif Denda Buku</strong>.
                            </p>
                        </div>
                        <button onclick="openMemberPickerModal()" class="btn btn-secondary btn-sm" style="white-space: nowrap;">
                            Ganti Anggota Peminjam 👤
                        </button>
                    </div>

                    <!-- RINGKASAN STATUS PINJAMAN & TAGIHAN DENDA -->
                    <div class="loans-summary-bar">
                        <div class="loan-summary-pill">
                            <div>
                                <div class="label">Buku Sedang Dipinjam</div>
                                <div class="val" id="summaryActiveLoans">0</div>
                            </div>
                            <span style="font-size: 24px;">📖</span>
                        </div>

                        <div class="loan-summary-pill danger">
                            <div>
                                <div class="label">Peminjaman Terlambat</div>
                                <div class="val" id="summaryOverdueLoans">0</div>
                            </div>
                            <span style="font-size: 24px;">🚨</span>
                        </div>

                        <div class="loan-summary-pill danger">
                            <div>
                                <div class="label">Total Tagihan Denda Berjalan</div>
                                <div class="val" id="summaryTotalFines">Rp 0</div>
                            </div>
                            <span style="font-size: 24px;">💸</span>
                        </div>
                    </div>

                    <!-- TOOLBAR FILTER ANGGOTA PENGEMBALIAN -->
                    <div class="toolbar-card">
                        <div class="filter-group">
                            <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">Filter Anggota:</span>
                            <select id="filterReturnMember" onchange="loadActiveLoans()" class="select-filter" style="min-width: 260px;">
                                <option value="0">Semua Anggota (Seluruh Pinjaman Aktif)</option>
                            </select>
                        </div>

                        <button onclick="loadActiveLoans()" class="btn btn-secondary btn-sm">
                            🔄 Perbarui Status Real-Time
                        </button>
                    </div>

                    <!-- DAFTAR KARTU BUKU YANG SEDANG DIPINJAM -->
                    <div id="activeLoansContainer" class="loans-grid">
                        <!-- Kartu pinjaman aktif dengan denda real-time -->
                    </div>

                    <!-- RIWAYAT PENGEMBALIAN BUKU -->
                    <div class="card" style="margin-top: 32px;">
                        <div class="card-header">
                            <div>
                                <div class="card-header-label">📜 Riwayat</div>
                                <h2>Histori Pengembalian Buku &amp; Denda</h2>
                                <p>Catatan buku yang telah sukses dikembalikan beserta status pelunasan denda</p>
                            </div>
                            <button onclick="loadReturnHistory()" class="btn btn-secondary btn-sm">
                                🔄 Refresh Riwayat
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Kembali</th>
                                        <th>Peminjam</th>
                                        <th>Judul Buku</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Tgl Kembali</th>
                                        <th>Denda Dibayar</th>
                                        <th style="text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="returnHistoryTableBody">
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                            Memuat riwayat pengembalian...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

                <!-- ============================================================== -->
                <!-- BAGIAN USER 3: PENDAFTARAN ANGGOTA & KARTU DIGITAL             -->
                <!-- ============================================================== -->
                <section id="viewUserDaftar" class="view-section hidden">
                    
                    <div class="portal-banner">
                        <div class="portal-banner-text">
                            <h2><span>📝</span> Pendaftaran Anggota Perpustakaan Baru</h2>
                            <p>
                                Daftarkan diri Anda sebagai anggota perpustakaan resmi untuk mendapatkan <strong>Kartu Anggota Digital</strong> dan hak peminjaman koleksi buku fisik maupun referensi.
                            </p>
                        </div>
                    </div>

                    <div class="registration-grid">
                        
                        <!-- FORM REGISTRASI ANGGOTA -->
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <div class="card-header-label">Formulir Pendaftaran</div>
                                    <h2>Data Diri Calon Anggota</h2>
                                    <p>Pastikan data yang dimasukkan benar dan nomor WhatsApp aktif</p>
                                </div>
                            </div>

                            <form id="memberRegisterForm" onsubmit="handleMemberRegistration(event)" style="padding: 24px;">
                                <div class="form-group">
                                    <label for="regNama" class="form-label">Nama Lengkap *</label>
                                    <input type="text" id="regNama" name="nama" required placeholder="Contoh: Muhammad Rizky Pratama" class="form-input" oninput="updateLiveCard()">
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="regNoHp" class="form-label">Nomor WhatsApp / HP *</label>
                                        <input type="text" id="regNoHp" name="no_hp" required placeholder="Contoh: 081234567890" class="form-input" oninput="updateLiveCard()">
                                    </div>
                                    <div class="form-group">
                                        <label for="regEmail" class="form-label">Alamat Email</label>
                                        <input type="email" id="regEmail" name="email" placeholder="Contoh: rizky@gmail.com" class="form-input">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="regAlamat" class="form-label">Alamat Lengkap *</label>
                                    <textarea id="regAlamat" name="alamat" rows="3" required placeholder="Contoh: Jl. Kaliurang KM 9, Sleman, D.I. Yogyakarta" class="form-input" style="resize: vertical;"></textarea>
                                </div>

                                <div style="display: flex; gap: 12px; margin-top: 10px;">
                                    <button type="submit" id="btnSubmitRegister" class="btn btn-primary" style="flex: 1; justify-content: center;">
                                        ✨ Daftarkan Anggota &amp; Terbitkan Kartu
                                    </button>
                                    <button type="reset" class="btn btn-secondary" onclick="resetLiveCard()">
                                        Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- LIVE PREVIEW KARTU ANGGOTA DIGITAL -->
                        <div class="digital-card-container">
                            <div style="text-align: center;">
                                <div class="card-header-label" style="display: inline-block;">Hologram Member Card Preview</div>
                                <h3 style="font-size: 16px; color: var(--text-main); margin-top: 4px;">Kartu Anggota Digital</h3>
                                <p style="font-size: 12px; color: var(--text-muted);">Pratinjau otomatis saat Anda mengisi formulir</p>
                            </div>

                            <!-- KARTU DIGITAL INTERAKTIF -->
                            <div class="digital-card" id="digitalMemberCard">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div class="digital-card-chip"></div>
                                    <div style="text-align: right;">
                                        <div class="digital-card-brand">SIM PERPUSTAKAAN</div>
                                        <div style="font-size: 9px; color: #38bdf8; letter-spacing: 0.1em;">UTS SISTEM INFORMASI</div>
                                    </div>
                                </div>

                                <div>
                                    <div class="digital-card-sub" style="margin-bottom: 2px;">NOMOR ID ANGGOTA</div>
                                    <div class="digital-card-id" id="cardPreviewId">ID #NEW-MEMBER</div>
                                </div>

                                <div class="digital-card-footer">
                                    <div>
                                        <div class="digital-card-sub">NAMA PEMEGANG KARTU</div>
                                        <div class="digital-card-holder-name" id="cardPreviewName">NAMA LENGKAP ANDA</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="digital-card-sub">TERDAFTAR SEJAK</div>
                                        <div style="font-size: 12px; font-weight: 700; color: #f8fafc;" id="cardPreviewDate">OKT 2026</div>
                                    </div>
                                </div>
                            </div>

                            <!-- PANDUAN PENGGUNAAN KARTU -->
                            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; padding: 14px 18px; max-width: 400px; font-size: 12px; color: var(--text-secondary); line-height: 1.5;">
                                💡 <strong>Info Anggota:</strong> Setelah mendaftar, ID Anggota Anda akan langsung aktif dan otomatis dipilih sebagai peminjam aktif. Anda dapat langsung meminjam buku di katalog.
                            </div>
                        </div>

                    </div>

                </section>

                <!-- ============================================================== -->
                <!-- BAGIAN ADMIN 1: DASHBOARD & ANALITIK                           -->
                <!-- ============================================================== -->
                <section id="viewAdminDashboard" class="view-section hidden">
                    
                    <!-- KARTU STATISTIK ADMIN (5 METRIK UTAMA) -->
                    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
                        
                        <div class="card-stat">
                            <div class="stat-info">
                                <p>Total Buku</p>
                                <h3 id="statTotalBuku">0</h3>
                                <div class="stat-trend">↑ Koleksi aktif</div>
                            </div>
                            <div class="stat-icon icon-blue">📚</div>
                        </div>

                        <div class="card-stat">
                            <div class="stat-info">
                                <p>Total Anggota</p>
                                <h3 id="statTotalAnggota">0</h3>
                                <div class="stat-trend">↑ Anggota terdaftar</div>
                            </div>
                            <div class="stat-icon icon-green">👥</div>
                        </div>

                        <div class="card-stat">
                            <div class="stat-info">
                                <p>Peminjaman Aktif</p>
                                <h3 id="statPinjamAktif">0</h3>
                                <div class="stat-trend">Sedang dipinjam</div>
                            </div>
                            <div class="stat-icon icon-purple">📑</div>
                        </div>

                        <div class="card-stat">
                            <div class="stat-info">
                                <p>Buku Dikembalikan</p>
                                <h3 id="statTotalKembali">0</h3>
                                <div class="stat-trend">Selesai dikembalikan</div>
                            </div>
                            <div class="stat-icon icon-amber">✅</div>
                        </div>

                        <div class="card-stat">
                            <div class="stat-info">
                                <p>Total Denda Masuk</p>
                                <h3 id="statTotalDenda" style="color: #f43f5e;">Rp 0</h3>
                                <div class="stat-trend" style="color: #f43f5e;">Kas denda terkumpul</div>
                            </div>
                            <div class="stat-icon" style="background: rgba(244, 63, 94, 0.15); color: #f43f5e;">💰</div>
                        </div>

                    </div>

                    <!-- GRAFIK STATISTIK (CHART.JS) -->
                    <div class="charts-grid">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <div class="card-header-label">📈 Analitik Sirkulasi</div>
                                    <h2>Grafik Tren Peminjaman Buku</h2>
                                    <p>Jumlah aktivitas peminjaman buku per bulan</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="myChart"></canvas>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <div class="card-header-label">🍩 Distribusi Koleksi</div>
                                    <h2>Distribusi Kategori</h2>
                                    <p>Persentase jenis buku di perpustakaan</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- DAFTAR BUKU TERBARU -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-header-label">🆕 Koleksi Baru</div>
                                <h2>Buku Terkini Ditambahkan</h2>
                                <p>Daftar buku terbaru dalam database perpustakaan</p>
                            </div>
                            <button onclick="switchView('admin-buku')" class="btn btn-secondary btn-sm">
                                Kelola Semua Buku →
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Judul Buku</th>
                                        <th>Penulis</th>
                                        <th>Penerbit</th>
                                        <th>Kategori</th>
                                        <th style="text-align: right;">Rating</th>
                                    </tr>
                                </thead>
                                <tbody id="recentBooksTableBody">
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 28px;">
                                            Memuat data buku...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

                <!-- ============================================================== -->
                <!-- BAGIAN ADMIN 2: KELOLA DATA BUKU (CRUD LENGKAP & BATAS PINJAM)  -->
                <!-- ============================================================== -->
                <section id="viewAdminBuku" class="view-section hidden">
                    
                    <div class="toolbar-card">
                        <div class="filter-group">
                            <div class="search-wrapper">
                                <span class="search-icon">🔍</span>
                                <input type="text" id="adminBookSearch" placeholder="Cari judul, penulis, penerbit..." class="input-search">
                            </div>

                            <select id="adminBookFilterKategori" onchange="applyAdminBookFilters()" class="select-filter">
                                <option value="">Semua Kategori</option>
                            </select>

                            <select id="adminBookFilterTahun" onchange="applyAdminBookFilters()" class="select-filter">
                                <option value="">Semua Tahun</option>
                            </select>
                        </div>

                        <button onclick="openCreateModal()" class="btn btn-primary">
                            <span>➕</span> Tambah Buku Baru
                        </button>
                    </div>

                    <div class="table-container">
                        <div class="table-header-info">
                            <span id="adminBookRecordCount">Menampilkan 0 data buku</span>
                            <span>Setting batas pinjam fleksibel tiap buku</span>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">ID</th>
                                        <th>Judul &amp; Edisi</th>
                                        <th>Penulis</th>
                                        <th>Penerbit</th>
                                        <th>Kategori</th>
                                        <th>Tahun</th>
                                        <th style="color: #22d3ee;">Batas Pinjam</th>
                                        <th style="color: #fbbf24;">Denda / Hari</th>
                                        <th style="text-align: center;">Rating</th>
                                        <th style="width: 90px; text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="adminBooksTableBody">
                                    <tr>
                                        <td colspan="10" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            Memuat data buku dari database...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

                <!-- ============================================================== -->
                <!-- BAGIAN ADMIN 3: KELOLA ANGGOTA                                  -->
                <!-- ============================================================== -->
                <section id="viewAdminAnggota" class="view-section hidden">
                    
                    <div class="toolbar-card">
                        <div class="filter-group">
                            <div class="search-wrapper">
                                <span class="search-icon">🔍</span>
                                <input type="text" id="adminMemberSearch" placeholder="Cari nama anggota, no hp, alamat..." class="input-search" oninput="filterAdminMembers()">
                            </div>
                        </div>

                        <button onclick="switchView('user-daftar')" class="btn btn-secondary">
                            <span>➕</span> Buka Form Pendaftaran Anggota
                        </button>
                    </div>

                    <div class="table-container">
                        <div class="table-header-info">
                            <span id="adminMemberCount">Menampilkan 0 anggota</span>
                            <span>Manajemen data seluruh peminjam terdaftar</span>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">ID</th>
                                        <th>Nama Lengkap</th>
                                        <th>No. WhatsApp / HP</th>
                                        <th>Email</th>
                                        <th>Alamat</th>
                                        <th>Tgl Daftar</th>
                                        <th style="text-align: center;">Pinjaman Aktif</th>
                                        <th style="width: 80px; text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="adminMembersTableBody">
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            Memuat data anggota...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

                <!-- ============================================================== -->
                <!-- BAGIAN ADMIN 4: SIRKULASI & LOG TRANSAKSI PERPUSTAKAAN         -->
                <!-- ============================================================== -->
                <section id="viewAdminSirkulasi" class="view-section hidden">
                    
                    <div class="toolbar-card">
                        <div class="filter-group">
                            <div class="search-wrapper">
                                <span class="search-icon">🔍</span>
                                <input type="text" id="adminCirculationSearch" placeholder="Cari nama peminjam, judul buku..." class="input-search" oninput="filterAdminCirculation()">
                            </div>

                            <select id="adminCirculationFilterStatus" onchange="filterAdminCirculation()" class="select-filter">
                                <option value="">Semua Status Transaksi</option>
                                <option value="Dipinjam">Sedang Dipinjam</option>
                                <option value="Terlambat">Terlambat (Overdue)</option>
                                <option value="Dikembalikan">Sudah Dikembalikan</option>
                            </select>
                        </div>

                        <button onclick="loadAdminCirculation()" class="btn btn-secondary btn-sm">
                            🔄 Refresh Data Sirkulasi
                        </button>
                    </div>

                    <div class="table-container">
                        <div class="table-header-info">
                            <span id="adminCirculationCount">Menampilkan 0 transaksi</span>
                            <span>Audit sirkulasi lengkap seluruh peminjaman dan denda</span>
                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">ID</th>
                                        <th>Peminjam</th>
                                        <th>Judul Buku</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Batas Kembali</th>
                                        <th style="text-align: center;">Status</th>
                                        <th>Denda Keterlambatan</th>
                                        <th>Petugas</th>
                                    </tr>
                                </thead>
                                <tbody id="adminCirculationTableBody">
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            Memuat sirkulasi perpustakaan...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </section>

            </main>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 4. MODAL PINJAM BUKU (USER)                                    -->
    <!-- ============================================================== -->
    <div id="borrowModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-header-icon" style="background: linear-gradient(135deg, var(--primary), var(--accent));">📖</div>
                    <div class="modal-header-text">
                        <h3>Pinjam Buku</h3>
                        <p>Konfirmasi peminjaman koleksi perpustakaan</p>
                    </div>
                </div>
                <button type="button" onclick="closeBorrowModal()" class="btn-close">&times;</button>
            </div>

            <form id="borrowForm" onsubmit="submitBorrowBook(event)">
                <input type="hidden" id="borrowBookId" value="">

                <div class="modal-body">
                    <!-- Kartu Info Buku yang Dipilih -->
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; margin-bottom: 18px;">
                        <span class="badge badge-primary" id="borrowModalCategory" style="margin-bottom: 6px;">Kategori</span>
                        <h4 id="borrowModalTitle" style="font-size: 15px; font-weight: 700; color: var(--text-main); line-height: 1.35; margin-top: 4px;">-</h4>
                        <p id="borrowModalAuthor" style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">-</p>
                    </div>

                    <!-- Pilih Anggota Peminjam -->
                    <div class="form-group">
                        <label for="borrowMemberSelect" class="form-label">Nama Anggota Peminjam *</label>
                        <select id="borrowMemberSelect" class="form-input" required>
                            <!-- Diisi via JavaScript -->
                        </select>
                    </div>

                    <!-- Aturan Batas Waktu & Denda Buku Ini -->
                    <div class="catalog-rules-box" style="margin-top: 14px;">
                        <div class="rule-item">
                            <span class="rule-item-label">⏱️ Batas Pengembalian:</span>
                            <span class="rule-tag-limit" id="borrowModalLimitText">7 Hari</span>
                        </div>
                        <div class="rule-item">
                            <span class="rule-item-label">📅 Tanggal Pinjam:</span>
                            <strong style="color: var(--text-main);" id="borrowModalBorrowDate">-</strong>
                        </div>
                        <div class="rule-item">
                            <span class="rule-item-label">🚨 Batas Waktu Kembali (Due Date):</span>
                            <strong style="color: #22d3ee;" id="borrowModalDueDate">-</strong>
                        </div>
                        <div class="rule-item">
                            <span class="rule-item-label">⚠️ Denda Jika Terlambat:</span>
                            <span class="rule-tag-fine" id="borrowModalFineRate">Rp 1.000 / hari</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeBorrowModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="btnConfirmBorrow" class="btn btn-primary">
                        ✅ Konfirmasi Pinjam Buku
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 5. MODAL STRUK PENGEMBALIAN & DENDA (USER)                     -->
    <!-- ============================================================== -->
    <div id="returnModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 480px;">
            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-header-icon" style="background: linear-gradient(135deg, #10b981, #059669);">🧾</div>
                    <div class="modal-header-text">
                        <h3>Slip Pengembalian Buku</h3>
                        <p>Rincian pengembalian dan kalkulasi denda</p>
                    </div>
                </div>
                <button type="button" onclick="closeReturnModal()" class="btn-close">&times;</button>
            </div>

            <div class="modal-body">
                <div class="return-receipt-paper">
                    <div class="receipt-header">
                        <h4>SISTEM INFORMASI PERPUSTAKAAN</h4>
                        <p>Tanda Terima &amp; Pelunasan Pengembalian</p>
                    </div>

                    <table class="receipt-table">
                        <tr>
                            <td style="color: var(--text-muted);">Judul Buku</td>
                            <td id="receiptBookTitle" style="max-width: 200px;">-</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Peminjam</td>
                            <td id="receiptBorrowerName">-</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Tanggal Pinjam</td>
                            <td id="receiptBorrowDate">-</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Batas Kembali</td>
                            <td id="receiptDueDate">-</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Tanggal Kembali</td>
                            <td id="receiptReturnDate">-</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Status Waktu</td>
                            <td id="receiptStatusBadge">-</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Hari Keterlambatan</td>
                            <td id="receiptOverdueDays">0 Hari</td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-muted);">Tarif Denda / Hari</td>
                            <td id="receiptFineRate">Rp 0</td>
                        </tr>
                        <tr class="receipt-total-row">
                            <td>TOTAL DENDA WAJIB</td>
                            <td class="receipt-total-val" id="receiptTotalFine">Rp 0</td>
                        </tr>
                    </table>
                </div>

                <div id="receiptNoticeBox" style="font-size: 12px; color: var(--text-secondary); text-align: center;">
                    Buku akan otomatis ditandai <strong>Dikembalikan</strong> dan denda dinyatakan lunas.
                </div>
            </div>

            <div class="modal-footer" style="justify-content: center;">
                <button type="button" onclick="closeReturnModal()" class="btn btn-secondary">Batal</button>
                <button type="button" id="btnExecuteReturn" onclick="executeReturnBook()" class="btn btn-primary" style="min-width: 180px;">
                    💵 Bayar &amp; Selesaikan Pengembalian
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 6. MODAL PILIH ANGGOTA AKTIF                                    -->
    <!-- ============================================================== -->
    <div id="memberPickerModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 420px;">
            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-header-icon">👥</div>
                    <div class="modal-header-text">
                        <h3>Pilih Anggota Peminjam</h3>
                        <p>Tentukan profil anggota untuk bertransaksi</p>
                    </div>
                </div>
                <button type="button" onclick="closeMemberPickerModal()" class="btn-close">&times;</button>
            </div>

            <div class="modal-body">
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                    Pilih nama anggota di bawah untuk melihat pinjaman, menghitung denda, atau meminjam buku atas nama anggota tersebut:
                </p>
                <div class="form-group">
                    <select id="memberPickerSelect" class="form-input" size="6" style="height: auto; padding: 6px;">
                        <!-- Diisi via JS -->
                    </select>
                </div>
                <div style="text-align: center; margin-top: 10px;">
                    <a href="javascript:void(0)" onclick="closeMemberPickerModal(); switchView('user-daftar');" style="color: var(--primary-light); font-size: 12px; font-weight: 600;">
                        + Belum terdaftar? Klik di sini untuk Daftar Baru
                    </a>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeMemberPickerModal()" class="btn btn-secondary">Tutup</button>
                <button type="button" onclick="saveActiveMemberSelection()" class="btn btn-primary">Pilih Anggota Ini</button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 7. MODAL POPUP FORM (TAMBAH & EDIT DATA BUKU - ADMIN)          -->
    <!-- ============================================================== -->
    <div id="bookModal" class="modal-overlay">
        <div class="modal-box">
            
            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-header-icon">📖</div>
                    <div class="modal-header-text">
                        <h3 id="modalTitle">Tambah Buku Baru</h3>
                        <p id="modalSubtitle">Isi data buku lengkap beserta durasi pinjam dan tarif denda</p>
                    </div>
                </div>
                <button type="button" onclick="closeBookModal()" class="btn-close">&times;</button>
            </div>

            <form id="bookForm" onsubmit="handleBookFormSubmit(event)">
                <input type="hidden" id="formIdBuku" name="id_buku" value="">

                <div class="modal-body">
                    <!-- Judul Buku -->
                    <div class="form-group">
                        <label for="formJudul" class="form-label">Judul Buku *</label>
                        <input type="text" id="formJudul" name="judul" required placeholder="Contoh: Pemrograman Web Modern" class="form-input">
                    </div>

                    <!-- Penulis & Penerbit -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="formPenulis" class="form-label">Penulis *</label>
                            <input type="text" id="formPenulis" name="penulis" required placeholder="Contoh: Budi Santoso" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="formPenerbit" class="form-label">Penerbit *</label>
                            <input type="text" id="formPenerbit" name="penerbit" required placeholder="Contoh: Informatika" class="form-input">
                        </div>
                    </div>

                    <!-- Edisi & Kategori -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="formEidisi" class="form-label">Edisi</label>
                            <input type="text" id="formEidisi" name="eidisi" placeholder="Contoh: Edisi 2" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="formKategori" class="form-label">Kategori</label>
                            <input type="text" id="formKategori" name="nama_kategori" placeholder="Contoh: Basis Data" class="form-input">
                        </div>
                    </div>

                    <!-- BATAS HARI PINJAM & TARIF DENDA -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="formBatasHari" class="form-label" style="color: #22d3ee;">Batas Pengembalian (Hari) *</label>
                            <input type="number" id="formBatasHari" name="batas_hari_pinjam" min="1" max="90" value="7" required class="form-input">
                            <div style="display: flex; gap: 4px; margin-top: 6px;">
                                <button type="button" onclick="setPresetDuration(3)" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 10px;">3 Hari</button>
                                <button type="button" onclick="setPresetDuration(7)" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 10px;">7 Hari</button>
                                <button type="button" onclick="setPresetDuration(14)" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 10px;">14 Hari</button>
                                <button type="button" onclick="setPresetDuration(21)" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 10px;">21 Hari</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="formDenda" class="form-label" style="color: #fbbf24;">Tarif Denda (Rp / Hari) *</label>
                            <input type="number" id="formDenda" name="denda" min="0" step="500" value="1000" required class="form-input">
                        </div>
                    </div>

                    <!-- Tahun Terbit & Rating -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="formTahun" class="form-label">Tahun Terbit</label>
                            <input type="date" id="formTahun" name="tahun_terbit" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="formRating" class="form-label">Rating</label>
                            <select id="formRating" name="jumlah_rating" class="form-input">
                                <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                                <option value="4">⭐⭐⭐⭐ (4)</option>
                                <option value="3">⭐⭐⭐ (3)</option>
                                <option value="2">⭐⭐ (2)</option>
                                <option value="1">⭐ (1)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeBookModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <span id="submitBtnText">💾 Simpan Data</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 8. MODAL KONFIRMASI HAPUS                                      -->
    <!-- ============================================================== -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 420px;">

            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-header-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">⚠️</div>
                    <div class="modal-header-text">
                        <h3 id="deleteModalHeader">Konfirmasi Hapus</h3>
                        <p>Tindakan ini tidak dapat dibatalkan</p>
                    </div>
                </div>
                <button type="button" onclick="closeDeleteModal()" class="btn-close">&times;</button>
            </div>

            <div class="modal-body" style="text-align: center;">
                <p id="deleteModalQuestion" style="color: var(--text-secondary); font-size: 13px;">Apakah Anda yakin ingin menghapus data ini dari database?</p>
                <p id="deleteItemTitle" style="font-weight: 600; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: var(--text-main); padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-top: 8px;">-</p>
            </div>

            <div class="modal-footer" style="justify-content: center;">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Batal</button>
                <button type="button" id="confirmDeleteBtn" onclick="executeDelete()" class="btn btn-danger">🗑️ Hapus Sekarang</button>
            </div>

        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 9. TOAST NOTIFIKASI FEEDBACK                                   -->
    <!-- ============================================================== -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- JavaScript Murni (Vanilla JS) with Cache Buster -->
    <script src="assets/js/main.js?v=<?= time() ?>"></script>
</body>
</html>
