<?php
require_once("global/config.php");

// ===== Google reCAPTCHA v2 settings =====
// Replace these with your own keys from https://www.google.com/recaptcha/admin
$RECAPTCHA_SITE_KEY   = '6LdZZHUtAAAAACzkCkI9IoZ6BWfkdLcYxCKW5JO1';
$RECAPTCHA_SECRET_KEY = '6LdZZHUtAAAAAMTTadugmUHybGcpt2Ygs4ABUqf3';

$success = false;
$message = '';
if (isset($_POST['name'])) {

  // ===== Verify reCAPTCHA first =====
  $captchaOk = false;
  if (!empty($_POST['g-recaptcha-response'])) {
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    $verify = file_get_contents(
      'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($RECAPTCHA_SECRET_KEY)
        . '&response=' . urlencode($recaptchaResponse)
        . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'])
    );
    $verifyData = json_decode($verify);

    if ($verifyData && $verifyData->success) {
      $captchaOk = true;
    }
  }

  if (!$captchaOk) {
    $message = 'Please verify that you are not a robot before submitting the form.';
  } else {
    require_once('global/phpmailer/class.phpmailer.php');
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $BUSINESS_TYPE = htmlspecialchars($_POST['BUSINESS_TYPE']);

    $hostname = 'smtp.protonmail.ch';
    $port = '587';
    $userName = 'demo@doable.net';
    $SendingPwd = '9B76V5Q2NPY7524W';

    $To = "demo@doable.net";
    $Subject = "Contact Us from Doable";

    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'html';
    //$mail->IsHTML(true);
    $mail->Host = $hostname;
    $mail->Port = $port;
    $mail->SMTPSecure = ($port == 465) ? 'ssl' : 'tls';
    $mail->SMTPAuth = true;
    $mail->Username = $userName;
    $mail->Password = $SendingPwd;
    $mail->setFrom($userName, "Doable");
    $mail->addAddress($To, "Doable");  //Set who the message is to be sent to.
    //Set the subject line
    $mail->Subject = $Subject;

    // Tell PHPMailer this is an HTML email
    $mail->IsHTML(true);

    $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                    <meta charset="UTF-8">
                    </head>
                    <body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, Helvetica, sans-serif;">
                      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7; padding:30px 0;">
                        <tr>
                          <td align="center">
                            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">

                              <!-- Header -->
                              <tr>
                                <td style="background-color:#39b54a; padding:24px 32px;">
                                  <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:600;">New Contact Form Enquiry</h1>
                                  <p style="margin:4px 0 0; color:#c7d2fe; font-size:13px;">Doable Website</p>
                                </td>
                              </tr>

                              <!-- Body -->
                              <tr>
                                <td style="padding:32px;">
                                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                      <td style="padding:10px 0; border-bottom:1px solid #eef0f4; width:140px; color:#6b7280; font-size:14px;">Name</td>
                                      <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#111827; font-size:14px; font-weight:600;">' . htmlspecialchars($name) . '</td>
                                    </tr>
                                    <tr>
                                      <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#6b7280; font-size:14px;">Email</td>
                                      <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#111827; font-size:14px; font-weight:600;">
                                        <a href="mailto:' . htmlspecialchars($email) . '" style="color:#39b54a; text-decoration:none;">' . htmlspecialchars($email) . '</a>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#6b7280; font-size:14px;">Phone</td>
                                      <td style="padding:10px 0; border-bottom:1px solid #eef0f4; color:#111827; font-size:14px; font-weight:600;">' . htmlspecialchars($phone) . '</td>
                                    </tr>
                                    <tr>
                                      <td style="padding:10px 0; color:#6b7280; font-size:14px;">Business Type</td>
                                      <td style="padding:10px 0; color:#111827; font-size:14px; font-weight:600;">' . htmlspecialchars($BUSINESS_TYPE) . '</td>
                                    </tr>
                                  </table>
                                </td>
                              </tr>

                              <!-- Footer -->
                              <tr>
                                <td style="background-color:#f9fafb; padding:16px 32px; border-top:1px solid #eef0f4;">
                                  <p style="margin:0; color:#9ca3af; font-size:12px;">This enquiry was submitted via the contact form on the Doable website.</p>
                                </td>
                              </tr>

                            </table>
                          </td>
                        </tr>
                      </table>
                    </body>
                    </html>';

    // Plain-text fallback for non-HTML email clients
    $mail->AltBody = "New Contact Form Enquiry\n\n"
      . "Name: $name\n"
      . "Email: $email\n"
      . "Phone: $phone\n"
      . "Business Type: $BUSINESS_TYPE\n";

    try {
      if (!$mail->send()) {
        $message  = $mail->ErrorInfo;
      } else {
        $success = true;
        $message = 'Your Enquiry has been submitted to our team. We will get back to you soon.';
      }
    } catch (phpmailerException $e) {
      $message  = $e->getMessage();
    }
  }
}

?>
<?php include("includes/homepage/header.php"); ?>
<div class="clearfix"></div>
<div class="header-inner-tmargin">
  <section class="section-side-image clearfix">
    <div class="img-holder col-md-12 col-sm-12 col-xs-12">
      <div class="background-imgholder" style="background:url(images/header-inner-1.jpg);"><img
          class="nodisplay-image" src="assets/homepage/images/header-inner-1.jpg" alt="" /> </div>
    </div>
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12 clearfix nopadding">
          <div class="header-inner">
            <div class="overlay">
              <div class="text text-center">
                <h5 class="uppercase text-white less-mar-1 title">CONTACT US</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <div class=" clearfix"></div>
  <!--end section-->
</div>
<div class=" clearfix"></div>
<!--end header section -->


<section class="sec-padding-2">
  <div class="container">
    <div class="row">

      <div class="col-md-8 col-md-offset-2">

        <div class="one_half form-demo">


          <div class="cforms_sty3">

            <?php if ($message && !$success) { ?>
              <div class="alert alert-danger">
                <strong><?= $message; ?></strong>
              </div>
            <?php } elseif ($success) { ?>
              <div class="alert alert-success">
                <strong><?= $message; ?></strong>
              </div>
            <?php } ?>
            <div id="form_status"></div>
            <form method="POST" id="gsr-contact" action="" enctype="multipart/form-data">
              <label class="label">Full Name <em>*</em></label>
              <label class="input">
                <input type="text" name="name" id="name" required>
              </label>

              <div class="clearfix"></div>

              <label class="label">E-mail <em>*</em></label>
              <label class="input">
                <input type="email" name="email" id="email" required>
              </label>

              <div class="clearfix"></div>


              <label class="label">Phone <em>*</em></label>
              <label class="input">
                <input type="text" name="phone" id="phone" class="format_phone_number" required>
              </label>

              <div class="clearfix"></div>

              <label class="label">Business Type <em>*</em></label>
              <div class="select-box-2">
                <select class="form-control" name="BUSINESS_TYPE" id="BUSINESS_TYPE" required>
                  <option value="">Select Business Type</option>
                  <?php
                  $row = $db->Execute("SELECT PK_BUSINESS_TYPE, BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE = 1");
                  while (!$row->EOF) { ?>
                    <option value="<?php echo $row->fields['BUSINESS_TYPE']; ?>"><?= $row->fields['BUSINESS_TYPE'] ?></option>
                  <?php
                    $row->MoveNext();
                  } ?>
                </select>
              </div>

              <div class="clearfix"></div>
              <br>

              <!-- ✅ CONSENT CHECKBOX -->
              <!-- CONSENT CHECKBOX FIXED -->
              <!-- ✅ CONSENT CHECKBOX -->
              <div class="row">
                <div class="col-md-12">
                  <label>
                    <div class="col-md-1" style="margin-left: -20px;">
                      <input type="checkbox" id="sms_consent" name="sms_consent" required>
                    </div>
                    <div class="col-md-11" style="margin-left: -25px; width: 97%;">
                      <p>
                        I agree to receive text messages from Doable related to service updates and support communications.
                        Message frequency may vary. Message & data rates may apply. Reply STOP to opt out or HELP for help.
                        Please review our <a href="terms_of_use.php" target="_blank">Terms of Use</a> and <a href="privacy_policy.php" target="_blank">Privacy Policy</a>.
                      </p>
                    </div>
                  </label>
                </div>
              </div>

              <div class="clearfix"></div>
              <br>

              <!-- ✅ GOOGLE reCAPTCHA -->
              <div class="row">
                <div class="col-md-12">
                  <div class="g-recaptcha" data-sitekey="<?= $RECAPTCHA_SITE_KEY; ?>"></div>
                  <div id="recaptcha_error" style="color:#d9534f; font-size:13px; margin-top:6px; display:none;">
                    Please verify that you are not a robot before submitting.
                  </div>
                </div>
              </div>

          </div>
          <button type="submit" class="btn btn-dark theme_doable_btn uppercase">Send Message</button>
          <div class="clearfix"></div>
          </form>
        </div>

      </div>

    </div>
    <!--end item-->



  </div>
  </div>
</section>
<div class="clearfix"></div>
<!-- end section -->

<!-- ✅ Google reCAPTCHA script -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
  document.getElementById('gsr-contact').addEventListener('submit', function(e) {
    var recaptchaError = document.getElementById('recaptcha_error');
    var recaptchaResponse = grecaptcha.getResponse();

    if (recaptchaResponse.length === 0) {
      e.preventDefault();
      recaptchaError.style.display = 'block';
      recaptchaError.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
    } else {
      recaptchaError.style.display = 'none';
    }
  });

  // Hide the error as soon as the user checks the box
  window.addEventListener('load', function() {
    var checkInterval = setInterval(function() {
      if (typeof grecaptcha !== 'undefined') {
        clearInterval(checkInterval);
      }
    }, 300);
  });
</script>

<?php require_once 'includes/homepage/footer.php'; ?>