<?php
require_once('global/config.php');
if(isset($_POST['subforgot'])){
    $login=$_REQUEST['login_var'];
    $query = "SELECT * from  DOA_USERS where USER_NAME='$login' OR EMAIL_ID = '$login')";
    $res = mysqli_query($dbc,$query);
    $count=mysqli_num_rows($res);
//echo $count;
    if($count==1)
    {
        $findresult = mysqli_query($dbc, "SELECT * FROM DOA_USERS WHERE (USER_NAME='$login' OR EMAIL_ID = '$login')");
        if($res = mysqli_fetch_array($findresult))
        {
            $oldftemail = $res['EMAIL_ID'];
        }
        $token = bin2hex(random_bytes(50));
        $inresult = mysqli_query($dbc,"insert into DOA_PASS_RESET values('','$oldftemail','$token')");
        if ($inresult)
        {
            $FromName="Doable";
            $FromEmail="no_reply@doable.com";
            $ReplyTo="doable@gmail.com";
            $credits="All rights are reserved | Doable ";
            $headers  = "MIME-Version: 1.0\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\n";
            $headers .= "From: ".$FromName." <".$FromEmail.">\n";
            $headers .= "Reply-To: ".$ReplyTo."\n";
            $headers .= "X-Sender: <".$FromEmail.">\n";
            $headers .= "X-Mailer: PHP\n";
            $headers .= "X-Priority: 1\n";
            $headers .= "Return-Path: <".$FromEmail.">\n";
            $subject="You have received password reset email";
            $msg="Your password reset link <br> http://localhost:8081/php/form/password-reset.php?token=".$token." <br> Reset your password with this link .Click or open in new tab<br><br> <br> <br> <center>".$credits."</center>";
            if(@mail($oldftemail, $subject, $msg, $headers,'-f'.$FromEmail) ){
                header("location:forgot-password.php?sent=1");
                $hide='1';

            } else {

                header("location:forgot-password.php?servererr=1");
            }
        }
        else
        {
            header("location:forgot-password.php?something_wrong=1");
        }
    }
    else
    {
        header("location:forgot-password.php?err=".$login);
    }
}
?>