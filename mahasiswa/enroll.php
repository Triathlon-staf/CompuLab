<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "MAHASISWA") {
  header("Location: ../login/mahasiswa.php");
  exit;
}

require_once __DIR__ . "/../login/config.php";

$user_id = (int)$_SESSION["user_id"];

$flash_success = "";
$flash_error = "";

/* ======================
   DATA MAHASISWA
====================== */

$stmt = $conn->prepare("
  SELECT nama, nim, angkatan
  FROM students
  WHERE user_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$mhs = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mhs) {
  session_destroy();
  header("Location: ../login/mahasiswa.php");
  exit;
}

$namaMahasiswa = $mhs["nama"];
$nimMahasiswa = $mhs["nim"];
$angkatanMahasiswa = (string)$mhs["angkatan"];


/* ======================
   HELPER INFO KELAS
====================== */

function get_kelas_info(mysqli $conn, int $kelas_matkul_id): ?array {
  $stmt = $conn->prepare("
    SELECT
      km.id AS kelas_matkul_id,
      km.kelas,
      mk.id AS mata_kuliah_id,
      mk.nama,
      mk.kode,
      mk.angkatan
    FROM kelas_matkul km
    JOIN mata_kuliah mk ON mk.id = km.mata_kuliah_id
    WHERE km.id = ?
    LIMIT 1
  ");

  $stmt->bind_param("i", $kelas_matkul_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  return $row ?: null;
}


/* ======================
   HANDLE POST
====================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $action = $_POST["action"] ?? "ambil";
  $kelas_matkul_id = (int)($_POST["kelas_matkul_id"] ?? 0);

  if ($kelas_matkul_id <= 0) {
    $flash_error = "Kelas tidak valid.";
  } else {

    $info = get_kelas_info($conn, $kelas_matkul_id);

    if (!$info) {
      $flash_error = "Kelas tidak ditemukan.";
    }

    elseif ((string)$info["angkatan"] !== $angkatanMahasiswa) {
      $flash_error = "Kelas tidak sesuai angkatan.";
    }

    else {

      $mata_kuliah_id = (int)$info["mata_kuliah_id"];

      $stmt = $conn->prepare("
        SELECT e.id, e.kelas_matkul_id
        FROM enrollments e
        JOIN kelas_matkul km2 ON km2.id = e.kelas_matkul_id
        WHERE e.student_user_id = ?
        AND e.status='AKTIF'
        AND km2.mata_kuliah_id = ?
        LIMIT 1
      ");

      $stmt->bind_param("ii",$user_id,$mata_kuliah_id);
      $stmt->execute();
      $alreadyActive = $stmt->get_result()->fetch_assoc();
      $stmt->close();


      /* ===== BATAL ===== */

      if ($action === "batal") {

        $stmt = $conn->prepare("
          SELECT id
          FROM enrollments
          WHERE student_user_id = ?
          AND kelas_matkul_id = ?
          AND status='AKTIF'
        ");

        $stmt->bind_param("ii",$user_id,$kelas_matkul_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
          $flash_error = "Kamu belum mengambil kelas ini.";
        } else {

          $stmt = $conn->prepare("
            UPDATE enrollments
            SET status='DROP'
            WHERE id=?
          ");

          $stmt->bind_param("i",$row["id"]);
          $stmt->execute();
          $stmt->close();

          $flash_success = "Berhasil membatalkan kelas.";
        }

      }

      /* ===== AMBIL ===== */

      else {

        if ($alreadyActive && $alreadyActive["kelas_matkul_id"] != $kelas_matkul_id) {
          $flash_error = "Kamu sudah mengambil kelas lain.";
        }

        else {

          $stmt = $conn->prepare("
            SELECT id,status
            FROM enrollments
            WHERE kelas_matkul_id=? AND student_user_id=?
            LIMIT 1
          ");

          $stmt->bind_param("ii",$kelas_matkul_id,$user_id);
          $stmt->execute();
          $existing = $stmt->get_result()->fetch_assoc();
          $stmt->close();

          if ($existing && $existing["status"] === "AKTIF") {
            $flash_error = "Kelas sudah diambil.";
          }

          else {

            if ($existing) {

              $stmt = $conn->prepare("
                UPDATE enrollments
                SET status='AKTIF'
                WHERE id=?
              ");

              $stmt->bind_param("i",$existing["id"]);
              $stmt->execute();
              $stmt->close();

              $flash_success = "Kelas diaktifkan kembali.";

            }

            else {

              $stmt = $conn->prepare("
                INSERT INTO enrollments
                (kelas_matkul_id,student_user_id,status)
                VALUES (?,?,'AKTIF')
              ");

              $stmt->bind_param("ii",$kelas_matkul_id,$user_id);
              $stmt->execute();
              $stmt->close();

              $flash_success = "Matkul berhasil diambil.";
            }

          }

        }

      }

    }

  }

}


/* ======================
   AMBIL DATA MATKUL
====================== */

$stmt = $conn->prepare("
SELECT
km.id AS kelas_matkul_id,
mk.id AS mata_kuliah_id,
mk.kode,
mk.nama,
km.kelas,
e.status AS enroll_status
FROM kelas_matkul km
JOIN mata_kuliah mk ON mk.id=km.mata_kuliah_id
LEFT JOIN enrollments e
ON e.kelas_matkul_id=km.id
AND e.student_user_id=?
WHERE mk.angkatan=?
ORDER BY mk.nama,km.kelas
");

$stmt->bind_param("is",$user_id,$angkatanMahasiswa);
$stmt->execute();
$res = $stmt->get_result();

$rows=[];
while($r=$res->fetch_assoc()) $rows[]=$r;

$stmt->close();


$activeByMk=[];
foreach($rows as $r){
if(($r["enroll_status"]??"")==="AKTIF"){
$activeByMk[(int)$r["mata_kuliah_id"]] = (int)$r["kelas_matkul_id"];
}
}
?>


<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>KRS | COMPULAB</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/dashboard-mahasiswa.css">

</head>
<body>


<div class="overlay" onclick="toggleSidebar()"></div>


<div class="sidebar">

<div class="logo">
<span>COMPULAB</span>
<small>Student Panel</small>
</div>

<nav>

<a href="dashboard.php">
<span class="icon">🏠</span>
<span class="text">Dashboard</span>
</a>

<a class="active" href="enroll.php">
<span class="icon">🧾</span>
<span class="text">KRS / Enroll</span>
</a>

<a href="materi.php">
<span class="icon">📚</span>
<span class="text">Materi</span>
</a>

<a href="kuis.php">
<span class="icon">📝</span>
<span class="text">Kuis</span>
</a>

<a href="scoreboard.php">
<span class="icon">🏆</span>
<span class="text">Scoreboard</span>
</a>

<a href="profil.php">
<span class="icon">👤</span>
<span class="text">Profil</span>
</a>

<div class="nav-divider"></div>

<a class="logout" href="../login/logout.php">
<span class="icon">🚪</span>
<span class="text">Logout</span>
</a>

</nav>

</div>


<div class="main">


<div class="topbar">

<div class="menu-toggle" onclick="toggleSidebar()">☰</div>
<h1>KRS / Enroll</h1>
<div class="profile-mini">🎓</div>

</div>


<div class="profile-info">

<div class="info-item">
<span>Nama</span>
<strong><?= htmlspecialchars($namaMahasiswa) ?></strong>
</div>

<div class="info-item">
<span>NIM</span>
<strong><?= htmlspecialchars($nimMahasiswa) ?></strong>
</div>

<div class="info-item">
<span>Angkatan</span>
<strong><?= htmlspecialchars($angkatanMahasiswa) ?></strong>
</div>

</div>


<?php if($flash_success): ?>
<div class="card" style="background:#d7ffe1;color:#0b6b2a;">
<?= htmlspecialchars($flash_success) ?>
</div>
<?php endif; ?>

<?php if($flash_error): ?>
<div class="card" style="background:#ffd7d7;color:#7a0000;">
<?= htmlspecialchars($flash_error) ?>
</div>
<?php endif; ?>


<div class="card large">

<h3>Daftar Matkul Angkatan <?= htmlspecialchars($angkatanMahasiswa) ?></h3>

<table class="krs-table">

<thead>
<tr>
<th>Kode</th>
<th>Mata Kuliah</th>
<th>Kelas</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php foreach($rows as $r): ?>

<?php

$mkId = (int)$r["mata_kuliah_id"];
$kelasId = (int)$r["kelas_matkul_id"];
$status = $r["enroll_status"] ?? "";

$taken = ($status === "AKTIF");
$locked = isset($activeByMk[$mkId]) && $activeByMk[$mkId] !== $kelasId;

?>

<tr>

<td><?= htmlspecialchars($r["kode"]) ?></td>
<td><?= htmlspecialchars($r["nama"]) ?></td>

<td>
<span class="krs-badge"><?= htmlspecialchars($r["kelas"]) ?></span>
</td>

<td>

<?php if($taken): ?>
<span class="krs-badge on">Sudah diambil</span>

<?php elseif($locked): ?>
<span class="krs-badge">Terkunci</span>

<?php else: ?>
<span class="krs-badge">Belum</span>
<?php endif; ?>

</td>

<td>

<?php if($taken): ?>

<form method="POST">
<input type="hidden" name="action" value="batal">
<input type="hidden" name="kelas_matkul_id" value="<?= $kelasId ?>">
<button class="btn btn-danger">Batal</button>
</form>

<?php elseif($locked): ?>

<button class="btn btn-disabled" disabled>Sudah pilih kelas lain</button>

<?php else: ?>

<form method="POST">
<input type="hidden" name="action" value="ambil">
<input type="hidden" name="kelas_matkul_id" value="<?= $kelasId ?>">
<button class="btn btn-primary">Ambil</button>
</form>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

</div>


<script>

function toggleSidebar(){
document.querySelector('.sidebar').classList.toggle('active');
document.querySelector('.overlay').classList.toggle('active');
}

</script>

</body>
</html>
