
<?php
include 'koneksi.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$jenis = 'success';
$edit = null;

// Hapus data
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $stmt = mysqli_prepare($koneksi, "DELETE FROM pasien WHERE id_pasien=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: pasien.php?status=hapus");
        exit;
    }

    $pesan = "Data pasien gagal dihapus. Pastikan tidak ada rekam medis yang masih terhubung.";
    $jenis = "danger";
    mysqli_stmt_close($stmt);
}

// Tambah dan edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id = (int)($_POST['id_pasien'] ?? 0);
    $no_rm = trim($_POST['no_rm'] ?? '');
    $nama = trim($_POST['nama_pasien'] ?? '');
    $jk = $_POST['jenis_kelamin'] ?? '';
    $tanggal = $_POST['tanggal_lahir'] ?? '';
    $alamat = trim($_POST['alamat'] ?? '');

    if ($no_rm === '' || $nama === '' || $tanggal === '' || !in_array($jk, ['L', 'P'], true)) {
        $pesan = "Semua data wajib diisi dengan benar.";
        $jenis = "danger";
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($koneksi, "UPDATE pasien SET no_rm=?, nama_pasien=?, jenis_kelamin=?, tanggal_lahir=?, alamat=? WHERE id_pasien=?");
            mysqli_stmt_bind_param($stmt, "sssssi", $no_rm, $nama, $jk, $tanggal, $alamat, $id);
        } else {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO pasien (no_rm, nama_pasien, jenis_kelamin, tanggal_lahir, alamat) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $no_rm, $nama, $jk, $tanggal, $alamat);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: pasien.php?status=simpan");
            exit;
        } else {
            $pesan = "Gagal menyimpan data. Periksa nomor rekam medis agar tidak duplikat.";
            $jenis = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

// Pesan status
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'simpan') $pesan = "Data pasien berhasil disimpan.";
    if ($_GET['status'] === 'hapus') $pesan = "Data pasien berhasil dihapus.";
}

// Ambil data edit
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmtEdit = mysqli_prepare($koneksi, "SELECT * FROM pasien WHERE id_pasien=?");
    mysqli_stmt_bind_param($stmtEdit, "i", $id);
    mysqli_stmt_execute($stmtEdit);

    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtEdit));
    mysqli_stmt_close($stmtEdit);
}

// Pencarian
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM pasien WHERE nama_pasien LIKE ? OR no_rm LIKE ? ORDER BY id_pasien DESC");
    $keyword = "%$cari%";
    mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
} else {
    $data = mysqli_query($koneksi, "SELECT * FROM pasien ORDER BY id_pasien DESC");
}

$totalResult = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pasien");
$total = (int)(mysqli_fetch_assoc($totalResult)['total'] ?? 0);

$ditampilkan = $data ? mysqli_num_rows($data) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien | MedCare</title>
    <link rel="stylesheet" href="assets/style.css?v=merah-pasien-2026">
</head>
<body>

<div class="dashboard">

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">✚</div>
        <div>
            <h2>MedCare</h2>
            <span>Medical Information<br>System</span>
        </div>
    </div>

    <div class="nav-title">MENU UTAMA</div>

    <nav class="nav-menu">
        <a href="index.php"><span class="nav-icon">⌂</span>Dashboard</a>
        <a href="pasien.php" class="active"><span class="nav-icon">♟</span>Data Pasien</a>
        <a href="dokter.php"><span class="nav-icon">⚕</span>Data Dokter</a>
        <a href="obat.php"><span class="nav-icon">✚</span>Data Obat</a>
        <a href="rekam_medis.php"><span class="nav-icon">▤</span>Rekam Medis</a>
        <a href="riwayat.php"><span class="nav-icon">◷</span>Riwayat</a>
    </nav>

    <div class="sidebar-bottom">
        MedCare System
        <small>Medical Record Management</small>
    </div>
</aside>

<main class="main">

<header class="topbar">
    <div class="page-heading">
        <h1>Data Pasien</h1>
        <p>Kelola informasi dan data pasien.</p>
    </div>

    <div class="topbar-right">
        <div class="date-widget">◷ <?= date('d F Y') ?></div>
        <div class="profile">
            <div class="profile-avatar">👩‍⚕️</div>
            <div>
                <strong>Administrator</strong>
                <small>Pengelola MedCare</small>
            </div>
        </div>
    </div>
</header>

<section class="stats">
    <div class="stat-card">
        <div class="stat-top">
            <div class="stat-icon purple">♟</div>
            <span class="badge">Pasien</span>
        </div>
        <h2><?= number_format($total) ?></h2>
        <small>Total data pasien</small>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <div class="stat-icon green">▤</div>
            <span class="badge">Data</span>
        </div>
        <h2><?= number_format($ditampilkan) ?></h2>
        <small>Data yang ditampilkan</small>
    </div>
</section>

<?php if ($pesan): ?>
<div class="alert alert-<?= e($jenis) ?>">
    <?= e($pesan) ?>
</div>
<?php endif; ?>

<section class="panel">

    <div class="panel-header">
        <div>
            <h3><?= $edit ? 'Edit Data Pasien' : 'Tambah Data Pasien' ?></h3>
            <p>Lengkapi informasi pasien di bawah ini.</p>
        </div>

        <?php if ($edit): ?>
            <a href="pasien.php" class="btn btn-secondary">Batal Edit</a>
        <?php endif; ?>
    </div>

    <form method="POST">
        <input type="hidden" name="id_pasien" value="<?= e($edit['id_pasien'] ?? '') ?>">

        <div class="form-grid">

            <div class="form-group">
                <label>Nomor Rekam Medis</label>
                <input type="text" name="no_rm" required
                    value="<?= e($edit['no_rm'] ?? '') ?>"
                    placeholder="Contoh: RM001">
            </div>

            <div class="form-group">
                <label>Nama Pasien</label>
                <input type="text" name="nama_pasien" required
                    value="<?= e($edit['nama_pasien'] ?? '') ?>"
                    placeholder="Masukkan nama pasien">
            </div>

            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" required>
                    <option value="">-- Pilih Jenis Kelamin --</option>
                    <option value="L" <?= (($edit['jenis_kelamin'] ?? '') === 'L') ? 'selected' : '' ?>>
                        Laki-laki
                    </option>
                    <option value="P" <?= (($edit['jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>
                        Perempuan
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" required
                    value="<?= e($edit['tanggal_lahir'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" placeholder="Masukkan alamat pasien"><?= e($edit['alamat'] ?? '') ?></textarea>
            </div>

        </div>

        <div class="action-buttons">
            <button type="submit" name="simpan" class="btn btn-primary">
                <?= $edit ? 'Simpan Perubahan' : '+ Simpan Pasien' ?>
            </button>
            <a href="pasien.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>

</section>

<section class="panel">

    <div class="panel-header">
        <div>
            <h3>Daftar Pasien</h3>
            <p>Seluruh data pasien yang terdaftar.</p>
        </div>
        <span class="badge"><?= number_format($total) ?> Pasien</span>
    </div>

    <form method="GET" class="table-search">
        <input type="text" name="cari"
            placeholder="Cari nama atau nomor rekam medis..."
            value="<?= e($cari) ?>">
        <button class="btn btn-primary" type="submit">Cari</button>
        <a href="pasien.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Jenis Kelamin</th>
                    <th>Tanggal Lahir</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($data && mysqli_num_rows($data) > 0): ?>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><span class="badge"><?= e($row['no_rm']) ?></span></td>
                    <td><strong><?= e($row['nama_pasien']) ?></strong></td>
                    <td>
                        <?= ($row['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : (($row['jenis_kelamin'] ?? '') === 'P' ? 'Perempuan' : '-') ?>
                    </td>
                    <td><?= e($row['tanggal_lahir']) ?></td>
                    <td><?= e($row['alamat']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <a class="btn btn-sm btn-primary"
                                href="pasien.php?edit=<?= (int)$row['id_pasien'] ?>">
                                Edit
                            </a>

                            <a class="btn btn-sm btn-danger"
                                href="pasien.php?hapus=<?= (int)$row['id_pasien'] ?>"
                                onclick="return confirm('Hapus data pasien ini?')">
                                Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon">♟</div>
                            <h3>Data Pasien Kosong</h3>
                            <p>Belum ada data pasien yang ditemukan.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>

</main>
</div>

</body>
</html>
