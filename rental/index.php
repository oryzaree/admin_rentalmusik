<?php
require_once __DIR__ . '/config/database.php';
require_login();
$page_title = 'Dashboard';

// Statistik
try {
  $totalPendapatan = (int)$pdo->query("SELECT COALESCE(SUM(total_sewa + denda),0) FROM rental WHERE status='Selesai'")->fetchColumn();
  $sedangDisewa   = (int)$pdo->query("SELECT COUNT(*) FROM alat_musik WHERE status='Sedang Disewa'")->fetchColumn();
  $totalInventaris= (int)$pdo->query("SELECT COUNT(*) FROM alat_musik")->fetchColumn();
  $terlambat      = (int)$pdo->query("SELECT COUNT(*) FROM rental WHERE status='Aktif' AND tgl_harus_kembali < CURDATE()")->fetchColumn();

  // Pendapatan per bulan (6 bulan terakhir)
  $rows = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, SUM(total_sewa + denda) AS total
    FROM rental WHERE status='Selesai'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY bln ORDER BY bln
  ")->fetchAll();

  // Aktivitas terkini
  $aktivitas = $pdo->query("
    SELECT r.id, r.status, r.tgl_harus_kembali, r.created_at,
           p.nama_lengkap, a.nama_alat
    FROM rental r
    JOIN pelanggan p ON p.id = r.pelanggan_id
    JOIN alat_musik a ON a.id = r.alat_id
    ORDER BY r.created_at DESC LIMIT 6
  ")->fetchAll();

  // Harus kembali hari ini
  $hariIni = $pdo->query("
    SELECT r.id, p.nama_lengkap, a.nama_alat, r.tgl_harus_kembali
    FROM rental r
    JOIN pelanggan p ON p.id = r.pelanggan_id
    JOIN alat_musik a ON a.id = r.alat_id
    WHERE r.status='Aktif' AND r.tgl_harus_kembali = CURDATE()
    ORDER BY r.id DESC
  ")->fetchAll();
} catch (Throwable $e) {
  $totalPendapatan=$sedangDisewa=$totalInventaris=$terlambat=0; $rows=[]; $aktivitas=[]; $hariIni=[];
}

// Susun data chart 6 bulan
$bulanLabel=[]; $bulanData=[];
for ($i=5; $i>=0; $i--) {
  $key = date('Y-m', strtotime("-$i month"));
  $bulanLabel[] = date('M', strtotime("-$i month"));
  $found = 0;
  foreach ($rows as $r) if ($r['bln'] === $key) $found = (int)$r['total'];
  $bulanData[] = $found;
}
$max = max($bulanData); if ($max <= 0) $max = 1;

include __DIR__ . '/includes/header.php'; ?>
<div class="app">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content">

  <div class="page-head">
    <div>
      <div class="eyebrow">Overview</div>
      <h1>Dashboard</h1>
      <p>Pantau performa rental, inventaris, dan transaksi dalam satu tampilan.</p>
    </div>
  </div>

  <div class="grid grid-4">
    <div class="card stat">
      <div class="ico"><i data-lucide="wallet"></i></div>
      <div class="label">Total Pendapatan</div>
      <div class="value"><?= rupiah($totalPendapatan) ?></div>
      <div class="delta">Akumulasi transaksi selesai</div>
    </div>
    <div class="card stat">
      <div class="ico"><i data-lucide="music-2"></i></div>
      <div class="label">Alat Sedang Disewa</div>
      <div class="value"><?= $sedangDisewa ?></div>
      <div class="delta">Item dengan status aktif</div>
    </div>
    <div class="card stat">
      <div class="ico"><i data-lucide="package"></i></div>
      <div class="label">Total Inventaris Alat</div>
      <div class="value"><?= $totalInventaris ?></div>
      <div class="delta">Seluruh unit terdaftar</div>
    </div>
    <div class="card stat">
      <div class="ico" style="background:rgba(239,68,68,.12);color:#fca5a5"><i data-lucide="alert-triangle"></i></div>
      <div class="label">Transaksi Terlambat</div>
      <div class="value"><?= $terlambat ?></div>
      <div class="delta">Melewati batas pengembalian</div>
    </div>
  </div>

  <div class="grid grid-2">
    <div class="card">
      <div class="card-head">
        <div>
          <h3>Sales Performance</h3>
          <div class="sub">Pendapatan bulanan 6 bulan terakhir.</div>
        </div>
      </div>
      <div class="chart">
        <?php foreach ($bulanData as $i => $v):
          $h = round(($v / $max) * 100); if ($h < 4) $h = 4; ?>
          <div class="bar" style="height:<?= $h ?>%"><span><?= rupiah($v) ?></span></div>
        <?php endforeach; ?>
      </div>
      <div class="chart-x">
        <?php foreach ($bulanLabel as $l): ?><div><?= $l ?></div><?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <div>
          <h3>Aktivitas Terkini</h3>
          <div class="sub">Log transaksi rental terbaru.</div>
        </div>
      </div>
      <div class="feed">
        <?php if (!$aktivitas): ?>
          <div class="muted">Belum ada aktivitas.</div>
        <?php endif; foreach ($aktivitas as $a):
          $late = ($a['status'] === 'Aktif' && $a['tgl_harus_kembali'] < date('Y-m-d'));
          $cls = $late ? 'red' : ($a['status']==='Selesai' ? 'green' : 'blue');
          $msg = $late
            ? 'Peringatan Terlambat: '.$a['nama_alat'].' telat '.((int)((strtotime(date('Y-m-d')) - strtotime($a['tgl_harus_kembali']))/86400)).' hari'
            : ($a['status']==='Selesai'
                ? $a['nama_alat'].' dikembalikan oleh '.$a['nama_lengkap']
                : $a['nama_alat'].' berhasil disewa oleh '.$a['nama_lengkap']);
        ?>
          <div class="feed-item">
            <div class="feed-dot <?= $cls ?>"></div>
            <div>
              <p><?= e($msg) ?></p>
              <time><?= date('d M Y H:i', strtotime($a['created_at'])) ?></time>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div>
        <h3>Alat Harus Kembali Hari Ini</h3>
        <div class="sub">Daftar pengembalian yang dijadwalkan hari ini.</div>
      </div>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>ID</th><th>Nama Penyewa</th><th>Alat Musik</th><th>Batas Waktu</th></tr></thead>
        <tbody>
          <?php if (!$hariIni): ?>
            <tr><td colspan="4" class="text-center muted" style="padding:24px">Tidak ada pengembalian terjadwal hari ini.</td></tr>
          <?php endif; foreach ($hariIni as $r): ?>
            <tr>
              <td>#TRX-<?= str_pad($r['id'],4,'0',STR_PAD_LEFT) ?></td>
              <td><?= e($r['nama_lengkap']) ?></td>
              <td><?= e($r['nama_alat']) ?></td>
              <td><?= date('d M Y', strtotime($r['tgl_harus_kembali'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
