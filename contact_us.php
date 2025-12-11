<?php
global $db;
require_once('global/config.php');
require_once('includes/homepage/header.php');
?>

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


            <div id="form_status"></div>
            <form method="POST" id="gsr-contact" action="contact_mail.php">
              <label class="label">Full Name <em>*</em></label>
              <label class="input">
                <input type="text" name="name" id="name">
              </label>

              <div class="clearfix"></div>

              <label class="label">E-mail <em>*</em></label>
              <label class="input">
                <input type="email" name="email" id="email">
              </label>

              <div class="clearfix"></div>


              <label class="label">Phone <em>*</em></label>
              <label class="input">
                <input type="text" name="phone" id="phone">
              </label>

              <div class="clearfix"></div>

              <label class="label">Business Type <em>*</em></label>
              <div class="select-box-2">
                <select class="form-control" name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                  <option value="">Select Business Type</option>
                  <?php
                  $row = $db->Execute("SELECT PK_BUSINESS_TYPE, BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE = 1");
                  while (!$row->EOF) { ?>
                    <option value="<?php echo $row->fields['PK_BUSINESS_TYPE']; ?>"><?= $row->fields['BUSINESS_TYPE'] ?></option>
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
<<<<<<< HEAD
                <div class="col-md-12">
                  <label>
                    <div class="col-md-1" style="margin-left: -20px;">
                      <input type="checkbox" id="sms_consent" name="sms_consent">
=======
                <div class="col-sm-3"> <a href="index.php" title="" class="logo style-2 mar-4"> <img height="45"
                      src="assets/homepage/images/doable_logo.png" alt=""> </a> </div>
                <div class="col-sm-9">
                  <div class="main-nav">
                    <ul class="nav navbar-nav top-nav">
                      <li class="visible-xs menu-icon"> <a href="javascript:void(0)" class="navbar-toggle collapsed"
                          data-toggle="collapse" data-target="#menu" aria-expanded="false"> <i aria-hidden="true"
                            class="fa fa-bars"></i> </a> </li>
                    </ul>
                    <div id="menu" class="collapse">
                      <ul class="nav navbar-nav">
                        <li> <a href="index.php">Home</a></li>
                        <li> <a href="about.html">About</a></li>
                        <li> <a href="javascript:void(0);">Services</a></li>
                        <li class="mega-menu active"> <a href="contact_us.php">Contact Us</a></li>
                        <li class="login-doable-btn"><a class="theme_doable_btn capitalize login-button" href="login.php"><span class="text-black">log</span> <span class="text-white">in</span></a></li>
                      </ul>
>>>>>>> 668c23c13021bc5102ad7a377826e0c39ecce077
                    </div>
                    <div class="col-md-11" style="margin-left: -25px; width: 97%;">
                      I Consent to Receive SMS Notifications, Alerts from Doable LLP.
                      Message frequency varies. Message and data rates may apply. You can reply STOP to unsubscribe at any
                      time. For more information please review our <a href="terms_of_use.php" target="_blank">Terms of Use</a> and <a href="privacy_policy.php" target="_blank">Privacy Policy</a>.
                    </div>
                  </label>
                </div>
              </div>
          </div>

          <input type="hidden" name="token" value="FsWga4&@f6aw" />
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

<?php require_once 'includes/homepage/footer.php'; ?>