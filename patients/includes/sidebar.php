<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? '' : 'collapsed' ?>" href="dashboard.php">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'book_test.php' ? '' : 'collapsed' ?>" href="book_test.php">
        <i class="bi bi-flask"></i>
        <span>Book Lab Test</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'my_results.php' ? '' : 'collapsed' ?>" href="my_results.php">
        <i class="bi bi-file-earmark-text"></i>
        <span>My Results</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? '' : 'collapsed' ?>" href="messages.php">
        <i class="bi bi-chat-left-text"></i>
        <span>Messages</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" href="logout.php">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </a>
    </li>
  </ul>
</aside><!-- End Sidebar-->