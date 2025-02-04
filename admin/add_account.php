<?php
require_once('../global/config.php');

if (empty($_GET['id']))
    $title = "Add Account";
else
    $title = "Edit Account";

if($_SESSION['PK_USER'] == 0 || $_SESSION['PK_USER'] == '' || in_array($_SESSION['PK_ROLES'], [1, 4, 5])){
    header("location:../login.php");
    exit;
}

if(!empty($_POST)){
    $ACCOUNT_DATA['PK_BUSINESS_TYPE'] = $_POST['PK_BUSINESS_TYPE'];
    $ACCOUNT_DATA['PK_ACCOUNT_TYPE'] = $_POST['PK_ACCOUNT_TYPE'];
    $ACCOUNT_DATA['BUSINESS_NAME'] = $_POST['BUSINESS_NAME'];
    $ACCOUNT_DATA['ADDRESS'] = $_POST['ADDRESS'];
    $ACCOUNT_DATA['ADDRESS_1'] = $_POST['ADDRESS_1'];
    $ACCOUNT_DATA['PK_COUNTRY'] = $_POST['PK_COUNTRY'];
    $ACCOUNT_DATA['PK_STATES'] = $_POST['PK_STATES'];
    $ACCOUNT_DATA['CITY'] = $_POST['CITY'];
    $ACCOUNT_DATA['ZIP'] = $_POST['ZIP'];
    $ACCOUNT_DATA['PHONE'] = $_POST['PHONE'];
    $ACCOUNT_DATA['FAX'] = $_POST['FAX'];
    $ACCOUNT_DATA['EMAIL'] = $_POST['EMAIL'];
    $ACCOUNT_DATA['WEBSITE'] = $_POST['WEBSITE'];

    if($_FILES['BUSINESS_LOGO']['name'] != ''){
        $USER_DATA = [];
        $extn 			= explode(".",$_FILES['BUSINESS_LOGO']['name']);
        $iindex			= count($extn) - 1;
        $rand_string 	= time()."-".rand(100000,999999);
        $file11			= 'business_logo_'.$_SESSION['PK_USER'].$rand_string.".".$extn[$iindex];
        $extension   	= strtolower($extn[$iindex]);

        if($extension == "gif" || $extension == "jpeg" || $extension == "pjpeg" || $extension == "png" || $extension == "jpg"){
            $image_path    = '../uploads/business_logo/'.$file11;
            move_uploaded_file($_FILES['BUSINESS_LOGO']['tmp_name'], $image_path);
            $ACCOUNT_DATA['BUSINESS_LOGO'] = $image_path;
        }
    }


    if(empty($_GET['id'])){
        $ACCOUNT_DATA['ACTIVE'] = 1;
        $ACCOUNT_DATA['CREATED_BY']  = $_SESSION['PK_USER'];
        $ACCOUNT_DATA['CREATED_ON']  = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'insert');
    }else{
        $ACCOUNT_DATA['ACTIVE'] = $_POST['ACTIVE'];
        $ACCOUNT_DATA['EDITED_BY']	= $_SESSION['PK_USER'];
        $ACCOUNT_DATA['EDITED_ON'] = date("Y-m-d H:i");
        db_perform('DOA_ACCOUNT_MASTER', $ACCOUNT_DATA, 'update'," PK_ACCOUNT_MASTER =  '$_GET[id]'");
        $USER_DATA['ACTIVE'] = $_POST['ACTIVE'];
        db_perform('DOA_USERS', $USER_DATA, 'update'," PK_ACCOUNT_MASTER =  '$_GET[id]'");
    }
    header("location:all_accounts.php");
}

if(empty($_GET['id'])){
    $PK_BUSINESS_TYPE   = '';
    $PK_ACCOUNT_TYPE    ='';
    $API_KEY  	        = '';
    $BUSINESS_NAME 	    ='';
    $BUSINESS_LOGO      = '';
    $ADDRESS 	        ='';
    $ADDRESS_1          = '';
    $CITY  	            = '';
    $PK_STATES 	        ='';
    $ZIP 	            ='';
    $PK_COUNTRY  	    = '';
    $PHONE          	='';
    $FAX 	            ='';
    $EMAIL              = '';
    $WEBSITE  	        = '';
    $ACTIVE  	        = '';
}else {
    $res = $db->Execute("SELECT * FROM DOA_ACCOUNT_MASTER WHERE PK_ACCOUNT_MASTER = '$_GET[id]'");
    if($res->RecordCount() == 0){
        header("location:all_account.php");
        exit;
    }
    $PK_BUSINESS_TYPE   = $res->fields['PK_BUSINESS_TYPE'];
    $API_KEY  	        = $res->fields['API_KEY'];
    $BUSINESS_NAME 	    = $res->fields['BUSINESS_NAME'];
    $BUSINESS_LOGO      = $res->fields['BUSINESS_LOGO'];
    $ADDRESS 	        = $res->fields['ADDRESS'];
    $ADDRESS_1          = $res->fields['ADDRESS_1'];
    $CITY  	            = $res->fields['CITY'];
    $PK_STATES 	        = $res->fields['PK_STATES'];
    $ZIP 	            = $res->fields['ZIP'];
    $PK_COUNTRY  	    = $res->fields['PK_COUNTRY'];
    $PHONE 	            = $res->fields['PHONE'];
    $FAX 	            = $res->fields['FAX'];
    $EMAIL              = $res->fields['EMAIL'];
    $WEBSITE  	        = $res->fields['WEBSITE'];
    $PK_ACCOUNT_TYPE    = $res->fields['PK_ACCOUNT_TYPE'];
    $ACTIVE             = $res->fields['ACTIVE'];
}

?>

<!DOCTYPE html>
<html lang="en">
<?php require_once('../includes/header.php');?>
<body class="skin-default-dark fixed-layout">
<?php require_once('../includes/loader.php');?>
<div id="main-wrapper">
    <?php require_once('../includes/top_menu.php');?>
    <?php require_once('../includes/left_menu.php') ?>
    <div class="page-wrapper">
        <div class="container-fluid">
            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h4 class="text-themecolor"><?=$title?></h4>
                </div>
                <div class="col-md-7 align-self-center text-end">
                    <div class="d-flex justify-content-end align-items-center">
                        <ol class="breadcrumb justify-content-end">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                            <li class="breadcrumb-item active"><?=$title?></li>
                        </ol>

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <form class="form-material form-horizontal m-t-30" action="" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Business Type<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <select class="form-select" required name="PK_BUSINESS_TYPE" id="PK_BUSINESS_TYPE">
                                                    <option>Select Business Type</option>
                                                    <?php
                                                    $result_dropdown_query = mysqli_query($conn,"SELECT PK_BUSINESS_TYPE,BUSINESS_TYPE FROM DOA_BUSINESS_TYPE WHERE ACTIVE='1' ORDER BY PK_BUSINESS_TYPE");
                                                    while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
                                                        <option value="<?php echo $result_dropdown['PK_BUSINESS_TYPE'];?>" <?php if($result_dropdown['PK_BUSINESS_TYPE'] == $PK_BUSINESS_TYPE) echo 'selected = "selected"';?> ><?=$result_dropdown['BUSINESS_TYPE']?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Account Type<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <?php $i=1;
                                                $result_dropdown_query = mysqli_query($conn,"select PK_ACCOUNT_TYPE,ACCOUNT_TYPE from DOA_ACCOUNT_TYPE WHERE ACTIVE='1' order by PK_ACCOUNT_TYPE");
                                                while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
                                                    <input type="radio" id="PK_ACCOUNT_TYPE_<?php echo $i;?>" name="PK_ACCOUNT_TYPE" value="<?php echo $result_dropdown['PK_ACCOUNT_TYPE'];?>" <?php if($result_dropdown['PK_ACCOUNT_TYPE'] == $PK_ACCOUNT_TYPE) echo 'checked';?>>
                                                    <label for="contactChoice1"><?=$result_dropdown['ACCOUNT_TYPE']?></label>
                                                    <?php
                                                    $i++; }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Business Name<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="BUSINESS_NAME" name="BUSINESS_NAME" class="form-control" placeholder="Enter Business Name" required data-validation-required-message="This field is required" value="<?php echo $BUSINESS_NAME?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Address
                                            </label>
                                            <div class="col-md-12">
                                                <textarea class="form-control" rows="2" id="ADDRESS" name="ADDRESS"><?php echo $ADDRESS?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Address 1
                                            </label>
                                            <div class="col-md-12">
                                                <textarea class="form-control" rows="2" id="ADDRESS_1" name="ADDRESS_1" ><?php echo $ADDRESS_1?></textarea>
                                            </div>
                                        </div>

                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Country<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <div class="col-sm-12">
                                                    <select class="form-select" required name="PK_COUNTRY" id="PK_COUNTRY" onChange="fetch_state(this.value)">
                                                        <option>Select Country</option>
                                                        <?php
                                                        $result_dropdown_query = mysqli_query($conn,"select PK_COUNTRY,COUNTRY_NAME from DOA_COUNTRY WHERE ACTIVE='1' order by PK_COUNTRY");
                                                        while ($result_dropdown=mysqli_fetch_array($result_dropdown_query,MYSQLI_ASSOC)) { ?>
                                                            <option value="<?php echo $result_dropdown['PK_COUNTRY'];?>" <?php if($result_dropdown['PK_COUNTRY'] == $PK_COUNTRY) echo 'selected = "selected"';?> ><?=$result_dropdown['COUNTRY_NAME']?></option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">State<span class="text-danger">*</span>
                                            </label>
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
                                            <label class="col-md-12" for="example-text">City</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="CITY" name="CITY" class="form-control" placeholder="Enter Your City" value="<?php echo $CITY?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Postal / Zip Code</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="ZIP" name="ZIP" class="form-control" placeholder="Enter Postal / Zip Code" value="<?php echo $ZIP?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">

                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Business Phone
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="PHONE" name="PHONE" class="form-control" placeholder="Enter Phone Number" value="<?php echo $PHONE?>">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Business Fax
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="FAX" name="FAX" class="form-control" placeholder="Enter Fax" value="<?php echo $FAX;?>">
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Business Email<span class="text-danger">*</span>
                                            </label>
                                            <div class="col-md-12">
                                                <input type="email" id="EMAIL" name="EMAIL" class="form-control" placeholder="Enter Email" required data-validation-required-message="This field is required" value="<?php echo $EMAIL?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Website
                                            </label>
                                            <div class="col-md-12">
                                                <input type="text" id="WEBSITE" name="WEBSITE" class="form-control" placeholder="Enter Website" value="<?php echo $WEBSITE?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="col-md-12" for="example-text">Business Logo
                                            </label>
                                            <div class="col-md-12">
                                                <input type="file" name="BUSINESS_LOGO" id="BUSINESS_LOGO" class="form-control" > </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <?php if($BUSINESS_LOGO!=''){?><div style="width: 120px;height: 120px;margin-top: 25px;"><a class="fancybox" href="<?php echo $BUSINESS_LOGO;?>" data-fancybox-group="gallery"><img src = "<?php echo $BUSINESS_LOGO;?>" style="width:auto; height:120px" /></a></div><?php } ?>
                                </div>

                                <?php if(!empty($_GET['id'])) { ?>
                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-6">
                                            <div class="col-md-2">
                                                <label>Active</label>
                                            </div>
                                            <div class="col-md-4">
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="1" <? if($ACTIVE == 1) echo 'checked="checked"'; ?> />&nbsp;Yes</label>&nbsp;&nbsp;
                                                <label><input type="radio" name="ACTIVE" id="ACTIVE" value="0" <? if($ACTIVE == 0) echo 'checked="checked"'; ?> />&nbsp;No</label>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>

                                <button type="submit" class="btn btn-info waves-effect waves-light m-r-10 text-white">Submit</button>
                                <button type="button" class="btn btn-inverse waves-effect waves-light" onclick="window.location.href='all_accounts.php'">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once('../includes/footer.php');?>
    <script>
        $(document).ready(function() {
            fetch_state(<?php  echo $PK_COUNTRY; ?>);
        });

        function fetch_state(PK_COUNTRY){
            $(document).ready(function(event) {
                let data = "PK_COUNTRY="+PK_COUNTRY+"&PK_STATES=<?=$PK_STATES;?>";
                let value = $.ajax({
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
    </script>



</body>
</html>