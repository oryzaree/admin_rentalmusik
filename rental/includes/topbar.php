<?php
// Build notifications from rental data
$notifs = [];
try {
  $q = $pdo->query("
    SELECT r.id, r.status, r.tgl_harus_kembali, r.created_at,
           p.nama_lengkap, a.nama_alat
    FROM rental r
    JOIN pelanggan p ON p.id = r.pelanggan_id
    JOIN alat_musik a ON a.id = r.alat_id
    ORDER BY r.created_at DESC LIMIT 8");
  foreach ($q->fetchAll() as $r) {
    $late = ($r['status']==='Aktif' && $r['tgl_harus_kembali'] < date('Y-m-d'));
    if ($late) {
      $notifs[] = ['icon'=>'alert-triangle','tone'=>'danger',
        'title'=>'Pengembalian Terlambat',
        'msg'=>$r['nama_alat'].' — '.$r['nama_lengkap'],
        'time'=>$r['created_at'],'href'=>'laporan.php'];
    } elseif ($r['status']==='Selesai') {
      $notifs[] = ['icon'=>'check-circle','tone'=>'success',
        'title'=>'Pengembalian Selesai',
        'msg'=>$r['nama_alat'].' dikembalikan oleh '.$r['nama_lengkap'],
        'time'=>$r['created_at'],'href'=>'laporan.php'];
    } else {
      $notifs[] = ['icon'=>'clipboard-list','tone'=>'info',
        'title'=>'Transaksi Baru',
        'msg'=>$r['nama_alat'].' disewa oleh '.$r['nama_lengkap'],
        'time'=>$r['created_at'],'href'=>'transaksi.php'];
    }
  }
} catch (Throwable $e) {}
$unread = count($notifs);
?>
<header class="topbar">
  <button class="icon-btn" type="button" id="btnSidebarToggle" aria-label="Menu">
    <i data-lucide="menu"></i>
  </button>
  <div class="search" id="searchBox">
    <i data-lucide="search"></i>
    <input type="text" id="searchInput" placeholder="Cari pelanggan, alat musik, transaksi…" autocomplete="off">
    <div class="search-results" id="searchResults"></div>
  </div>
  <div class="top-actions">
    <button class="icon-btn" type="button" id="btnTheme" aria-label="Ganti tema">
      <i data-lucide="sun" data-theme-icon="dark"></i>
      <i data-lucide="moon" data-theme-icon="light" style="display:none"></i>
    </button>

    <div class="dd" id="ddNotif">
      <button class="icon-btn dd-trigger" type="button" aria-label="Notifikasi">
        <i data-lucide="bell"></i>
        <?php if ($unread): ?><span class="badge-dot"></span><?php endif; ?>
      </button>
      <div class="dd-menu dd-menu-wide">
        <div class="dd-head">
          <strong>Notifikasi</strong>
          <span class="muted"><?= $unread ?> baru</span>
        </div>
        <div class="dd-list">
          <?php if (!$notifs): ?>
            <div class="dd-empty">Tidak ada notifikasi.</div>
          <?php endif; foreach ($notifs as $n): ?>
            <a class="dd-item notif-item tone-<?= $n['tone'] ?>" href="<?= e($n['href']) ?>">
              <span class="notif-ico"><i data-lucide="<?= $n['icon'] ?>"></i></span>
              <span class="notif-body">
                <strong><?= e($n['title']) ?></strong>
                <span><?= e($n['msg']) ?></span>
                <time><?= date('d M Y H:i', strtotime($n['time'])) ?></time>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="dd" id="ddProfile">
      <button class="user-chip dd-trigger" type="button">
        <div class="avatar">K14</div>
        <strong><?= e($_SESSION['user']['nama'] ?? 'Admin Kelompok 14') ?></strong>
        <i data-lucide="chevron-down" style="width:14px;height:14px;color:#94a3b8"></i>
      </button>
      <div class="dd-menu">
        <a class="dd-item" href="profile.php"><i data-lucide="user"></i> Setting Profiles</a>
        <a class="dd-item" href="profile.php?tab=account"><i data-lucide="settings"></i> Account Setting</a>
        <div class="dd-sep"></div>
        <a class="dd-item danger" href="logout.php"><i data-lucide="log-out"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>
