<?php
require_once __DIR__ . '/config/database.php';
require_login();
$page_title = 'Data Pelanggan';
$flash = '';

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah') {
      $stmt = $pdo->prepare('INSERT INTO pelanggan (nama_lengkap,no_wa,alamat,no_identitas) VALUES (?,?,?,?)');
      $stmt->execute([$_POST['nama_lengkap'],$_POST['no_wa'],$_POST['alamat'],$_POST['no_identitas']]);
      $flash = 'Pelanggan berhasil ditambahkan.';
    } elseif ($aksi === 'edit') {
      $stmt = $pdo->prepare('UPDATE pelanggan SET nama_lengkap=?,no_wa=?,alamat=?,no_identitas=? WHERE id=?');
      $stmt->execute([$_POST['nama_lengkap'],$_POST['no_wa'],$_POST['alamat'],$_POST['no_identitas'],(int)$_POST['id']]);
      $flash = 'Data pelanggan berhasil diperbarui.';
    } elseif ($aksi === 'hapus') {
      $stmt = $pdo->prepare('DELETE FROM pelanggan WHERE id=?');
      $stmt->execute([(int)$_POST['id']]);
      $flash = 'Pelanggan berhasil dihapus.';
    }
    header('Location: pelanggan.php?msg=' . urlencode($flash));
    exit;
  }
  $data = $pdo->query('SELECT * FROM pelanggan ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) { $data = []; }

if (!empty($_GET['msg'])) $flash = $_GET['msg'];

include __DIR__ . '/includes/header.php'; ?>
<div class="app">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content">

  <div class="page-head">
    <div>
      <div class="eyebrow">Manajemen</div>
      <h1>Data Pelanggan</h1>
      <p>Kelola informasi pelanggan rental alat musik.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')">
      <i data-lucide="plus"></i> Tambah Pelanggan
    </button>
  </div>

  <?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>ID Pelanggan</th><th>Nama Lengkap</th><th>No. WhatsApp</th>
            <th>Alamat</th><th>No. Identitas</th><th class="text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$data): ?>
            <tr><td colspan="6" class="text-center muted" style="padding:24px">Belum ada data pelanggan.</td></tr>
          <?php endif; foreach ($data as $p): ?>
            <tr>
              <td>PLG-<?= str_pad($p['id'],4,'0',STR_PAD_LEFT) ?></td>
              <td><?= e($p['nama_lengkap']) ?></td>
              <td><?= e($p['no_wa']) ?></td>
              <td><?= e($p['alamat']) ?></td>
              <td><?= e($p['no_identitas']) ?></td>
              <td>
                <div class="actions-cell" style="justify-content:flex-end">
                  <button class="btn btn-sm" onclick='openEdit(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                    <i data-lucide="pencil"></i> Edit
                  </button>
                  <form method="post" onsubmit="return confirm('Hapus pelanggan ini?')" style="display:inline">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit"><i data-lucide="trash-2"></i> Hapus</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- Modal Tambah -->
<div class="modal-bg" id="modalTambah">
  <div class="modal">
    <h2>Tambah Pelanggan</h2>
    <p class="sub">Lengkapi informasi pelanggan baru di bawah ini.</p>
    <form method="post">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div><label>Nama Lengkap</label><input name="nama_lengkap" required></div>
        <div class="form-row cols-2">
          <div><label>No. WhatsApp</label><input name="no_wa" required></div>
          <div><label>No. Identitas (KTP/KTM)</label><input name="no_identitas" required></div>
        </div>
        <div><label>Alamat</label><textarea name="alamat" required></textarea></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modalTambah')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal-bg" id="modalEdit">
  <div class="modal">
    <h2>Edit Pelanggan</h2>
    <p class="sub">Perbarui informasi pelanggan terpilih.</p>
    <form method="post">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id" id="e_id">
      <div class="form-row">
        <div><label>Nama Lengkap</label><input name="nama_lengkap" id="e_nama" required></div>
        <div class="form-row cols-2">
          <div><label>No. WhatsApp</label><input name="no_wa" id="e_wa" required></div>
          <div><label>No. Identitas</label><input name="no_identitas" id="e_idn" required></div>
        </div>
        <div><label>Alamat</label><textarea name="alamat" id="e_alamat" required></textarea></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
function openEdit(p){
  e_id.value=p.id; e_nama.value=p.nama_lengkap; e_wa.value=p.no_wa;
  e_idn.value=p.no_identitas; e_alamat.value=p.alamat;
  openModal('modalEdit');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
