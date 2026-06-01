<?php
$ENTERED_PASSWORD = trim($_POST['ENTERED_PASSWORD']);
$SAVED_PASSWORD = trim($_POST['SAVED_PASSWORD']);
if (password_verify($ENTERED_PASSWORD, $SAVED_PASSWORD)) {
    echo 1;
}else{
    echo 0;
}
