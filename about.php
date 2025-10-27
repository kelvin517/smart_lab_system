<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
  /* About Page Styling */
  .about-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  }

  .about-section h5.card-title {
    color: #007bff;
    font-weight: 700;
    margin-top: 25px;
    margin-bottom: 10px;
  }

  .about-section p, 
  .about-section ul li {
    color: #555;
    line-height: 1.7;
    font-size: 15px;
  }

  .about-section ul {
    list-style: none;
    padding-left: 0;
  }

  .about-section ul li::before {
    content: "✔️";
    margin-right: 8px;
    color: #00bcd4;
  }

  .team-section {
    background: linear-gradient(90deg, #007bff0a, #00c6ff0a);
    border-radius: 10px;
    padding: 20px;
    margin-top: 25px;
  }

  .team-section strong {
    color: #003366;
  }

  .tech-stack {
    background: #e9f5ff;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
    color: #0056b3;
    display: inline-block;
    margin-top: 10px;
  }

  .pagetitle h1 {
    font-weight: 800;
    color: #003366;
  }

  .pagetitle ol.breadcrumb {
    background: transparent;
  }

  .pagetitle .breadcrumb-item a {
    color: #007bff;
    text-decoration: none;
  }

  .pagetitle .breadcrumb-item.active {
    color: #555;
  }
</style>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>About Smart Laboratory System</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">About</li>
      </ol>
    </nav>
  </div>

  <section class="section about">
    <div class="row">
      <div class="col-lg-12">
        <div class="about-section">
          <h5 class="card-title"><i class="bi bi-info-circle-fill me-2 text-primary"></i>Overview</h5>
          <p>
            The <strong>Smart Laboratory System</strong> is a web-based solution designed to revolutionize laboratory
            operations through automation, efficiency, and collaboration. It connects <strong>patients, doctors, and technicians</strong>
            on one digital platform for seamless medical test management.
          </p>

          <h5 class="card-title"><i class="bi bi-bullseye me-2 text-success"></i>Key Objectives</h5>
          <ul>
            <li>Digitize laboratory test booking and result management.</li>
            <li>Facilitate real-time communication between doctors and patients.</li>
            <li>Reduce paperwork and streamline medical data access.</li>
            <li>Enhance data privacy and secure authentication.</li>
          </ul>

          <h5 class="card-title"><i class="bi bi-star-fill me-2 text-warning"></i>System Features</h5>
          <ul>
            <li>Online test booking and tracking.</li>
            <li>Automated test result uploads by technicians.</li>
            <li>Doctor diagnosis and patient feedback integration.</li>
            <li>Interactive doctor-patient messaging module.</li>
            <li>Profile management and password protection.</li>
          </ul>

          <h5 class="card-title"><i class="bi bi-people-fill me-2 text-info"></i>Development Team</h5>
          <div class="team-section">
            <p>
              This project was developed by <strong>Kelvin Kiptoo</strong>, <strong>Collins Cheriyout</strong>,
              <strong>Elly Akhwaba</strong>, and <strong>Bruno Koech</strong> as part of the 
              <strong>BSc. Information Technology</strong> program at <strong>Kabarak University</strong>.
            </p>
          </div>

          <h5 class="card-title"><i class="bi bi-cpu-fill me-2 text-danger"></i>Technologies Used</h5>
          <div class="tech-stack">
            PHP · MySQL · JavaScript · HTML · CSS · Bootstrap (NiceAdmin Theme)
          </div>
        </div>
      </div>
    </div>
  </section>
</main>