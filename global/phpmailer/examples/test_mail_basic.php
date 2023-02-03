<html>
<head>
<title>PHPMailer - Mail() basic test</title>
</head>
<body>

<?php

require_once('../class.phpmailer.php');

$mail             = new PHPMailer(); // defaults to using php "mail()"


$body             = 'gggggdfgdfgdfgdfgfdg';

$mail->AddReplyTo("karthee13@gmail.com","First Last");

$mail->SetFrom('karthee13@gmail.com', 'First Last');

$address = "karthee13@gmail.in";
$mail->AddAddress($address, "Karthee");

$mail->Subject    = "PHPMailer Test Subject via mail(), basic";

$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

$mail->MsgHTML($body);

$mail->AddAttachment("images/phpmailer.jpg");      // attachment
//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
//$mail->Send();
if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
  echo "Message sent!";
}

?>

</body>
</html>
