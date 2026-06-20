<?php
require_once __DIR__ . '/config/database.php';
require_login();
$page_title = 'Laporan Pengembalian';

try {
  $data = $pdo->query('
    SELECT r.*, p.nama_lengkap, a.nama_alat, a.kode_alat
    FROM rental r
    JOIN pelanggan p ON p.id = r.pelanggan_id
    JOIN alat_musik a ON a.id = r.alat_id
    WHERE r.status = "Selesai"
    ORDER BY r.id DESC
  ')->fetchAll();
} catch (Throwable $e) { $data = []; }

include __DIR__ . '/includes/header.php'; ?>
<div class="app">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content">

  <div class="page-head">
    <div>
      <div class="eyebrow">Riwayat</div>
      <h1>Laporan Pengembalian</h1>
      <p>Seluruh riwayat transaksi rental yang telah diselesaikan.</p>
    </div>
    <button class="btn btn-primary no-print" onclick="window.print()">
      <i data-lucide="printer"></i> Cetak Laporan
    </button>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>ID Transaksi</th><th>Penyewa</th><th>Alat Musik</th>
            <th>Durasi (Hari)</th><th>Total Bayar</th><th>Denda Terbayar</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$data): ?>
            <tr><td colspan="7" class="text-center muted" style="padding:24px">Belum ada transaksi selesai.</td></tr>
          <?php endif; foreach ($data as $r):
            $durasi = max(1, (int)((strtotime($r['tgl_harus_kembali']) - strtotime($r['tgl_pinjam']))/86400));
            $grand = (int)$r['total_sewa'] + (int)$r['denda']; ?>
            <tr>
              <td>TRX-<?= str_pad($r['id'],4,'0',STR_PAD_LEFT) ?></td>
              <td><?= e($r['nama_lengkap']) ?></td>
              <td>[<?= e($r['kode_alat']) ?>] <?= e($r['nama_alat']) ?></td>
              <td><?= $durasi ?> hari</td>
              <td><?= rupiah($grand) ?></td>
              <td>
                <?= rupiah($r['denda']) ?>
                <?php if ((int)$r['denda'] > 0): ?>
                  <span class="badge badge-warning" style="margin-left:6px">Terlambat</span>
                <?php endif; ?>
              </td>
              <td><span class="badge badge-success">Selesai</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
