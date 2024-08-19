<head>
    <meta charset="UTF-8">
    <title><?=$title?></title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="stylesheet" type="text/css" href="../assets/node_modules/datatables.net-bs4/css/dataTables.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="../assets/node_modules/datatables.net-bs4/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="../assets/node_modules/html5-editor/bootstrap-wysihtml5.css" />
    <link href="../assets/dist/css/jquery-ui.css" rel="stylesheet" type="text/css">
    <!-- Dropzone css -->
    <link href="../assets/node_modules/dropzone-master/dist/dropzone.css" rel="stylesheet" type="text/css" />
    <!-- Custom CSS -->
    <link href="../assets/dist/css/style.min.css" rel="stylesheet">
    <link href="../assets/dist/css/credit-card.css" rel="stylesheet">
    <!-- page css -->
    <link href="../assets/dist/css/pages/inbox.css" rel="stylesheet">

    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">

    <link href="../assets/sumoselect/sumoselect.min.css" rel="stylesheet"/>

    <style>
        .multiselect-box {
            margin-bottom: 15px;
            margin-top: 6px;
            width: 149%;
        }
    </style>

    <style>
        body {font-family: Arial, Helvetica, sans-serif;}
    </style>

    <style>
        span.active-box-green {
            display: inline-block;
            width: 19px;
            height: 19px;
            background-color: #02f737;
            vertical-align: top;
            position: relative;
            top: 2px;
            margin: 0 3px 0 0;
        }

        span.active-box-red {
            display: inline-block;
            width: 19px;
            height: 19px;
            background-color: red;
            vertical-align: top;
            position: relative;
            top: 2px;
            margin: 0 2px 0 0;
        }

        .div_inactive{
            pointer-events: none;
            opacity: 0.4;
        }
    </style>

    <style>
        .center .pagination {
            display: inline-block;
        }
        .center .pagination a {
            color: black;
            float: left;
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color .3s;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 5px;
        }
        .center .pagination a.active {
            background-color: #39B54A;
            color: white;
            border: 1px solid #39B54A;
        }
        .center .pagination a.hidden {
            display: none;
        }
        .center .pagination a:hover:not(.active) {background-color: #ddd;}

        .outer{
            width:100%;
            overflow:auto;
            white-space:nowrap;
        }
        .outer ul{
            text-align: center;
        }
        .outer li{
            display: inline-block;
            *display: inline;/*For IE7*/
            *zoom:1;/*For IE7*/
            vertical-align:top;
            white-space:normal;
        }
    </style>
    <style>
        .body_content {
            margin-top: 67px;
        }
    </style>
    <style>
        th {
            font-weight: bold !important;
        }
    </style>
</head>
