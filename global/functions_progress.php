<?php
global $db;
if (!isset($GLOBALS['__PROGRESS'])) {
    $GLOBALS['__PROGRESS'] = [
        'job'      => 0,
        'last_upd' => 0,
        'processed' => 0,
        'created'  => 0,
        'wallet'   => 0,
        'skipped'  => 0,
    ];
}

function progress_init($job_id)
{
    $GLOBALS['__PROGRESS']['job'] = (int)$job_id;
}

function progress_tick($delta_processed = 1, $delta_created = 0, $delta_wallet = 0, $delta_skipped = 0)
{
    $p = &$GLOBALS['__PROGRESS'];
    $p['processed'] += $delta_processed;
    $p['created']   += $delta_created;
    $p['wallet']    += $delta_wallet;
    $p['skipped']   += $delta_skipped;

    // flush at most every 2s to keep DB load low
    if (time() - $p['last_upd'] >= 2) {
        $p['last_upd'] = time();
        global $db;
        $db->Execute("UPDATE DOA_IMPORT_JOBS SET
            PROCESSED_ROWS=" . (int)$p['processed'] . ",
            ROWS_CREATED="   . (int)$p['created']   . ",
            WALLET_CREDITS=" . (int)$p['wallet']    . ",
            ROWS_SKIPPED="   . (int)$p['skipped']   . "
            WHERE PK_IMPORT_JOB=" . (int)$p['job']);
    }
}

function progress_flush()
{
    $p = &$GLOBALS['__PROGRESS'];
    if (!$p['job']) return;
    global $db;
    $db->Execute("UPDATE DOA_IMPORT_JOBS SET
        PROCESSED_ROWS=" . (int)$p['processed'] . ",
        ROWS_CREATED="   . (int)$p['created']   . ",
        WALLET_CREDITS=" . (int)$p['wallet']    . ",
        ROWS_SKIPPED="   . (int)$p['skipped']   . "
        WHERE PK_IMPORT_JOB=" . (int)$p['job']);
}
