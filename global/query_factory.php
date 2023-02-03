<?php
class queryFactory
{

    private $count_queries;
    private $total_query_time;
    /**
     * @var false|mysqli
     */
    public $link;
    public $db_connected;
    public $database;
    public $error_number;

    public function __construct()
    {
        $this->count_queries = 0;
        $this->total_query_time = 0;
    }

    public function connect($zf_host, $zf_user, $zf_password, $zf_database)
    {
        $this->database = $zf_database;
        // pconnect disabled
        $this->link = mysqli_connect($zf_host, $zf_user, $zf_password, $zf_database);
        if ($this->link) {
            //echo "aaa";exit;
            //  if (@mysql_select_db($zf_database, $this->link)) {
            $this->db_connected = true;
            // set the character set
            //mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'", $this->link);
            //        mysql_query("SET NAMES utf8", $this->link);
            //        mysql_query("SET CHARACTER SET utf8", $this->link);
            //        mysql_set_charset('utf8', $this->link);
            return $this->link;
            // } else {
            // $this->set_error($this->link->errno, $this->link->connect_errno, false);
            // }
        } else {
            //echo "ccc";exit;
            $this->set_error($this->link->errno, $this->link->connect_errno, false);
        }
        return false;
    }

    public function selectdb($zf_database)
    {
        mysqli_select_db($zf_database, $this->link);
    }

    public function transStart()
    {
        $this->Execute("START TRANSACTION");
    }

    public function transCommit()
    {
        $this->Execute("COMMIT");
    }

    public function transRollback()
    {
        $this->Execute("ROLLBACK");
    }

    public function prepare_input($zp_string)
    {
        if (function_exists('mysql_real_escape_string')) {
            return mysqli_real_escape_string($zp_string, $this->link);
        } elseif (function_exists('mysql_escape_string')) {
            return mysqli_escape_string($zp_string, $this->link);
        } else {
            return addslashes($zp_string);
        }
    }

    public function close()
    {
        mysqli_close($this->link);
    }

    public function set_error($zp_err_num, $zp_err_text, $zp_fatal = true)
    {
        $this->error_number = $zp_err_num;
        $this->error_text = $zp_err_text;
        if ($zp_fatal && $zp_err_num != 1141) {
            $this->show_error();
            die();
        }
    }

    public function show_error()
    {
        echo $this->error_number . ' ' . $this->error_text;
    }

    public function Execute($zf_sql, $zf_limit = false, $zf_cache = false, $zf_cachetime = 0)
    {
        global $zc_cache, $messageStack;
        if ($zf_limit) {
            $zf_sql = $zf_sql . ' LIMIT ' . $zf_limit;
        }
        if ($zf_cache and $zc_cache->sql_cache_exists($zf_sql) and !$zc_cache->sql_cache_is_expired($zf_sql, $zf_cachetime)) {
            $obj = new queryFactoryResult;
            $obj->cursor = 0;
            $obj->is_cached = true;
            $obj->sql_query = $zf_sql;
            $zp_result_array = $zc_cache->sql_cache_read($zf_sql);
            $obj->result = $zp_result_array;
            if (sizeof($zp_result_array) > 0) {
                $obj->EOF = false;
                while (list($key, $value) = each($zp_result_array[0])) {
                    $obj->fields[$key] = $value;
                }
                return $obj;
            } else {
                $obj->EOF = true;
            }
        } elseif ($zf_cache) {
            $zc_cache->sql_cache_expire_now($zf_sql);
            $time_start = explode(' ', microtime());
            $obj = new queryFactoryResult;
            $obj->sql_query = $zf_sql;
            if (!$this->db_connected) {
                $this->set_error('0', DB_ERROR_NOT_CONNECTED);
            }

            $zp_db_resource = mysqli_query($this->link, $zf_sql);
            if (!$zp_db_resource) {
                $this->set_error($this->link->errno, $this->link->connect_errno);
            }

            $obj->resource = $zp_db_resource;
            $obj->cursor = 0;
            $obj->is_cached = true;
            if ($obj->RecordCount() > 0) {
                $obj->EOF = false;
                $zp_ii = 0;
                while (!$obj->EOF) {
                    $zp_result_array = mysqli_fetch_array($zp_db_resource);
                    if ($zp_result_array) {
                        while (list($key, $value) = each($zp_result_array)) {
                            if (!preg_match('/^[0-9]/', $key)) {
                                $obj->result[$zp_ii][$key] = $value;
                            }
                        }
                    } else {
                        $obj->Limit = $zp_ii;
                        $obj->EOF = true;
                    }
                    $zp_ii++;
                }
                while (list($key, $value) = each($obj->result[$obj->cursor])) {
                    if (!preg_match('/^[0-9]/', $key)) {
                        $obj->fields[$key] = $value;
                    }
                }
                $obj->EOF = false;
            } else {
                $obj->EOF = true;
            }
            $zc_cache->sql_cache_store($zf_sql, $obj->result);
            $time_end = explode(' ', microtime());
            $query_time = $time_end[1] + $time_end[0] - $time_start[1] - $time_start[0];
            $this->total_query_time += $query_time;
            $this->count_queries++;
            return ($obj);
        } else {
            $time_start = explode(' ', microtime());
            $obj = new queryFactoryResult;
            if (!$this->db_connected) {
                $this->set_error('0', DB_ERROR_NOT_CONNECTED);
            }

            $zp_db_resource = $this->link->query($zf_sql);
            if (!$zp_db_resource) {
                if (method_exists($messageStack, 'debug')) {
                    $messageStack->debug("\n\nThe failing sql was: " . $zf_sql);
                    $messageStack->debug("\n\nmySQL returned: " . $this->link->errno . ' ' . $this->link->connect_errno);
                    if (defined('FILENAME_DEFAULT')) {
                        $messageStack->write_debug();
                        $messageStack->add_session('The last transaction had a SQL database error.', 'error');
                        gen_redirect(html_href_link(FILENAME_DEFAULT, 'cat=phreedom&page=main&amp;action=crash', 'SSL'));
                    } else {
                        echo str_replace("\n", '<br />', $messageStack->debug_info);
                        die;
                    }
                }
            }
            $obj->resource = $zp_db_resource;
            $obj->cursor = 0;
            if ($obj->RecordCount() > 0) {
                $obj->EOF = false;
                $zp_result_array = mysqli_fetch_array($zp_db_resource);
                if ($zp_result_array) {
                    while (list($key, $value) = each($zp_result_array)) {
                        if (!preg_match('/^[0-9]/', $key)) {
                            $obj->fields[$key] = $value;
                        }
                    }
                    $obj->EOF = false;
                } else {
                    $obj->EOF = true;
                }
            } else {
                $obj->EOF = true;
            }

            $time_end = explode(' ', microtime());
            $query_time = $time_end[1] + $time_end[0] - $time_start[1] - $time_start[0];
            $this->total_query_time += $query_time;
            $this->count_queries++;
//$messageStack->add('query execution time = ' . $query_time . ' and sql = ' . $zf_sql, 'caution');
            return ($obj);
        }
    }

    public function Execute_return_error($zf_sql)
    {
        $time_start = explode(' ', microtime());
        $obj = new queryFactoryResult;
        if (!$this->db_connected) {
            $this->set_error('0', DB_ERROR_NOT_CONNECTED);
        }

        $zp_db_resource = mysqli_query($zf_sql, $this->link);
        if (!$zp_db_resource) {
            $this->set_error($this->link->errno, $this->link->connect_errno, false);
        } else {
            $this->set_error(0, '', false); // clear the error
        }
        $obj->resource = $zp_db_resource;
        $obj->cursor = 0;
        if ($obj->RecordCount() > 0) {
            $obj->EOF = false;
            $zp_result_array = mysqli_fetch_array($zp_db_resource);
            if ($zp_result_array) {
                while (list($key, $value) = each($zp_result_array)) {
                    if (!preg_match('/^[0-9]/', $key)) {
                        $obj->fields[$key] = $value;
                    }

                }
                $obj->EOF = false;
            } else {
                $obj->EOF = true;
            }
        } else {
            $obj->EOF = true;
        }

        $time_end = explode(' ', microtime());
        $query_time = $time_end[1] + $time_end[0] - $time_start[1] - $time_start[0];
        $this->total_query_time += $query_time;
        $this->count_queries++;
        return ($obj);
    }

    public function insert_ID()
    {
        return @mysqli_insert_id($this->link);
    }

}
class queryFactoryResult
{

    public function queryFactoryResult()
    {
        $this->is_cached = false;
    }

    public function MoveNext()
    {
        global $zc_cache;
        $this->cursor++;
        if ($this->is_cached) {
            if ($this->cursor >= sizeof($this->result)) {
                $this->EOF = true;
            } else {
                while (list($key, $value) = each($this->result[$this->cursor])) {
                    $this->fields[$key] = $value;
                }
            }
        } else {
            $zp_result_array = mysqli_fetch_array($this->resource);
            if (!$zp_result_array) {
                $this->EOF = true;
            } else {
                while (list($key, $value) = each($zp_result_array)) {
                    if (!preg_match('/^[0-9]/', $key)) {
                        $this->fields[$key] = $value;
                    }

                }
            }
        }
    }

    public function RecordCount()
    { // use for SELECT queries
        // echo "WWWW: ".mysqli_insert_id($this->link);exit;
        // print_r($this);die();
        return @mysqli_num_rows($this->resource);
    }

    public function AffectedRows()
    { // use for INSERT, UPDATE or DELETE queries
        return mysqli_affected_rows($this->link);
    }

    public function Move($zp_row)
    {
        global $db;
        if (mysqli_data_seek($this->resource, $zp_row)) {
            $zp_result_array = mysqli_fetch_array($this->resource);
            while (list($key, $value) = each($zp_result_array)) {
                $this->fields[$key] = $value;
            }
            mysqli_data_seek($this->resource, $zp_row);
            $this->EOF = false;
            return;
        } else {
            $this->EOF = true;
        }
    }
}
