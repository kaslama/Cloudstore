<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$baseURL = '/cloudstore'; // Change if your base path is different
?>

<style>
  /* Sidebar link hover with smooth text color and slide */
  .sidebar-link:hover {
      background: rgba(59, 130, 246, 0.1);
      transform: translateX(6px);
  }
  .sidebar-link span {
      transition: all 0.3s ease;
  }
  .sidebar-link:hover span {
      color: #3b82f6;
      font-weight: 600;
  }

  /* Glass effect with smooth backdrop */
  .glass {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: saturate(180%) blur(12px);
      -webkit-backdrop-filter: saturate(180%) blur(12px);
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
  }

  /* Smooth shadow on sidebar */
  #sidebar {
      box-shadow: 2px 0 8px rgba(0,0,0,0.15);
  }

  /* Mobile sidebar full screen overlay */
  .mobile-overlay {
      background: rgba(0,0,0,0.4);
      position: fixed;
      inset: 0;
      z-index: 30;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease;
  }
  .mobile-overlay.active {
      opacity: 1;
      visibility: visible;
  }
</style>

<!-- Sidebar -->
<nav id="sidebar" class="glass border-r border-gray-200 w-64 h-screen fixed top-0 left-0 shadow-xl z-40 transition-transform duration-300 transform md:translate-x-0 -translate-x-full md:block">

  <div class="px-6 py-4 border-b border-gray-300 text-center">
      <h1 class="text-2xl font-bold text-blue-600">CloudStore 🚀</h1>
      <p class="text-xs text-gray-500">Your personal cloud</p>
  </div>

  <ul class="mt-6 space-y-2 text-gray-800 text-sm font-medium">
      <li class="<?= $currentPage === 'dashboard.php' ? 'bg-blue-100 rounded-l-full' : '' ?>">
          <a href="<?= $baseURL ?>/dashboard.php" class="sidebar-link flex items-center px-6 py-3 transition-all duration-200 ease-in-out">
              <span class="material-icons mr-3">drive_folder_upload</span> My Drive
          </a>
      </li>
      <li class="<?= $currentPage === 'starred.php' ? 'bg-blue-100 rounded-l-full' : '' ?>">
          <a href="<?= $baseURL ?>/pages/starred.php" class="sidebar-link flex items-center px-6 py-3 transition-all duration-200 ease-in-out">
              <span class="material-icons mr-3">star</span> Starred
          </a>
      </li>
      <li class="<?= $currentPage === 'recent.php' ? 'bg-blue-100 rounded-l-full' : '' ?>">
          <a href="<?= $baseURL ?>/pages/recent.php" class="sidebar-link flex items-center px-6 py-3 transition-all duration-200 ease-in-out">
              <span class="material-icons mr-3">history</span> Recent
          </a>
      </li>
      <li class="<?= $currentPage === 'trash.php' ? 'bg-blue-100 rounded-l-full' : '' ?>">
          <a href="<?= $baseURL ?>/pages/trash.php" class="sidebar-link flex items-center px-6 py-3 transition-all duration-200 ease-in-out">
              <span class="material-icons mr-3">delete</span> Trash
          </a>
      </li>
  </ul>

</nav>

<!-- Mobile overlay behind sidebar (click to close) -->
<div id="mobile-overlay" class="mobile-overlay md:hidden"></div>

<!-- Mobile menu toggle button -->
<button id="menu-toggle" class="fixed top-4 left-4 z-50 md:hidden bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition">
    <span class="material-icons">menu</span>
</button>

<script>
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('mobile-overlay');

  function toggleSidebar() {
      sidebar.classList.toggle('-translate-x-full');
      overlay.classList.toggle('active');
  }

  menuToggle.addEventListener('click', toggleSidebar);
  overlay.addEventListener('click', toggleSidebar);

  // Optional: Close sidebar on window resize if desktop view
  window.addEventListener('resize', () => {
    if(window.innerWidth >= 768) {
      if(sidebar.classList.contains('-translate-x-full') === false) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.remove('active');
      }
    }
  });
</script>

<!-- Material Icons CDN -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
