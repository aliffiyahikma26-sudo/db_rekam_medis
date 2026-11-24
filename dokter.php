
<?php
include 'koneksi.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$jenis = 'success';
$edit = null;

// Hapus dokter
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = mysqli_prepare($koneksi, "DELETE FROM dokter WHERE id_dokter=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: dokter.php?status=hapus");
        exit;
    }

    $pesan = "Data dokter gagal dihapus. Pastikan dokter tidak sedang digunakan pada rekam medis.";
    $jenis = "danger";
    mysqli_stmt_close($stmt);
}

// Simpan dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id = (int)($_POST['id_dokter'] ?? 0);
    $nama = trim($_POST['nama_dokter'] ?? '');
    $spesialisasi = trim($_POST['spesialisasi'] ?? '');

    if ($nama === '' || $spesialisasi === '') {
        $pesan = "Nama dokter dan spesialisasi wajib diisi.";
        $jenis = "danger";
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($koneksi, "UPDATE dokter SET nama_dokter=?, spesialisasi=? WHERE id_dokter=?");
            mysqli_stmt_bind_param($stmt, "ssi", $nama, $spesialisasi, $id);
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO dokter (nama_dokter, spesialisasi) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $nama, $spesialisasi);
        }

        if (mysqli_stmt_execute($stmt)) {
            header("Location: dokter.php?status=simpan");
            exit;
        }

        $pesan = "Gagal menyimpan data dokter.";
        $jenis = "danger";
        mysqli_stmt_close($stmt);
    }
}

if (($_GET['status'] ?? '') === 'simpan') $pesan = "Data dokter berhasil disimpan.";
if (($_GET['status'] ?? '') === 'hapus') $pesan = "Data dokter berhasil dihapus.";

// Edit
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM dokter WHERE id_dokter=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

// Cari
$cari = trim($_GET['cari'] ?? '');
if ($cari !== '') {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM dokter WHERE nama_dokter LIKE ? OR spesialisasi LIKE ? ORDER BY id_dokter DESC");
    $keyword = "%$cari%";
    mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
} else {
    $data = mysqli_query($koneksi, "SELECT * FROM dokter ORDER BY id_dokter DESC");
}

$total = (int)(mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM dokter"))['total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Dokter | MedCare</title>
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
        <a href="dokter.php" class="active"><span class="nav-icon">⚕</span>Data Dokter</a>
        <a href="obat.php"><span class="nav-icon">✚</span>Data Obat</a>
        <a href="rekam_medis.php"><span class="nav-icon">▤</span>Rekam Medis</a>
        <a href="riwayat.php"><span class="nav-icon">◷</span>Riwayat</a>
    </nav>
    <div class="sidebar-bottom">MedCare System<small>Medical Record Management</small></div>
</aside>

<main class="main">
<header class="topbar">
    <div class="page-heading"><h1>Data Dokter</h1><p>Kelola informasi dokter dan spesialisasi.</p></div>
    <div class="topbar-right">
        <div class="date-widget">◷ <?= date('d F Y') ?></div>
        <div class="profile"><div class="profile-avatar">👩‍⚕️</div><div><strong>Administrator</strong><small>Pengelola MedCare</small></div></div>
    </div>
</header>

<section class="stats">
    <div class="stat-card">
        <div class="stat-top"><div class="stat-icon purple">⚕</div><span class="badge">Dokter</span></div>
        <h2><?= number_format($total) ?></h2><small>Total dokter terdaftar</small>
    </div>
    <div class="stat-card">
        <div class="stat-top"><div class="stat-icon green">▤</div><span class="badge">Data</span></div>
        <h2><?= number_format(mysqli_num_rows($data)) ?></h2><small>Data yang ditampilkan</small>
    </div>
</section>

<?php if ($pesan): ?><div class="alert alert-<?= e($jenis) ?>"><?= e($pesan) ?></div><?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div><h3><?= $edit ? 'Edit Data Dokter' : 'Tambah Data Dokter' ?></h3><p>Masukkan informasi dokter.</p></div>
        <?php if ($edit): ?><a href="dokter.php" class="btn btn-secondary">Batal Edit</a><?php endif; ?>
    </div>

    <form method="POST">
        <input type="hidden" name="id_dokter" value="<?= e($edit['id_dokter'] ?? '') ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Nama Dokter</label>
                <input type="text" name="nama_dokter" required value="<?= e($edit['nama_dokter'] ?? '') ?>" placeholder="Masukkan nama dokter">
            </div>
            <div class="form-group">
                <label>Spesialisasi</label>
                <input type="text" name="spesialisasi" required value="<?= e($edit['spesialisasi'] ?? '') ?>" placeholder="Contoh: Dokter Umum">
            </div>
        </div>
        <div class="action-buttons">
            <button type="submit" name="simpan" class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : '+ Simpan Dokter' ?></button>
            <a href="dokter.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div><h3>Daftar Dokter</h3><p>Seluruh dokter yang terdaftar di MedCare.</p></div>
        <span class="badge"><?= number_format($total) ?> Dokter</span>
    </div>

    <form method="GET" class="table-search">
        <input type="text" name="cari" placeholder="Cari nama atau spesialisasi dokter..." value="<?= e($cari) ?>">
        <button class="btn btn-primary" type="submit">Cari</button>
        <a href="dokter.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Nama Dokter</th><th>Spesialisasi</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($data && mysqli_num_rows($data) > 0): ?>
                <?php $no=1; while ($row=mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= e($row['nama_dokter']) ?></strong></td>
                    <td><span class="badge"><?= e($row['spesialisasi']) ?></span></td>
                    <td><div class="action-buttons">
                        <a class="btn btn-sm btn-primary" href="dokter.php?edit=<?= (int)$row['id_dokter'] ?>">Edit</a>
                        <a class="btn btn-sm btn-danger" href="dokter.php?hapus=<?= (int)$row['id_dokter'] ?>" onclick="return confirm('Hapus data dokter ini?')">Hapus</a>
                    </div></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4"><div class="empty-state"><div class="empty-icon">⚕</div><h3>Data Dokter Kosong</h3><p>Belum ada data dokter yang ditemukan.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</main>
</div>
</body>
</html>
