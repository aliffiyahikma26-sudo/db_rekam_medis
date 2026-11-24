
<?php
include 'koneksi.php';

function e($v) {
    return htmlspecialchars((string)($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$jenis = 'success';

// Hapus riwayat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)($_POST['id_rekam'] ?? 0);

    $stmt = mysqli_prepare(
        $koneksi,
        "DELETE FROM rekam_medis WHERE id_rekam = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: riwayat.php?status=hapus");
        exit;
    }

    $pesan = "Gagal menghapus riwayat pemeriksaan.";
    $jenis = "danger";
    mysqli_stmt_close($stmt);
}

if (($_GET['status'] ?? '') === 'hapus') {
    $pesan = "Riwayat pemeriksaan berhasil dihapus.";
}

// Pencarian
$cari = trim($_GET['cari'] ?? '');

$sql = "
    SELECT
        r.id_rekam,
        r.id_pasien,
        r.id_dokter,
        r.id_obat,
        r.tanggal_periksa,
        r.keluhan,
        r.diagnosa,
        r.tindakan,
        r.resep,
        p.nama_pasien,
        p.no_rm,
        p.tanggal_lahir,
        p.alamat,
        d.nama_dokter,
        d.spesialisasi,
        o.nama_obat,
        o.satuan
    FROM rekam_medis r
    LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
    LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
    LEFT JOIN obat o ON r.id_obat = o.id_obat
";

if ($cari !== '') {
    $sql .= "
        WHERE p.nama_pasien LIKE ?
        OR p.no_rm LIKE ?
        OR d.nama_dokter LIKE ?
        OR r.diagnosa LIKE ?
        OR o.nama_obat LIKE ?
        OR r.keluhan LIKE ?
    ";

    $stmt = mysqli_prepare(
        $koneksi,
        $sql . " ORDER BY r.tanggal_periksa DESC, r.id_rekam DESC"
    );

    $keyword = "%$cari%";

    mysqli_stmt_bind_param(
        $stmt,
        "ssssss",
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

// Simpan hasil pencarian
$daftarRiwayat = [];

if ($data) {
    while ($row = mysqli_fetch_assoc($data)) {
        $daftarRiwayat[] = $row;
    }
}

$jumlahData = count($daftarRiwayat);

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
<title>Riwayat Pemeriksaan | MedCare</title>
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

        <a href="rekam_medis.php">
            <span class="nav-icon">▤</span>Rekam Medis
        </a>

        <a href="riwayat.php" class="active">
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
        <h1>Riwayat Pemeriksaan</h1>
        <p>Kelola dan pantau seluruh riwayat pemeriksaan pasien.</p>
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
        <small>Total riwayat pemeriksaan</small>
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

<section class="panel">

    <div class="panel-header">
        <div>
            <h3>Daftar Riwayat Pemeriksaan</h3>
            <p>Seluruh riwayat pemeriksaan pasien yang tersimpan.</p>
        </div>

        <div class="action-buttons">
            <span class="badge">
                <?= number_format($jumlahData) ?> Data
            </span>
        </div>
    </div>

    <form method="GET" class="table-search search-form">
        <input
            type="text"
            name="cari"
            placeholder="Cari pasien, nomor RM, dokter, diagnosis, atau obat..."
            value="<?= e($cari) ?>"
        >

        <button type="submit" class="btn btn-primary">
            Cari
        </button>

        <a href="riwayat.php" class="btn btn-secondary">
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
                    <th>Diagnosis</th>
                    <th>Obat</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($jumlahData > 0): ?>

                <?php $no = 1; ?>

                <?php foreach ($daftarRiwayat as $row): ?>

                <tr>
                    <td><?= $no++ ?></td>

                    <td>
                        <?= !empty($row['tanggal_periksa'])
                            ? e(date('d-m-Y', strtotime($row['tanggal_periksa'])))
                            : '-' ?>
                    </td>

                    <td>
                        <strong><?= e($row['nama_pasien']) ?></strong><br>
                        <small><?= e($row['no_rm']) ?></small>
                    </td>

                    <td><?= e($row['nama_dokter']) ?></td>

                    <td><?= e($row['diagnosa']) ?></td>

                    <td><?= e($row['nama_obat']) ?></td>

                    <td>
                        <div class="action-buttons">

                            <a
                                href="rekam_medis.php?edit=<?= (int)$row['id_rekam'] ?>"
                                class="btn btn-sm btn-secondary"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus riwayat ini?')"
                            >
                                <input
                                    type="hidden"
                                    name="id_rekam"
                                    value="<?= (int)$row['id_rekam'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="hapus"
                                    class="btn btn-sm btn-danger"
                                >
                                    Hapus
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon">◷</div>
                            <h3>Riwayat Belum Ditemukan</h3>
                            <p>Data riwayat pemeriksaan belum tersedia.</p>
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
