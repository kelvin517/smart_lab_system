<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? '' : 'collapsed' ?>" href="dashboard.php">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'view_results.php' ? '' : 'collapsed' ?>" href="view_results.php">
        <i class="bi bi-clipboard-data"></i>
        <span>View Results</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'patient_history.php' ? '' : 'collapsed' ?>" href="patient_history.php">
        <i class="bi bi-clock-history"></i>
        <span>Patient History</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'send_message.php' ? '' : 'collapsed' ?>" href="send_message.php">
        <i class="bi bi-chat-dots"></i>
        <span>Send Message</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'messages.php' ? '' : 'collapsed' ?>" href="messages.php">
        <i class="bi bi-envelope"></i>
        <span>Inbox / Outbox</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? '' : 'collapsed' ?>" href="profile.php">
        <i class="bi bi-person-circle"></i>
        <span>My Profile</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'change_password.php' ? '' : 'collapsed' ?>" href="change_password.php">
        <i class="bi bi-key"></i>
        <span>Change Password</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link collapsed" href="logout.php">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </a>
    </li>

  </ul>
</aside>