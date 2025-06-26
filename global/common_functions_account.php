<?php
// +-----------------------------------------------------------------+
// |                   PhreeBooks Open Source ERP                    |
// +-----------------------------------------------------------------+
// | Copyright (c) 2008, 2009, 2010, 2011, 2012 PhreeSoft, LLC       |
// | http://www.PhreeSoft.com                                        |
// +-----------------------------------------------------------------+
// | This program is free software: you can redistribute it and/or   |
// | modify it under the terms of the GNU General Public License as  |
// | published by the Free Software Foundation, either version 3 of  |
// | the License, or any later version.                              |
// |                                                                 |
// | This program is distributed in the hope that it will be useful, |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of  |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the   |
// | GNU General Public License for more details.                    |
// +-----------------------------------------------------------------+
//  Path: /includes/common_functions._accountphp
//

// General functions used_account across modules. Divided into the following sections:
// Section 1. General Functions
// Section 2. Database Functions
// Section 3. HTML Functions
// Section 4. localization Functions
// Section 5. Extra Fields Functions
// Section 6. Validation Functions
// Section 7. Password Functions
// Section 8. Conversion Functions

/**************************************************************************************************************/
// Section 1. General Functions
/**************************************************************************************************************/
// Redirect to another page or site
function gen_redirect_account($url)
{
    global $messageStack;
    // put any messages form the messageStack into a session variable to recover after redirect
    $messageStack->convert_add_to_session();
    // clean up URL before executing it
    while (strstr($url, '&&'))    $url = str_replace('&&', '&', $url);
    // header locates should not have the &amp; in the address it breaks things
    while (strstr($url, '&amp;')) $url = str_replace('&amp;', '&', $url);
    header('Location: ' . $url);
    exit;
}

function gen_not_null_account($value)
{
    return (!is_null($value) || strlen(trim($value)) > 0) ? true : false;
}

function strip_alphanumeric_account($value)
{
    return preg_replace("/[^a-zA-Z0-9\s]/", "", $value);
}

function remove_special_chars_account($value)
{
    $value = str_replace('&', '-', $value);
    return $value;
}

function gen_js_encode_account($str)
{
    $str = str_replace('"', '\"', $str);
    $str = str_replace(chr(10), '\n', $str);
    $str = str_replace(chr(13), '', $str);
    return $str;
}

function gen_trim_string_account($string, $length = 20, $add_dots = false)
{
    return mb_strimwidth($string, 0, $length, $add_dots ? '...' : '');
}

function compare_strings_account($string1, $string2, $allow_empty = 1)
{
    if ($allow_empty == 0) {
        $string1 = trim($string1);
        $string2 = trim($string2);
        if ($string1 == '' || $string2 == '')
            return false;
    }
    if (strcmp($string1, $string2) == 0)
        return true;
    else
        return false;
}

function duplicate_email_account($db_account, $table_name, $field, $email)
{
    $email = trim($email);
    $result = $db_account->Execute("select " . $field . " from " . $table_name . " where " . $field . " = '" . $email . "' ");
    if ($result->RecordCount())
        return true;
    else
        return false;
}

function is_valid_email_account($email)
{
    $email = trim($email);
    if (!empty($email)) {
        $regexp = "/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
        if (!preg_match($regexp, $email)) {
            return false;
        } else
            return true;
    } else
        return false;
}
/*************** Other Functions *******************************/

function get_ip_address_account()
{
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else {
            $ip = getenv('REMOTE_ADDR');
        }
    }
    return $ip;
}

// Return a random value
function general_rand_account($min = null, $max = null)
{
    static $seeded;
    if (!$seeded) {
        mt_srand((float)microtime() * 1000000);
        $seeded = true;
    }
    if (isset($min) && isset($max)) {
        if ($min >= $max) {
            return $min;
        } else {
            return mt_rand($min, $max);
        }
    } else {
        return mt_rand();
    }
}

/**************************************************************************************************************/
// Section 2. Database Functions
/**************************************************************************************************************/
function db_perform_account($table, $data, $action = 'insert', $parameters = '')
{
    global $db_account;
    if (!is_array($data)) return false;
    reset($data);
    $query = '';
    if ($action == 'insert') {
        $query = 'insert into ' . $table . ' (';
        while (list($columns,) = each($data)) {
            $query .= $columns . ', ';
        }
        $query = substr($query, 0, -2) . ') values (';
        reset($data);
        while (list(, $value) = each($data)) {
            switch ((string)$value) {
                case 'now()':
                    $query .= 'now(), ';
                    break;
                case 'null':
                    $query .= 'null, ';
                    break;
                default:
                    $query .= '\'' . db_input($value) . '\', ';
                    break;
            }
        }
        $query = substr($query, 0, -2) . ')';
    } elseif ($action == 'update') {
        $query = 'update ' . $table . ' set ';
        while (list($columns, $value) = each($data)) {
            switch ((string)$value) {
                case 'now()':
                    $query .= $columns . ' = now(), ';
                    break;
                case 'null':
                    $query .= $columns .= ' = null, ';
                    break;
                default:
                    $query .= $columns . ' = \'' . db_input($value) . '\', ';
                    break;
            }
        }
        $query = substr($query, 0, -2) . ' where ' . $parameters;
    }
    // echo $query . "<br>";
    return $db_account->Execute($query);
}

function db_insert_id_account()
{
    global $db_account;
    return $db_account->insert_ID();
}

function db_input_account($string)
{
    return addslashes($string);
}

function db_prepare_input_account($string, $required = false)
{
    if (is_string($string)) {
        $temp = trim(stripslashes($string));
        if ($required && (strlen($temp) == 0)) {
            return false;
        } else {
            return ucwords($temp);
        }
    } elseif (is_array($string)) {
        reset($string);
        while (list($key, $value) = each($string)) $string[$key] = ucwords(db_prepare_input($value));
        return $string;
    } else {
        return $string;
    }
}
function db_prepare_input_no_format_account($string, $required = false)
{
    if (is_string($string)) {
        $temp = trim(stripslashes($string));
        if ($required && (strlen($temp) == 0)) {
            return false;
        } else {
            return $temp;
        }
    } elseif (is_array($string)) {
        reset($string);
        while (list($key, $value) = each($string)) $string[$key] = db_prepare_input($value);
        return $string;
    } else {
        return $string;
    }
}

function db_table_exists_account($table_name)
{
    global $db_account;
    $tables = $db_account->Execute("SHOW TABLES like '" . $table_name . "'");
    return ($tables->RecordCount() > 0) ? true : false;
}

function db_field_exists_account($table_name, $field_name)
{
    global $db_account;
    $result = $db_account->Execute("show fields from " . $table_name);
    while (!$result->EOF) {
        if ($result->fields['Field'] == $field_name) return true;
        $result->MoveNext();
    }
    return false;
}
function createthumb_gallery_account($name, $filename, $new_w, $new_h, $dir_name, $large_image = 0)
{
    $system = explode($dir_name, $name);
    $size = getimagesize($dir_name . $filename);
    if (preg_match("/jpeg|jpg|JPEG|JPG/", $system[0])) {
        $src_img = imagecreatefromjpeg($dir_name . $filename);
    }
    if (preg_match("/gif|GIF/", $system[0])) {
        $src_img = imagecreatefromgif($dir_name . $filename);
    }
    if (preg_match("/png|PNG/", $system[0])) {
        $src_img = imagecreatefrompng($dir_name . $filename);
    }

    $old_x = imagesx($src_img);
    $old_y = imagesy($src_img);
    $thumb_w = round(($old_y * $new_h) / $old_x);
    $thumb_h = round(($old_y * $new_w) / $old_x);
    $dst_img = imagecreatetruecolor($new_w, $thumb_h);
    $transparent = imagecolorallocate($dst_img, 0, 255, 0);
    imagecolortransparent($dst_img, $transparent);
    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    if ($large_image == 1)
        $file_name = $dir_name . $filename;
    else
        $file_name = $dir_name . "thumb" . $filename;
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $thumb_h, $old_x, $old_y);
    if (preg_match("/png/", $system[0])) {
        imagepng($dst_img, $file_name);
    } else {
        imagejpeg($dst_img, $file_name);
    }
    imagedestroy($dst_img);
    imagedestroy($src_img);
    return $file_name;
}
function cropImage_account($source_image, $target_image, $crop_area)
{
    // detect source image type from extension
    $source_file_name = basename($source_image);
    $ext = explode(".", $source_file_name);
    $source_image_type = $ext[1];
    //echo $source_image_type;exit;
    // create an image resource from the source image
    switch (strtolower($source_image_type)) {
        case 'jpg':
            $original_image = imagecreatefromjpeg($source_image);
            break;

        case 'jpeg':
            $original_image = imagecreatefromjpeg($source_image);
            break;

        case 'pjpeg':
            $original_image = imagecreatefromjpeg($source_image);
            break;

        case 'gif':
            $original_image = imagecreatefromgif($source_image);
            break;

        case 'png':
            $original_image = imagecreatefrompng($source_image);
            break;

        default:
            trigger_error('cropImage(): Invalid source image type', E_USER_ERROR);
            return false;
    }

    // create a blank image having the same width and height as the crop area
    // this will be our cropped image
    $cropped_image = imagecreatetruecolor($crop_area['width'], $crop_area['height']);

    // copy the crop area from the source image to the blank image created above
    imagecopy(
        $cropped_image,
        $original_image,
        0,
        0,
        $crop_area['left'],
        $crop_area['top'],
        $crop_area['width'],
        $crop_area['height']
    );

    // detect target image type from extension
    $target_file_name = basename($target_image);
    $tar_ext = explode(".", $target_file_name);
    $target_image_type = $tar_ext[1];
    //echo $target_image;exit;
    // save the cropped image to disk
    switch (strtolower($target_image_type)) {
        case 'jpg':
            imagejpeg($cropped_image, $target_image, 100);
            break;

        case 'jpeg':
            imagejpeg($cropped_image, $target_image, 100);
            break;

        case 'pjpeg':
            imagejpeg($cropped_image, $target_image, 100);
            break;

        case 'gif':
            imagegif($cropped_image, $target_image);
            break;

        case 'png':
            imagepng($cropped_image, $target_image, 0);
            break;

        default:
            trigger_error('cropImage(): Invalid target image type', E_USER_ERROR);
            imagedestroy($cropped_image);
            imagedestroy($original_image);
            return false;
    }

    // free resources
    imagedestroy($cropped_image);
    imagedestroy($original_image);

    return true;
}
function get_currency_symbol_account($db_account)
{
    $res = $db_account->Execute("SELECT CURRENCY_SYMBOL FROM APP_CURRENCY,APP_COMPANY WHERE APP_COMPANY.PK_CURRENCY = APP_CURRENCY.PK_CURRENCY AND PK_COMPANY = '$_SESSION[PK_COMPANY]' ");
    return $res->fields['CURRENCY_SYMBOL'];
}
function get_currency_symbol_1_account($db_account, $PK_COMPANY)
{
    $res = $db_account->Execute("SELECT CURRENCY_SYMBOL FROM APP_CURRENCY,APP_COMPANY WHERE APP_COMPANY.PK_CURRENCY = APP_CURRENCY.PK_CURRENCY AND PK_COMPANY = '$PK_COMPANY' ");
    return $res->fields['CURRENCY_SYMBOL'];
}
function generateRandomString_account($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function generateRandomStringNumber_account($length)
{
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function convert_to_user_date_account($date, $format, $userTimeZone, $serverTimeZone = 'CET')
{
    try {
        $dateTime = new DateTime($date, new DateTimeZone($serverTimeZone));
        $dateTime->setTimezone(new DateTimeZone($userTimeZone));
        return $dateTime->format($format);
    } catch (Exception $e) {
        return '';
    }
}
function forceDownloadQR_account($QR_MSG, $PK_QR_MASTER, $width = 350, $height = 350)
{
    $image  = 'http://chart.apis.google.com/chart?chs=' . $width . 'x' . $height . '&cht=qr&chl=' . urlencode($QR_MSG);
    $file = file_get_contents($image);
    //echo $image;exit;
    $img = file_get_contents($image);
    $target  = $_SESSION['PK_ACCOUNT'] . '_' . $PK_QR_MASTER . '.png';
    $newname = '../qr_images/' . $target;
    file_put_contents($newname, $img);

    return $newname;
}
function url_format_account($str)
{
    $str = str_replace(" ", "-", $str);
    $str = str_replace("/", "-", $str);
    $str = str_replace("\\", "-", $str);
    $str = str_replace("?", "-", $str);
    $str = str_replace("&", "-", $str);

    return $str;
}
function get_sequence_no_account($type)
{
    global $db_account;
    $res = $db_account->Execute("SELECT $type FROM NO_SEQUENCE WHERE PK_COMPANY = '$_SESSION[PK_COMPANY]' AND ACTIVE = 1 ");

    $no = $res->fields[$type] + 1;

    $db_account->Execute("UPDATE NO_SEQUENCE SET $type = '$no' WHERE PK_COMPANY = '$_SESSION[PK_COMPANY]' AND ACTIVE = 1 ");
    return $no;
}

function convert_to_inches_account($val, $METRIC_TYPE)
{
    if ($METRIC_TYPE == 2 || $METRIC_TYPE == 4) {
        $val = $val * 0.03937;
    }
    return $val;
}
/*
function my_encrypt_account($key,$txt){
	$ciphering 	= "AES-128-CTR";
	$options 	= 0;
	$crypto_key = "A!12V534s8E(RT$".$key;
	$crypto_iv  = '4583260547891161';

	$encryption = openssl_encrypt($txt, $ciphering, $crypto_key, $options, $crypto_iv);

	return $encryption;
}

function my_decrypt_account($key,$txt){
	$ciphering 	= "AES-128-CTR";
	$options 	= 0;
	$crypto_key = "A!12V534s8E(RT$".$key;
	$crypto_iv  = '4583260547891161';

	$decryption	= openssl_decrypt($txt, $ciphering, $crypto_key, $options, $crypto_iv);

	return $decryption;
}
*/
function my_encrypt_account($key, $txt)
{
    $ciphering     = "AES-128-CTR";
    $options     = 0;
    $crypto_key = "A!12V534s8E(RT$";
    $crypto_iv  = '4583260547891161';

    $encryption = openssl_encrypt($txt, $ciphering, $crypto_key, $options, $crypto_iv);

    return $encryption;
}

function my_decrypt_account($key, $txt)
{
    $ciphering     = "AES-128-CTR";
    $options     = 0;
    $crypto_key = "A!12V534s8E(RT$";
    $crypto_iv  = '4583260547891161';

    $decryption    = openssl_decrypt($txt, $ciphering, $crypto_key, $options, $crypto_iv);

    return $decryption;
}

function get_db_table_account($table_name)
{
    global $db_account;
    $tables = $db_account->Execute("SHOW TABLES like '%" . $table_name . "%'");
    $db_account_arr = get_object_vars($db_account);
    while (!$tables->EOF) {
        foreach ($tables->fields as $KEY => $VALUE)
            $result[] = $VALUE;

        $tables->MoveNext();
    }
    return $result;
}
function get_db_field_account($table_name)
{
    global $db_account;
    $result = $db_account->Execute("show fields from " . $table_name);
    $i = 0;
    while (!$result->EOF) {
        $tables[$i]['Field'] =  $result->fields['Field'];
        preg_match('/(([^()]+))/', $result->fields['Type'], $matches);
        $tables[$i]['Type'] = substr($result->fields['Type'], strlen($matches[0]) + 1, -1);
        if ($tables[$i]['Field'] == 'CREATED_ON') {
            $tables[$i]['Type'] = date("Y-m-d H:i");
        }
        $result->MoveNext();
        $i++;
    }
    return $tables;
}

function pre_r_account($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    die;
}
