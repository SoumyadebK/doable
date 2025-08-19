<?php
require_once('global/config.php');
global $db;

if (!empty($_POST) && $_POST['function'] == 'run_query') {
    $all_account = $db->Execute("SELECT DOA_ACCOUNT_MASTER.DB_NAME FROM DOA_ACCOUNT_MASTER WHERE DOA_ACCOUNT_MASTER.ACTIVE = 1");

    while (!$all_account->EOF) {
        $DB_NAME = $all_account->fields['DB_NAME'];

        $db_exists = $db->Execute("SHOW DATABASES LIKE '$DB_NAME'");
        if ($db_exists->RecordCount() == 0) {
            echo "Database $DB_NAME does not exist.<br>";
        } else {

            if ($_SERVER['HTTP_HOST'] == 'localhost') {
                $conn_account_db = new mysqli('localhost', 'root', '', $DB_NAME);
            } else {
                $conn_account_db = new mysqli('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
            }

            if ($conn_account_db->connect_error) {
                die("Connection failed: " . $conn_account_db->connect_error . "<br>");
            }

            $query = $_POST['query'];
            if ($conn_account_db->multi_query($query) === FALSE) {
                echo "Error executing query on database $DB_NAME: " . $conn_account_db->error . "<br>";
            } else {
                // Clear pending results
                do {
                    if ($result = $conn_account_db->store_result()) {
                        $result->free();
                    }
                } while ($conn_account_db->more_results() && $conn_account_db->next_result());

                echo "Query executed successfully on database $DB_NAME.<br>";
            }

            $conn_account_db->close();
        }

        $all_account->MoveNext();
    }
}

?>

<h3>Enter your query to run on all database</h3>
<form method="POST" action="run_query.php">
    <input type="hidden" name="function" value="run_query">
    <label for="query">Query:</label>
    <textarea id="query" name="query" required rows="15" style="width: 800px;"></textarea>
    <br><br>
    <input type="submit" value="RUN">
</form>