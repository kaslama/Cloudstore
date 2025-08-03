<div class="relative inline-block text-left">
  <button onclick="toggleDropdown(this)" class="p-2 rounded hover:bg-gray-100">
    ⋮
  </button>
  <div class="dropdown-menu hidden absolute right-0 z-10 mt-2 w-40 rounded-md bg-white shadow-lg border border-gray-200">
    <a href="rename.php?file_id=<?= $file['id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Rename</a>
    <a href="properties.php?file_id=<?= $file['id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Properties</a>
    <a href="delete_file.php?file_id=<?= $file['id'] ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Delete</a>
  </div>
</div>
