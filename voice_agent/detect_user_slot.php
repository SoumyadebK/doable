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
