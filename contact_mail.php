<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name   = $_POST['name'];
    $email  = $_POST['email'];
    $phone  = $_POST['phone'];
    $business_type = $_POST['PK_BUSINESS_TYPE'];

    $mail = new PHPMailer(true);

    try {

        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'yourgmail@gmail.com';   // ← YOUR GMAIL
        $mail->Password   = 'your-app-password';     // ← GMAIL APP PASSWORD
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // FROM + TO
        $mail->setFrom('yourgmail@gmail.com', 'Doable Contact');
        $mail->addAddress('roumya.karmakar.01@gmail.com');   // ← RECEIVING EMAIL

        // MESSAGE
        $mail->isHTML(true);
        $mail->Subject = "New Contact Submission";
        $mail->Body = "
        <h3>New Contact Submission</h3>
        Full Name: $name <br>
        Email: $email <br>
        Phone: $phone <br>
        Business Type: $business_type <br>
        ";

        $mail->send();
        echo "SUCCESS";
    } catch (Exception $e) {
        echo "FAILED: {$mail->ErrorInfo}";
    }
}
