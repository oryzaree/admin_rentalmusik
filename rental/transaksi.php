<?php
require_once __DIR__ . '/config/database.php';
require_login();
$page_title = 'Transaksi Rental';
$flash = '';

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'rental') {
      $alat = $pdo->prepare('SELECT * FROM alat_musik WHERE id=? AND status="Tersedia"');
      $alat->execute([(int)$_POST['alat_id']]);
      $row = $alat->fetch();
      if (!$row) throw new Exception('Alat tidak tersedia.');

      $t1 = strtotime($_POST['tgl_pinjam']);
      $t2 = strtotime($_POST['tgl_harus_kembali']);
      $hari = max(1, (int)(($t2 - $t1) / 86400));
      $total = $hari * (int)$row['harga_sewa'];

      $pdo->beginTransaction();
      $stmt = $pdo->prepare('INSERT INTO rental (pelanggan_id,alat_id,tgl_pinjam,tgl_harus_kembali,jaminan,total_sewa) VALUES (?,?,?,?,?,?)');
      $stmt->execute([
        (int)$_POST['pelanggan_id'], (int)$_POST['alat_id'],
        $_POST['tgl_pinjam'], $_POST['tgl_harus_kembali'],
        $_POST['jaminan'], $total
      ]);
      $pdo->prepare('UPDATE alat_musik SET status="Sedang Disewa" WHERE id=?')->execute([(int)$_POST['alat_id']]);
      $pdo->commit();
      $flash = 'Transaksi rental berhasil disimpan. Total: ' . rupiah($total);
    } elseif ($aksi === 'kembali') {
      $id = (int)$_POST['id'];
      $r = $pdo->prepare('SELECT * FROM rental WHERE id=? AND status="Aktif"');
      $r->execute([$id]);
      $row = $r->fetch();
      if ($row) {
        $denda = 0;
        $today = strtotime(date('Y-m-d'));
        $due   = strtotime($row['tgl_harus_kembali']);
        if ($today > $due) {
          $telat = (int)(($today - $due) / 86400);
          $denda = $telat * 15000;
        }
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE rental SET status="Selesai", tgl_kembali=CURDATE(), denda=? WHERE id=?')->execute([$denda, $id]);
        $pdo->prepare('UPDATE alat_musik SET status="Tersedia" WHERE id=?')->execute([(int)$row['alat_id']]);
        $pdo->commit();
        $flash = 'Pengembalian diproses. Denda: ' . rupiah($denda);
      }
    }
    header('Location: transaksi.php?msg=' . urlencode($flash));
    exit;
  }

  $pelanggan = $pdo->query('SELECT id,nama_lengkap FROM pelanggan ORDER BY nama_lengkap')->fetchAll();
  $alatTersedia = $pdo->query('SELECT id,kode_alat,nama_alat,harga_sewa FROM alat_musik WHERE status="Tersedia" ORDER BY nama_alat')->fetchAll();
  $aktif = $pdo->query('
    SELECT r.*, p.nama_lengkap, a.nama_alat, a.kode_alat
    FROM rental r
    JOIN pelanggan p ON p.id = r.pelanggan_id
    JOIN alat_musik a ON a.id = r.alat_id
    WHERE r.status="Aktif"
    ORDER BY r.id DESC
  ')->fetchAll();
} catch (Throwable $e) { $pelanggan=[]; $alatTersedia=[]; $aktif=[]; $flash=$flash ?: $e->getMessage(); }

if (!empty($_GET['msg'])) $flash = $_GET['msg'];

include __DIR__ . '/includes/header.php'; ?>
<div class="app">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<main class="main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content">

  <div class="page-head">
    <div>
      <div class="eyebrow">Operasional</div>
      <h1>Transaksi Rental</h1>
      <p>Buat transaksi sewa baru dan kelola rental yang sedang aktif.</p>
    </div>
    <div class="tabs">
      <button class="active" data-tab="t1" onclick="switchTab('t1',this)">Form Rental Baru</button>
      <button data-tab="t2" onclick="switchTab('t2',this)">Daftar Rental Aktif</button>
    </div>
  </div>

  <?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

  <div id="t1" class="card">
    <div class="card-head"><h3>Form Rental Baru</h3></div>
    <form method="post" id="formRental">
      <input type="hidden" name="aksi" value="rental">
      <div class="form-row">
        <div class="form-row cols-2">
          <div>
            <label>Nama Penyewa</label>
            <select name="pelanggan_id" required>
              <option value="">Pilih pelanggan…</option>
              <?php foreach ($pelanggan as $p): ?>
                <option value="<?= $p['id'] ?>"><?= e($p['nama_lengkap']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>Pilih Alat Musik</label>
            <select name="alat_id" id="alatSel" required>
              <option value="">Hanya menampilkan alat Tersedia…</option>
              <?php foreach ($alatTersedia as $a): ?>
                <option value="<?= $a['id'] ?>" data-harga="<?= $a['harga_sewa'] ?>">
                  [<?= e($a['kode_alat']) ?>] <?= e($a['nama_alat']) ?> · <?= rupiah($a['harga_sewa']) ?>/hari
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row cols-2">
          <div><label>Tanggal Pinjam</label><input type="date" name="tgl_pinjam" id="tgl1" required value="<?= date('Y-m-d') ?>"></div>
          <div><label>Tanggal Harus Kembali</label><input type="date" name="tgl_harus_kembali" id="tgl2" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
        </div>
        <div class="form-row cols-2">
          <div><label>Jaminan</label>
            <select name="jaminan" required>
              <option value="KTP">KTP</option>
              <option value="KTM">KTM</option>
              <option value="SIM">SIM</option>
              <option value="Paspor">Paspor</option>
            </select>
          </div>
          <div>
            <label>Estimasi Total Biaya</label>
            <input type="text" id="totalBiaya" readonly value="Rp 0" style="font-weight:600;color:#fff">
          </div>
        </div>
      </div>
      <div class="modal-actions">
        <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Simpan Transaksi</button>
      </div>
    </form>
  </div>

  <div id="t2" class="card" style="display:none">
    <div class="card-head"><h3>Daftar Rental Aktif</h3></div>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>ID</th><th>Penyewa</th><th>Alat Musik</th><th>Pinjam</th><th>Harus Kembali</th><th>Total</th><th class="text-right">Aksi</th></tr>
        </thead>
        <tbody>
          <?php if (!$aktif): ?>
            <tr><td colspan="7" class="text-center muted" style="padding:24px">Belum ada transaksi aktif.</td></tr>
          <?php endif; foreach ($aktif as $r):
            $late = $r['tgl_harus_kembali'] < date('Y-m-d'); ?>
            <tr>
              <td>TRX-<?= str_pad($r['id'],4,'0',STR_PAD_LEFT) ?></td>
              <td><?= e($r['nama_lengkap']) ?></td>
              <td>[<?= e($r['kode_alat']) ?>] <?= e($r['nama_alat']) ?></td>
              <td><?= date('d M Y', strtotime($r['tgl_pinjam'])) ?></td>
              <td>
                <?= date('d M Y', strtotime($r['tgl_harus_kembali'])) ?>
                <?php if ($late): ?><span class="badge badge-danger" style="margin-left:6px">Terlambat</span><?php endif; ?>
              </td>
              <td><?= rupiah($r['total_sewa']) ?></td>
              <td class="text-right">
                <form method="post" onsubmit="return confirm('Proses pengembalian sekarang?')" style="display:inline">
                  <input type="hidden" name="aksi" value="kembali">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-success" type="submit"><i data-lucide="check"></i> Proses Kembali</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
function switchTab(id, btn){
  document.querySelectorAll('.tabs button').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('t1').style.display = (id==='t1')?'block':'none';
  document.getElementById('t2').style.display = (id==='t2')?'block':'none';
}
function hitungTotal(){
  const sel = document.getElementById('alatSel');
  const harga = parseInt(sel.options[sel.selectedIndex]?.dataset.harga || 0);
  const t1 = new Date(document.getElementById('tgl1').value);
  const t2 = new Date(document.getElementById('tgl2').value);
  let hari = Math.max(1, Math.round((t2 - t1) / 86400000));
  const total = harga * hari;
  document.getElementById('totalBiaya').value =
    'Rp ' + total.toLocaleString('id-ID') + '  (' + hari + ' hari)';
}
['alatSel','tgl1','tgl2'].forEach(id=>document.getElementById(id).addEventListener('change',hitungTotal));
hitungTotal();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
