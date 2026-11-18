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
//  Path: /includes/common_functions.php
//  Updated for PHP 8.x (tested against 8.0 - 8.5 behaviour)
//

// General functions used across modules. Divided into the following sections:
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
    $url = (string)$url;
    // put any messages form the messageStack into a session variable to recover after redirect
    $messageStack->convert_add_to_session();
    // clean up URL before executing it
    while (strpos($url, '&&') !== false)    $url = str_replace('&&', '&', $url);
    // header locates should not have the &amp; in the address it breaks things
    while (strpos($url, '&amp;') !== false) $url = str_replace('&amp;', '&', $url);
    header('Location: ' . $url);
    exit;
}

function gen_not_null_account($value)
{
    // original used "||" which made this true for almost everything and
    // called strlen(null) which is deprecated since PHP 8.1
    if (is_null($value)) return false;
    if (is_array($value)) return count($value) > 0;
    return strlen(trim((string)$value)) > 0;
}

function strip_alphanumeric_account($value)
{
    return preg_replace("/[^a-zA-Z0-9\s]/", "", (string)$value);
}

function remove_special_chars_account($value)
{
    return str_replace('&', '-', (string)$value);
}

function gen_js_encode_account($str)
{
    $str = (string)$str;
    $str = str_replace('"', '\"', $str);
    $str = str_replace(chr(10), '\n', $str);
    $str = str_replace(chr(13), '', $str);
    return $str;
}

function gen_trim_string_account($string, $length = 20, $add_dots = false)
{
    return mb_strimwidth((string)$string, 0, (int)$length, $add_dots ? '...' : '');
}

function compare_strings_account($string1, $string2, $allow_empty = 1)
{
    $string1 = (string)$string1;
    $string2 = (string)$string2;
    if ($allow_empty == 0) {
        $string1 = trim($string1);
        $string2 = trim($string2);
        if ($string1 == '' || $string2 == '')
            return false;
    }
    return strcmp($string1, $string2) == 0;
}

function duplicate_email_account($db_account, $table_name, $field, $email)
{
    $email = db_input(trim((string)$email));
    $result = $db_account->Execute("select " . $field . " from " . $table_name . " where " . $field . " = '" . $email . "' ");
    return $result->RecordCount() ? true : false;
}

function is_valid_email_account($email)
{
    $email = trim((string)$email);
    if ($email === '') return false;
    $regexp = "/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
    return (bool)preg_match($regexp, $email);
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
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
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
    // mt_srand() seeding removed: PHP seeds automatically (since 7.1)
    if (isset($min) && isset($max)) {
        if ($min >= $max) {
            return $min;
        } else {
            return mt_rand((int)$min, (int)$max);
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
    $query = '';
    if ($action == 'insert') {
        // each() was removed in PHP 8.0 - replaced with foreach
        $query = 'insert into ' . $table . ' (' . implode(', ', array_keys($data)) . ') values (';
        foreach ($data as $value) {
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
        foreach ($data as $columns => $value) {
            switch ((string)$value) {
                case 'now()':
                    $query .= $columns . ' = now(), ';
                    break;
                case 'null':
                    $query .= $columns . ' = null, ';
                    break;
                default:
                    $query .= $columns . ' = \'' . db_input($value) . '\', ';
                    break;
            }
        }
        $query = substr($query, 0, -2) . ' where ' . $parameters;
    }
    return $db_account->Execute($query);
}

function db_insert_id_account()
{
    global $db_account;
    return $db_account->insert_ID();
}

function db_input_account($string)
{
    // NOTE: addslashes() is not a safe escape for SQL. If your $db_account object exposes
    // a real escape (e.g. mysqli::real_escape_string / ADOdb qstr), use that instead.
    return addslashes((string)$string);
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
        foreach ($string as $key => $value) {
            $string[$key] = db_prepare_input($value);
        }
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
        foreach ($string as $key => $value) {
            $string[$key] = db_prepare_input_no_format($value);
        }
        return $string;
    } else {
        return $string;
    }
}

function db_table_exists_account($table_name)
{
    global $db_account;
    $tables = $db_account->Execute("SHOW TABLES like '" . db_input($table_name) . "'");
    return $tables->RecordCount() > 0;
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
    // explode() with an empty separator throws ValueError in PHP 8
    $system = ($dir_name !== '' && $dir_name !== null) ? explode($dir_name, $name) : [$name];
    $src_img = false;
    if (preg_match("/jpeg|jpg/i", $system[0])) {
        $src_img = imagecreatefromjpeg($dir_name . $filename);
    } elseif (preg_match("/gif/i", $system[0])) {
        $src_img = imagecreatefromgif($dir_name . $filename);
    } elseif (preg_match("/png/i", $system[0])) {
        $src_img = imagecreatefrompng($dir_name . $filename);
    }
    if (!$src_img) return false;   // unsupported / unreadable image

    $old_x = imagesx($src_img);
    $old_y = imagesy($src_img);
    // GD functions in PHP 8.1+ deprecate implicit float -> int conversion
    $new_w   = (int)$new_w;
    $thumb_h = (int)round(($old_y * $new_w) / $old_x);
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
    if (preg_match("/png/i", $system[0])) {
        imagepng($dst_img, $file_name);
    } else {
        imagejpeg($dst_img, $file_name);
    }
    // imagedestroy() is a no-op since PHP 8.0 (GdImage objects) and deprecated in 8.5.
    // Images are freed automatically when the variables go out of scope.
    unset($dst_img, $src_img);
    return $file_name;
}

function cropImage_account($source_image, $target_image, $crop_area)
{
    // detect source image type from extension (pathinfo is safe with dots in names)
    $source_image_type = strtolower(pathinfo(basename($source_image), PATHINFO_EXTENSION));

    switch ($source_image_type) {
        case 'jpg':
        case 'jpeg':
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
    if (!$original_image) return false;

    // create a blank image having the same width and height as the crop area
    $cropped_image = imagecreatetruecolor((int)$crop_area['width'], (int)$crop_area['height']);

    // copy the crop area from the source image to the blank image created above
    imagecopy(
        $cropped_image,
        $original_image,
        0,
        0,
        (int)$crop_area['left'],
        (int)$crop_area['top'],
        (int)$crop_area['width'],
        (int)$crop_area['height']
    );

    // detect target image type from extension
    $target_image_type = strtolower(pathinfo(basename($target_image), PATHINFO_EXTENSION));

    switch ($target_image_type) {
        case 'jpg':
        case 'jpeg':
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
            return false;
    }

    // no imagedestroy() needed (deprecated in PHP 8.5)
    unset($cropped_image, $original_image);

    return true;
}

function get_currency_symbol_account($db_account)
{
    $res = $db_account->Execute("SELECT CURRENCY_SYMBOL FROM APP_CURRENCY,APP_COMPANY WHERE APP_COMPANY.PK_CURRENCY = APP_CURRENCY.PK_CURRENCY AND PK_COMPANY = '" . db_input($_SESSION['PK_COMPANY'] ?? '') . "' ");
    return $res->fields['CURRENCY_SYMBOL'];
}

function get_currency_symbol_1_account($db_account, $PK_COMPANY)
{
    $res = $db_account->Execute("SELECT CURRENCY_SYMBOL FROM APP_CURRENCY,APP_COMPANY WHERE APP_COMPANY.PK_CURRENCY = APP_CURRENCY.PK_CURRENCY AND PK_COMPANY = '" . db_input($PK_COMPANY) . "' ");
    return $res->fields['CURRENCY_SYMBOL'];
}

function generateRandomString_account($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function generateRandomStringNumber_account($length)
{
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function convert_to_user_date_account($date, $format, $userTimeZone, $serverTimeZone = 'CET')
{
    try {
        $dateTime = new DateTime((string)$date, new DateTimeZone($serverTimeZone));
        $dateTime->setTimezone(new DateTimeZone($userTimeZone));
        return $dateTime->format($format);
    } catch (Exception $e) {
        return '';
    }
}

function forceDownloadQR_account($QR_MSG, $PK_QR_MASTER, $width = 350, $height = 350)
{
    // WARNING: the Google Image Charts QR API was shut down. Replace this URL with
    // a working QR service (or a PHP library such as endroid/qr-code / chillerlan/php-qrcode).
    $image = 'https://chart.apis.google.com/chart?chs=' . (int)$width . 'x' . (int)$height . '&cht=qr&chl=' . urlencode((string)$QR_MSG);
    $img = @file_get_contents($image);
    if ($img === false) return false;
    $target  = ($_SESSION['PK_ACCOUNT'] ?? '') . '_' . $PK_QR_MASTER . '.png';
    $newname = '../qr_images/' . $target;
    file_put_contents($newname, $img);

    return $newname;
}

function url_format_account($str)
{
    return str_replace([" ", "/", "\\", "?", "&"], "-", (string)$str);
}

function get_sequence_no_account($type)
{
    global $db_account;
    $company = db_input($_SESSION['PK_COMPANY'] ?? '');
    $res = $db_account->Execute("SELECT $type FROM NO_SEQUENCE WHERE PK_COMPANY = '$company' AND ACTIVE = 1 ");

    $no = (int)$res->fields[$type] + 1;

    $db_account->Execute("UPDATE NO_SEQUENCE SET $type = '$no' WHERE PK_COMPANY = '$company' AND ACTIVE = 1 ");
    return $no;
}

function convert_to_inches_account($val, $METRIC_TYPE)
{
    if ($METRIC_TYPE == 2 || $METRIC_TYPE == 4) {
        $val = $val * 0.03937;
    }
    return $val;
}

// NOTE: kept identical to the original so existing encrypted data can still be decrypted.
// $key is ignored (as before). The hard-coded key/IV and AES-CTR without authentication
// are weak; consider migrating to AES-256-GCM with a random IV and key stored outside the code.
function my_encrypt_account($key, $txt)
{
    $ciphering  = "AES-128-CTR";
    $options    = 0;
    $crypto_key = 'A!12V534s8E(RT$';   // single quotes: avoids "$" interpolation ambiguity
    $crypto_iv  = '4583260547891161';

    return openssl_encrypt((string)$txt, $ciphering, $crypto_key, $options, $crypto_iv);
}

function my_decrypt_account($key, $txt)
{
    $ciphering  = "AES-128-CTR";
    $options    = 0;
    $crypto_key = 'A!12V534s8E(RT$';
    $crypto_iv  = '4583260547891161';

    return openssl_decrypt((string)$txt, $ciphering, $crypto_key, $options, $crypto_iv);
}

function get_db_table_account($table_name)
{
    global $db_account;
    $result = [];   // was undefined when no tables matched
    $tables = $db_account->Execute("SHOW TABLES like '%" . db_input($table_name) . "%'");
    while (!$tables->EOF) {
        foreach ($tables->fields as $VALUE)
            $result[] = $VALUE;

        $tables->MoveNext();
    }
    return $result;
}

function get_db_field_account($table_name)
{
    global $db_account;
    $tables = [];
    $result = $db_account->Execute("show fields from " . $table_name);
    $i = 0;
    while (!$result->EOF) {
        $tables[$i]['Field'] = $result->fields['Field'];
        // original pattern had unescaped parentheses; escape them to capture "(...)" of the type
        if (preg_match('/\(([^()]+)\)/', $result->fields['Type'], $matches)) {
            $tables[$i]['Type'] = $matches[1];
        } else {
            $tables[$i]['Type'] = '';
        }
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

function getUserIdMainDb_account($PK_USER)
{
    global $db_account_account;
    $user_data = $db_account_account->Execute("SELECT * FROM `DOA_USERS` WHERE `PK_USER` = '" . db_input($PK_USER) . "'");
    return $user_data->fields['PK_USER_MASTER_DB'];
}
