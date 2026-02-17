<?php

class time {

    /**
     * Get a human readable time from number of seconds
     * https://stackoverflow.com/questions/15548792/best-way-to-calculate-human-readable-elapsed-time
     * 
     * @example:
     * <code>echo time::hrDelta(<seconds>);</code>
     * 
     * @param int $iSeconds  seconds
     * @return string
     */
    public static function hrDelta(int $iSeconds): string
    {
        // $names = ["s", "min", "h", "d", "months", "years"];
        // $values = [1, 60, 3600, 24 * 3600, 30 * 24 * 3600, 365 * 24 * 3600];

        $names =  ['min', 'h', 'd',       "y"];
        $values = [60,    3600, 24 * 3600, 365 * 24 * 3600];

        // if ($iNoOutput && $iSeconds > $iNoOutput) {
        //     return '';
        // }
        for ($i = count($values) - 1; $i > 0 && $iSeconds < $values[$i]; $i--);
        if ($i == 0) {
            return intval($iSeconds / $values[$i]) . ' ' . $names[$i];
        } else {
            $t1 = intval($iSeconds / $values[$i]);
            $t2 = intval(($iSeconds - ($t1 * $values[$i])) / $values[$i - 1]);
            return "$t1 " . $names[$i] . ($t2 ? ", $t2 " . $names[$i - 1] : "" );
        }
    }

}