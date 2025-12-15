<?php

/**
 * Advanced slot choice detection using speech or DTMF.
 * Returns 1-based index of selected option, or null if no match.
 *
 * $options: array of ['id'=>..., 'label'=>'10:00 AM'] etc.
 */
function detectUserChoiceAdvanced($speech, $digits, $options)
{
    // 1) DTMF priority
    if (!empty($digits)) {
        $digit = intval($digits);
        if ($digit >= 1 && $digit <= count($options)) return $digit;
    }

    // 2) Normalize speech
    $s = strtolower(trim($speech ?? ''));
    $s = preg_replace('/[^\p{L}\p{N}\s:]/u', ' ', $s); // keep letters, numbers, spaces, colons
    $s = preg_replace('/\s+/', ' ', $s);
    if ($s === '') return null;

    // 3) expanded spoken-number map (covers many ASR quirks)
    $map = [
        'one' => 1,
        '1' => 1,
        'first' => 1,
        '1st' => 1,
        'two' => 2,
        'to' => 2,
        'too' => 2,
        'second' => 2,
        '2' => 2,
        '2nd' => 2,
        'three' => 3,
        'tree' => 3,
        'third' => 3,
        '3' => 3,
        '3rd' => 3,
        'four' => 4,
        'for' => 4,
        'fore' => 4,
        'fourth' => 4,
        '4' => 4,
        '4th' => 4,
        'five' => 5,
        'fifth' => 5,
        '5' => 5,
        '5th' => 5,
        'six' => 6,
        'sixth' => 6,
        '6' => 6,
        '6th' => 6,
        'seven' => 7,
        'seventh' => 7,
        '7' => 7,
        '7th' => 7,
        'eight' => 8,
        'ate' => 8,
        'eighth' => 8,
        '8' => 8,
        '8th' => 8,
        'nine' => 9,
        'ninth' => 9,
        '9' => 9,
        '9th' => 9,
        'option one' => 1,
        'option 1' => 1,
        'option two' => 2,
        'option 2' => 2,
        'option three' => 3,
        'option 3' => 3,
        'option four' => 4,
        'option 4' => 4,
        'option five' => 5,
        'option 5' => 5,
        'option six' => 6,
        'option 6' => 6,
        'option seven' => 7,
        'option 7' => 7,
        'option eight' => 8,
        'option 8' => 8,
        'option nine' => 9,
        'option 9' => 9,
        'the first one' => 1,
        'the second one' => 2,
        'the third one' => 3,
        'the fourth one' => 4,
        'the fifth one' => 5,
        'the sixth one' => 6,
        'the seventh one' => 7,
        'the eighth one' => 8,
        'the ninth one' => 9,
        'one o clock' => 1,
        'two o clock' => 2,
        'three o clock' => 3,
        'four o clock' => 4,
        'five o clock' => 5,
        'six o clock' => 6,
        'seven o clock' => 7,
        'eight o clock' => 8,
        'nine o clock' => 9,
    ];

    // direct whole-string match to map
    if (isset($map[$s])) {
        $n = $map[$s];
        if ($n >= 1 && $n <= count($options)) return $n;
    }

    // if string contains a mapped word, return it (e.g. "i want option three")
    foreach ($map as $word => $n) {
        if (strpos($s, $word) !== false) {
            if ($n >= 1 && $n <= count($options)) return $n;
        }
    }

    // 4) Try to parse an explicit time expression out of speech (e.g. "ten am", "2:30", "half past three")
    $parsedTime = parseTimeFromSpeech($s); // returns ['hour'=>H,'minute'=>M,'ampm'=>'am'|'pm'|null] or null
    if ($parsedTime) {
        // compare with options: normalize each option label to hour/minute and compare
        foreach ($options as $idx => $opt) {
            $optTime = parseTimeFromLabel($opt['label']); // returns ['hour'=>H,'minute'=>M,'ampm'=>...]
            if ($optTime) {
                // normalize both to minutes since midnight for loose comparison (if am/pm unknown use best guess)
                $a = timeToMinutes($parsedTime);
                $b = timeToMinutes($optTime);
                if ($a !== null && $b !== null && abs($a - $b) <= 5) { // 5 min tolerance
                    return $idx + 1;
                }
                // if one is null for am/pm, compare hour only
                if ($a !== null && $b !== null && abs($a - $b) <= 60) {
                    return $idx + 1;
                }
            }
        }
    }

    // 5) Match spoken numeric tokens inside speech vs option labels:
    // e.g., user says "ten" and option label "10:00 AM" should match
    foreach ($options as $idx => $opt) {
        $labelClean = strtolower(preg_replace('/[^\p{L}\p{N}\s:]/u', ' ', $opt['label']));
        $labelClean = preg_replace('/\s+/', ' ', $labelClean);
        if (strpos($s, $labelClean) !== false) return $idx + 1;
        // also check if numeric part (like "10" or "1000" or "2 30") exists in speech
        $digitsInLabel = preg_replace('/[^\d]/', '', $opt['label']);
        if ($digitsInLabel !== '' && strpos(preg_replace('/[^\d]/', '', $s), $digitsInLabel) !== false) {
            return $idx + 1;
        }
    }

    // 6) Fuzzy match: compare similarity between speech and option labels
    $bestIdx = null;
    $bestScore = 0;
    foreach ($options as $idx => $opt) {
        $label = strtolower($opt['label']);
        $labelNorm = preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $label));
        similar_text($s, $labelNorm, $perc);
        if ($perc > $bestScore) {
            $bestScore = $perc;
            $bestIdx = $idx;
        }
    }
    if ($bestScore >= 55) { // threshold, tune as needed
        return $bestIdx + 1;
    }

    // 7) no match
    return null;
}

/**
 * Parse simple time expressions from cleaned speech string.
 * Returns ['hour'=>int,'minute'=>int,'ampm'=>'am'|'pm'|null] or null.
 * Handles: "10 am", "10 a m", "10:30", "half past three", "quarter to four", "two thirty", "2 30 pm"
 */
function parseTimeFromSpeech($s)
{
    // normalize common phrases
    $s = preg_replace('/\b(o clock|oclock)\b/', '', $s);
    $s = str_replace(['a m', 'p m', 'a\.m\.', 'p\.m\.'], ['am', 'pm', 'am', 'pm'], $s);

    // 1) regex for numeric times like 2:30, 14:00, 2 30 pm, 230 pm
    if (preg_match('/\b([01]?\d|2[0-3])[:\s]?([0-5]\d)?\s*(am|pm)?\b/i', $s, $m)) {
        $hour = intval($m[1]);
        $minute = isset($m[2]) && $m[2] !== '' ? intval($m[2]) : 0;
        $ampm = isset($m[3]) && $m[3] !== '' ? strtolower($m[3]) : null;
        return ['hour' => $hour, 'minute' => $minute, 'ampm' => $ampm];
    }

    // 2) half past X / quarter past/to patterns
    if (preg_match('/\bhalf (past|after) (\w+)\b/i', $s, $m)) {
        $hour = wordsToHour($m[2]);
        if ($hour !== null) return ['hour' => $hour, 'minute' => 30, 'ampm' => null];
    }
    if (preg_match('/\b(quarter) (past|after) (\w+)\b/i', $s, $m)) {
        $hour = wordsToHour($m[3]);
        if ($hour !== null) return ['hour' => $hour, 'minute' => 15, 'ampm' => null];
    }
    if (preg_match('/\b(quarter) to (\w+)\b/i', $s, $m)) {
        $hour = wordsToHour($m[2]);
        if ($hour !== null) {
            $hour = ($hour - 1) == 0 ? 12 : ($hour - 1);
            return ['hour' => $hour, 'minute' => 45, 'ampm' => null];
        }
    }

    // 3) textual hour + optional minute ("two thirty", "three fifteen")
    if (preg_match('/\b(\w+)\s+(?:(thirty|fifteen|forty five|forty-five|twenty|twenty five|twenty-five|ten|five|00|0|oh)\b)?\s*(am|pm)?\b/i', $s, $m)) {
        $hour = wordsToHour($m[1]);
        $minute = 0;
        if (!empty($m[2])) {
            $minWord = str_replace('-', ' ', $m[2]);
            $minute = wordsToMinutes($minWord);
        }
        $ampm = isset($m[3]) && $m[3] !== '' ? strtolower($m[3]) : null;
        if ($hour !== null) return ['hour' => $hour, 'minute' => $minute, 'ampm' => $ampm];
    }

    return null;
}

/** Convert 'ten'|'two' etc to hour number 1..12 or null */
function wordsToHour($w)
{
    $w = strtolower($w);
    $map = [
        'one' => 1,
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'nine' => 9,
        'ten' => 10,
        'eleven' => 11,
        'twelve' => 12,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        '10' => 10,
        '11' => 11,
        '12' => 12
    ];
    return $map[$w] ?? null;
}

/** Convert minute words to integer minute */
function wordsToMinutes($w)
{
    $w = strtolower(trim($w));
    $map = [
        'oh' => 0,
        '00' => 0,
        '0' => 0,
        'five' => 5,
        'ten' => 10,
        'fifteen' => 15,
        'quarter' => 15,
        'twenty' => 20,
        'twenty five' => 25,
        'twenty-five' => 25,
        'thirty' => 30,
        'half' => 30,
        'forty five' => 45,
        'forty-five' => 45
    ];
    return $map[$w] ?? intval(preg_replace('/[^\d]/', '', $w)) ?? 0;
}

/** Parse option label like "10:00 AM" or "2 PM" to same structure or null */
function parseTimeFromLabel($label)
{
    $s = strtolower($label);
    $s = preg_replace('/[^\p{L}\p{N}\s:]/u', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    // try numeric
    if (preg_match('/\b([01]?\d|2[0-3])[:\s]?([0-5]\d)?\s*(am|pm)?\b/i', $s, $m)) {
        $hour = intval($m[1]);
        $minute = isset($m[2]) && $m[2] !== '' ? intval($m[2]) : 0;
        $ampm = isset($m[3]) && $m[3] !== '' ? strtolower($m[3]) : null;
        return ['hour' => $hour, 'minute' => $minute, 'ampm' => $ampm];
    }
    // fallback: try word->hour
    if (preg_match('/\b(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\b/i', $s, $m)) {
        $hour = wordsToHour($m[1]);
        return ['hour' => $hour, 'minute' => 0, 'ampm' => null];
    }
    return null;
}

/** Convert a time array to minutes since midnight; if am/pm present convert; returns null on failure */
function timeToMinutes($t)
{
    if (!isset($t['hour'])) return null;
    $h = intval($t['hour']);
    $m = isset($t['minute']) ? intval($t['minute']) : 0;
    $ampm = $t['ampm'] ?? null;
    if ($ampm === 'pm' && $h < 12) $h += 12;
    if ($ampm === 'am' && $h == 12) $h = 0;
    // if am/pm missing, we still convert assuming given hour (could be ambiguous)
    return $h * 60 + $m;
}




function getLocationSlotDetails($PK_LOCATION, $DATE)
{
    global $db;
    $location_data = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE DOA_LOCATION.PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
    $DB_NAME = $location_data->fields['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }
    if ($db_account->error_number) {
        die("Connection Error");
    }

    $callSettingData = $db->Execute("SELECT * FROM DOA_DEFAULT_CALL_SETTING WHERE PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
    $PK_USER = $callSettingData->fields['PK_USER'] ?? null;
    $PK_SCHEDULING_CODE = $callSettingData->fields['PK_SCHEDULING_CODE'] ?? null;

    $schedulingCodeData = $db_account->Execute("SELECT * FROM DOA_SCHEDULING_CODE WHERE PK_SCHEDULING_CODE = " . addslashes($PK_SCHEDULING_CODE) . " LIMIT 1");
    $SLOT_DURATION = $schedulingCodeData->fields['DURATION'] ?? '00:30:00';

    $available_slots = getAvailableSlots($db_account, $PK_USER, $PK_LOCATION, $DATE, '00:' . $SLOT_DURATION . ':00');

    return $available_slots;
}

function getAvailableSlots($db_account, $provider_id, $location_id, $date, $slot_duration)
{

    $provider_id   = (int)$provider_id;
    $location_id   = (int)$location_id;
    $date          = addslashes($date);
    $slot_duration = addslashes($slot_duration);

    // Get provider working hours for the day
    $day_name = strtoupper(substr(date('l', strtotime($date)), 0, 3));
    $dayNumber = date('N', strtotime($date));

    $sql_hours = "
        SELECT 
            {$day_name}_START_TIME AS WORK_START,
            {$day_name}_END_TIME AS WORK_END
        FROM DOA_SERVICE_PROVIDER_LOCATION_HOURS
        WHERE PK_USER = $provider_id AND PK_LOCATION = $location_id
    ";

    $hours_data = $db_account->Execute($sql_hours);
    $hours = $hours_data->fields;

    if (!$hours || !$hours['WORK_START'] || !$hours['WORK_END']) return [];

    $location_operational_hour = $db_account->Execute("SELECT MIN(DOA_OPERATIONAL_HOUR.OPEN_TIME) AS OPEN_TIME, MAX(DOA_OPERATIONAL_HOUR.CLOSE_TIME) AS CLOSE_TIME FROM DOA_OPERATIONAL_HOUR WHERE DAY_NUMBER = '$dayNumber' AND PK_LOCATION = $location_id");
    $LOCATION_SLOT_START = $location_operational_hour->fields['OPEN_TIME'] ?? '00:00:00';
    $LOCATION_SLOT_END = $location_operational_hour->fields['CLOSE_TIME'] ?? '23:00:00';

    if ($LOCATION_SLOT_START > $hours['WORK_START']) {
        $hours['WORK_START'] = $LOCATION_SLOT_START;
    } else {
        $hours['WORK_START'] = $hours['WORK_START'];
    }

    if ($LOCATION_SLOT_END > $hours['WORK_END']) {
        $hours['WORK_END'] = $hours['WORK_END'];
    } else {
        $hours['WORK_END'] = $LOCATION_SLOT_END;
    }

    $work_start = strtotime("$date " . $hours['WORK_START']);
    $work_end   = strtotime("$date " . $hours['WORK_END']);
    $duration   = strtotime("1970-01-01 $slot_duration UTC") - strtotime("1970-01-01 00:00:00 UTC");

    // Get all booked slots
    $sql_booked = "
        SELECT START_TIME, END_TIME
        FROM DOA_APPOINTMENT_MASTER a
        LEFT JOIN DOA_APPOINTMENT_SERVICE_PROVIDER sp
            ON a.PK_APPOINTMENT_MASTER = sp.PK_APPOINTMENT_MASTER
        WHERE a.PK_LOCATION = $location_id
            AND a.PK_APPOINTMENT_STATUS NOT IN (2,6)
            AND sp.PK_USER = $provider_id
            AND a.DATE = '$date'
        UNION ALL
        SELECT START_TIME, END_TIME
        FROM DOA_SPECIAL_APPOINTMENT sa
        LEFT JOIN DOA_SPECIAL_APPOINTMENT_USER sau
            ON sa.PK_SPECIAL_APPOINTMENT = sau.PK_SPECIAL_APPOINTMENT
        WHERE sa.PK_LOCATION = $location_id
            AND sa.PK_APPOINTMENT_STATUS NOT IN (2,6)
            AND sau.PK_USER = $provider_id
            AND sa.DATE = '$date'
    ";

    $booked_data = $db_account->Execute($sql_booked);
    $booked_slots = [];
    while (!$booked_data->EOF) {
        $booked_slots[] = [
            'start' => strtotime("$date " . $booked_data->fields['START_TIME']),
            'end'   => strtotime("$date " . $booked_data->fields['END_TIME'])
        ];
        $booked_data->MoveNext();
    }

    // Sort booked slots by start time
    usort($booked_slots, function ($a, $b) {
        return $a['start'] - $b['start'];
    });

    $available_slots = [];
    $current = $work_start;

    foreach ($booked_slots as $b) {
        // If there is free time before this booked slot
        if ($current + $duration <= $b['start']) {
            $slot_start = $current;
            while ($slot_start + $duration <= $b['start']) {
                $available_slots[] = [
                    'slot_start_time' => date('H:i:s', $slot_start),
                    'slot_end_time'   => date('H:i:s', $slot_start + $duration)
                ];
                $slot_start += $duration;
            }
        }
        // Move current time after booked slot ends
        if ($current < $b['end']) {
            $current = $b['end'];
        }
    }

    // Check for any free slots after last booked slot until work_end
    while ($current + $duration <= $work_end) {
        $available_slots[] = [
            'slot_start_time' => date('H:i:s', $current),
            'slot_end_time'   => date('H:i:s', $current + $duration)
        ];
        $current += $duration;
    }

    return $available_slots;
}

function getTimeSlot($interval, $start_time, $end_time): array
{
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $startTime = $start->format('H:i');
    $endTime = $end->format('H:i');
    $i = 0;
    $time = [];
    while ((strtotime($startTime) <= strtotime($endTime)) && ((strtotime($endTime) - strtotime($startTime) >= ($interval * 60)))) {
        $start = $startTime;
        $end = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
        $startTime = date('H:i', strtotime('+' . $interval . ' minutes', strtotime($startTime)));
        $i++;
        if (strtotime($startTime) <= strtotime($endTime)) {
            $time[$i]['slot_start_time'] = $start;
            $time[$i]['slot_end_time'] = $end;
        }
    }
    return $time;
}

function createUserFromLeads($PK_LEADS): array
{
    global $db;
    $leadsData = $db->Execute("SELECT * FROM DOA_LEADS WHERE PK_LEADS = " . $PK_LEADS . " LIMIT 1");
    $PK_LOCATION = $leadsData->fields['PK_LOCATION'] ?? null;
    $location_data = $db->Execute("SELECT DOA_LOCATION.PK_LOCATION, DOA_LOCATION.LOCATION_NAME, DOA_LOCATION.PK_ACCOUNT_MASTER, DOA_LOCATION.HOUR, DOA_ACCOUNT_MASTER.DB_NAME, DOA_TIMEZONE.TIMEZONE FROM DOA_LOCATION LEFT JOIN DOA_TIMEZONE ON DOA_LOCATION.PK_TIMEZONE = DOA_TIMEZONE.PK_TIMEZONE LEFT JOIN DOA_ACCOUNT_MASTER ON DOA_LOCATION.PK_ACCOUNT_MASTER = DOA_ACCOUNT_MASTER.PK_ACCOUNT_MASTER  WHERE DOA_LOCATION.PK_LOCATION = " . $PK_LOCATION . " LIMIT 1");
    $DB_NAME = $location_data->fields['DB_NAME'];
    $db_account = new queryFactory();
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
        $conn1 = $db_account->connect('localhost', 'root', '', $DB_NAME);
        $http_path = 'http://localhost/doable/';
    } else {
        $conn1 = $db_account->connect('localhost', 'root', 'b54eawxj5h8ev', $DB_NAME);
        $http_path = 'https://doable.net/';
    }
    if ($db_account->error_number) {
        die("Connection Error");
    }

    $PK_ACCOUNT_MASTER = $locationData->fields['PK_ACCOUNT_MASTER'] ?? null;
    $FIRST_NAME = $leadsData->fields['FIRST_NAME'] ?? '';
    $LAST_NAME = $leadsData->fields['LAST_NAME'] ?? '';
    $EMAIL_ID = $leadsData->fields['EMAIL_ID'] ?? '';
    $PHONE = $leadsData->fields['PHONE'] ?? '';

    $USER_DATA['PK_ACCOUNT_MASTER'] = $USER_DATA_ACCOUNT['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
    $USER_DATA['FIRST_NAME'] = $USER_DATA_ACCOUNT['FIRST_NAME'] = $FIRST_NAME;
    $USER_DATA['LAST_NAME'] = $USER_DATA_ACCOUNT['LAST_NAME'] = $LAST_NAME;
    $USER_DATA['EMAIL_ID'] = $USER_DATA_ACCOUNT['EMAIL_ID'] = $EMAIL_ID;
    $USER_DATA['PHONE'] = $USER_DATA_ACCOUNT['PHONE'] = $PHONE;
    $USER_DATA['CREATE_LOGIN'] = 0;
    $USER_DATA['APPEAR_IN_CALENDAR'] = 0;
    $USER_DATA['IS_DELETED'] = 0;

    $row = $db->Execute("SELECT UNIQUE_ID FROM DOA_USERS ORDER BY UNIQUE_ID DESC LIMIT 1");
    if ($row->RecordCount() > 0 && $row->fields['UNIQUE_ID'] > 0) {
        $USER_DATA['UNIQUE_ID']  =  intval($row->fields['UNIQUE_ID']) + 1;
    } else {
        $USER_DATA['UNIQUE_ID']  =  300580;
    }

    $USER_DATA['JOINING_DATE'] = date("Y-m-d H:i");
    $USER_DATA['ACTIVE'] = $USER_DATA_ACCOUNT['ACCOUNT'] = 1;
    $USER_DATA['CREATED_BY']  = $USER_DATA_ACCOUNT['CREATED_BY'] = 0;
    $USER_DATA['CREATED_ON']  = date("Y-m-d H:i");
    db_perform('DOA_USERS', $USER_DATA, 'insert');
    $PK_USER = $db->insert_ID();

    $USER_DATA_ACCOUNT['PK_USER_MASTER_DB'] = $PK_USER;
    db_perform_account('DOA_USERS', $USER_DATA_ACCOUNT, 'insert');

    $USER_MASTER_DATA['PK_USER'] = $PK_USER;
    $USER_MASTER_DATA['PK_ACCOUNT_MASTER'] = $PK_ACCOUNT_MASTER;
    $USER_MASTER_DATA['PRIMARY_LOCATION_ID'] = $PK_LOCATION;
    $USER_MASTER_DATA['CREATED_BY'] = 0;
    $USER_MASTER_DATA['CREATED_ON'] = date("Y-m-d H:i");
    db_perform('DOA_USER_MASTER', $USER_MASTER_DATA, 'insert');
    $PK_USER_MASTER = $db->insert_ID();

    $CUSTOMER_USER_DATA['PK_USER_MASTER'] = $PK_USER_MASTER;
    $CUSTOMER_USER_DATA['IS_PRIMARY'] = 1;
    $CUSTOMER_USER_DATA['FIRST_NAME'] = $FIRST_NAME;
    $CUSTOMER_USER_DATA['LAST_NAME'] = $LAST_NAME;
    $CUSTOMER_USER_DATA['PHONE'] = $PHONE;
    $CUSTOMER_USER_DATA['EMAIL'] = $EMAIL_ID;
    db_perform_account('DOA_CUSTOMER_DETAILS', $CUSTOMER_USER_DATA, 'insert');
    $PK_CUSTOMER_DETAILS = $db_account->insert_ID();

    $CUSTOMER_PHONE['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
    $CUSTOMER_PHONE['PHONE'] = $PHONE;
    db_perform_account('DOA_CUSTOMER_PHONE', $CUSTOMER_PHONE, 'insert');

    $CUSTOMER_EMAIL['PK_CUSTOMER_DETAILS'] = $PK_CUSTOMER_DETAILS;
    $CUSTOMER_EMAIL['EMAIL'] = $EMAIL_ID;
    db_perform_account('DOA_CUSTOMER_EMAIL', $CUSTOMER_EMAIL, 'insert');

    $USER_ROLE_DATA['PK_USER'] = $PK_USER;
    $USER_ROLE_DATA['PK_ROLES'] = 4;
    db_perform('DOA_USER_ROLES', $USER_ROLE_DATA, 'insert');

    $CUSTOMER_LOCATION_DATA['PK_USER'] = $PK_USER;
    $CUSTOMER_LOCATION_DATA['PK_LOCATION'] = $PK_LOCATION;
    db_perform('DOA_USER_LOCATION', $CUSTOMER_LOCATION_DATA, 'insert');

    return [$PK_USER, $PK_USER_MASTER];
}
