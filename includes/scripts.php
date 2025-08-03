<!-- includes/scripts.php -->
<script>
document.querySelectorAll('.delete-btn').forEach(button => {
  button.addEventListener('click', () => {
    const card = button.closest('.file-card');
    const fileId = card.getAttribute('data-file-id');
    if (confirm('Are you sure you want to delete this file? It will move to Trash.')) {
      fetch('/cloudstore/delete.php?file_id=' + encodeURIComponent(fileId), { method: 'GET' })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.text();
        })
        .then(data => {
          card.remove();
          alert('File deleted (moved to trash)');
        })
        .catch(error => {
          alert('Error deleting file.');
          console.error('Error:', error);
        });
    }
  });
});
</script>
