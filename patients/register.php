<?php
// patients/register.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config/db.php'; // adjust if your config is elsewhere

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // basic validation
    if ($name === '' || $email === '' || $phone === '' || $gender === '' || $password === '' || $confirm_password === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^07[0-9]{8}$/', $phone)) {
        $error = "Phone number must be in the format 07XXXXXXXX.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // optional avatar upload handling
        $avatar_path = null;
        if (!empty($_FILES['avatar']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/patients/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['avatar']['name']));
            $targetFile = $uploadDir . $fileName;
            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (in_array($ext, $allowed) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                    $avatar_path = 'uploads/patients/' . $fileName;
                }
            }
        }

        // check existing email
        $check = $conn->prepare("SELECT email FROM patients WHERE email = ? LIMIT 1");
        if (!$check) {
            $error = "Database error: " . $conn->error;
        } else {
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = "Email is already registered.";
            } else {
                // insert
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                if ($avatar_path) {
                    $stmt = $conn->prepare("INSERT INTO patients (full_name, email, phone, gender, password, avatar) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('ssssss', $name, $email, $phone, $gender, $hashed, $avatar_path);
                } else {
                    $stmt = $conn->prepare("INSERT INTO patients (full_name, email, phone, gender, password) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('sssss', $name, $email, $phone, $gender, $hashed);
                }

                if ($stmt && $stmt->execute()) {
                    $success = "Registration successful. <a href='login.php'>Click here to login</a>.";
                    // Clear POST so form resets
                    $_POST = [];
                } else {
                    $error = "Registration failed. " . ($stmt ? htmlspecialchars($stmt->error) : htmlspecialchars($conn->error));
                }
                if ($stmt) $stmt->close();
            }
            $check->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Register — Smart Laboratory</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --accent: #2fbfbd;
      --accent-dark: #1ea8a6;
      --muted: #6c757d;
      --card-radius: 14px;
    }
    body {
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: #f3faf9;
      color: #222;
      -webkit-font-smoothing:antialiased;
    }
    .register-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }
    .card-register {
      width: 100%;
      max-width: 1100px;
      background: #fff;
      border-radius: var(--card-radius);
      box-shadow: 0 8px 30px rgba(35,40,50,0.08);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 460px;
    }

    /* Left form */
    .register-form {
      padding: 46px;
    }
    .brand {
      font-weight: 700;
      color: var(--accent-dark);
      letter-spacing: .2px;
      margin-bottom: 6px;
    }
    .brand-sub {
      color: var(--muted);
      margin-bottom: 22px;
      font-size: 0.95rem;
    }
    h2.register-title {
      color: #05263f;
      margin-bottom: 6px;
      font-size: 1.45rem;
      font-weight: 700;
    }
    p.lead {
      color: var(--muted);
      margin-bottom: 18px;
    }

    .form-control {
      border-radius: 10px;
      height: 48px;
      box-shadow: none;
      border: 1px solid #e6eef0;
    }
    .form-label { font-weight:600; color:#233142; font-size:0.9rem; }

    .btn-primary.custom {
      background: linear-gradient(90deg,var(--accent), var(--accent-dark));
      border: none;
      box-shadow: 0 8px 18px rgba(46,191,189,0.12);
      height: 48px;
      border-radius: 10px;
      font-weight:700;
    }

    .info-small { color: var(--muted); font-size:0.9rem; }

    /* avatar box */
    .avatar-preview {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #e9f6f5;
    }
    .avatar-upload-btn {
      cursor: pointer;
      display:inline-block;
      padding:6px 10px;
      border-radius:8px;
      background: #f1f8f7;
      color: var(--muted);
      font-weight:600;
      margin-left:12px;
      font-size:0.85rem;
    }

    .msgs {
      margin-bottom: 16px;
    }

    /* Right illustration */
    .register-illustration {
      background: linear-gradient(180deg,#f0fffe, #e4fbfb);
      padding: 36px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      align-items:center;
      gap:18px;
    }
    .illus-box {
      width: 100%;
      max-width: 360px;
      border-radius:50px;
    }
    .illus-title {
      font-weight:700;
      color:#064b4b;
      font-size:1.1rem;
      margin-top:10px;
      text-align:center;
    }
    .illus-sub { color:var(--muted); text-align:center; font-size:0.95rem; }

    /* small helpers */
    .password-strength { height:6px; border-radius:8px; width:100%; background:#eee; overflow:hidden; margin-top:8px; }
    .strength-bar { height:100%; width:0%; transition:width .25s ease; }

    @media (max-width: 991px) {
      .card-register { grid-template-columns: 1fr; }
      .register-illustration { order: -1; padding: 28px 20px; }
      .illus-box { max-width: 260px; }
    }
  </style>
</head>
<body>

<div class="register-wrap">
  <div class="card-register">
    <!-- FORM LEFT -->
    <div class="register-form">
      <div class="brand">Smart<span style="color:var(--accent);">Lab</span></div>
      <div class="brand-sub">Smart Laboratory System — patient registration</div>

      <h2 class="register-title">Create your patient account</h2>
      <p class="lead">Register to book tests, view results and communicate securely with doctors and technicians.</p>

      <!-- messages -->
      <div class="msgs">
        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
      </div>

      <form method="POST" enctype="multipart/form-data" id="regForm" novalidate>
        <div class="row g-3">
          <div class="col-12 d-flex align-items-center mb-2">
            <img id="avatarPreview" src="/assets/img/avatar.png" alt="avatar" class="avatar-preview">
            <label class="avatar-upload-btn ms-2">
              <input type="file" id="avatar" name="avatar" accept="image/*" style="display:none;">
              <span id="avatarLabel">Upload</span>
            </label>
          </div>

          <div class="col-md-12">
            <label class="form-label">Full name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" required value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" placeholder="07XXXXXXXX">
          </div>

          <div class="col-md-6">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select" required>
              <option value="">Choose...</option>
              <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender']=='Male') ? 'selected':''; ?>>Male</option>
              <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender']=='Female') ? 'selected':''; ?>>Female</option>
              <option value="Other" <?= (isset($_POST['gender']) && $_POST['gender']=='Other') ? 'selected':''; ?>>Other</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required placeholder="Min 8 characters">
            <div class="password-strength mt-2"><div id="strengthBar" class="strength-bar"></div></div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Confirm password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            <small id="matchInfo" class="info-small"></small>
          </div>

          <div class="col-12 text-end mt-2">
            <button type="submit" class="btn btn-primary custom">Create account</button>
          </div>

          <div class="col-12 mt-3 text-center">
            <small class="info-small">Already registered? <a href="login.php">Login here</a></small>
          </div>
        </div>
      </form>
    </div>

    <!-- ILLUSTRATION RIGHT -->
    <div class="register-illustration">
      <div class="illus-box">
        <!-- Use an svg/png illustration — replace the src with your project's illustration path -->
        <img src="/assets/img/preg.jpeg" alt="illustration" style="width:100%; display:block;">
        <div class="illus-title">Smart diagnostics, faster care</div>
        <div class="illus-sub">Book tests, get faster lab results, and securely communicate with clinicians — all in one place.</div>
      </div>
    </div>
  </div>
</div>

<script src="/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  // Avatar upload preview
  const avatarInput = document.getElementById('avatar');
  const avatarPreview = document.getElementById('avatarPreview');
  const avatarLabel = document.getElementById('avatarLabel');

  document.querySelector('.avatar-upload-btn').addEventListener('click', () => avatarInput.click());
  avatarInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => avatarPreview.src = ev.target.result;
    reader.readAsDataURL(file);
    avatarLabel.textContent = 'Change';
  });

  // Password strength
  const password = document.getElementById('password');
  const strengthBar = document.getElementById('strengthBar');
  const confirm = document.getElementById('confirm_password');
  const matchInfo = document.getElementById('matchInfo');

  password.addEventListener('input', () => {
    const val = password.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
    if (/\d/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const pct = (score / 4) * 100;
    strengthBar.style.width = pct + '%';
    if (pct <= 25) strengthBar.style.background = '#e74c3c';
    else if (pct <= 50) strengthBar.style.background = '#f39c12';
    else if (pct <= 75) strengthBar.style.background = '#2ecc71';
    else strengthBar.style.background = '#16a085';
  });

  // confirm password match
  confirm.addEventListener('input', () => {
    if (!confirm.value) { matchInfo.textContent = ''; return; }
    if (password.value === confirm.value) {
      matchInfo.textContent = 'Passwords match';
      matchInfo.style.color = '#198754';
    } else {
      matchInfo.textContent = 'Passwords do not match';
      matchInfo.style.color = '#dc3545';
    }
  });

  // phone validation on submit
  document.getElementById('regForm').addEventListener('submit', function(e){
    const phone = document.getElementById('phone').value.trim();
    const phoneRegex = /^07[0-9]{8}$/;
    if (!phoneRegex.test(phone)) {
      e.preventDefault();
      alert('Please enter a valid phone number in the format 07XXXXXXXX');
      document.getElementById('phone').focus();
      return false;
    }
    // let server handle the rest
  });
</script>
</body>
</html>
