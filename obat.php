
<?php
include 'koneksi.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$jenis = 'success';
$edit = null;

// Hapus obat
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = mysqli_prepare($koneksi, "DELETE FROM obat WHERE id_obat=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: obat.php?status=hapus");
        exit;
    }

    $pesan = "Data obat gagal dihapus. Periksa apakah obat digunakan pada rekam medis.";
    $jenis = "danger";
    mysqli_stmt_close($stmt);
}

// Simpan obat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id = (int)($_POST['id_obat'] ?? 0);
    $nama = trim($_POST['nama_obat'] ?? '');
    $satuan = trim($_POST['satuan'] ?? '');
    $stok = (int)($_POST['stok'] ?? 0);

    if ($nama === '' || $satuan === '' || $stok < 0) {
        $pesan = "Nama, satuan, dan stok obat harus diisi dengan benar.";
        $jenis = "danger";
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($koneksi, "UPDATE obat SET nama_obat=?, satuan=?, stok=? WHERE id_obat=?");
            mysqli_stmt_bind_param($stmt, "ssii", $nama, $satuan, $stok, $id);
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO obat (nama_obat, satuan, stok) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssi", $nama, $satuan, $stok);
        }

        if (mysqli_stmt_execute($stmt)) {
            header("Location: obat.php?status=simpan");
            exit;
        }

        $pesan = "Gagal menyimpan data obat.";
        $jenis = "danger";
        mysqli_stmt_close($stmt);
    }
}

if (($_GET['status'] ?? '') === 'simpan') $pesan = "Data obat berhasil disimpan.";
if (($_GET['status'] ?? '') === 'hapus') $pesan = "Data obat berhasil dihapus.";

// Edit
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM obat WHERE id_obat=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

// Cari
$cari = trim($_GET['cari'] ?? '');
if ($cari !== '') {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM obat WHERE nama_obat LIKE ? OR satuan LIKE ? ORDER BY id_obat DESC");
    $keyword = "%$cari%";
    mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
} else {
    $data = mysqli_query($koneksi, "SELECT * FROM obat ORDER BY id_obat DESC");
}

$total = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM obat"))['total'] ?? 0);
$totalStok = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(stok),0) AS total FROM obat"))['total'] ?? 0);
$stokRendah = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM obat WHERE stok <= 5"))['total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Obat | MedCare</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="dashboard">

<aside class="sidebar">
    <div class="brand"><div class="brand-icon">✚</div><div><h2>MedCare</h2><span>Medical Information<br>System</span></div></div>
    <div class="nav-title">MENU UTAMA</div>
    <nav class="nav-menu">
        <a href="index.php"><span class="nav-icon">⌂</span>Dashboard</a>
        <a href="pasien.php"><span class="nav-icon">♟</span>Data Pasien</a>
        <a href="dokter.php"><span class="nav-icon">⚕</span>Data Dokter</a>
        <a href="obat.php" class="active"><span class="nav-icon">✚</span>Data Obat</a>
        <a href="rekam_medis.php"><span class="nav-icon">▤</span>Rekam Medis</a>
        <a href="riwayat.php"><span class="nav-icon">◷</span>Riwayat</a>
    </nav>
    <div class="sidebar-bottom">MedCare System<small>Medical Record Management</small></div>
</aside>

<main class="main">
<header class="topbar">
    <div class="page-heading"><h1>Data Obat</h1><p>Kelola persediaan dan informasi obat.</p></div>
    <div class="topbar-right">
        <div class="date-widget">◷ <?= date('d F Y') ?></div>
        <div class="profile"><div class="profile-avatar">👩‍⚕️</div><div><strong>Administrator</strong><small>Pengelola MedCare</small></div></div>
    </div>
</header>

<section class="stats">
    <div class="stat-card"><div class="stat-top"><div class="stat-icon purple">✚</div><span class="badge">Jenis Obat</span></div><h2><?= number_format($total) ?></h2><small>Total jenis obat</small></div>
    <div class="stat-card"><div class="stat-top"><div class="stat-icon green">▤</div><span class="badge">Persediaan</span></div><h2><?= number_format($totalStok) ?></h2><small>Total stok obat</small></div>
    <div class="stat-card"><div class="stat-top"><div class="stat-icon pink">!</div><span class="badge">Perhatian</span></div><h2><?= number_format($stokRendah) ?></h2><small>Obat dengan stok 5 atau kurang</small></div>
    <div class="stat-card"><div class="stat-top"><div class="stat-icon blue">▦</div><span class="badge">Data</span></div><h2><?= number_format(mysqli_num_rows($data)) ?></h2><small>Data yang ditampilkan</small></div>
</section>

<?php if ($pesan): ?><div class="alert alert-<?= e($jenis) ?>"><?= e($pesan) ?></div><?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div><h3><?= $edit ? 'Edit Data Obat' : 'Tambah Data Obat' ?></h3><p>Masukkan informasi dan jumlah persediaan obat.</p></div>
        <?php if ($edit): ?><a href="obat.php" class="btn btn-secondary">Batal Edit</a><?php endif; ?>
    </div>

    <form method="POST">
        <input type="hidden" name="id_obat" value="<?= e($edit['id_obat'] ?? '') ?>">
        <div class="form-grid">
            <div class="form-group"><label>Nama Obat</label><input type="text" name="nama_obat" required value="<?= e($edit['nama_obat'] ?? '') ?>" placeholder="Masukkan nama obat"></div>
            <div class="form-group"><label>Satuan</label><input type="text" name="satuan" required value="<?= e($edit['satuan'] ?? '') ?>" placeholder="Contoh: Tablet, Kapsul, Botol"></div>
            <div class="form-group"><label>Stok</label><input type="number" name="stok" min="0" required value="<?= e($edit['stok'] ?? '0') ?>" placeholder="Masukkan jumlah stok"></div>
        </div>
        <div class="action-buttons">
            <button type="submit" name="simpan" class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : '+ Simpan Obat' ?></button>
            <a href="obat.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header"><div><h3>Daftar Obat</h3><p>Daftar persediaan obat yang tersedia.</p></div><span class="badge"><?= number_format($total) ?> Jenis Obat</span></div>

    <form method="GET" class="table-search">
        <input type="text" name="cari" placeholder="Cari nama obat atau satuan..." value="<?= e($cari) ?>">
        <button class="btn btn-primary" type="submit">Cari</button>
        <a href="obat.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Nama Obat</th><th>Satuan</th><th>Stok</th><th>Status Stok</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($data && mysqli_num_rows($data) > 0): ?>
                <?php $no=1; while ($row=mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= e($row['nama_obat']) ?></strong></td>
                    <td><?= e($row['satuan']) ?></td>
                    <td><?= number_format((int)$row['stok']) ?></td>
                    <td>
                        <?php if ((int)$row['stok'] <= 5): ?>
                            <span class="badge" style="background:#f9ece8;color:#b96b60">Stok Rendah</span>
                        <?php else: ?>
                            <span class="badge">Tersedia</span>
                        <?php endif; ?>
                    </td>
                    <td><div class="action-buttons">
                        <a class="btn btn-sm btn-primary" href="obat.php?edit=<?= (int)$row['id_obat'] ?>">Edit</a>
                        <a class="btn btn-sm btn-danger" href="obat.php?hapus=<?= (int)$row['id_obat'] ?>" onclick="return confirm('Hapus data obat ini?')">Hapus</a>
                    </div></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">✚</div><h3>Data Obat Kosong</h3><p>Belum ada data obat yang ditemukan.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</main>
</div>
</body>
</html>
