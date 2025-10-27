<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
/* ===== Custom Styling for Departments Page ===== */
.card-dept {
  border: none;
  transition: all 0.3s ease-in-out;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border-radius: 12px;
}

.card-dept:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0, 123, 255, 0.2);
}

.card-dept i {
  font-size: 45px;
  margin-bottom: 15px;
}

.card-dept .card-title {
  font-weight: 600;
  color: #012970;
}

.card-dept .card-text {
  color: #6c757d;
}
</style>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Our Hospital Departments</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Departments</li>
      </ol>
    </nav>
  </div>

  <section class="section">
    <div class="row gy-4">

      <!-- Pathology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-clipboard2-pulse text-primary"></i>
            <h5 class="card-title mt-2">Pathology</h5>
            <p class="card-text">Conducts medical laboratory tests and disease diagnostics through biological samples.</p>
          </div>
        </div>
      </div>

      <!-- Radiology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-eye text-success"></i>
            <h5 class="card-title mt-2">Radiology</h5>
            <p class="card-text">Provides imaging services such as X-rays, CT scans, MRI, and ultrasound diagnostics.</p>
          </div>
        </div>
      </div>

      <!-- Cardiology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-heart-pulse text-danger"></i>
            <h5 class="card-title mt-2">Cardiology</h5>
            <p class="card-text">Specializes in cardiovascular health, ECG testing, and heart disease diagnostics.</p>
          </div>
        </div>
      </div>

      <!-- Microbiology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-virus text-warning"></i>
            <h5 class="card-title mt-2">Microbiology</h5>
            <p class="card-text">Focuses on identifying bacteria, viruses, and other microorganisms causing diseases.</p>
          </div>
        </div>
      </div>

      <!-- Hematology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-droplet-half text-danger"></i>
            <h5 class="card-title mt-2">Hematology</h5>
            <p class="card-text">Analyzes blood components to diagnose anemia, infections, and clotting disorders.</p>
          </div>
        </div>
      </div>

      <!-- Immunology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-shield-check text-info"></i>
            <h5 class="card-title mt-2">Immunology</h5>
            <p class="card-text">Deals with immune system-related disorders, allergies, and autoimmune conditions.</p>
          </div>
        </div>
      </div>

      <!-- Pharmacology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-capsule text-secondary"></i>
            <h5 class="card-title mt-2">Pharmacology</h5>
            <p class="card-text">Monitors drug interactions, toxicity, and ensures safe medication administration.</p>
          </div>
        </div>
      </div>

      <!-- Biochemistry -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-beaker text-primary"></i>
            <h5 class="card-title mt-2">Biochemistry</h5>
            <p class="card-text">Analyzes biochemical substances to assess liver, kidney, and metabolic functions.</p>
          </div>
        </div>
      </div>

      <!-- Molecular Diagnostics -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-dna text-success"></i>
            <h5 class="card-title mt-2">Molecular Diagnostics</h5>
            <p class="card-text">Performs genetic and DNA-based tests for precision diagnosis and treatment planning.</p>
          </div>
        </div>
      </div>

      <!-- Parasitology -->
      <div class="col-md-6 col-lg-4">
        <div class="card info-card card-dept text-center p-3">
          <div class="card-body">
            <i class="bi bi-bug text-danger"></i>
            <h5 class="card-title mt-2">Parasitology</h5>
            <p class="card-text">Detects and studies parasites responsible for malaria, worms, and protozoan infections.</p>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>