<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $title ?></title>
<meta name="description" content="">
<meta name="keywords" content="">

<!-- Vendor CSS Files -->
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<!-- Main CSS File -->
<link href="assets/css/main.css" rel="stylesheet">

<link href="../assets/dist/css/jquery-ui.css" rel="stylesheet" type="text/css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">

<style>
    .overlay2 {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1049;
    }

    .side-drawer {
        position: fixed;
        top: 0;
        right: -500px;
        width: 500px;
        max-width: 90vw;
        height: 100vh;
        background: white;
        transition: right 0.3s ease;
        z-index: 1050;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .side-drawer.open {
        right: 0;
    }

    .close-btn {
        font-size: 24px;
        cursor: pointer;
        background: none;
        border: none;
    }

    /* Make sure the drawer appears above calendar */
    .fc .fc-daygrid-day-frame,
    .fc .fc-timegrid-slot-lane {
        z-index: auto !important;
    }


    .side-drawer {
        margin-top: 70px;
        height: 92% !important;
        border-radius: 15px;
        max-width: 575px;
    }


    .edit-btn {
        font-size: 18px;
        color: #39b54a;
        margin-right: 5px;
    }

    .delete-btn {
        font-size: 18px;
        color: #ef4444;
    }

    .btn-icon {
        font-size: 18px;
        color: #6b7280;
    }

    .ext-tag {
        background-color: #eeebff;
        color: #8c75e7;
    }

    .pri-tag {
        background-color: #feebf4;
        color: #ed85b7;
    }

    .grp-tag {
        background-color: #ebf2ff;
        color: #6b82e2;
    }

    .f-12 {
        font-size: 12px;
    }
</style>