<?php
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .glass {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .animate-pop {
      animation: pop-in 0.5s ease-out forwards;
    }
    @keyframes pop-in {
      from {
        opacity: 0;
        transform: scale(0.95);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }
    @keyframes fade-slide {
    from {
      opacity: 0;
      transform: translateY(-8px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
    }
    .animate-fade-slide {
    animation: fade-slide 0.4s ease-out forwards;
    }

  </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-indigo-900 to-indigo-700 min-h-screen flex items-center justify-center">
  <div class="glass rounded-2xl shadow-2xl p-8 max-w-sm w-full text-white animate-pop">
    <h2 class="text-3xl font-bold text-center mb-6">🔐 Login Admin</h2>
    <form method="POST" action="proses_login.php" class="space-y-5">
      <?php if ($error): ?>
      <div class="animate-pop bg-red-500/20 border border-red-500/40 text-red-200 text-sm rounded-lg px-4 py-2 mb-2">
      ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>
      <div>
        <label class="block text-sm mb-1" for="username">Username</label>
        <input id="username" name="username" type="text" placeholder="admin" class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-indigo-300" required>
      </div>
    <div class="relative">
        <label class="block text-sm mb-1" for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="••••••••"
            class="w-full px-4 py-2 pr-10 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-indigo-300"
            required>
        <button type="button" id="togglePassword" class="absolute top-9 right-3 text-white/70 hover:text-white"
            tabindex="-1">
            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </button>
    </div>

      <div class="flex items-center text-sm">
        <label class="flex items-center gap-2">
          <input type="checkbox" class="accent-indigo-400" />
          <span>Ingat saya</span>
        </label>
      </div>
      <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300">Masuk</button>
    </form>
    <!-- Tombol Kembali -->
    <div class="mt-6 text-center">
        <a href="../index.html"
            class="inline-flex items-center gap-2 text-sm px-5 py-2 rounded-full border border-white/30 bg-white/10 hover:bg-white/20 text-white shadow-lg hover:shadow-indigo-500/40 transition duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Beranda
        </a>
    </div>
  </div>
</body>
<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  togglePassword.addEventListener('click', () => {
    const isVisible = passwordInput.type === 'text';
    passwordInput.type = isVisible ? 'password' : 'text';

    // Ganti ikon (opsional: bisa ditambahkan kondisi SVG berbeda)
    eyeIcon.innerHTML = isVisible
      ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`
      : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a10.05 10.05 0 013.038-4.362M9.88 9.88a3 3 0 104.24 4.24M3 3l18 18" />`;
  });
</script>

</html>
