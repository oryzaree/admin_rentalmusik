<?php
$current = basename($_SERVER['PHP_SELF']);
$nav = [
  ['index.php','Dashboard','layout-dashboard'],
  ['pelanggan.php','Data Pelanggan','users'],
  ['alat.php','Data Alat Musik','music-2'],
  ['transaksi.php','Transaksi Rental','clipboard-list'],
  ['laporan.php','Laporan Pengembalian','file-text'],
];
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="brand-logo"><i data-lucide="layers"></i></div>
    <div class="brand-text">
      <div class="brand-name">Kelompok 14</div>
      <div class="brand-sub">Rental Alat Musik</div>
    </div>
  </div>

  

  <div class="nav-section">Menu</div>
  <nav class="nav">
    <?php foreach ($nav as [$href,$label,$icon]): ?>
      <a href="<?= $href ?>" class="<?= $current === $href ? 'active' : '' ?>" data-tip="<?= e($label) ?>">
        <i data-lucide="<?= $icon ?>"></i><span class="brand-text"><?= $label ?></span>
      </a>
    <?php endforeach; ?>
    <a href="logout.php" style="margin-top:auto" data-tip="Keluar">
      <i data-lucide="log-out"></i><span class="brand-text">Keluar</span>
    </a>
  </nav>
</aside>
