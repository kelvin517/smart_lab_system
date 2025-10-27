<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'technician_dashboard.php' ? '' : 'collapsed' ?>" href="technician_dashboard.php">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li><!-- End Dashboard Nav -->

    <!-- View All Bookings -->
    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'all_bookings.php' ? '' : 'collapsed' ?>" href="all_bookings.php">
        <i class="bi bi-clipboard-check"></i>
        <span>All Bookings</span>
      </a>
    </li><!-- End Bookings Nav -->

    <!-- Upload Results -->
    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'view_details.php' ? '' : 'collapsed' ?>" href="view_details.php?id=1">
        <i class="bi bi-upload"></i>
        <span>Upload Results</span>
      </a>
    </li><!-- End Upload Nav -->

    <!-- Contact Patients -->
    <li class="nav-item">
      <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact_patient.php' ? '' : 'collapsed' ?>" href="contact_patient.php?id=1">
        <i class="bi bi-chat-dots"></i>
        <span>Contact Patient</span>
      </a>
    </li><!-- End Contact Nav -->

    <!-- Logout -->
    <li class="nav-item">
      <a class="nav-link collapsed" href="logout.php">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </a>
    </li><!-- End Logout Nav -->

  </ul>

</aside><!-- End Sidebar -->