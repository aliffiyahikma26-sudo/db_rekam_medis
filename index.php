<?php
include 'koneksi.php';

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function totalData($koneksi, $tabel) {
    $allowed = ['pasien', 'dokter', 'obat', 'rekam_medis'];
    if (!in_array($tabel, $allowed, true)) return 0;

    $result = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM `$tabel`");
    return $result ? (int)mysqli_fetch_assoc($result)['total'] : 0;
}

$totalPasien = totalData($koneksi, 'pasien');
$totalDokter = totalData($koneksi, 'dokter');
$totalObat = totalData($koneksi, 'obat');
$totalRekam = totalData($koneksi, 'rekam_medis');

$terbaru = mysqli_query($koneksi, "
    SELECT r.id_rekam, r.tanggal_periksa, r.diagnosa,
           p.nama_pasien, p.no_rm, d.nama_dokter
    FROM rekam_medis r
    LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
    LEFT JOIN dokter d ON r.id_dokter = d.id_dokter
    ORDER BY r.tanggal_periksa DESC, r.id_rekam DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MedCare</title>
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
            <a href="index.php" class="active"><span class="nav-icon">⌂</span>Dashboard</a>
            <a href="pasien.php"><span class="nav-icon">♟</span>Data Pasien</a>
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
                <h1>Dashboard</h1>
                <p>Selamat datang di sistem informasi MedCare.</p>
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

        <section class="welcome">
            <div>
                <h2>Selamat Datang di MedCare!</h2>
                <p>Kelola informasi pasien dan pelayanan kesehatan dengan mudah.</p>
            </div>
        </section>

        <section class="stats">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon purple">♟</div>
                    <span class="badge">Pasien</span>
                </div>
                <h2><?= number_format($totalPasien) ?></h2>
                <small>Total data pasien</small>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon pink">⚕</div>
                    <span class="badge">Dokter</span>
                </div>
                <h2><?= number_format($totalDokter) ?></h2>
                <small>Total data dokter</small>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon blue">✚</div>
                    <span class="badge">Obat</span>
                </div>
                <h2><?= number_format($totalObat) ?></h2>
                <small>Total jenis obat</small>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon green">▤</div>
                    <span class="badge">Rekam Medis</span>
                </div>
                <h2><?= number_format($totalRekam) ?></h2>
                <small>Total pemeriksaan</small>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h3>Riwayat Pemeriksaan Terbaru</h3>
                    <p>Lima data pemeriksaan terakhir yang tercatat.</p>
                </div>
                <a href="riwayat.php" class="btn btn-primary">Lihat Semua</a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tanggal</th>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Diagnosis</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($terbaru && mysqli_num_rows($terbaru) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($terbaru)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e(date('d-m-Y', strtotime($row['tanggal_periksa']))) ?></td>
                                <td>
                                    <strong><?= e($row['nama_pasien'] ?? '-') ?></strong><br>
                                    <small><?= e($row['no_rm'] ?? '-') ?></small>
                                </td>
                                <td><?= e($row['nama_dokter'] ?? '-') ?></td>
                                <td><?= e($row['diagnosa'] ?? '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">▤</div>
                                    <h3>Belum Ada Pemeriksaan</h3>
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