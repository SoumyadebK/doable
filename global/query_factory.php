<?php

/**
 * PHP 8.x (8.1 - 8.5) MySQLi query wrapper.
 *
 * Preserves the original queryFactory / queryFactoryResult API.
 * Uses MySQLi's non-strict error mode (PHP 8.1+ defaults to exceptions), and
 * also catches mysqli_sql_exception in case strict mode is switched on elsewhere.
 */
class queryFactory
{
    private int $count_queries = 0;
    private float $total_query_time = 0.0;

    /** @var mysqli|false|null */
    public $link = null;

    public bool $db_connected = false;
    public ?string $database = null;
    public $error_number = 0;
    public string $error_text = '';

    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    public function connect($zf_host, $zf_user, $zf_password, $zf_database)
    {
        $this->database = $zf_database;
        mysqli_report(MYSQLI_REPORT_OFF);

        try {
            // @ : a failed connect still raises an E_WARNING even with reporting off
            $this->link = @mysqli_connect($zf_host, $zf_user, $zf_password, $zf_database);
        } catch (mysqli_sql_exception $e) {
            $this->link = false;
            $this->db_connected = false;
            $this->set_error((int)$e->getCode(), $e->getMessage(), false);
            return false;
        }

        if ($this->link instanceof mysqli) {
            $this->db_connected = true;
            $this->set_error(0, '', false);

            // utf8mb4 for full Unicode support
            if (!mysqli_set_charset($this->link, 'utf8mb4')) {
                $this->set_error(mysqli_errno($this->link), mysqli_error($this->link), false);
            }
            return $this->link;
        }

        $this->db_connected = false;
        $this->set_error(mysqli_connect_errno(), mysqli_connect_error(), false);
        return false;
    }

    public function selectdb($zf_database)
    {
        if (!$this->isConnected()) {
            $this->set_error(0, 'Database is not connected.', false);
            return false;
        }

        try {
            $selected = mysqli_select_db($this->link, $zf_database);
        } catch (mysqli_sql_exception $e) {
            $selected = false;
        }

        if (!$selected) {
            $this->set_error(mysqli_errno($this->link), mysqli_error($this->link), false);
            return false;
        }

        $this->database = $zf_database;
        return true;
    }

    public function transStart()
    {
        return $this->Execute('START TRANSACTION');
    }

    public function transCommit()
    {
        return $this->Execute('COMMIT');
    }

    public function transRollback()
    {
        return $this->Execute('ROLLBACK');
    }

    /**
     * Escape a string for use in a SQL string literal.
     * Prefer ExecutePrepared() for new code.
     */
    public function prepare_input($zp_string)
    {
        if (!$this->isConnected()) {
            throw new RuntimeException('Cannot escape SQL input: database is not connected.');
        }

        if (is_array($zp_string) || is_object($zp_string)) {
            throw new InvalidArgumentException('prepare_input() expects a scalar value.');
        }

        return mysqli_real_escape_string($this->link, (string) $zp_string);
    }

    public function close()
    {
        if ($this->link instanceof mysqli) {
            mysqli_close($this->link);
        }

        $this->link = null;
        $this->db_connected = false;

        return true;
    }

    public function set_error($zp_err_num, $zp_err_text, $zp_fatal = true)
    {
        $this->error_number = (int) $zp_err_num;
        $this->error_text = (string) $zp_err_text;

        if ($zp_fatal && $this->error_number !== 1141) {
            $this->show_error();
            die();
        }
    }

    public function show_error()
    {
        // Escaped so DB error text can't inject markup into the page
        echo htmlspecialchars($this->error_number . ' ' . $this->error_text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Execute SQL.
     *
     * The optional cache arguments and return-object interface are retained
     * for compatibility with the original application.
     *
     * @return queryFactoryResult|false
     */
    public function Execute(
        $zf_sql,
        $zf_limit = false,
        $zf_cache = false,
        $zf_cachetime = 0
    ) {
        global $zc_cache, $messageStack;

        if (!is_string($zf_sql) || trim($zf_sql) === '') {
            $this->set_error(0, 'SQL query cannot be empty.');
            return false;
        }

        if ($zf_limit !== false && $zf_limit !== null && $zf_limit !== '') {
            // Accept numeric limits and "offset, count" limits only.
            $limit = trim((string) $zf_limit);

            if (!preg_match('/^\d+(?:\s*,\s*\d+)?$/', $limit)) {
                $this->set_error(0, 'Invalid SQL LIMIT value.');
                return false;
            }

            $zf_sql .= ' LIMIT ' . $limit;
        }

        $cacheAvailable = (
            $zf_cache &&
            is_object($zc_cache) &&
            method_exists($zc_cache, 'sql_cache_exists') &&
            method_exists($zc_cache, 'sql_cache_is_expired') &&
            method_exists($zc_cache, 'sql_cache_read') &&
            method_exists($zc_cache, 'sql_cache_store')
        );

        if (
            $cacheAvailable &&
            $zc_cache->sql_cache_exists($zf_sql) &&
            !$zc_cache->sql_cache_is_expired($zf_sql, $zf_cachetime)
        ) {
            $cachedRows = $zc_cache->sql_cache_read($zf_sql);

            if (!is_array($cachedRows)) {
                $cachedRows = array();
            }

            $obj = new queryFactoryResult();
            $obj->cursor = 0;
            $obj->is_cached = true;
            $obj->sql_query = $zf_sql;
            $obj->result = array_values($cachedRows);
            $obj->link = $this->link;

            if (count($obj->result) > 0) {
                $obj->fields = $obj->result[0];
                $obj->EOF = false;
            } else {
                $obj->EOF = true;
            }

            return $obj;
        }

        if (!$this->isConnected()) {
            $this->set_error(0, defined('DB_ERROR_NOT_CONNECTED')
                ? DB_ERROR_NOT_CONNECTED
                : 'Database is not connected.');
            return false;
        }

        if ($cacheAvailable && method_exists($zc_cache, 'sql_cache_expire_now')) {
            $zc_cache->sql_cache_expire_now($zf_sql);
        }

        $timeStart = microtime(true);
        $dbResource = $this->runQuery($zf_sql);

        if ($dbResource === false) {
            $this->handleQueryError($zf_sql, $messageStack);
            return false;
        }

        $obj = $this->buildResult($dbResource, $zf_sql);

        if ($cacheAvailable && $obj->resource instanceof mysqli_result) {
            $cachedRows = array();

            if ($obj->fields) {
                $cachedRows[] = $obj->fields;
            }

            while (($row = mysqli_fetch_assoc($obj->resource)) !== null) {
                $cachedRows[] = $row;
            }

            $obj->result = $cachedRows;
            $obj->is_cached = true;
            $obj->cursor = 0;

            $zc_cache->sql_cache_store($zf_sql, $cachedRows);

            // Point back at the first row so MoveNext() walks the cached array.
            if (count($cachedRows) > 0) {
                $obj->fields = $cachedRows[0];
                $obj->EOF = false;
            } else {
                $obj->fields = array();
                $obj->EOF = true;
            }
        }

        $this->recordQueryTime($timeStart);

        return $obj;
    }

    /**
     * Execute a prepared statement (recommended for any query containing user input).
     *
     * Example:
     *   $res = $db->ExecutePrepared('SELECT * FROM users WHERE id = ? AND status = ?', [$id, 'active']);
     *
     * All parameters are bound as strings, which MySQL converts as needed.
     *
     * @return queryFactoryResult|false
     */
    public function ExecutePrepared($zf_sql, array $zf_params = array())
    {
        if (!is_string($zf_sql) || trim($zf_sql) === '') {
            $this->set_error(0, 'SQL query cannot be empty.');
            return false;
        }

        if (!$this->isConnected()) {
            $this->set_error(0, 'Database is not connected.');
            return false;
        }

        $timeStart = microtime(true);

        try {
            $stmt = mysqli_prepare($this->link, $zf_sql);
            if ($stmt === false) {
                $this->set_error(mysqli_errno($this->link), mysqli_error($this->link), false);
                return false;
            }

            // PHP 8.1+: execute() accepts the parameter array directly
            $ok = $stmt->execute(array_values($zf_params));
            if (!$ok) {
                $this->set_error($stmt->errno, $stmt->error, false);
                $stmt->close();
                return false;
            }

            $dbResource = $stmt->get_result();   // false for INSERT/UPDATE/DELETE
            $this->set_error(0, '', false);
            $obj = $this->buildResult($dbResource === false ? true : $dbResource, $zf_sql);
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $this->set_error((int) $e->getCode(), $e->getMessage(), false);
            error_log('SQL ERROR: ' . $zf_sql . ' | ' . $e->getMessage());
            return false;
        }

        $this->recordQueryTime($timeStart);
        return $obj;
    }

    /**
     * Execute SQL without terminating the application on a query error.
     *
     * @return queryFactoryResult
     */
    public function Execute_return_error($zf_sql)
    {
        $timeStart = microtime(true);
        $obj = new queryFactoryResult();
        $obj->sql_query = (string) $zf_sql;
        $obj->link = $this->link instanceof mysqli ? $this->link : null;
        $obj->cursor = 0;

        if (!$this->isConnected()) {
            $this->set_error(
                0,
                defined('DB_ERROR_NOT_CONNECTED') ? DB_ERROR_NOT_CONNECTED : 'Database is not connected.',
                false
            );

            $obj->EOF = true;
            $this->recordQueryTime($timeStart);
            return $obj;
        }

        $dbResource = $this->runQuery((string) $zf_sql);

        if ($dbResource === false) {
            error_log("SQL ERROR: " . $zf_sql);
            error_log("MYSQL ERROR: " . mysqli_error($this->link));

            $this->set_error(mysqli_errno($this->link), mysqli_error($this->link), false);

            $obj->resource = null;
            $obj->EOF = true;
        } else {
            $this->set_error(0, '', false);
            $obj = $this->buildResult($dbResource, (string) $zf_sql);
        }

        $this->recordQueryTime($timeStart);

        return $obj;
    }

    public function insert_ID()
    {
        return $this->isConnected() ? mysqli_insert_id($this->link) : 0;
    }

    public function affected_rows()
    {
        return $this->isConnected() ? mysqli_affected_rows($this->link) : 0;
    }

    /** Total number of queries run and total time spent (seconds). */
    public function getStats(): array
    {
        return array(
            'queries' => $this->count_queries,
            'time'    => $this->total_query_time,
        );
    }

    private function isConnected(): bool
    {
        return (
            $this->db_connected === true &&
            $this->link instanceof mysqli
        );
    }

    /**
     * Run a query, returning mysqli_result|true on success, false on failure.
     * Converts mysqli_sql_exception to false so legacy error handling still applies.
     *
     * @return mysqli_result|bool
     */
    private function runQuery(string $sql)
    {
        try {
            return mysqli_query($this->link, $sql);
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }

    /**
     * Wrap a mysqli result (or true for non-SELECT) in a queryFactoryResult
     * positioned on the first row.
     *
     * @param mysqli_result|bool $dbResource
     */
    private function buildResult($dbResource, string $sql): queryFactoryResult
    {
        $obj = new queryFactoryResult();
        $obj->resource = ($dbResource instanceof mysqli_result) ? $dbResource : null;
        $obj->link = $this->link instanceof mysqli ? $this->link : null;
        $obj->cursor = 0;
        $obj->sql_query = $sql;

        if ($dbResource instanceof mysqli_result) {
            $firstRow = mysqli_fetch_assoc($dbResource);

            if ($firstRow !== null && $firstRow !== false) {
                $obj->fields = $firstRow;
                $obj->EOF = false;
            } else {
                $obj->EOF = true;
            }
        } else {
            // INSERT, UPDATE, DELETE and other non-result queries.
            $obj->EOF = true;
        }

        return $obj;
    }

    private function recordQueryTime($timeStart): void
    {
        $this->total_query_time += microtime(true) - $timeStart;
        $this->count_queries++;
    }

    private function handleQueryError($sql, $messageStack): void
    {
        $errorNumber = mysqli_errno($this->link);
        $errorText = mysqli_error($this->link);

        // Keep error state in sync before any redirect/exit below
        $this->error_number = $errorNumber;
        $this->error_text = (string) $errorText;

        error_log("========== MYSQL QUERY ERROR ==========");
        error_log("SQL: " . $sql);
        error_log("Error Number: " . $errorNumber);
        error_log("Error Message: " . $errorText);

        // Limit the trace depth: full traces are noisy and slow
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($backtrace as $trace) {
            if (isset($trace['file'], $trace['line'])) {
                error_log(
                    "Called from: " . $trace['file'] .
                        " | Line: " . $trace['line'] .
                        " | Function: " . ($trace['function'] ?? 'Unknown')
                );
            }
        }
        error_log("=======================================");

        if (is_object($messageStack) && method_exists($messageStack, 'debug')) {
            $messageStack->debug("\n\nThe failing sql was: " . $sql);
            $messageStack->debug("\n\nmySQL returned: " . $errorNumber . ' ' . $errorText);

            if (
                defined('FILENAME_DEFAULT') &&
                method_exists($messageStack, 'write_debug') &&
                method_exists($messageStack, 'add_session') &&
                function_exists('gen_redirect') &&
                function_exists('html_href_link')
            ) {
                $messageStack->write_debug();
                $messageStack->add_session('The last transaction had a SQL database error.', 'error');

                gen_redirect(
                    html_href_link(FILENAME_DEFAULT, 'cat=phreedom&page=main&action=crash', 'SSL')
                );
                return; // gen_redirect() exits
            }

            // SECURITY: this prints the failing SQL to the browser. Remove or restrict
            // to development environments before going to production.
            if (isset($messageStack->debug_info)) {
                echo str_replace("\n", '<br />', htmlspecialchars((string) $messageStack->debug_info, ENT_QUOTES, 'UTF-8'));
                die;
            }
        }

        $this->set_error($errorNumber, $errorText);
    }
}


#[\AllowDynamicProperties]
class queryFactoryResult
{
    /** @var mysqli_result|null */
    public $resource = null;

    /** @var mysqli|null */
    public $link = null;

    public int $cursor = 0;
    public bool $is_cached = false;
    public string $sql_query = '';
    public $fields = array();
    public array $result = array();
    public bool $EOF = true;
    public $Limit = 0;

    public function __construct()
    {
        $this->is_cached = false;
        $this->EOF = true;
        $this->fields = array();
        $this->result = array();
    }

    public function MoveNext(): void
    {
        $this->cursor++;

        if ($this->is_cached) {
            if (!isset($this->result[$this->cursor])) {
                $this->EOF = true;
                $this->fields = array();
                return;
            }

            $this->fields = $this->result[$this->cursor];
            $this->EOF = false;
            return;
        }

        if (!($this->resource instanceof mysqli_result)) {
            $this->EOF = true;
            $this->fields = array();
            return;
        }

        $row = mysqli_fetch_assoc($this->resource);

        if ($row === null || $row === false) {
            $this->EOF = true;
            $this->fields = array();
            return;
        }

        $this->fields = $row;
        $this->EOF = false;
    }

    /**
     * Number of rows returned by a SELECT query.
     */
    public function RecordCount(): int
    {
        if ($this->is_cached) {
            return count($this->result);
        }

        if (!($this->resource instanceof mysqli_result)) {
            return 0;
        }

        return (int) mysqli_num_rows($this->resource);
    }

    /**
     * Number of rows affected by the last INSERT, UPDATE or DELETE on this connection.
     */
    public function AffectedRows(): int
    {
        if (!($this->link instanceof mysqli)) {
            return 0;
        }

        return (int) mysqli_affected_rows($this->link);
    }

    public function Move($zp_row): bool
    {
        if (!is_numeric($zp_row) || (int) $zp_row < 0) {
            $this->EOF = true;
            $this->fields = array();
            return false;
        }

        $rowNumber = (int) $zp_row;

        if ($this->is_cached) {
            if (!isset($this->result[$rowNumber])) {
                $this->EOF = true;
                $this->fields = array();
                return false;
            }

            $this->cursor = $rowNumber;
            $this->fields = $this->result[$rowNumber];
            $this->EOF = false;
            return true;
        }

        if (
            !($this->resource instanceof mysqli_result) ||
            !mysqli_data_seek($this->resource, $rowNumber)
        ) {
            $this->EOF = true;
            $this->fields = array();
            return false;
        }

        $row = mysqli_fetch_assoc($this->resource);

        if ($row === null || $row === false) {
            $this->EOF = true;
            $this->fields = array();
            return false;
        }

        $this->cursor = $rowNumber;
        $this->fields = $row;
        $this->EOF = false;

        return true;
    }

    /**
     * Release the underlying result set early (useful for large SELECTs).
     */
    public function Free(): void
    {
        if ($this->resource instanceof mysqli_result) {
            mysqli_free_result($this->resource);
        }
        $this->resource = null;
        $this->EOF = true;
        $this->fields = array();
    }
}
