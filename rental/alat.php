<?php
require_once __DIR__ . '/config/database.php';
require_login();
$page_title = 'Data Alat Musik';
$flash = '';
$kategoriList = ['Gitar','Bass','Drum','Keyboard','Sound System'];

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah') {
      $stmt = $pdo->prepare('INSERT INTO alat_musik (kode_alat,nama_alat,kategori,harga_sewa,status) VALUES (?,?,?,?,?)');
      $stmt->execute([$_POST['kode_alat'],$_POST['nama_alat'],$_POST['kategori'],(int)$_POST['harga_sewa'],$_POST['status'] ?? 'Tersedia']);
      $flash = 'Alat musik berhasil ditambahkan.';
    } elseif ($aksi === 'edit') {
      $stmt = $pdo->prepare('UPDATE alat_musik SET kode_alat=?,nama_alat=?,kategori=?,harga_sewa=?,status=? WHERE id=?');
      $stmt->execute([$_POST['kode_alat'],$_POST['nama_alat'],$_POST['kategori'],(int)$_POST['harga_sewa'],$_POST['status'],(int)$_POST['id']]);
      $flash = 'Data alat musik berhasil diperbarui.';
    } elseif ($aksi === 'hapus') {
      $stmt = $pdo->prepare('DELETE FROM alat_musik WHERE id=?');
      $stmt->execute([(int)$_POST['id']]);
      $flash = 'Alat musik berhasil dihapus.';
    }
    header('Location: alat.php?msg=' . urlencode($flash));
    exit;
  }
  $data = $pdo->query('SELECT * FROM alat_musik ORDER BY id DESC')->fetchAll();
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
      <div class="eyebrow">Inventaris</div>
      <h1>Data Alat Musik</h1>
      <p>Kelola inventaris alat musik beserta status ketersediaannya.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')">
      <i data-lucide="plus"></i> Tambah Alat
    </button>
  </div>

  <?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

  <div class="card">
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr>
            <th>Kode Alat</th><th>Nama Alat</th><th>Kategori</th>
            <th>Harga Sewa / Hari</th><th>Status</th><th class="text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$data): ?>
            <tr><td colspan="6" class="text-center muted" style="padding:24px">Belum ada data alat musik.</td></tr>
          <?php endif; foreach ($data as $a): ?>
            <tr>
              <td><strong><?= e($a['kode_alat']) ?></strong></td>
              <td><?= e($a['nama_alat']) ?></td>
              <td><?= e($a['kategori']) ?></td>
              <td><?= rupiah($a['harga_sewa']) ?></td>
              <td>
                <?php if ($a['status']==='Tersedia'): ?>
                  <span class="badge badge-success">Tersedia</span>
                <?php else: ?>
                  <span class="badge badge-warning">Sedang Disewa</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="actions-cell" style="justify-content:flex-end">
                  <button class="btn btn-sm" onclick='openEdit(<?= json_encode($a, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                    <i data-lucide="pencil"></i> Edit
                  </button>
                  <form method="post" onsubmit="return confirm('Hapus alat ini?')" style="display:inline">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
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

<div class="modal-bg" id="modalTambah">
  <div class="modal">
    <h2>Tambah Alat Musik</h2>
    <p class="sub">Tambahkan unit baru ke dalam inventaris.</p>
    <form method="post">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-row cols-2">
          <div><label>Kode Alat</label><input name="kode_alat" required></div>
          <div><label>Kategori</label>
            <select name="kategori" required>
              <?php foreach ($kategoriList as $k): ?><option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div><label>Nama Alat</label><input name="nama_alat" required></div>
        <div class="form-row cols-2">
          <div><label>Harga Sewa / Hari (Rp)</label><input type="number" min="0" name="harga_sewa" required></div>
          <div><label>Status</label>
            <select name="status">
              <option value="Tersedia">Tersedia</option>
              <option value="Sedang Disewa">Sedang Disewa</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeModal('modalTambah')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-bg" id="modalEdit">
  <div class="modal">
    <h2>Edit Alat Musik</h2>
    <p class="sub">Perbarui detail alat musik terpilih.</p>
    <form method="post">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id" id="e_id">
      <div class="form-row">
        <div class="form-row cols-2">
          <div><label>Kode Alat</label><input name="kode_alat" id="e_kode" required></div>
          <div><label>Kategori</label>
            <select name="kategori" id="e_kat" required>
              <?php foreach ($kategoriList as $k): ?><option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div><label>Nama Alat</label><input name="nama_alat" id="e_nama" required></div>
        <div class="form-row cols-2">
          <div><label>Harga Sewa / Hari</label><input type="number" min="0" name="harga_sewa" id="e_harga" required></div>
          <div><label>Status</label>
            <select name="status" id="e_status">
              <option value="Tersedia">Tersedia</option>
              <option value="Sedang Disewa">Sedang Disewa</option>
            </select>
          </div>
        </div>
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
function openEdit(a){
  e_id.value=a.id; e_kode.value=a.kode_alat; e_nama.value=a.nama_alat;
  e_kat.value=a.kategori; e_harga.value=a.harga_sewa; e_status.value=a.status;
  openModal('modalEdit');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
