<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userName = $_SESSION['user_name'] ?? 'User';
$userInitial = strtoupper($userName[0] ?? 'U');
?>
<header class="bg-gray-900 shadow sticky top-0 z-50 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-end h-16 items-center">
      <!-- User Dropdown -->
      <div class="relative">
        <button id="userMenuButton"
          class="flex items-center space-x-3 group focus:outline-none focus:ring-2 focus:ring-white rounded-full p-2 transition"
          aria-haspopup="true" aria-expanded="false">
          
          <div class="w-10 h-10 bg-white text-blue-600 rounded-full flex items-center justify-center font-semibold text-lg">
            <?= htmlspecialchars($userInitial) ?>
          </div>
          <span class="font-medium hidden sm:inline group-hover:text-white transition"><?= htmlspecialchars($userName) ?></span>
          
          <svg class="w-5 h-5 text-white group-hover:text-gray-100 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div id="userMenu"
          class="hidden absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg z-50 transition transform scale-95 opacity-0 pointer-events-none"
          role="menu" aria-orientation="vertical" aria-labelledby="userMenuButton" tabindex="-1">
          <a href="/cloudstore/auth/auth.php"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-600 hover:text-white transition"
            role="menuitem">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- JS for dropdown -->
  <script>
    const userMenuButton = document.getElementById('userMenuButton');
    const userMenu = document.getElementById('userMenu');

    function openMenu() {
      userMenu.classList.remove('hidden', 'opacity-0', 'scale-95', 'pointer-events-none');
      userMenu.classList.add('opacity-100', 'scale-100');
      userMenuButton.setAttribute('aria-expanded', 'true');
      userMenu.focus();
    }

    function closeMenu() {
      userMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
      userMenu.classList.remove('opacity-100', 'scale-100');
      userMenuButton.setAttribute('aria-expanded', 'false');
      setTimeout(() => userMenu.classList.add('hidden'), 200);
    }

    userMenuButton.addEventListener('click', (e) => {
      e.stopPropagation();
      if (userMenu.classList.contains('hidden')) {
        openMenu();
      } else {
        closeMenu();
      }
    });

    document.addEventListener('click', (e) => {
      if (!userMenu.contains(e.target) && !userMenuButton.contains(e.target)) {
        if (!userMenu.classList.contains('hidden')) {
          closeMenu();
        }
      }
    });

    userMenu.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        closeMenu();
        userMenuButton.focus();
      }
    });
  </script>
</header>
