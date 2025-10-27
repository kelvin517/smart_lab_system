<?php
session_start();
include 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
/* ===== Custom Styling for Contact Page ===== */
.contact .info-card {
  border: none;
  border-radius: 12px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.contact .info-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,123,255,0.2);
}

.contact .info-card i {
  font-size: 24px;
  color: #0d6efd;
  margin-right: 10px;
}

.contact form {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  padding: 25px;
}

.contact .btn-primary {
  background: #0d6efd;
  border: none;
  transition: 0.3s;
}

.contact .btn-primary:hover {
  background: #0b5ed7;
}

.map-container {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.contact h5.card-title {
  color: #012970;
  font-weight: 600;
}
</style>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Contact Us</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Contact</li>
      </ol>
    </nav>
  </div>

  <section class="section contact">
    <div class="row gy-4">

      <!-- Contact Info Cards -->
      <div class="col-lg-4 col-md-6">
        <div class="card info-card">
          <div class="card-body pt-4">
            <h5 class="card-title">📍 Location</h5>
            <p><i class="bi bi-geo-alt-fill"></i> Kabarak University, Nakuru, Kenya</p>
            <p>Visit our main campus laboratory for consultations and services.</p>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="card info-card">
          <div class="card-body pt-4">
            <h5 class="card-title">📞 Call Us</h5>
            <p><i class="bi bi-telephone-fill"></i> +254 712 345 678</p>
            <p><i class="bi bi-phone-vibrate-fill"></i> +254 799 123 456</p>
            <p>We’re available Mon–Fri: 8:00 AM – 5:00 PM</p>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <div class="card info-card">
          <div class="card-body pt-4">
            <h5 class="card-title">📧 Email Us</h5>
            <p><i class="bi bi-envelope-fill"></i> support@smartlab.co.ke</p>
            <p><i class="bi bi-envelope-open"></i> info@smartlab.co.ke</p>
            <p>We respond within 24 hours on business days.</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Google Map -->
    <div class="row mt-4">
      <div class="col-lg-12">
        <div class="map-container mb-4">
          <iframe 
            src="https://www.google.com/maps?q=Kabarak%20University,%20Nakuru,%20Kenya&output=embed" 
            width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy">
          </iframe>
        </div>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="row mt-4">
      <div class="col-lg-8 mx-auto">
        <div class="card">
          <div class="card-body pt-4">
            <h5 class="card-title text-center">Send Us a Message</h5>
            <p class="text-center text-muted">Have a question or need assistance? Fill out the form below.</p>

            <form action="#" method="post">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="name" class="form-label">Your Name</label>
                  <input type="text" class="form-control" id="name" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="email" class="form-label">Your Email</label>
                  <input type="email" class="form-control" id="email" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" required>
              </div>
              <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" rows="5" required></textarea>
              </div>
              <div class="text-center">
                <button type="submit" class="btn btn-primary px-5">Send Message</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </section>
</main>
