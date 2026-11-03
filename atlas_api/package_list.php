<?php
require_once("../global/config.php");
require_once('../global/common_functions_account.php');

$database_id = $_GET['id'];

$account_database = 'DOA_' . $database_id;
$db_account = new queryFactory();
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $conn_account = $db_account->connect('localhost', 'root', '', $account_database);
} else {
    $conn_account = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $account_database);
}

$package_list = $db_account->Execute("SELECT PK_PACKAGE, PACKAGE_NAME FROM DOA_PACKAGE WHERE ACTIVE = 1 AND IS_DELETED = 0");
$packages = [];
while (!$package_list->EOF) {
    $packages[] = ['id' => $package_list->fields['PK_PACKAGE'], 'package_name' => $package_list->fields['PACKAGE_NAME']];
    $package_list->MoveNext();
}
$return_data['name'] = "get_packages";
$return_data['description'] = "Fetch list of packages available for enrollment";
$return_data['parameters']['type'] = "array";
$return_data['parameters']['properties'] = $packages;
echo json_encode($return_data);
?>
<!-- <html>
<head>
    <title>Package List API</title>
</head>
<body>
<h1>Package List API</h1>
<table border="1">
    <tr>
        <th>ID</th>
        <th>NAME</th>
    </tr>
    <?php foreach ($packages as $package) { ?>
        <tr>
            <td><?php echo $package['id']; ?></td>
            <td><?php echo $package['name']; ?></td>
        </tr>
    <?php } ?>
</table>
</body>
</html> -->