<?php
global $db;
require_once('global/config.php');
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Doable </title>
  <meta name="keywords" content="" />
  <meta name="description" content="">
  <meta name="author" content="">

  <!-- Mobile view -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Favicon -->
  <link rel="shortcut icon" href="assets/homepage/images/doable_logo.ico">
  <link rel="stylesheet" type="text/css" href="assets/homepage/js/bootstrap/bootstrap.min.css">

  <!-- Google fonts  -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i"
    rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Yesteryear" rel="stylesheet">

  <!-- Template's stylesheets -->
  <link rel="stylesheet" href="assets/homepage/js/megamenu/stylesheets/screen.css">
  <link rel="stylesheet" href="assets/homepage/css/theme-default.css" type="text/css">
  <link rel="stylesheet" href="assets/homepage/js/loaders/stylesheets/screen.css">
  <link rel="stylesheet" href="assets/homepage/css/corporate.css" type="text/css">
  <link rel="stylesheet" href="assets/homepage/css/shortcodes.css" type="text/css">
  <link rel="stylesheet" href="assets/homepage/fonts/font-awesome/css/font-awesome.min.css" type="text/css">
  <link rel="stylesheet" type="text/css" href="assets/homepage/fonts/Simple-Line-Icons-Webfont/simple-line-icons.css"
    media="screen" />
  <link rel="stylesheet" href="assets/homepage/fonts/et-line-font/et-line-font.css">
  <link rel="stylesheet" href="assets/homepage/css/custom.css" type="text/css">

  <!-- Template's stylesheets END -->

  <!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

  <!-- Style Customizer's stylesheets -->

  <link rel="stylesheet/less" type="text/css" href="assets/homepage/less/skin.less">
  <!-- Style Customizer's stylesheets END -->

  <!-- Skin stylesheet -->

</head>
<style>
  a:link {
    color: green;
    background-color: transparent;
    text-decoration: none;
  }

  a:visited {
    color: pink;
    background-color: transparent;
    text-decoration: none;
  }

  a:hover {
    color: red;
    background-color: transparent;
    text-decoration: underline;
  }

  a:active {
    color: yellow;
    background-color: transparent;
    text-decoration: underline;
  }
</style>


<body>
  <div class="over-loader loader-live">
    <div class="loader">
      <div class="loader-item style5">
        <div class="bounce1"></div>
        <div class="bounce2"></div>
        <div class="bounce3"></div>
      </div>

    </div>
  </div>
  <!--end loading-->
  <div class="wrapper-boxed">
    <div class="site-wrapper">
      <!--end topbar-->

      <div class="col-md-12 nopadding">
        <div class="header-section style1 pin-style main-header">
          <div class="container">
            <div class="mod-menu">
              <div class="row">
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
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!--end menu-->

      </div>
      <!--end menu-->

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
                <div class="header-inner single-head">
                  <div class="overlay">
                    <div class="text text-center">
                      <h3 class="uppercase text-white less-mar-1 title">CONTACT US</h3>
                      <div class="ce-title-line align-center theme-title-line"></div>
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
                      <div class="col-md-12">
                        <label>
                          <div class="col-md-1" style="margin-left: -20px;">
                            <input type="checkbox" id="sms_consent" name="sms_consent">
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

    <div class="section-dark sec-padding">
      <div class="container">
        <div class="row">
          <div class="col-md-3 col-sm-12 colmargin clearfix margin-bottom">
            <div class="fo-map">
              <div class="footer-logo"><img src="assets/homepage/images/doable_logo.png" width="80" alt="" /></div>
              <p class="text-light">Running a business is hard enough. You love what you do, but hate what you don't.</p>
              <p class="text-light">DOable makes it easy to run your business the way you want.</p>
            </div>
          </div>
          <!--end item-->

          <div class="col-md-3 col-xs-12 clearfix margin-bottom">
            <h4 class="text-white less-mar3 font-weight-5">About Us</h4>
            <div class="clearfix"></div>
          </div>
          <!--end item-->

          <div class="col-md-3 col-xs-12 clearfix margin-bottom">
            <h4 class="text-white less-mar3 font-weight-5">Quick Links</h4>
            <a class="text-white" target="_blank" href="terms_of_use.php">Terms of Use</a>
            <div class="clearfix"></div>
            <a class="text-white" target="_blank" href="privacy_policy.php">Privacy Policy</a>
            <div class="clearfix"></div>
          </div>
          <!--end item-->

          <div class="col-md-3 col-xs-12 clearfix margin-bottom">
            <h4 class="text-white less-mar3 font-weight-5">Contact Us</h4>
            <div class="clearfix"></div>
            <br />
            <address class="text-light">
              <strong class="text-white">Address:</strong> <br>
              No.28 - 63739 street lorem ipsum, <br>
              ipsum City, Country
            </address>
            <span class="text-light"><strong class="text-white">Phone:</strong> + 1 (234) 567 8901</span><br>
            <span class="text-light"><strong class="text-white">Email:</strong> xyz@abc.com </span><br>
            <span class="text-light"><strong class="text-white">Fax:</strong> + 1 (234) 567 8901</span>
            <ul class="footer-social-icons white left-align icons-plain text-center">
              <li><a class="twitter" href="javascript:void(0);"><i class="fa fa-twitter"></i></a></li>
              <li><a href="javascript:void(0);"><i class="fa fa-facebook"></i></a></li>
              <li><a class="active" href="javascript:void(0);"><i class="fa fa-google-plus"></i></a></li>
              <li><a href="javascript:void(0);"><i class="fa fa-linkedin"></i></a></li>
              <li><a href="javascript:void(0);"><i class="fa fa-dribbble"></i></a></li>
            </ul>
          </div>
          <!--end item-->

        </div>
      </div>
    </div>
    <div class="clearfix"></div>
    <!-- end section -->

    <section class="sec-padding-6 section-medium-dark">
      <div class="container">
        <div class="row">
          <div class="fo-copyright-holder text-center"> Copyright © 2025 l Topcone. All rights reserved. </div>
        </div>
      </div>
    </section>
    <div class="clearfix"></div>
    <!-- end section -->



    <a href="#" class="scrollup"></a><!-- end scroll to top of the page-->

  </div>
  <!--end site wrapper-->
  </div>
  <!--end wrapper boxed-->

  <!-- Modal HTML -->
  <div id="myModal" class="modal fade">
    <div class="modal-dialog modal-confirm">
      <div class="modal-content">
        <div class="modal-header justify-content-center">
          <div class="icon-box">
            <i class="fa fa-check"></i>
          </div>
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        </div>
        <div class="modal-body text-center">
          <h4>Great!</h4>
          <p>Your request has been sent successfully.</p>
          <!-- <button class="btn btn-success" data-dismiss="modal"><span>Start Exploring</span> <i class="fa fa-chevron-right"></i></button> -->
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="assets/homepage/js/jquery/jquery.js"></script>
  <script src="assets/homepage/js/bootstrap/bootstrap.min.js"></script>

  <script src="assets/homepage/js/less/less.min.js" data-env="development"></script>

  <!-- Scripts END -->

  <!-- Template scripts -->
  <script src="assets/homepage/js/megamenu/js/main.js"></script>
  <script src="assets/homepage/js/owl-carousel/owl.carousel.js"></script>
  <script src="assets/homepage/js/owl-carousel/custom.js"></script>
  <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
  <script type="text/javascript" src="assets/homepage/js/gmaps/jquery.gmap.min.js"></script>
  <script type="text/javascript" src="assets/homepage/js/gmaps/examples.js"></script>
  <script src="assets/homepage/js/parallax/jquery.parallax-1.1.3.js"></script>
  <script type="text/javascript" src="assets/homepage/js/cform/form-validate.js"></script>



  <script>
    $(window).load(function() {
      setTimeout(function() {

        $('.loader-live').fadeOut();
      }, 1000);
    })
  </script>
  <script src="assets/homepage/js/functions/functions.js"></script>

</body>

</html>