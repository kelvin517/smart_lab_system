<?php
// index.php — Smart Laboratory System homepage with multi-role login
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Laboratory System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Topbar */
    .topbar {
      background: linear-gradient(90deg, #007bff, #00c6ff);
      color: white;
      font-size: 0.9rem;
      padding: 8px 0;
    }
    .topbar a {
      color: white;
      text-decoration: none;
      margin-right: 15px;
    }

    /* Navbar */
    .navbar {
      background-color: #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .navbar-brand {
      font-weight: bold;
      color: #007bff;
      font-size: 1.5rem;
    }
    .navbar-brand span {
      color: #00c6ff;
    }
    .nav-link {
      color: #333 !important;
      font-weight: 500;
    }
    .nav-link:hover {
      color: #007bff !important;
    }
    .btn-quote {
      background: linear-gradient(90deg, #007bff, #00c6ff);
      color: #fff !important;
      border-radius: 25px;
      padding: 8px 20px;
      font-weight: 600;
    }

    /* Hero Section */
    .hero {
      background: linear-gradient(90deg, #e9f5ff, #ffffff);
      padding: 100px 0;
      position: relative;
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: 800;
      color: #003366;
    }
    .hero h1 span {
      color: #007bff;
    }
    .hero p {
      font-size: 1.1rem;
      color: #555;
      margin-top: 15px;
      max-width: 500px;
    }
    .btn-learn {
      background: linear-gradient(90deg, #007bff, #00c6ff);
      border: none;
      border-radius: 25px;
      padding: 10px 25px;
      color: #fff;
      font-weight: 600;
      margin-top: 20px;
    }

    /* Footer */
    footer {
      background: #003366;
      color: #fff;
      padding: 15px 0;
      text-align: center;
      font-size: 0.9rem;
    }

    /* Dropdown styling */
    .dropdown-menu a {
      font-weight: 500;
      color: #333;
    }
    .dropdown-menu a:hover {
      background: #f8f9fa;
      color: #007bff;
    }

    @media (max-width: 768px) {
      .hero {
        text-align: center;
      }
      .hero img {
        margin-top: 30px;
      }
    }
  </style>
</head>
<body>

  <!-- Top Bar -->
  <div class="topbar">
    <div class="container d-flex justify-content-between align-items-center">
      <div>
        <i class="fa-solid fa-envelope"></i> info@smartlab.com
        <span class="mx-3">|</span>
        <i class="fa-solid fa-phone"></i> +254 792460351
      </div>
      <div>
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
      </div>
    </div>
  </div>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
      <a class="navbar-brand" href="#">Smart<span>Lab</span></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#">About</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Services</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Departments</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>

          <!-- Login Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle btn-quote text-white ms-3" href="#" role="button" data-bs-toggle="dropdown">
              Login
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="admin/admin_login.php"><i class="fa-solid fa-user-shield me-2"></i>Admin Login</a></li>
              <li><a class="dropdown-item" href="doctor/doctor_login.php"><i class="fa-solid fa-user-md me-2"></i>Doctor Login</a></li>
              <li><a class="dropdown-item" href="technician/login_technician.php"><i class="fa-solid fa-flask me-2"></i>Technician Login</a></li>
              <li><a class="dropdown-item" href="patients/login.php"><i class="fa-solid fa-user me-2"></i>Patient Login</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h1>Get Better Care For<br>Your <span>Health</span></h1>
          <p>Our Smart Laboratory System connects doctors, technicians, and patients — making medical test management faster, smarter, and more efficient.</p>
          <a href="patients/register.php" class="btn btn-learn">Register as Patient</a>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/img/homepage.jpeg" alt="Hospital logo" class="img-fluid rounded">
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    &copy; <?php echo date('Y'); ?> Smart Laboratory System | Designed by Kelvin Kiptoo
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
