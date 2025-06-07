<?php
require_once __DIR__ . '/vendor/autoload.php'; // mPDF dan PHPMailer dari Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fungsi format tanggal Indonesia
function tanggal_indonesia($tanggal) {
    $bulan_map = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('j', strtotime($tanggal));
    $bln = $bulan_map[(int)date('n', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return "$tgl $bln $thn";
}

// Fungsi untuk mendapatkan nomor urut surat berikutnya
// Dimodifikasi agar nomor tetap jika nama pasien sama dalam TAHUN yang sama
// dan reset nomor per TAHUN
function getNextNomorSurat($pasien_nama_sekarang, &$nomor_urut_display, &$tahun_display, &$bagian_tengah_surat_display) {
    $counter_file = __DIR__ . '/nomor_surat_counter.json';
    $current_year = (int)date('Y');
    // $current_month = (int)date('n'); // Tidak lagi digunakan untuk reset utama, tapi disimpan di JSON
    $pasien_nama_sekarang = trim(strtolower($pasien_nama_sekarang)); // Normalisasi nama pasien

    $bagian_tengah_surat_konstan = "RS"; // Bagian tengah surat sekarang "RS"

    $nomor_urut_raw_untuk_simpan = 1; // Nomor urut mentah (integer) untuk perhitungan dan penyimpanan
    $nomor_urut_formatted_untuk_pakai = "001"; // Nomor urut yang diformat untuk dipakai di surat
    $tahun_untuk_pakai = $current_year;
    $bagian_tengah_untuk_pakai = $bagian_tengah_surat_konstan;

    $fp = fopen($counter_file, 'c+');
    if (!$fp) {
        error_log("Tidak dapat membuka file counter: " . $counter_file);
        // Jika file tidak bisa dibuka, gunakan default
        $nomor_urut_display = $nomor_urut_formatted_untuk_pakai;
        $tahun_display = $tahun_untuk_pakai;
        $bagian_tengah_surat_display = $bagian_tengah_untuk_pakai;
        return "460 / {$nomor_urut_formatted_untuk_pakai} / {$bagian_tengah_untuk_pakai} / 412.206 / {$tahun_untuk_pakai}";
    }

    if (flock($fp, LOCK_EX)) {
        $content = stream_get_contents($fp);
        $data = json_decode($content, true);

        if (is_array($data) &&
            isset($data['last_number_raw']) &&
            isset($data['year']) &&
            // isset($data['month']) && // month tidak lagi krusial untuk reset utama
            isset($data['last_patient_name']) &&
            isset($data['last_nomor_urut_formatted']) &&
            isset($data['last_bagian_tengah_surat']) && // Dulu last_bulan_roman
            isset($data['last_tahun_surat'])) {

            // Cek apakah pasien sama dan masih dalam TAHUN yang sama
            if (strtolower(trim($data['last_patient_name'])) === $pasien_nama_sekarang &&
                $data['year'] == $current_year) {
                // Pasien sama, TAHUN sama: Gunakan nomor yang tersimpan
                $nomor_urut_raw_untuk_simpan = $data['last_number_raw'];
                $nomor_urut_formatted_untuk_pakai = $data['last_nomor_urut_formatted'];
                $bagian_tengah_untuk_pakai = $data['last_bagian_tengah_surat']; // Seharusnya "RS"
                $tahun_untuk_pakai = $data['last_tahun_surat'];
            } elseif ($data['year'] == $current_year) {
                // TAHUN sama, tapi pasien berbeda: Increment nomor
                $nomor_urut_raw_untuk_simpan = $data['last_number_raw'] + 1;
                $nomor_urut_formatted_untuk_pakai = sprintf("%03d", $nomor_urut_raw_untuk_simpan);
                $bagian_tengah_untuk_pakai = $bagian_tengah_surat_konstan; // Pastikan "RS"
                // tahun_untuk_pakai sudah default ke current_year
            } else {
                // TAHUN berbeda: Reset nomor ke 1 (sudah default di atas)
                // nomor_urut_raw_untuk_simpan sudah 1
                // nomor_urut_formatted_untuk_pakai sudah "001"
                $bagian_tengah_untuk_pakai = $bagian_tengah_surat_konstan; // Pastikan "RS"
            }
        }
        // Jika $data tidak valid atau kondisi di atas tidak ada yang cocok,
        // maka akan menggunakan nilai default (nomor 1 untuk tahun ini)

        // Data yang disimpan ke file
        $new_data = [
            'last_number_raw' => $nomor_urut_raw_untuk_simpan,
            'year' => $current_year, // Tahun saat ini (untuk reset tahunan)
            'month' => (int)date('n'), // Bulan saat ini (disimpan untuk info, tidak untuk reset utama)
            'last_patient_name' => $pasien_nama_sekarang,
            'last_nomor_urut_formatted' => $nomor_urut_formatted_untuk_pakai,
            'last_bagian_tengah_surat' => $bagian_tengah_untuk_pakai, // Menyimpan "RS"
            'last_tahun_surat' => $tahun_untuk_pakai
        ];

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($new_data));
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        error_log("Tidak dapat mendapatkan lock pada file counter: " . $counter_file);
    }
    fclose($fp);

    $nomor_urut_display = $nomor_urut_formatted_untuk_pakai;
    $tahun_display = $tahun_untuk_pakai;
    $bagian_tengah_surat_display = $bagian_tengah_untuk_pakai;

    return "460 / {$nomor_urut_formatted_untuk_pakai} / {$bagian_tengah_untuk_pakai} / 412.206 / {$tahun_untuk_pakai}";
}

// Fungsi untuk memeriksa apakah email harus dikirim
function shouldSendEmail($pasien_nama_current, $nomor_surat_current, $current_date_iso, &$last_email_log_file) {
    if (!file_exists($last_email_log_file)) {
        return true; // File log tidak ada, jadi kirim email
    }

    $fp = fopen($last_email_log_file, 'r');
    if (!$fp) {
        error_log("Tidak dapat membuka file log email: " . $last_email_log_file);
        return true; // Anggap harus kirim jika tidak bisa baca log
    }

    $content = stream_get_contents($fp);
    fclose($fp);
    $log_data = json_decode($content, true);

    if (is_array($log_data) && 
        isset($log_data['last_sent_patient_name']) && 
        isset($log_data['last_sent_nomor_surat']) && 
        isset($log_data['last_sent_date'])) {
        
        if (strtolower(trim($log_data['last_sent_patient_name'])) === strtolower(trim($pasien_nama_current)) &&
            $log_data['last_sent_nomor_surat'] === $nomor_surat_current && 
            $log_data['last_sent_date'] === $current_date_iso) {
            return false; // Nama pasien, nomor surat, dan tanggal sama, jangan kirim
        }
    }
    return true; // Kondisi tidak terpenuhi atau log tidak valid, kirim email
}

// Fungsi untuk mencatat email yang telah dikirim
function logSentEmail($pasien_nama_current, $nomor_surat_current, $current_date_iso, &$last_email_log_file) {
    $data_to_log = [
        'last_sent_patient_name' => trim($pasien_nama_current),
        'last_sent_nomor_surat' => $nomor_surat_current,
        'last_sent_date' => $current_date_iso
    ];
    $fp = fopen($last_email_log_file, 'w'); 
    if ($fp) {
        if (flock($fp, LOCK_EX)) {
            fwrite($fp, json_encode($data_to_log));
            fflush($fp);
            flock($fp, LOCK_UN);
        } else {
            error_log("Tidak dapat mendapatkan lock pada file log email: " . $last_email_log_file . " saat menulis.");
        }
        fclose($fp);
    } else {
        error_log("Tidak dapat membuka file log email untuk ditulis: " . $last_email_log_file);
    }
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Metode permintaan tidak valid.";
    exit;
}

// Ambil data dari POST
$pendamping_nama = isset($_POST['pendamping_nama']) && trim($_POST['pendamping_nama']) !== '' ? $_POST['pendamping_nama'] : 'Tidak Ada';
$pendamping_nik = isset($_POST['pendamping_nik']) && trim($_POST['pendamping_nik']) !== '' ? $_POST['pendamping_nik'] : 'Tidak Ada';
$pendamping_alamat = isset($_POST['pendamping_alamat']) && trim($_POST['pendamping_alamat']) !== '' ? $_POST['pendamping_alamat'] : 'Tidak Ada';
$pendamping_hubungan = isset($_POST['pendamping_hubungan']) && trim($_POST['pendamping_hubungan']) !== '' ? $_POST['pendamping_hubungan'] : 'Tidak Ada';

$rujukan_dari = $_POST['rujukan_dari'];

$pasien_nama = $_POST['pasien_nama']; 
$pasien_nik = $_POST['pasien_nik'];
$pasien_alamat = $_POST['pasien_alamat'];

$current_date_iso = date('Y-m-d'); 
$tanggal_surat = tanggal_indonesia($current_date_iso); 

$nomor_urut_surat_saja = ''; 
$tahun_surat_digunakan = '';
$bagian_tengah_surat = ''; // Dulu $bulan_surat_romawi
// Panggil getNextNomorSurat dengan nama pasien
$nomor_surat_lengkap = getNextNomorSurat($pasien_nama, $nomor_urut_surat_saja, $tahun_surat_digunakan, $bagian_tengah_surat);

// HTML isi surat (SAMA SEPERTI SEBELUMNYA, hanya nomor surat yang terpengaruh)
$html = '
<html>
<head>
  <style>
    body { font-family: "Times New Roman", Times, serif; font-size: 12pt; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .mt-3 { margin-top: 20px; }
  </style>
</head>
<body>
  <table width="100%">
    <tr>
      <td width="10%" valign="top">
        <img src="galery/logo.png" height="80">
      </td>
      <td width="90%" align="center">
        <div style="text-align: center; line-height: 1.5;">
          <span class="bold" style="font-size: 14pt;">PEMERINTAH KABUPATEN BOJONEGORO</span><br>
          <span class="bold" style="font-size: 13pt;">DINAS SOSIAL</span><br>
          Jl. Dr. Wahidin Nomor.40, Telp. 0353 - 888918<br>
          <span class="bold">BOJONEGORO 6211</span>
        </div>
      </td>
    </tr>
  </table>
  <hr class="mt-3">

  <div class="center mt-3">
    SURAT REKOMENDASI BAGI PENERIMA LAYANAN<br>
    RUMAH SINGGAH DINAS SOSIAL KABUPATEN BOJONEGORO DI SURABAYA<br>
    Nomor: ' . htmlspecialchars($nomor_surat_lengkap) . '
  </div>
  
  <p>Yang bertanda tangan di bawah ini:</p>
  <table>
    <tr><td>Nama</td><td>:</td><td>EKA PUSPITASARI, S.Sos</td></tr>
    <tr><td>NIP</td><td>:</td><td>19861001 201101 2 017</td></tr>
    <tr><td>Jabatan</td><td>:</td><td>Kabid Pelayanan dan Rehabilitasi Sosial Kabupaten Bojonegoro</td></tr>
  </table>

  <p>Memberikan rekomendasi kepada:</p>
  <table>
    <tr><td>Nama</td><td>:</td><td>' . htmlspecialchars($pendamping_nama) . '</td></tr>
    <tr><td>NIK</td><td>:</td><td>' . htmlspecialchars($pendamping_nik) . '</td></tr>
    <tr><td>Alamat</td><td>:</td><td>' . nl2br(htmlspecialchars($pendamping_alamat)) . '</td></tr>
    <tr><td>Hubungan</td><td>:</td><td>' . htmlspecialchars($pendamping_hubungan) . '</td></tr>
  </table>

  <p style="margin-top: 10;">Untuk mendampingi penderita rujukan dari RSUD Bojonegoro/Puskesmas: ' . htmlspecialchars($rujukan_dari) . '</p>

  <table>
    <tr><td>Nama</td><td>:</td><td>' . htmlspecialchars($pasien_nama) . '</td></tr>
    <tr><td>NIK</td><td>:</td><td>' . htmlspecialchars($pasien_nik) . '</td></tr>
    <tr><td>Alamat</td><td>:</td><td>' . nl2br(htmlspecialchars($pasien_alamat)) . '</td></tr>
  </table>

  <p style="margin-top: 6; margin-bottom: 10;"><b>Di Rumah Singgah Jalan Gubeng Kertajaya VII F Nomor 3 Surabaya</b></p>

  <p style="margin-top: 2; margin-bottom: 4;">Demikian surat rekomendasi ini diberikan kepada yang bersangkutan untuk mendapatkan layanan sebagaimana mestinya.</p>
<p style="margin-top: 0;">Untuk memulai layanan di Rumah Singgah silahkan hubungi petugas di Rumah Singgah <b>Hubungi : 0896-7680-1211 (YOYOK)</b></p>
  
  <div style="margin-left: 60%; text-align: center;">
    <p>Bojonegoro, ' . $tanggal_surat . '</p>
    <p style="margin-bottom: -35;">An. KEPALA DINAS SOSIAL<br>
    KABUPATEN BOJONEGORO<br>
    Kabid Pelayanan dan Rehabsos</p>

    <img src="galery/tanda_tangan.png"
         alt="Tanda Tangan Eka Puspitasari"
         style="width: 300px; height: auto; margin-top: 5px; margin-bottom: 0px;">

    <p style="margin-top: -10; margin-bottom: 0;"><b><u>EKA PUSPITASARI, S.Sos</u></b></p>
    <p style="margin-top: 0; margin-bottom: 0;">Penata Tingkat I</p>
    <p style="margin-top: 0;">NIP. 19861001 201101 2 017</p>

  </div>
</body>
</html>
';

// Buat instance mPDF
$mpdf = new \Mpdf\Mpdf([
    'format' => [210, 330] // Ukuran F4 dalam mm
]);

$mpdf->WriteHTML($html);

$pdf_filename = 'surat_rekomendasi_rs_' . str_replace(['/', ' '], '_', $nomor_surat_lengkap) . '_' . preg_replace("/[^a-zA-Z0-9]/", "_", $pasien_nama) . '.pdf';
$pdf_content_string = $mpdf->Output(null, \Mpdf\Output\Destination::STRING_RETURN);

$email_sent_message = "";
$last_email_log_file_path = __DIR__ . '/last_email_sent_log.json';

if (shouldSendEmail($pasien_nama, $nomor_surat_lengkap, $current_date_iso, $last_email_log_file_path)) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rehabsospelayanan@gmail.com'; 
        $mail->Password   = 'vnpf xvmk ynxy hqhj'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('rehabsospelayanan@gmail.com', 'Sistem Rumah Singgah');
        $mail->addAddress('alfiakbartaufiqi2901@gmail.com', 'Dinas Sosial Bojonegoro'); 

        $mail->Subject = 'Surat Rekomendasi Rumah Singgah No: ' . $nomor_surat_lengkap . ' untuk ' . $pasien_nama;
        $mail->Body    = 'Terlampir surat rekomendasi rumah singgah yang baru saja diisi melalui sistem dengan nomor ' . $nomor_surat_lengkap . '.';
        $mail->Body   .= "\n\nDetail Pemohon:\n";
        $mail->Body   .= "Nama Pasien: " . $pasien_nama . "\n";
        $mail->Body   .= "NIK Pasien: " . $pasien_nik . "\n";
        $mail->Body   .= "Nama Pendamping: " . $pendamping_nama . "\n";

        $mail->addStringAttachment($pdf_content_string, $pdf_filename, 'base64', 'application/pdf');

        $mail->send();
        $email_sent_message = "Email berhasil dikirim.";
        logSentEmail($pasien_nama, $nomor_surat_lengkap, $current_date_iso, $last_email_log_file_path);

    } catch (Exception $e) {
        $email_sent_message = "Gagal mengirim email: {$mail->ErrorInfo}";
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
    }
} else {
    $email_sent_message = "Email tidak dikirim karena sudah pernah dikirim untuk pasien " . htmlspecialchars($pasien_nama) . " dengan nomor surat " . htmlspecialchars($nomor_surat_lengkap) . " pada hari ini (" . tanggal_indonesia($current_date_iso) . ").";
    error_log($email_sent_message);
}


// Tampilkan PDF di browser
if (!headers_sent()) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdf_filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . strlen($pdf_content_string));
    echo $pdf_content_string;
} else {
    echo "Tidak dapat menampilkan PDF secara inline karena output sudah dimulai. ";
    if (!empty($email_sent_message)) {
        echo "<br>Status Email: " . htmlspecialchars($email_sent_message);
    }
}

exit;
?>
