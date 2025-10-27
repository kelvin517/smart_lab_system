<?php
function sendEmail($to, $subject, $message) {
    $headers = "From: no-reply@smartlab.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $message, $headers);
}
?>
