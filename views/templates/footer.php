<!-- JavaScript for Sidebar Toggle -->
  <script>
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('overlay');

    // Toggle sidebar and overlay
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
    });

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
    });

    // Ensure sidebar links are clickable by preventing overlay interference
    sidebar.addEventListener('click', (e) => {
      e.stopPropagation();
    });

    // Close sidebar when clicking a nav link on mobile
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 991.98) {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
        }
      });
    });
  </script>
</body>

</html>