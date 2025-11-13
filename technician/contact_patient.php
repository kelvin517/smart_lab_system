<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: technician_login.php");
    exit;
}
$technician_id = $_SESSION['technician_id'];

if (!isset($_GET['id'])) {
    die("Patient ID missing.");
}

$patient_id = intval($_GET['id']);
$query = $conn->prepare("SELECT patient_id, full_name, email, phone, preferred_contact FROM patients WHERE patient_id = ?");
$query->bind_param("i", $patient_id);
$query->execute();
$res = $query->get_result();
$patient = $res->fetch_assoc();

if (!$patient) {
    die("Patient not found.");
}

$status = '';
$contact_history = [];

// Get contact history
$history_query = $conn->prepare("
    SELECT contact_method, subject, message, contact_date, technician_name 
    FROM contact_history 
    WHERE patient_id = ? 
    ORDER BY contact_date DESC 
    LIMIT 10
");
$history_query->bind_param("i", $patient_id);
$history_query->execute();
$history_result = $history_query->get_result();
while ($row = $history_result->fetch_assoc()) {
    $contact_history[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_method = $_POST['contact_method'];
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $technician_name = $_SESSION['technician_username'] ?? 'Technician';
    
    // Log contact attempt
    $log_query = $conn->prepare("
        INSERT INTO contact_history (technician_id, patient_id, contact_method, subject, message, technician_name) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $log_query->bind_param("isssss", $technician_id, $patient_id, $contact_method, $subject, $message, $technician_name);
    $log_query->execute();
    
    switch ($contact_method) {
        case 'email':
            $to = $patient['email'];
            $headers = "From: lab@smartclinic.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $email_message = "
                <html>
                <head>
                    <title>$subject</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #3498db; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Smart Lab System</h2>
                        </div>
                        <div class='content'>
                            <h3>$subject</h3>
                            <p>" . nl2br(htmlspecialchars($message)) . "</p>
                            <p><strong>This is an automated message from our lab system.</strong></p>
                        </div>
                        <div class='footer'>
                            <p>Please do not reply to this email. Contact the lab directly if you have questions.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            if (mail($to, $subject, $email_message, $headers)) {
                $status = "<div class='alert alert-success'>Email sent successfully to {$patient['full_name']}!</div>";
            } else {
                $status = "<div class='alert alert-danger'>Failed to send email. Check mail server configuration.</div>";
            }
            break;
            
        case 'sms':
            // In a real implementation, you would integrate with an SMS gateway like Twilio
            $phone = preg_replace('/[^0-9]/', '', $patient['phone']);
            if (strlen($phone) >= 10) {
                // Simulate SMS sending
                $status = "<div class='alert alert-success'>SMS sent to {$patient['phone']} (Simulated - integrate with SMS gateway in production)</div>";
            } else {
                $status = "<div class='alert alert-warning'>Invalid phone number for SMS</div>";
            }
            break;
            
        case 'call':
            // Log call attempt
            $status = "<div class='alert alert-info'>Call logged for {$patient['full_name']} at {$patient['phone']}. Use your phone system to dial.</div>";
            break;
            
        case 'whatsapp':
            $phone = preg_replace('/[^0-9]/', '', $patient['phone']);
            if (strlen($phone) >= 10) {
                $whatsapp_url = "https://wa.me/{$phone}?text=" . urlencode($message);
                $status = "<div class='alert alert-success'><a href='{$whatsapp_url}' target='_blank' class='btn btn-success'>Open WhatsApp Chat</a> - Message prepared for {$patient['full_name']}</div>";
            } else {
                $status = "<div class='alert alert-warning'>Invalid phone number for WhatsApp</div>";
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Patient - Smart Lab System</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .contact-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 25px;
        }
        
        .card-header {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
        }
        
        .patient-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .contact-method-card {
            border: 2px solid transparent;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .contact-method-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .contact-method-card.selected {
            border-color: var(--primary);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .method-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .email { color: #d44638; border-left: 4px solid #d44638; }
        .sms { color: #25d366; border-left: 4px solid #25d366; }
        .call { color: #3498db; border-left: 4px solid #3498db; }
        .whatsapp { color: #25d366; border-left: 4px solid #25d366; }
        
        .history-item {
            border-left: 4px solid var(--primary);
            padding: 12px 15px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
        }
        
        .btn-contact {
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .template-btn {
            border-radius: 20px;
            margin: 5px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="contact-container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Contact Patient</span>
                <a href="technician_dashboard.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="card-body">
                <!-- Status Message -->
                <?= $status ?>
                
                <!-- Patient Information -->
                <div class="patient-info">
                    <div class="row">
                        <div class="col-md-8">
                            <h4><?= htmlspecialchars($patient['full_name']) ?></h4>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p><i class="bi bi-envelope-fill text-primary"></i> <strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
                                    <p><i class="bi bi-telephone-fill text-success"></i> <strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="bi bi-star-fill text-warning"></i> <strong>Preferred Contact:</strong> <?= htmlspecialchars($patient['preferred_contact'] ?? 'Not specified') ?></p>
                                    <p><i class="bi bi-person-badge-fill text-info"></i> <strong>Patient ID:</strong> <?= $patient['patient_id'] ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                <i class="fas fa-user-injured text-white fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Methods -->
                <form method="POST" id="contactForm">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="contact-method-card email" onclick="selectMethod('email')">
                                <div class="method-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h5>Email</h5>
                                <p class="small text-muted">Send a detailed message</p>
                                <input type="radio" name="contact_method" value="email" id="email" class="d-none" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="contact-method-card sms" onclick="selectMethod('sms')">
                                <div class="method-icon">
                                    <i class="fas fa-sms"></i>
                                </div>
                                <h5>SMS</h5>
                                <p class="small text-muted">Short text message</p>
                                <input type="radio" name="contact_method" value="sms" id="sms" class="d-none">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="contact-method-card call" onclick="selectMethod('call')">
                                <div class="method-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <h5>Phone Call</h5>
                                <p class="small text-muted">Direct conversation</p>
                                <input type="radio" name="contact_method" value="call" id="call" class="d-none">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="contact-method-card whatsapp" onclick="selectMethod('whatsapp')">
                                <div class="method-icon">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <h5>WhatsApp</h5>
                                <p class="small text-muted">Instant messaging</p>
                                <input type="radio" name="contact_method" value="whatsapp" id="whatsapp" class="d-none">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message Form (shown for email, SMS, WhatsApp) -->
                    <div id="messageForm" class="d-none">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control" placeholder="Enter message subject">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea name="message" id="message" class="form-control" rows="5" placeholder="Type your message here..."></textarea>
                        </div>
                        
                        <!-- Quick Templates -->
                        <div class="mb-3">
                            <label class="form-label">Quick Templates</label>
                            <div>
                                <button type="button" class="btn btn-outline-primary template-btn" onclick="useTemplate('appointment')">Appointment Reminder</button>
                                <button type="button" class="btn btn-outline-success template-btn" onclick="useTemplate('results')">Test Results Ready</button>
                                <button type="button" class="btn btn-outline-warning template-btn" onclick="useTemplate('delay')">Test Delay Notice</button>
                                <button type="button" class="btn btn-outline-info template-btn" onclick="useTemplate('followup')">Follow-up Required</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Call Notes (shown for phone calls) -->
                    <div id="callNotes" class="d-none">
                        <div class="mb-3">
                            <label for="call_subject" class="form-label">Call Purpose</label>
                            <input type="text" name="subject" id="call_subject" class="form-control" placeholder="Reason for the call">
                        </div>
                        <div class="mb-3">
                            <label for="call_notes" class="form-label">Notes (Optional)</label>
                            <textarea name="message" id="call_notes" class="form-control" rows="3" placeholder="Any notes about the call..."></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="technician_dashboard.php" class="btn btn-secondary btn-contact">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-contact" id="submitBtn">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Contact History -->
        <div class="card">
            <div class="card-header">
                Contact History
            </div>
            <div class="card-body">
                <?php if (!empty($contact_history)): ?>
                    <?php foreach ($contact_history as $history): ?>
                        <div class="history-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= htmlspecialchars($history['subject']) ?></strong>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($history['message']) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary"><?= htmlspecialchars($history['contact_method']) ?></span>
                                    <div class="small text-muted">
                                        <?= date("M j, Y g:i A", strtotime($history['contact_date'])) ?>
                                    </div>
                                    <div class="small">
                                        By: <?= htmlspecialchars($history['technician_name']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted py-3">No contact history found for this patient.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function selectMethod(method) {
            // Update UI
            document.querySelectorAll('.contact-method-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelector(`.${method}`).classList.add('selected');
            
            // Update radio button
            document.getElementById(method).checked = true;
            
            // Show/hide forms based on method
            const messageForm = document.getElementById('messageForm');
            const callNotes = document.getElementById('callNotes');
            const submitBtn = document.getElementById('submitBtn');
            
            if (method === 'call') {
                messageForm.classList.add('d-none');
                callNotes.classList.remove('d-none');
                submitBtn.textContent = 'Log Call';
            } else {
                messageForm.classList.remove('d-none');
                callNotes.classList.add('d-none');
                
                if (method === 'sms') {
                    submitBtn.textContent = 'Send SMS';
                } else if (method === 'whatsapp') {
                    submitBtn.textContent = 'Prepare WhatsApp';
                } else {
                    submitBtn.textContent = 'Send Email';
                }
            }
        }
        
        function useTemplate(type) {
            const subjectField = document.getElementById('subject');
            const messageField = document.getElementById('message');
            
            const templates = {
                appointment: {
                    subject: "Appointment Reminder - Smart Lab System",
                    message: "Dear patient,\n\nThis is a reminder for your upcoming appointment at our lab. Please arrive 15 minutes early.\n\nIf you need to reschedule, please contact us at least 24 hours in advance.\n\nThank you,\nSmart Lab Team"
                },
                results: {
                    subject: "Your Test Results Are Ready",
                    message: "Dear patient,\n\nYour laboratory test results are now available. You can view them through our patient portal or visit our lab to collect a printed copy.\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\nSmart Lab Team"
                },
                delay: {
                    subject: "Update on Your Test Results",
                    message: "Dear patient,\n\nWe're writing to inform you that there will be a slight delay in processing your test results due to [reason]. We apologize for any inconvenience this may cause.\n\nWe expect to have your results available by [date]. We will notify you as soon as they are ready.\n\nThank you for your understanding.\n\nSincerely,\nSmart Lab Team"
                },
                followup: {
                    subject: "Follow-up Required",
                    message: "Dear patient,\n\nRegarding your recent tests, we recommend a follow-up consultation with our specialist. Please contact us to schedule an appointment at your earliest convenience.\n\nThis is important for discussing your results and next steps.\n\nBest regards,\nSmart Lab Team"
                }
            };
            
            if (templates[type]) {
                subjectField.value = templates[type].subject;
                messageField.value = templates[type].message;
            }
        }
        
        // Initialize with email selected
        document.addEventListener('DOMContentLoaded', function() {
            selectMethod('email');
        });
    </script>
</body>
</html>