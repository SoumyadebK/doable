<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Account";
else
    $title = "Edit Account";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || $_SESSION['PK_ROLES'] != 1 ){
    header("location:../login.php");
    exit;
}

if (!empty($_GET['cond']) && $_GET['cond'] == 'del'){
    $db->Execute("DELETE FROM `DOA_USERS` WHERE `PK_USER` = ".$_GET['PK_USER']);
    header('location:account.php?id='.$_GET['id']);
}

$PK_ACCOUNT_MASTER = '';
$PK_BUSINESS_TYPE = '';
$PK_ACCOUNT_TYPE = '';
$BUSINESS_NAME = '';
$ACCOUNT_ADDRESS = '';
$ACCOUNT_ADDRESS_1 = '';
$ACCOUNT_PK_COUNTRY = '';
$ACCOUNT_PK_STATES = '';
$PK_STATE = '';
$ACCOUNT_CITY = '';
$ACCOUNT_ZIP = '';
$ACCOUNT_PHONE = '';
$ACCOUNT_FAX = '';
$ACCOUNT_EMAIL = '';
$ACCOUNT_WEBSITE = '';
$ACTIVE = '';
$ABLE_TO_EDIT_PAYMENT_GATEWAY = '';
$USERNAME_PREFIX = '';

$PK_USER_EDIT = '';
$USER_NAME = '';
$FIRST_NAME = '';
$LAST_NAME = '';
$EMAIL_ID = '';
$USER_IMAGE = '';
$GENDER = '';
$DOB = '';
$ADDRESS = '';
$ADDRESS_1 = '';
$PK_COUNTRY = '';
$PK_STATES = '';
$CITY = '';
$ZIP = '';
$PHONE = '';
$NOTES = '';
if(!empty($_GET['id'])) {
    $account_res = $db->Execute("SELECT * FROM `DOA_ACCOUNT_MASTER` WHERE `PK_ACCOUNT_MASTER`  = '$_GET[id]'");
    if($account_res->RecordCount() == 0){
        header("location:all_accounts.php");
        exit;
    }
    $PK_ACCOUNT_MASTER = $_GET['id'];
    $PK_BUSINESS_TYPE = $account_res->fields['PK_BUSINESS_TYPE'];
    $PK_ACCOUNT_TYPE = $account_res->fields['PK_ACCOUNT_TYPE'];
    $BUSINESS_NAME = $account_res->fields['BUSINESS_NAME'];
    $ACCOUNT_ADDRESS = $account_res->fields['ADDRESS'];
    $ACCOUNT_ADDRESS_1 = $account_res->fields['ADDRESS_1'];
    $ACCOUNT_PK_COUNTRY = $account_res->fields['PK_COUNTRY'];
    $ACCOUNT_PK_STATES = $account_res->fields['PK_STATES'];
    $ACCOUNT_CITY = $account_res->fields['CITY'];
    $ACCOUNT_ZIP = $account_res->fields['ZIP'];
    $ACCOUNT_PHONE = $account_res->fields['PHONE'];
    $ACCOUNT_FAX = $account_res->fields['FAX'];
    $ACCOUNT_EMAIL = $account_res->fields['EMAIL'];
    $ACCOUNT_WEBSITE = $account_res->fields['WEBSITE'];
    $ACTIVE = $account_res->fields['ACTIVE'];
    $USERNAME_PREFIX = $account_res->fields['USERNAME_PREFIX'];

    $user_res = $db->Execute("SELECT * FROM DOA_USERS WHERE PK_ACCOUNT_MASTER = '$_GET[id]' AND CREATED_BY = '$_SESSION[PK_USER]'");
    if($user_res->RecordCount() > 0) {
        $PK_USER_EDIT = $user_res->fields['PK_USER'];
        $USER_NAME = $user_res->fields['USER_NAME'];
        $FIRST_NAME = $user_res->fields['FIRST_NAME'];
        $LAST_NAME = $user_res->fields['LAST_NAME'];
        $EMAIL_ID = $user_res->fields['EMAIL_ID'];
        $USER_IMAGE = $user_res->fields['USER_IMAGE'];
        $GENDER = $user_res->fields['GENDER'];
        $DOB = $user_res->fields['DOB'];
        $ADDRESS = $user_res->fields['ADDRESS'];
        $ADDRESS_1 = $user_res->fields['ADDRESS_1'];
        $PK_COUNTRY = $user_res->fields['PK_COUNTRY'];
        $PK_STATES = $user_res->fields['PK_STATES'];
        $CITY = $user_res->fields['CITY'];
        $ZIP = $user_res->fields['ZIP'];
        $PHONE = $user_res->fields['PHONE'];
        $NOTES = $user_res->fields['NOTES'];
        $ABLE_TO_EDIT_PAYMENT_GATEWAY = $user_res->fields['ABLE_TO_EDIT_PAYMENT_GATEWAY'];
    }
}

$location_data = $db->Execute("SELECT * FROM `DOA_LOCATION` WHERE `PK_ACCOUNT_MASTER`  = ".$_GET['id']);
$location_count = ($location_data->RecordCount() > 0) ? $location_data->RecordCount() : 1;

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<style>
    #advice-required-entry-ACCEPT_HANDLING{width: 150px;top: 20px;position: absolute;}
    .StripeElement {
        display: block;
        width: 100%;
        height: 34px;
        padding: 6px 12px;
        font-size: 14px;
        line-height: 1.42857143;
        color: #555;
        background-color: #fff;
        background-image: none;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .StripeElement--focus {
        box-shadow: 0 1px 3px 0 #cfd7df;
    }

    .StripeElement--invalid {
        border-color: #fa755a;
    }

    .StripeElement--webkit-autofill {
        background-color: #fefde5 !important;
    }

    .SumoSelect{
        width: 90%;
    }

    /*STRIPE_CSS*/

    /* MAIN CREDIT CARD CONTAINER */

    .credit-card {
        margin: auto;
        margin-top: 20px;
        margin-bottom: 20px;
        border-radius: 7px;
        width: 95%;
        max-width: 250px;
        position: relative;
        transition: all 0.4s ease;
        box-shadow: 0 2px 4px 0 #cfd7df;
        min-height: 125px;
        padding: 13px;
        background: linear-gradient(to left, #283593, #1976d2);;
        color: #ffffff;
    }

    .credit-card.selectable:hover {
        cursor: pointer;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
    }


    /*  NUMBER FORMATTING */

    .credit-card-last4 {
        font-family: "PT Mono", Helvetica, sans-serif;
        font-size: 18px;
    }

    .credit-card-last4:before {
        content: "**** **** **** ";
        color: #4f4d4d;
        font-size: 18px;
    }

    .credit-card.american-express .credit-card-last4:before,
    .credit-card.amex .credit-card-last4:before {
        content: "**** ****** *";
        margin-right: -10px;
    }

    .credit-card.diners-club .credit-card-last4:before,
    .credit-card.diners .credit-card-last4:before {
        content: "**** ****** ";
    }

    .credit-card-expiry {
        font-family: "PT Mono", Helvetica, sans-serif;
        font-size: 18px;
        position: absolute;
        bottom: 8px;
        left: 15px;
    }


    /* BRAND CUSTOMIZATION */

    .credit-card.visa {
        background: #4862e2;
        color: #eaeef2;
    }

    .credit-card.visa .credit-card-last4:before {
        color: #8999e5;
    }

    .credit-card.mastercard {
        background: #4f0cd6;
        color: #e3e8ef;
    }

    .credit-card.mastercard .credit-card-last4:before {
        color: #8a82dd;
    }

    .credit-card.american-express,
    .credit-card.amex {
        background: #1cd8b3;
        color: #f2fcfa;
    }

    .credit-card.american-express .credit-card-last4:before,
    .credit-card.amex .credit-card-last4:before {
        color: #99efe0;
    }

    .credit-card.diners, .credit-card.diners-club {
        background: #8a38ff;
        color: #f5efff;
    }

    .credit-card.diners .credit-card-last4:before, .credit-card.diners-club .credit-card-last4:before {
        color: #b284f4;
    }

    .credit-card.discover {
        background: #f16821;
        color: #fff4ef;
    }

    .credit-card.discover .credit-card-last4:before {
        color: #ffae84;
    }

    .credit-card.jcb {
        background: #cc3737;
        color: #f7e8e8;
    }

    .credit-card.jcb .credit-card-last4:before {
        color: #f28a8a;
    }

    .credit-card.unionpay {
        background: #47bfff;
        color: #fafdff;
    }

    .credit-card.unionpay .credit-card-last4:before {
        color: #99dcff;
    }


    /*   LOGOS  */

    .credit-card::after {
        content: " ";
        position: absolute;
        bottom: 10px;
        right: 15px;
    }

    .credit-card.visa::after {
        height: 16px;
        width: 50px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAQCAYAAABUWyyMAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAC4jAAAuIwF4pT92AAABWWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgpMwidZAAAExUlEQVRIDXWWW4hWVRSA/9+ZNA3TtFJUZDIsTSNLUpIwHzTogl3oKkVERgQhvQRTINFDUdhDUdBDhBMUTUFRJnSzQglqIC+U3YZEI+wiWjmF42X07/v2WWs4M6MLvn+tvdbal7P23uf8zVar9Vyj0ZgL46EF/0ET2uEPeKzZbO4hbxT6BLoNfRy9klgnHIQzoBf/avzLsZ+APjgTPsO/ttZvBr7VcDXMgingnL/ATniK/G/IH4XdwsZsjcZ2zCXQD863ndgaYqhmq4ExARbDo7AThssGOpnnwHX96bDEpyP+4sn8EbuL2F+1uIURC6NWVkVuO7bFdM5HDAyTf2hPjbiFHyoEn4wOh0P/ip5kFrot9ELsI3AUXMR+mBmxfMB+fMoN4b+papZf+55MnNNdqhdsHL4fItl+xwIffGnklnWVTjjdPu1z4QAoJttxUcQy51mDSD7s+ohPxbe3RKqff1G5sG3hz4fYQvsWWAE3wjrohpwjd+NWfMpApcqva1IeinlLrnYRAnl8NpW0quKad5qA9sCeBbtBycGXRXxZ5R70bwv/PPw+tIXJ4pxn7FRCXq7lQ2zFfgfhEHgKlC77o9tKcm2wbH8ZvuOhL1GXS9VoXI/ZAUfBLd0MW0CZV6nGQGgvrzIOzPVlIlbwcRZwNtqFeB/KTkQ7XyyX014Ojuc9eAksTq7zIvqVl086iBVxEuWLSpXJNedHW3V3zdZczwOeCF85grV4T9jfo78D53NRznMPeNzWoF24960669WicTfuhfQdw+6CPaA454VQ7qaOQWEgn9oKTYH6Wf8x/Avwez5za3dhT4iYVf0alDxyVxpT8F0F+QJw0ZKyFWNO5JXzTnsa7MsEtDvvOGvDl3ftWv1DdsSjg6CafxLbYQLi8ZqFvwN9GziRx0p5nVy/I0oHzNZArOJv0GuDvu3kuZCl4NE4LXB3rPRl8DF508nTp9wO58BhG8jblWp8GzrVgjSGaCfVge4ExR3woq0CP1QpfRgXZGfslRHISn8S44zCb4XKEUGPhvvA3VTcXV8Eyrro4yt3e/FUP7+j8psxA9tvkf2Ud+xTFq1RE8+ekhfeXXNXOsHt13ZRG6leLwONQR+hfSkoxq34YOWIO6HFGYN/gPYr2H5o34UlkCcjXxYr8FnpnMt1vkwftcff8bPPHPxjaQ8VnCY66UTYDYo7kpKVWB55Dmr+hkjIs3tH+H1d+zdkhOB/Ifrk3XnTJHw5lndN6vPbxXb67Dt/xI5E9XyL+BfA89wBWRl3y934Cj4nTlrTo+f/tHJZ0T6YO1TuB3oxdJHjEXCX94PFsuoPgJLVfZ+8DtrX6ETMy1hxxI9+33yu63SYO+JBcCp2dtGb4eaw9eUDvcoDuDO++734s2EmeFEd8+cAVb4t7siDgb4U5/CyO04PY77GmM9gO0Y/jIWPwCLkn1ov//nwMDifhV0II4XBShXQi2C4ePEm2wudx+r+YUme/yL4rbKSR6F+LKpIq/UBxiSYDJ6EulyRY6UmOB7+riX1nGpH8sPohX0LpoMVmghvUDn/i1kJK6r45d4KB8CHfA98UI/A87APLoZpYNyq7oUd0M14G9HmX4f6CfrAMXeB35j6Oh3zEHSD/zg8xn3/A2haarqHiZpPAAAAAElFTkSuQmCC');
    }

    .credit-card.mastercard::after {
        width: 40px;
        height: 25px;
        background-image: url('data:image/png;base64,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');
    }

    .credit-card.amex::after,
    .credit-card.american-express::after {
        width: 50px;
        height: 14px;
        background-image: url('data:image/png;base64,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');
    }

    .credit-card.diners::after,
    .credit-card.diners-club::after {
        width: 30px;
        height: 24px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAYCAYAAADtaU2/AAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAAsTAAALEwEAmpwYAAAED2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiCiAgICAgICAgICAgIHhtbG5zOnRpZmY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vdGlmZi8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOkFDMEM4Rjk2NTQzRDExRTQ5MzZBQzlERDRCNDEwQzZDPC94bXBNTTpEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06RGVyaXZlZEZyb20gcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICA8c3RSZWY6aW5zdGFuY2VJRD54bXAuaWlkOkFDMEM4RjkzNTQzRDExRTQ5MzZBQzlERDRCNDEwQzZDPC9zdFJlZjppbnN0YW5jZUlEPgogICAgICAgICAgICA8c3RSZWY6ZG9jdW1lbnRJRD54bXAuZGlkOkFDMEM4Rjk0NTQzRDExRTQ5MzZBQzlERDRCNDEwQzZDPC9zdFJlZjpkb2N1bWVudElEPgogICAgICAgICA8L3htcE1NOkRlcml2ZWRGcm9t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');
    }

    .credit-card.discover::after {
        width: 50px;
        height: 14px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAOCAYAAABth09nAAAAAXNSR0IArs4c6QAAAAlwSFlzAAALEwAACxMBAJqcGAAAAVlpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IlhNUCBDb3JlIDUuNC4wIj4KICAgPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iPgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAgPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KTMInWQAAA/tJREFUSA19ll2IVVUUx73jmEYgiUpYiIREkFqQD0FhKiVIoPaSRaEPQlATZmk9JERBQVCSldjHk9iDqE8KQaTQB0lFoljDSGKiFI3VWJZZfo6n32+fta5nrrcW/O/6r8+999n77HNbVVWNHnVZqlardSlNYj1yffAWVFt9qSNPX/axxzB2kehhfERdjNs5njmO4RyyBlok52W80pNziLmVpPaPA8fgnT4btyWL0bmAdixJt1inr9FnRP/s8X+aWuda6lqQmZHsUxxklX9qR8J0Ob7vwzcDPQHsx/cPOb3oixG7A309OIjvELEetDt5Nb5ZwAV/h++kdXB7/Yp9HBvlVKqx+G4Bx4H8WnAejAH6zoCJwB05Rc0faOd6lT8X7BByHv0OuCYSDsA/D64/ZQgyO/yz4P0ZCH1fxJZiH2nEfoGvjNg+eH9wF+ZcHo/ce9EfBE+1FvJkGL+jz4G3wFTQZ7GyHcwFG4CyOxoPwHeB2TqRl8EU8CKYDCaCU+Bv8DBwUW+AuWAeSHFnRMqDkAfCmONYCvZBcDS4D/EwuAvcDyaBV4GyGDxXWFU9gx6fC3m+dKqbZcIcEvaAj4Bc2ZB5Mdibtbu6vemP2LcRO4POhZwN3zH0DeA02B75Tlh5KuxD8E/lKdivm9CwNd8ttgx5pRG8sXZVK9Efg6+Mod8Pv5NaD3zR9oIjEdf2XJtrD3dJGa5V+zd35mY87YnB346MSdHj67B/Cv0Q2uOlrAFbCquqheaXsylpyLjgvkiuvuTwYi2ncD32KuBTGwJeDNcBxdxyXaPz+tTfFHOEN42Lfg88Td8X0EvBVsY5gVYcdxC8BBxjL/AiUNbVatQ88j+jvieP1toIaG8CimfyC2DiOOCNVATuEfHlfxYod2ZMjT0aeMaVbkfLm8rbz1z7p9ytT8ExAD6srfoXeyOwtg8oSyK31wLlS+CknLjSFwlH4Z8Aj5LX7QrgApRHI+fn2iz1C+Bu+T1gfvi7qcfqqZUJPxIJe9IXfV2It9Nq8Bq4DawD7qjz3iZHbi11kH5wGHgWXciiEqiTd2BvAtPBNyBlM8Tvgw2nAS+EFHdrfsSWwX/IANpFPxExvyvWjwe/gXx46d+Jz8vgBFBWBdyRMWACGAQ77dOCWNj5VyE/Zp55eX70bsI+i/0j2kk0P4hTcHlcjhF397JHfhDtNUDsL7S10FY+3am4hrB9CMWP9lvm3IaB79Q5YA/frdMkeWn4oXTMk+Cy2ARccQHgK08pMyOvvNjGtDPW1Pi79ept5v8Xb/bpxqkr42fMiafDR1GeUAZzEP3BSy62T6ktzRjOK3YXX44x4s9mNqDeB2WsPT6+rGmnBSGt/mObOdr/Ap6tK4eqKaaFAAAAAElFTkSuQmCC');
    }

    .credit-card.jcb::after {
        width: 30px;
        height: 15px;
        background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAPCAYAAADzun+cAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAACXBIWXMAAAsTAAALEwEAmpwYAAABWWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS40LjAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyI+CiAgICAgICAgIDx0aWZmOk9yaWVudGF0aW9uPjE8L3RpZmY6T3JpZW50YXRpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgpMwidZAAACsklEQVQ4EX2U32uOYRjH32c2zIjyKyc7IDkRZw4cMEcjxIHSyoH4C6yNNJRJOZKWAyeEQqI4wQnlhE0phCHmR1NsI43ttdnm8fk8nktPWu+3Prvu676v57qv+3rve0kJpWk6FbMdVkICRemfS5LkiZPEbsSsgRpdUNpauEbc7WwiTVdjjXW+GDcd/4GJauAiVJJFGXusUhBrh/O4XYzHKsReryZwEzTBOFiZJ9ROgLIbgyRZgm11Ao1AsTPGzoAfxBl/CMxdzi3mX07jhl00YSjaZ1LHod8M6qEKxsB2TSbjFsG8fNFNJlPixkXFiT8y2Q1usAqmwC9QFvQUXoNdshhPth5cc3PVBf2gH9/OZtwIVX4UctP46AqXxIDN8BZMGK0/ztoK/Gb4Brb9IbyHOIixu4nbgu2BpXAH3+LaoM5AN1QmHwVPJ8obadstyPFnOADqLDSA2vHXlC5j4zDT8rlZ2DqYmftztG7sZbCtc2EhqPjIFsVls5gvVP2TC7SA8WJQFhWbZRP88TBx+nuM++A+qPkw4QdWdBc2wDN4BadBNcAy8ELZjeVsupbN/e3a4SvYbn/zIbC4uCfOq21wEHaC6oDUqgzYD1beAgOO2WAfdi/Es/F3U+dZO4J9B0fBFr6EPWCn3NgDbSXuObYTXsAbfA/n3fjuP4UWCA0z6IVyTOS2Ebvuvzld361vfEgHNUM9hO/cCBg3rpPrQvwOFJG10ncXby+eitVLyHm7IHFhohu2OjrEMHtudiE64Z3xTlWb0NYox7bbxCYqJvA2x+YRrzUuYFj6AM6bJxTrzsW3PSazAuVz0rcLVi76Z7hMj7BxOuNiPaw5bsFVKHatmMtYT/4YOlzoBRMPQpyKYVb1TewJHfQJboD/MIqyM51wigK9lGXGl6AWPG3IjbvhpK/iD/ZAl+AbzJMOAAAAAElFTkSuQmCC');
    }

    .credit-card.unionpay::after {
        width: 50px;
        height: 30px;
        background-image: url('data:image/png;base64,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');
    }

</style>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <div class="page-wrapper">
        <?php require_once('../includes/top_menu_bar.php') ?>
        <div class="container-fluid body_content">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="all_accounts.php">All Accounts</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>
                    </div>
                </div>
            </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card wizard-content">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="active"> <a class="nav-link active" id="home_tab_link" data-bs-toggle="tab" href="#home" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Account Info</span></a> </li>
                                    <?php if(empty($_GET['id'])) { ?>
                                        <li> <a class="nav-link" id="profile_tab_link" data-bs-toggle="tab" href="#profile" role="tab"><span class="hidden-sm-up"><i class="ti-folder"></i></span> <span class="hidden-xs-down">User Profile</span></a> </li>
                                    <?php } else { ?>
                                        <li> <a class="nav-link" data-bs-toggle="tab" href="#login" role="tab" id="logintab"><span class="hidden-sm-up"><i class="ti-list"></i></span> <span class="hidden-xs-down">User List</span></a> </li>
                                    <?php } ?>
                                    <li> <a class="nav-link" data-bs-toggle="tab" href="#billing" role="tab" id="billingtab"><span class="hidden-sm-up"><i class="ti-receipt"></i></span> <span class="hidden-xs-down">Billing</span></a> </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content tabcontent-border">
                                    <!--Account Info Tab-->
                                    <div class="tab-pane active" id="home" role="tabpanel">

                                        <form class="form-material form-horizontal" id="account_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveAccountInfoData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <div class="p-20">
                                                <div class="row align-items-end">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Type<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <select class="form-control" required name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                                                                    <option value="">Select Business Type</option>
                                                                    <?php
                                                                    $row = $db->Execute("SELECT PK_BUSINESS_TYPE,BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE='1' ORDER BY PK_BUSINESS_TYPE");
                                                                    while (!$row->EOF) { ?>
                                                                        <option value="<?php echo $row->fields['PK_BUSINESS_TYPE'];?>" <?=($row->fields['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE)?"selected":""?>><?=$row->fields['BUSINESS_TYPE']?></option>
                                                                    <?php $row->MoveNext(); } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Account Type<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <?php
                                                                $row = $db->Execute("SELECT PK_ACCOUNT_TYPE,ACCOUNT_TYPE FROM DOA_ACCOUNT_TYPE WHERE ACTIVE='1' ORDER BY PK_ACCOUNT_TYPE");
                                                                while (!$row->EOF) { ?>
                                                                    <input type="radio" name="PK_ACCOUNT_TYPE" id="<?=$row->fields['PK_ACCOUNT_TYPE'];?>" value="<?=$row->fields['PK_ACCOUNT_TYPE'];?>" <?php if($row->fields['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked';?> required>
                                                                    <label for="<?=$row->fields['PK_ACCOUNT_TYPE'];?>"><?=$row->fields['ACCOUNT_TYPE']?></label>
                                                                <?php $row->MoveNext(); } ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="BUSINESS_NAME" name="BUSINESS_NAME" class="form-control" placeholder="Enter Business Name" required value="<?php echo $BUSINESS_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Address</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS" name="ACCOUNT_ADDRESS" placeholder="Enter Address"><?php echo $ACCOUNT_ADDRESS?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Apt/Ste</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="ACCOUNT_ADDRESS_1" name="ACCOUNT_ADDRESS_1" placeholder="Enter Street/Apartment"><?php echo $ACCOUNT_ADDRESS_1?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Country</label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <select class="form-control" name="ACCOUNT_PK_COUNTRY" id="ACCOUNT_PK_COUNTRY" onChange="fetch_Account_State(this.value)">
                                                                        <option>Select Country</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_COUNTRY'];?>" <?=($row->fields['PK_COUNTRY'] == $ACCOUNT_PK_COUNTRY)?"selected":""?>><?=$row->fields['COUNTRY_NAME']?></option>
                                                                        <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">State</label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <div id="Account_State_div"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">City</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_CITY" name="ACCOUNT_CITY" class="form-control" placeholder="Enter City" value="<?php echo $ACCOUNT_CITY?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Zip Code</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_ZIP" name="ACCOUNT_ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ACCOUNT_ZIP?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Phone</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_PHONE" name="ACCOUNT_PHONE" class="form-control" placeholder="Enter Business Phone No." value="<?php echo $ACCOUNT_PHONE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Fax</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_FAX" name="ACCOUNT_FAX" class="form-control" placeholder="Enter Business Fax" value="<?php echo $ACCOUNT_FAX;?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Business Email<span class="text-danger">*</span></label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="ACCOUNT_EMAIL" name="ACCOUNT_EMAIL" class="form-control" placeholder="Enter Business Email" required value="<?php echo $ACCOUNT_EMAIL?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Website
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ACCOUNT_WEBSITE" name="ACCOUNT_WEBSITE" class="form-control" placeholder="Enter Website" value="<?php echo $ACCOUNT_WEBSITE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if(!empty($_GET['id'])) { ?>
                                                    <div class="row" style="margin-bottom: 15px; margin-top: 15px;">
                                                        <div class="col-md-1">
                                                            <label class="form-label">Active : </label>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                            <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                                        </div>
                                                    </div>
                                                <? } ?>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Username Prefix
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="USERNAME_PREFIX" name="USERNAME_PREFIX" class="form-control" placeholder="Enter Username Prefix" value="<?php echo $USERNAME_PREFIX?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </form>
                                    </div>


                                    <?php if(empty($_GET['id'])) { ?>
                                    <!--User Profile Info Tab-->
                                    <div class="tab-pane p-20" id="profile" role="tabpanel">
                                        <form class="form-material form-horizontal" id="profile_info_form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveProfileInfoData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <input type="hidden" class="PK_USER_EDIT" name="PK_USER_EDIT" value="<?=$PK_USER_EDIT?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label mb-0">Roles</label>
                                                        <input type="hidden" name="PK_ROLES" value="2">
                                                        <input type="text" class="form-control" value="Account Admin" readonly>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">User Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="USER_NAME" name="USER_NAME" class="form-control" placeholder="Enter User Name" required data-validation-required-message="This field is required" onkeyup="ValidateUsername()" value="<?=$USER_NAME?>">
                                                            </div>
                                                        </div>
                                                        <span id="lblError" style="color: red"></span>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">First Name<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="FIRST_NAME" name="FIRST_NAME" class="form-control" placeholder="Enter First Name" required value="<?=$FIRST_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Last Name
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="LAST_NAME" name="LAST_NAME" class="form-control" placeholder="Enter Last Name" value="<?=$LAST_NAME?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Email<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="email" id="EMAIL_ID" name="EMAIL_ID" class="form-control" placeholder="Enter Email Address" required data-validation-required-message="This field is required" value="<?=$EMAIL_ID?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Gender</label>
                                                            <select class="form-control form-control" id="GENDER" name="GENDER">
                                                                <option value="1" <?php if($GENDER == "1") echo 'selected = "selected"';?>>Male</option>
                                                                <option value="2" <?php if($GENDER == "2") echo 'selected = "selected"';?>>Female</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label">Date of Birth</label>
                                                            <input type="text" class="form-control datepicker-past"  id="DOB" name="DOB" value="<?=($DOB)?date('m/d/Y', strtotime($DOB)):''?>">
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Address
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS" name="ADDRESS" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Apt/Ste
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ADDRESS_1" name="ADDRESS_1" class="form-control" placeholder="Enter Address" value="<?php echo $ADDRESS_1?>">
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>


                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Country</label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <select class="form-control" name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                                                        <option>Select Country</option>
                                                                        <?php
                                                                        $row = $db->Execute("SELECT PK_COUNTRY,COUNTRY_NAME FROM DOA_COUNTRY WHERE ACTIVE = 1 ORDER BY PK_COUNTRY");
                                                                        while (!$row->EOF) { ?>
                                                                            <option value="<?php echo $row->fields['PK_COUNTRY'];?>" <?=($row->fields['PK_COUNTRY'] == $PK_COUNTRY)?"selected":""?>><?=$row->fields['COUNTRY_NAME']?></option>
                                                                            <?php $row->MoveNext(); } ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">State</label>
                                                            <div class="col-md-12">
                                                                <div class="col-sm-12">
                                                                    <div id="State_div"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">City</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter your city" value="<?php echo $CITY?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Zip Code</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Zip Code" value="<?php echo $ZIP?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Phone
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone No." value="<?php echo $PHONE?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Remarks</label>
                                                            <div class="col-md-12">
                                                                <textarea class="form-control" rows="2" id="NOTES" name="NOTES"><?php echo $NOTES?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Image Upload</label>
                                                            <div class="col-md-12">
                                                                <input type="file" name="USER_IMAGE" id="USER_IMAGE" class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <?php if($USER_IMAGE!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $USER_IMAGE;?>" data-fancybox-group="gallery"><img src = "<?php echo $USER_IMAGE;?>" style="width:120px; height:120px" /></a></div><?php } ?>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Password<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="PASSWORD" id="PASSWORD" onkeyup="isGood(this.value)" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Confirm Password<span class="text-danger">*</span>
                                                            </label>
                                                            <div class="col-md-12">
                                                                <input type="password" autocomplete="off" class="form-control" placeholder="Password" aria-label="Password" aria-describedby="basic-addon3" name="CONFIRM_PASSWORD" id="CONFIRM_PASSWORD" onkeyup="isGood(this.value)" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-12">
                                                        <span style="color:red">Note  : Password Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters</span>
                                                    </div>
                                                </div>
                                                <div class="row" style="margin-bottom: 20px;">
                                                    <div class="col-2">
                                                        Password Strength:
                                                    </div>
                                                    <div class="col-3">
                                                        <small id="password-text"></small>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <label class="col-md-12"><input type="checkbox" id="ABLE_TO_EDIT_PAYMENT_GATEWAY" name="ABLE_TO_EDIT_PAYMENT_GATEWAY" class="form-check-inline" <?=($ABLE_TO_EDIT_PAYMENT_GATEWAY == 1)?'checked':''?>> Able to edit payment gateway</label>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white"><?=empty($_GET['id'])?'Continue':'Save'?></button>
                                                <button type="button" id="cancel_button" class="btn btn-inverse waves-effect waves-light">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php } else { ?>
                                    <!--User List Tab-->
                                    <div class="tab-pane p-20" id="login" role="tabpanel">
                                        <table id="myTable" class="table table-striped border">
                                            <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Roles</th>
                                                <th>Email Id</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <?php
                                            $i=1;
                                            $row = $db->Execute("SELECT DISTINCT (DOA_USERS.PK_USER), CONCAT(DOA_USERS.FIRST_NAME, ' ', DOA_USERS.LAST_NAME) AS NAME, DOA_USERS.USER_NAME, DOA_USERS.EMAIL_ID, DOA_USERS.ACTIVE FROM DOA_USERS LEFT JOIN DOA_USER_ROLES ON DOA_USERS.PK_USER = DOA_USER_ROLES.PK_USER WHERE DOA_USER_ROLES.PK_ROLES IN(2,3,5,6,7,8) AND DOA_USERS.PK_ACCOUNT_MASTER='$_GET[id]'");
                                            while (!$row->EOF) {
                                                $selected_roles = [];
                                                if(!empty($row->fields['PK_USER'])) {
                                                    $PK_USER = $row->fields['PK_USER'];
                                                    $selected_roles_row = $db->Execute("SELECT DOA_ROLES.ROLES FROM `DOA_USER_ROLES` LEFT JOIN DOA_ROLES ON DOA_USER_ROLES.PK_ROLES = DOA_ROLES.PK_ROLES WHERE `PK_USER` = '$PK_USER'");
                                                    while (!$selected_roles_row->EOF) {
                                                        $selected_roles[] = $selected_roles_row->fields['ROLES'];
                                                        $selected_roles_row->MoveNext();
                                                    }
                                                } ?>
                                                <tr>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$i;?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['NAME']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['USER_NAME']?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=implode(', ', $selected_roles)?></td>
                                                    <td onclick="editpage(<?=$row->fields['PK_USER']?>, <?=$_GET['id']?>);"><?=$row->fields['EMAIL_ID']?></td>
                                                    <td style="padding: 10px 0px 0px 0px;font-size: 20px;">
                                                        <a href="edit_account_user.php?id=<?=$row->fields['PK_USER']?>&ac_id=<?=$_GET['id']?>" title="Reset Password" style="color: #03a9f3;"><i class="ti-lock"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                                        <?php if($row->fields['ACTIVE']==1){ ?>
                                                            <span title="Active" class="active-box-green"></span>
                                                        <?php } else{ ?>
                                                            <span title="Inactive" class="active-box-red"></span>
                                                        <?php } ?>&nbsp;&nbsp;
                                                        <a href="javascript:;" data-href="account.php?id=<?=$_GET['id']?>&PK_USER=<?=$row->fields['PK_USER']?>&cond=del" onclick="confirmDelete(this);" title="Delete" style="color: red;"><i class="ti-trash"></i></a>
                                                    </td>
                                                </tr>
                                                <?php $row->MoveNext();
                                                $i++; } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } ?>


                                    <div class="tab-pane p-20" id="billing" role="tabpanel">
                                        <form class="form-material form-horizontal" id="stripe-payment-form">
                                            <input type="hidden" name="FUNCTION_NAME" value="saveBillingData">
                                            <input type="hidden" class="PK_ACCOUNT_MASTER" name="PK_ACCOUNT_MASTER" value="<?=$PK_ACCOUNT_MASTER?>">
                                            <div class="p-20">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="row">
                                                            <div class="form-group">
                                                                <label class="form-label" style="margin-bottom: 5px;">Billing Type</label><br>
                                                                <label style="margin-right: 70px;"><input type="radio" name="BILLING_TYPE" class="form-check-inline BILLING_TYPE" value="PER_ACCOUNT" onchange="calculatePaymentAmount()" checked>Bill Per Account</label>
                                                                <label style="margin-right: 70px;"><input type="radio" name="BILLING_TYPE" class="form-check-inline BILLING_TYPE" value="PER_LOCATION" onchange="calculatePaymentAmount()">Bill Per Location</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="AMOUNT" name="AMOUNT" class="form-control" placeholder="Enter Amount" onkeyup="calculatePaymentAmount()">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group">
                                                            <label class="col-md-12">Total Amount</label>
                                                            <div class="col-md-12">
                                                                <input type="text" id="TOTAL_AMOUNT" name="TOTAL_AMOUNT" class="form-control" placeholder="Total Amount" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="card-element"></div>

                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .progress-bar {
        border-radius: 5px;
        height:18px !important;
    }
</style>
<?php require_once('../includes/footer.php');?>
<script>
    $('.datepicker-past').datepicker({
        format: 'mm/dd/yyyy',
        maxDate: 0
    });

    $(document).ready(function() {
        fetch_state(<?php  echo $PK_COUNTRY; ?>);
        fetch_Account_State(<?php  echo $ACCOUNT_PK_COUNTRY; ?>);
    });

    function fetch_state(PK_COUNTRY){

        jQuery(document).ready(function($) {

            var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";

            var value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache :false,
                success: function (result) {
                    document.getElementById('State_div').innerHTML = result;

                }
            }).responseText;
        });
    }
    function fetch_Account_State(PK_COUNTRY){

        jQuery(document).ready(function($) {

            var data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$ACCOUNT_PK_STATES;?>";

            var value = $.ajax({
                url: "ajax/state.php",
                type: "POST",
                data: data,
                async: false,
                cache :false,
                success: function (result) {
                    document.getElementById('Account_State_div').innerHTML = result;

                }
            }).responseText;
        });
    }

</script>
<script>
    function isGood(password) {
        //alert(password);
        var password_strength = document.getElementById("password-text");

        //TextBox left blank.
        if (password.length == 0) {
            password_strength.innerHTML = "";
            return;
        }

        //Regular Expressions.
        var regex = new Array();
        regex.push("[A-Z]"); //Uppercase Alphabet.
        regex.push("[a-z]"); //Lowercase Alphabet.
        regex.push("[0-9]"); //Digit.
        regex.push("[$@$!%*#?&]"); //Special Character.

        var passed = 0;

        //Validate for each Regular Expression.
        for (var i = 0; i < regex.length; i++) {
            if (new RegExp(regex[i]).test(password)) {
                passed++;
            }
        }

        //Display status.
        var strength = "";
        switch (passed) {
            case 0:
            case 1:
            case 2:
                strength = "<small class='progress-bar bg-danger' style='width: 50%'>Weak</small>";
                break;
            case 3:
                strength = "<small class='progress-bar bg-warning' style='width: 60%'>Medium</small>";
                break;
            case 4:
                strength = "<small class='progress-bar bg-success' style='width: 100%'>Strong</small>";
                break;

        }
        // alert(strength);
        password_strength.innerHTML = strength;

    }
    function ValidateUsername() {
        var username = document.getElementById("User_Id").value;
        var lblError = document.getElementById("lblError");
        lblError.innerHTML = "";
        var expr = /^[a-zA-Z0-9_]{8,20}$/;
        if (!expr.test(username)) {
            lblError.innerHTML = "Only Alphabets, Numbers and Underscore and between 8 to 20 characters.";
        }
        else{
            lblError.innerHTML = "";
        }
    }

    function editpage(PK_USER, AC_ID){
        window.location.href = "edit_account_user.php?id="+PK_USER+"&ac_id="+AC_ID;
    }

    function confirmDelete(anchor)
    {
        let conf = confirm("Are you sure you want to delete?");
        if(conf)
            window.location=$(anchor).data("href");
    }

    $(document).on('click', '#cancel_button', function () {
        window.location.href='all_accounts.php';
    });

    let PK_ACCOUNT_MASTER = parseInt(<?=empty($_GET['id'])?0:$_GET['id']?>);

    $(document).on('submit', '#account_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#account_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'JSON',
            success:function (data) {
                $('.PK_ACCOUNT_MASTER').val(data);
                if (PK_ACCOUNT_MASTER == 0) {
                    $('#profile_tab_link')[0].click();
                }else{
                   window.location.href='all_accounts.php';
                }
            }
        });
    });

    $(document).on('submit', '#profile_info_form', function (event) {
        event.preventDefault();
        let form_data = $('#profile_info_form').serialize();
        $.ajax({
            url: "ajax/AjaxFunctions.php",
            type: 'POST',
            data: form_data,
            dataType: 'JSON',
            success:function (data) {
                $('.PK_ACCOUNT_MASTER').val(data);
                window.location.href='all_accounts.php';
            }
        });
    });

    function calculatePaymentAmount() {
        let BILLING_TYPE = $('.BILLING_TYPE:checked').val();
        let AMOUNT = $('#AMOUNT').val();
        let LOCATION_COUNT = parseInt(<?=$location_count?>);

        if (BILLING_TYPE == 'PER_ACCOUNT') {
            $('#TOTAL_AMOUNT').val(AMOUNT);
        } else {
            $('#TOTAL_AMOUNT').val(AMOUNT*LOCATION_COUNT);
        }
    }
</script>


<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    stripePaymentFunction();
    function stripePaymentFunction() {

        // Create a Stripe client.
        var stripe = Stripe('pk_test_fK4FLd857rrVoQ8rWarTDBKW');

        // Create an instance of Elements.
        var elements = stripe.elements();

        // Custom styling can be passed to options when creating an Element.
        // (Note that this demo uses a wider set of styles than the guide below.)
        var style = {
            base: {
                height: '34px',
                padding: '6px 12px',
                fontSize: '14px',
                lineHeight: '1.42857143',
                color: '#555',
                backgroundColor: '#fff',
                border: '1px solid #ccc',
                borderRadius: '4px',
                '::placeholder': {
                    color: '#ddd'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        // Create an instance of the card Element.
        var card = elements.create('card', {style: style});

        // Add an instance of the card Element into the `card-element` <div>.
        if (($('#card-element')).length > 0) {
            card.mount('#card-element');
        }

        // Handle real-time validation errors from the card Element.
        card.addEventListener('change', function (event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission.
        var form = document.getElementById('stripe-payment-form');
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            stripe.createToken(card).then(function (result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    // Send the token to your server.
                    stripeTokenHandler(result.token);
                }
            });
        });

        // Submit the form with the token ID.
        function stripeTokenHandler(token) {
            // Insert the token ID into the form so it gets submitted to the server
            var form = document.getElementById('stripe-payment-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'token');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);

            //ACCEPT_HANDLING_ERROR
            // Submit the form
            form.submit();
        }
    }

</script>


</body>
</html>