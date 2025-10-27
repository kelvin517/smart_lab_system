<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
  /* Services Page Styling */
  body {
    background-color: #f8fafc;
  }

  .pagetitle h1 {
    font-weight: 800;
    color: #003366;
  }

  .pagetitle .breadcrumb-item a {
    color: #007bff;
    text-decoration: none;
  }

  .pagetitle .breadcrumb-item.active {
    color: #555;
  }

  .services-section {
    background: #ffffff;
    border-radius: 12px;
    padding: 40px 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  }

  .card.info-card {
    border: none;
    border-radius: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  }

  .card.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  }

  .card .card-body i {
    background: linear-gradient(90deg, #007bff, #00c6ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .card .card-title {
    font-weight: 700;
    color: #003366;
  }

  .card .card-text {
    font-size: 15px;
    color: #555;
  }

  .section h2 {
    color: #007bff;
    font-weight: 700;
    text-align: center;
    margin-bottom: 30px;
  }
</style>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Our Laboratory Services</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Services</li>
      </ol>
    </nav>
  </div>

  <section class="section services-section">
    <h2><i class="bi bi-hospital me-2"></i>What We Offer</h2>
    <div class="row">

      <!-- Clinical Chemistry -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-heart-pulse" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Clinical Chemistry</h5>
            <p class="card-text">Comprehensive biochemical analysis for liver, kidney, and heart functions using modern analyzers.</p>
          </div>
        </div>
      </div>

      <!-- Hematology -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-droplet-half" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Hematology</h5>
            <p class="card-text">Blood tests such as CBC, ESR, and coagulation studies for anemia and infection detection.</p>
          </div>
        </div>
      </div>

      <!-- Microbiology -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-virus" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Microbiology</h5>
            <p class="card-text">Isolation and identification of bacteria, fungi, and viruses to guide proper antibiotic therapy.</p>
          </div>
        </div>
      </div>

      <!-- Immunology -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-eyedropper" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Immunology</h5>
            <p class="card-text">Testing for allergies, immune disorders, and autoimmune diseases using advanced immunoassays.</p>
          </div>
        </div>
      </div>

      <!-- Pharmacology -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-capsule" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Pharmacology</h5>
            <p class="card-text">Drug concentration monitoring, toxicology, and therapeutic drug management for patient safety.</p>
          </div>
        </div>
      </div>

      <!-- Pathology -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-microscope" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Histopathology</h5>
            <p class="card-text">Tissue biopsy analysis for cancer, infections, and organ abnormalities using microscopic evaluation.</p>
          </div>
        </div>
      </div>

      <!-- Radiology -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-radiation" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Radiology Integration</h5>
            <p class="card-text">Integration with imaging systems (X-ray, CT, MRI) for comprehensive diagnostic support.</p>
          </div>
        </div>
      </div>

      <!-- Molecular Diagnostics -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-dna" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Molecular Diagnostics</h5>
            <p class="card-text">DNA and RNA-based tests for infectious diseases and genetic analysis using PCR technology.</p>
          </div>
        </div>
      </div>

      <!-- Parasitology -->
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card info-card text-center p-3">
          <div class="card-body">
            <i class="bi bi-bug" style="font-size:40px;"></i>
            <h5 class="card-title mt-3">Parasitology</h5>
            <p class="card-text">Microscopic examination for malaria, intestinal parasites, and other tropical infections.</p>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>