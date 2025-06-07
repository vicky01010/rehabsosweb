<?php
session_start();
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Halaman Admin Persetujuan</title>
  <style>
    .btn-logout {
  display: inline-flex;
  align-items: center;
  background: linear-gradient(135deg, #ef4444, #b91c1c);
  color: white;
  font-weight: 600;
  border: none;
  padding: 10px 18px;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
  transition: all 0.3s ease;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  user-select: none;
}

.btn-logout:hover {
  background: linear-gradient(135deg, #b91c1c, #ef4444);
  box-shadow: 0 6px 20px rgba(185, 28, 28, 0.7);
  transform: scale(1.05) rotate(-3deg);
}

.btn-logout:active {
  transform: scale(0.95) rotate(0deg);
  box-shadow: 0 2px 6px rgba(185, 28, 28, 0.6);
}

    :root {
      --primary: #6366f1;
      --danger: #ef4444;
      --success: #22c55e;
      --bg: #f9fafb;
      --text: #1f2937;
      --white: #ffffff;
      --gray: #e5e7eb;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background-color: var(--bg);
      color: var(--text);
      padding: 20px;
    }

    h2 {
      text-align: center;
      color: var(--primary);
      margin-bottom: 30px;
      animation: popIn 0.8s ease-out;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: var(--white);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      border-radius: 8px;
      overflow: hidden;
      animation: fadeIn 1s ease;
    }

    th, td {
      padding: 12px 16px;
      text-align: left;
    }

    th {
      background-color: var(--gray);
      color: var(--text);
      font-weight: 600;
    }

    tr:nth-child(even) {
      background-color: #f3f4f6;
    }

    tr:hover {
      background-color: #e0e7ff;
      transition: background-color 0.3s ease;
    }

    .btn {
      padding: 8px 14px;
      font-size: 14px;
      cursor: pointer;
      border: none;
      border-radius: 6px;
      transition: all 0.3s ease;
      margin-right: 5px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .approve {
      background-color: var(--success);
      color: white;
    }

    .approve:hover {
      background-color: #16a34a;
      transform: scale(1.1);
      animation: wiggle 0.3s;
    }

    .reject {
      background-color: var(--danger);
      color: white;
    }

    .reject:hover {
      background-color: #dc2626;
      transform: rotate(-2deg) scale(1.05);
      animation: wiggle 0.3s;
    }

    .row-animated {
      opacity: 0;
      transform: translateY(10px);
      animation: slideIn 0.5s ease forwards;
    }

    .message {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: var(--white);
      border-left: 5px solid var(--primary);
      padding: 12px 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      border-radius: 8px;
      font-size: 14px;
      animation: fadeIn 0.3s ease, popIn 0.3s ease;
      z-index: 999;
    }

    @keyframes slideIn {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes popIn {
      0% { transform: scale(0.9); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }

    @keyframes wiggle {
      0%, 100% { transform: rotate(0deg); }
      25% { transform: rotate(2deg); }
      75% { transform: rotate(-2deg); }
    }

    @media (max-width: 600px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }

      tr {
        margin-bottom: 1rem;
      }

      th {
        display: none;
      }

      td {
        position: relative;
        padding-left: 50%;
      }

      td::before {
        position: absolute;
        top: 12px;
        left: 16px;
        width: 45%;
        white-space: nowrap;
        font-weight: bold;
        color: var(--text);
      }

      td:nth-of-type(1)::before { content: "Nama Pengajuan"; }
      td:nth-of-type(2)::before { content: "Deskripsi"; }
      td:nth-of-type(3)::before { content: "Aksi"; }
    }
  </style>
</head>
<body>
  <div style="text-align: right; margin-bottom: 20px;">
  <form method="POST" action="" style="display:inline;">
    <button type="submit" name="logout" class="btn-logout" aria-label="Logout">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="margin-right: 8px;">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
      </svg>
      Logout
    </button>
  </form>
</div>
  <h2>🧑‍💼 Halaman Admin - Persetujuan Pengajuan</h2>

  <table>
    <thead>
      <tr>
        <th>Nama Pengajuan</th>
        <th>Deskripsi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody id="requestTable">
      <!-- Data akan dimasukkan oleh JS -->
    </tbody>
  </table>

  <script>
    const dataPengajuan = [
      { id: 1, nama: "🎓 Pengajuan Beasiswa", deskripsi: "Beasiswa Mahasiswa Aktif" },
      { id: 2, nama: "🏫 Renovasi Kelas", deskripsi: "Perbaikan ruang kelas 2A" },
      { id: 3, nama: "📦 Pengadaan Alat", deskripsi: "Pembelian proyektor baru" },
    ];

    function showMessage(text) {
      const msg = document.createElement('div');
      msg.className = 'message';
      msg.textContent = text;
      document.body.appendChild(msg);
      setTimeout(() => msg.remove(), 2500);
    }

    function setujui(id) {
      showMessage(`✅ Pengajuan ID ${id} disetujui!`);
      hapusBaris(id);
    }

    function tolak(id) {
      showMessage(`❌ Pengajuan ID ${id} ditolak.`);
      hapusBaris(id);
    }

    function hapusBaris(id) {
      const baris = document.getElementById(`row-${id}`);
      if (baris) baris.remove();
    }

    function tampilkanData() {
      const tabel = document.getElementById("requestTable");
      dataPengajuan.forEach((item, index) => {
        const row = document.createElement("tr");
        row.id = `row-${item.id}`;
        row.className = "row-animated";
        row.style.animationDelay = `${index * 0.2}s`;

        row.innerHTML = `
          <td>${item.nama}</td>
          <td>${item.deskripsi}</td>
          <td>
            <button class="btn approve" onclick="setujui(${item.id})">👍 Setujui</button>
            <button class="btn reject" onclick="tolak(${item.id})">👎 Tolak</button>
          </td>
        `;
        tabel.appendChild(row);
      });
    }

    window.onload = tampilkanData;
  </script>
  <script>
async function updateStatus(id, status) {
  const response = await fetch('update_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, status })
  });

  const result = await response.json();
  if (result.success) {
    showMessage(result.message);
    hapusBaris(id);
  } else {
    showMessage("Gagal memperbarui status.");
  }
}

function setujui(id) {
  updateStatus(id, 'disetujui');
}

function tolak(id) {
  updateStatus(id, 'ditolak');
}
</script>


</body>
</html>
