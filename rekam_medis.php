
<?php
include 'koneksi.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$jenis = 'success';
$edit = null;

// Hapus rekam medis
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    $stmt = mysqli_prepare(
        $koneksi,
        "DELETE FROM rekam_medis WHERE id_rekam = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: rekam_medis.php?status=hapus");
        exit;
    }

    $pesan = "Gagal menghapus rekam medis.";
    $jenis = "danger";
    mysqli_stmt_close($stmt);
}

// Simpan rekam medis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id = (int)($_POST['id_rekam'] ?? 0);
    $id_pasien = (int)($_POST['id_pasien'] ?? 0);
    $id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $id_obat = (int)($_POST['id_obat'] ?? 0);

    $tanggal = $_POST['tanggal_periksa'] ?? '';
    $keluhan = trim($_POST['keluhan'] ?? '');
    $diagnosa = trim($_POST['diagnosa'] ?? '');
    $tindakan = trim($_POST['tindakan'] ?? '');
    $resep = trim($_POST['resep'] ?? '');

    if (
        $id_pasien < 1 ||
        $id_dokter < 1 ||
        $tanggal === '' ||
        $diagnosa === ''
    ) {
        $pesan = "Pasien, dokter, tanggal pemeriksaan, dan diagnosis wajib diisi.";
        $jenis = "danger";
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($koneksi, "
                UPDATE rekam_medis
                SET
                    id_pasien = ?,
                    id_dokter = ?,
                    id_obat = ?,
                    tanggal_periksa = ?,
                    keluhan = ?,
                    diagnosa = ?,
                    tindakan = ?,
                    resep = ?
                WHERE id_rekam = ?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iiisssssi",
                $id_pasien,
                $id_dokter,
                $id_obat,
                $tanggal,
                $keluhan,
                $diagnosa,
                $tindakan,
                $resep,
                $id
            );
        } else {
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO rekam_medis
                (
                    id_pasien,
                    id_dokter,
                    id_obat,
                    tanggal_periksa,
                    keluhan,
                    diagnosa,
                    tindakan,
                    resep
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "iiisssss",
                $id_pasien,
                $id_dokter,
                $id_obat,
                $tanggal,
                $keluhan,
                $diagnosa,
                $tindakan,
                $resep
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            header("Location: rekam_medis.php?status=simpan");
            exit;
        }

        $pesan = "Gagal menyimpan rekam medis. Periksa kembali data yang dipilih.";
        $jenis = "danger";
        mysqli_stmt_close($stmt);
    }
}

// Pesan notifikasi
if (($_GET['status'] ?? '') === 'simpan') {
    $pesan = "Rekam medis berhasil disimpan.";
}

if (($_GET['status'] ?? '') === 'hapus') {
    $pesan = "Rekam medis berhasil dihapus.";
}

// Data pilihan formulir
$pasien = mysqli_query(
    $koneksi,
    "SELECT id_pasien, no_rm, nama_pasien FROM pasien ORDER BY nama_pasien"
);

$dokter = mysqli_query(
    $koneksi,
    "SELECT id_dokter, nama_dokter, spesialisasi FROM dokter ORDER BY nama_dokter"
);

$obat = mysqli_query(
    $koneksi,
    "SELECT id_obat, nama_obat, satuan, stok FROM obat ORDER BY nama_obat"
);

// Ambil data untuk diedit
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM rekam_medis WHERE id_rekam = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);
}

// Pencarian
$cari = trim($_GET['cari'] ?? '');

$sql = "
    SELECT
        r.*,
        p.nama_pasien,
        p.no_rm,
        d.nama_dokter,
        o.nama_obat
    FROM rekam_medis r
    LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
    LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
    LEFT JOIN obat o ON r.id_obat = o.id_obat
";

if ($cari !== '') {
    $sql .= "
        WHERE
            p.nama_pasien LIKE ?
            OR p.no_rm LIKE ?
            OR d.nama_dokter LIKE ?
            OR r.keluhan LIKE ?
            OR r.diagnosa LIKE ?
            OR r.tindakan LIKE ?
            OR r.resep LIKE ?
    ";

    $stmt = mysqli_prepare(
        $koneksi,
        $sql . " ORDER BY r.tanggal_periksa DESC, r.id_rekam DESC"
    );

    $keyword = "%$cari%";

    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    mysqli_stmt_execute($stmt);
    $data = mysqli_stmt_get_result($stmt);
} else {
    $data = mysqli_query(
        $koneksi,
        $sql . " ORDER BY r.tanggal_periksa DESC, r.id_rekam DESC"
    );
}

// Statistik
$resultTotal = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM rekam_medis"
);
$total = (int)(mysqli_fetch_assoc($resultTotal)['total'] ?? 0);

$resultHari = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM rekam_medis WHERE tanggal_periksa = CURDATE()"
);
$hariIni = (int)(mysqli_fetch_assoc($resultHari)['total'] ?? 0);

$resultPasien = mysqli_query(
    $koneksi,
    "SELECT COUNT(DISTINCT id_pasien) AS total FROM rekam_medis"
);
$totalPasien = (int)(mysqli_fetch_assoc($resultPasien)['total'] ?? 0);

$resultDokter = mysqli_query(
    $koneksi,
    "SELECT COUNT(DISTINCT id_dokter) AS total FROM rekam_medis"
);
$totalDokter = (int)(mysqli_fetch_assoc($resultDokter)['total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekam Medis | MedCare</title>
    <link rel="stylesheet" href="assets/style.css">
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
            <a href="index.php">
                <span class="nav-icon">⌂</span>Dashboard
            </a>

            <a href="pasien.php">
                <span class="nav-icon">♟</span>Data Pasien
            </a>

            <a href="dokter.php">
                <span class="nav-icon">⚕</span>Data Dokter
            </a>

            <a href="obat.php">
                <span class="nav-icon">✚</span>Data Obat
            </a>

            <a href="rekam_medis.php" class="active">
                <span class="nav-icon">▤</span>Rekam Medis
            </a>

            <a href="riwayat.php">
                <span class="nav-icon">◷</span>Riwayat
            </a>
        </nav>

        <div class="sidebar-bottom">
            MedCare System
            <small>Medical Record Management</small>
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div class="page-heading">
                <h1>Rekam Medis</h1>
                <p>Catat dan kelola pemeriksaan pasien.</p>
            </div>

            <div class="topbar-right">
                <div class="date-widget">
                    ◷ <?= date('d F Y') ?>
                </div>

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
                    <div class="stat-icon purple">▤</div>
                    <span class="badge">Total</span>
                </div>
                <h2><?= number_format($total) ?></h2>
                <small>Total rekam medis</small>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon pink">◷</div>
                    <span class="badge">Hari Ini</span>
                </div>
                <h2><?= number_format($hariIni) ?></h2>
                <small>Pemeriksaan hari ini</small>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon blue">♟</div>
                    <span class="badge">Pasien</span>
                </div>
                <h2><?= number_format($totalPasien) ?></h2>
                <small>Pasien yang diperiksa</small>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon green">⚕</div>
                    <span class="badge">Dokter</span>
                </div>
                <h2><?= number_format($totalDokter) ?></h2>
                <small>Dokter yang menangani</small>
            </div>

        </section>

        <?php if ($pesan): ?>
            <div class="alert alert-<?= e($jenis) ?>">
                <?= e($pesan) ?>
            </div>
        <?php endif; ?>

        <!-- FORM REKAM MEDIS -->
        <section class="panel">

            <div class="panel-header">
                <div>
                    <h3>
                        <?= $edit ? 'Edit Rekam Medis' : 'Tambah Rekam Medis' ?>
                    </h3>
                    <p>Isi informasi pemeriksaan pasien.</p>
                </div>

                <?php if ($edit): ?>
                    <a href="rekam_medis.php" class="btn btn-secondary">
                        Batal Edit
                    </a>
                <?php endif; ?>
            </div>

            <form method="POST">

                <input
                    type="hidden"
                    name="id_rekam"
                    value="<?= e($edit['id_rekam'] ?? '') ?>"
                >

                <div class="form-grid">

                    <div class="form-group">
                        <label>Pasien</label>

                        <select name="id_pasien" required>
                            <option value="">Pilih pasien</option>

                            <?php while ($p = mysqli_fetch_assoc($pasien)): ?>
                                <option
                                    value="<?= (int)$p['id_pasien'] ?>"
                                    <?= (int)($edit['id_pasien'] ?? 0) === (int)$p['id_pasien'] ? 'selected' : '' ?>
                                >
                                    <?= e($p['no_rm']) ?> -
                                    <?= e($p['nama_pasien']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dokter</label>

                        <select name="id_dokter" required>
                            <option value="">Pilih dokter</option>

                            <?php while ($d = mysqli_fetch_assoc($dokter)): ?>
                                <option
                                    value="<?= (int)$d['id_dokter'] ?>"
                                    <?= (int)($edit['id_dokter'] ?? 0) === (int)$d['id_dokter'] ? 'selected' : '' ?>
                                >
                                    <?= e($d['nama_dokter']) ?> -
                                    <?= e($d['spesialisasi']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Pemeriksaan</label>

                        <input
                            type="date"
                            name="tanggal_periksa"
                            required
                            value="<?= e($edit['tanggal_periksa'] ?? date('Y-m-d')) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Obat</label>

                        <select name="id_obat">
                            <option value="0">Tidak ada / belum dipilih</option>

                            <?php while ($o = mysqli_fetch_assoc($obat)): ?>
                                <option
                                    value="<?= (int)$o['id_obat'] ?>"
                                    <?= (int)($edit['id_obat'] ?? 0) === (int)$o['id_obat'] ? 'selected' : '' ?>
                                >
                                    <?= e($o['nama_obat']) ?>
                                    (Stok: <?= (int)$o['stok'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Keluhan</label>

                        <textarea
                            name="keluhan"
                            placeholder="Masukkan keluhan pasien"
                        ><?= e($edit['keluhan'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Diagnosis</label>

                        <textarea
                            name="diagnosa"
                            required
                            placeholder="Masukkan diagnosis"
                        ><?= e($edit['diagnosa'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tindakan</label>

                        <textarea
                            name="tindakan"
                            placeholder="Masukkan tindakan pemeriksaan"
                        ><?= e($edit['tindakan'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Resep</label>

                        <textarea
                            name="resep"
                            placeholder="Masukkan resep atau catatan obat"
                        ><?= e($edit['resep'] ?? '') ?></textarea>
                    </div>

                </div>

                <div class="action-buttons">
                    <button
                        type="submit"
                        name="simpan"
                        class="btn btn-primary"
                    >
                        <?= $edit ? 'Simpan Perubahan' : '+ Simpan Rekam Medis' ?>
                    </button>

                    <a href="rekam_medis.php" class="btn btn-secondary">
                        Reset
                    </a>
                </div>

            </form>
        </section>

        <!-- TABEL DAFTAR REKAM MEDIS -->
        <section class="panel">

            <div class="panel-header">
                <div>
                    <h3>Daftar Rekam Medis</h3>
                    <p>Daftar pemeriksaan pasien yang tercatat.</p>
                </div>

                <span class="badge">
                    <?= number_format($total) ?> Data
                </span>
            </div>

            <form method="GET" class="table-search">
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari pasien, nomor RM, dokter, keluhan, diagnosis..."
                    value="<?= e($cari) ?>"
                >

                <button type="submit" class="btn btn-primary">
                    Cari
                </button>

                <a href="rekam_medis.php" class="btn btn-secondary">
                    Reset
                </a>
            </form>

            <div class="table-wrap">
                <table>

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Keluhan</th>
                            <th>Diagnosis</th>
                            <th>Tindakan</th>
                            <th>Obat</th>
                            <th>Resep</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($data && mysqli_num_rows($data) > 0): ?>

                            <?php
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($data)):
                            ?>

                                <tr>
                                    <td><?= $no++ ?></td>

                                    <td>
                                        <?= e(date(
                                            'd-m-Y',
                                            strtotime($row['tanggal_periksa'])
                                        )) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= e($row['nama_pasien'] ?? '-') ?>
                                        </strong>
                                        <br>
                                        <small>
                                            <?= e($row['no_rm'] ?? '-') ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= e($row['nama_dokter'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= e($row['keluhan'] ?: '-') ?>
                                    </td>

                                    <td>
                                        <?= e($row['diagnosa'] ?: '-') ?>
                                    </td>

                                    <td>
                                        <?= e($row['tindakan'] ?: '-') ?>
                                    </td>

                                    <td>
                                        <?= e($row['nama_obat'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= e($row['resep'] ?: '-') ?>
                                    </td>

                                    <td>
                                        <div class="action-buttons">

                                            <a
                                                class="btn btn-sm btn-primary"
                                                href="rekam_medis.php?edit=<?= (int)$row['id_rekam'] ?>"
                                            >
                                                Edit
                                            </a>

                                            <a
                                                class="btn btn-sm btn-danger"
                                                href="rekam_medis.php?hapus=<?= (int)$row['id_rekam'] ?>"
                                                onclick="return confirm('Hapus rekam medis ini?')"
                                            >
                                                Hapus
                                            </a>

                                        </div>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="10">
                                    <div class="empty-state">
                                        <div class="empty-icon">▤</div>
                                        <h3>Belum Ada Rekam Medis</h3>
                                        <p>Data pemeriksaan akan tampil di sini.</p>
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
