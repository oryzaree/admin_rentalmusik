<?php
require_once __DIR__ . '/config/database.php';
require_login();
$page_title = 'Profile';
$tab = $_GET['tab'] ?? 'profile';
include __DIR__ . '/includes/header.php'; ?>
<div class="app">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content">
  <div class="page-head">
    <div>
      <div class="eyebrow">Pengaturan</div>
      <h1><?= $tab==='account' ? 'Account Setting' : 'Setting Profiles' ?></h1>
      <p>Kelola informasi akun administrator Kelompok 14.</p>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3><?= $tab==='account' ? 'Keamanan Akun' : 'Profil Pengguna' ?></h3></div>
    <?php if ($tab==='account'): ?>
      <form class="form-row cols-2" onsubmit="event.preventDefault();alert('Pengaturan akun disimpan.')">
        <div><label>Username</label><input type="text" value="<?= e($_SESSION['user']['username'] ?? 'admin') ?>"></div>
        <div><label>Email</label><input type="email" placeholder="admin@kelompok14.id"></div>
        <div><label>Password Lama</label><input type="password"></div>
        <div><label>Password Baru</label><input type="password"></div>
        <div style="grid-column:1/-1"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Simpan Perubahan</button></div>
      </form>
    <?php else: ?>
      <form class="form-row cols-2" onsubmit="event.preventDefault();alert('Profil disimpan.')">
        <div><label>Nama Lengkap</label><input type="text" value="<?= e($_SESSION['user']['nama'] ?? 'Admin Kelompok 14') ?>"></div>
        <div><label>Jabatan</label><input type="text" value="Administrator"></div>
        <div><label>No. WhatsApp</label><input type="text" placeholder="08xx"></div>
        <div><label>Lokasi</label><input type="text" placeholder="Studio Kelompok 14"></div>
        <div style="grid-column:1/-1"><label>Bio Singkat</label><textarea rows="3" placeholder="Tentang Anda"></textarea></div>
        <div style="grid-column:1/-1"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Simpan Profil</button></div>
      </form>
    <?php endif; ?>
  </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
