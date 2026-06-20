<?php
require_once __DIR__ . '/config/database.php';
require_login();
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$out = ['pelanggan'=>[], 'alat'=>[], 'transaksi'=>[]];
if ($q === '') { echo json_encode($out); exit; }

$like = '%' . $q . '%';
try {
  $s = $pdo->prepare("SELECT id,nama_lengkap,no_wa FROM pelanggan
      WHERE nama_lengkap LIKE ? OR no_wa LIKE ? OR no_identitas LIKE ? LIMIT 5");
  $s->execute([$like,$like,$like]);
  $out['pelanggan'] = $s->fetchAll();

  $s = $pdo->prepare("SELECT id,nama_alat,kategori,status FROM alat_musik
      WHERE nama_alat LIKE ? OR kategori LIKE ? LIMIT 5");
  $s->execute([$like,$like]);
  $out['alat'] = $s->fetchAll();

  $s = $pdo->prepare("SELECT r.id, p.nama_lengkap, a.nama_alat, r.status
      FROM rental r JOIN pelanggan p ON p.id=r.pelanggan_id
      JOIN alat_musik a ON a.id=r.alat_id
      WHERE p.nama_lengkap LIKE ? OR a.nama_alat LIKE ? OR r.status LIKE ?
      ORDER BY r.id DESC LIMIT 5");
  $s->execute([$like,$like,$like]);
  $out['transaksi'] = $s->fetchAll();
} catch (Throwable $e) {}
echo json_encode($out);
