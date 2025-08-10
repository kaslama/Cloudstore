<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userName = $_SESSION['user_name'] ?? 'User';
$userInitial = strtoupper($userName[0] ?? 'U');
?>
<header class="bg-white shadow sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 py-3">

      <!-- Left Logo or Placeholder -->
      <div class="flex-shrink-0 w-full sm:w-auto flex items-center justify-start sm:justify-start">
        <!-- Optional logo or empty space -->
      </div>

      <!-- Search Bar Center -->
      <div class="w-full sm:flex-1 sm:max-w-xl relative">
        <form method="GET" action="/cloudstore/search.php" class="relative w-full" autocomplete="off" novalidate>
          <label for="search-input" class="sr-only">Search files and folders</label>
          <input
            id="search-input"
            name="q"
            type="search"
            placeholder="Search files and folders..."
            required
            class="block w-full rounded-full border border-gray-300 pl-12 pr-4 py-2 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
            aria-label="Search files and folders"
            autocomplete="off"
            spellcheck="false"
          />
          <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <div id="searchDropdown" class="hidden absolute z-50 mt-1 w-full bg-white rounded-lg shadow-lg max-h-72 overflow-auto border border-gray-300 scrollbar-thin scrollbar-thumb-indigo-300 scrollbar-track-indigo-50"></div>
        </form>
      </div>

      <!-- User Menu -->
      <div class="flex-shrink-0 w-full sm:w-auto flex justify-end sm:justify-end">
        <div class="relative">
          <button id="userMenuButton"
            class="flex items-center space-x-3 group focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-full p-2 transition"
            aria-haspopup="true" aria-expanded="false" aria-label="User menu">

            <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold text-lg select-none">
              <?= htmlspecialchars($userInitial) ?>
            </div>
            <span class="hidden sm:inline font-medium text-gray-700 group-hover:text-indigo-700 transition select-none"><?= htmlspecialchars($userName) ?></span>

            <svg class="w-5 h-5 text-gray-700 group-hover:text-indigo-700 transition" xmlns="http://www.w3.org/2000/svg" fill="none"
              viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <div id="userMenu"
            class="hidden absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg z-50 transition transform scale-95 opacity-0 pointer-events-none"
            role="menu" aria-orientation="vertical" aria-labelledby="userMenuButton" tabindex="-1">
            <a href="/cloudstore/auth/auth.php"
              class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-600 hover:text-white transition"
              role="menuitem">Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts (same logic as before, unchanged) -->
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

    // Search live dropdown
    const searchInput = document.getElementById('search-input');
    const searchDropdown = document.getElementById('searchDropdown');
    let debounceTimeout = null;

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimeout);
      const query = searchInput.value.trim();

      if (query.length === 0) {
        searchDropdown.innerHTML = '';
        searchDropdown.classList.add('hidden');
        return;
      }

      debounceTimeout = setTimeout(() => {
        fetch(`/cloudstore/search_api.php?q=` + encodeURIComponent(query))
          .then(res => res.json())
          .then(data => {
            if (!data.success) {
              searchDropdown.innerHTML = `<div class="p-3 text-red-600">Error loading results</div>`;
              searchDropdown.classList.remove('hidden');
              return;
            }

            let html = '';

            if (data.folders.length === 0 && data.files.length === 0) {
              html = `<div class="p-3 text-gray-500">No results found.</div>`;
            } else {
              if (data.folders.length > 0) {
                html += `<div class="px-3 py-1 border-b border-gray-200 font-semibold text-indigo-700 select-none">Folders</div>`;
                data.folders.forEach(folder => {
                  html += `
                    <a href="folder_view.php?id=${folder.id}"
                      class="block px-4 py-2 hover:bg-indigo-100 text-indigo-900 truncate"
                      title="${escapeHtml(folder.name)}">
                      📁 ${escapeHtml(folder.name)}
                    </a>`;
                });
              }
              if (data.files.length > 0) {
                html += `<div class="px-3 py-1 border-b border-gray-200 font-semibold text-indigo-700 select-none">Files</div>`;
                data.files.forEach(file => {
                  const folderName = file.folder_name ? file.folder_name : 'Root';
                  const folderLink = file.folder_id ? `folder_view.php?id=${file.folder_id}` : 'dashboard.php';

                  html += `
                    <a href="${folderLink}"
                      class="block px-4 py-2 hover:bg-indigo-100 text-indigo-900 truncate"
                      title="${escapeHtml(file.original_name)} (Location: ${escapeHtml(folderName)})">
                      📄 ${escapeHtml(file.original_name)} <span class="text-sm text-gray-500">(${escapeHtml(folderName)})</span>
                    </a>`;
                });
              }
            }

            searchDropdown.innerHTML = html;
            searchDropdown.classList.remove('hidden');
          })
          .catch(() => {
            searchDropdown.innerHTML = `<div class="p-3 text-red-600">Network error</div>`;
            searchDropdown.classList.remove('hidden');
          });
      }, 300);
    });

    document.addEventListener('click', (e) => {
      if (!searchDropdown.contains(e.target) && e.target !== searchInput) {
        searchDropdown.classList.add('hidden');
      }
    });

    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const q = searchInput.value.trim();
        if (q.length > 0) {
          window.location.href = `/cloudstore/search.php?q=` + encodeURIComponent(q);
        }
      }
    });
  </script>
</header>
