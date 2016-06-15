<?php

define('DATE_MYSQL', 'Y-m-d H:i:s');

class Date
{
    private static $globalo_days = array();

    public static function to_days($time = false)
    {
        if (isset(Date::$globalo_days[$time? $time : 0])) {
            return Date::$globalo_days[$time? $time : 0];
        }

        if (!$time) {
            $time = time();
        }

        $date = date(DATE_MYSQL, $time);
        $bits = explode('-', $date, 2);
        $year = $bits[0];

        $bits[0] = Date::is_leap_year($year)? '2000' : '1999';
        $date = implode('-', $bits);

        //$leaps = 387; //leap years up to 1600
        $leaps = 460; //leap years up to 1900
        for ($i = 1900; $i < $year; $i++) {
            if (Date::is_leap_year($i)) {
                ++$leaps;
            }
        }
        $days = date('z', strtotime($date));
        $result = $leaps + ($year * 365) + $days + 1;
        Date::$globalo_days[$time? $time : 0] = $result;

        return $result;
    }

    private static function is_leap_year($year)
    {
        if ($year % 100 == 0 && $year % 400 == 0) {
            return true;
        }
        if ($year % 100 == 0) {
            return false;
        }
        if ($year % 4 == 0) {
            return true;
        }
        return false;
    }


    public static function from_days($days)
    {
        if (is_array($days)) {
            debug_print_backtrace();die;
        }
        $offset = Yii::app()->params['server_timezone'];
        $time = ($days - 719528) * 86400 - ($offset * 3600);
        return $time;
    }

    public static function is_days($date)
    {
        return $date < 1000000 && $date > 100000;
    }

    public static function is_date($date)
    {
        return $date > 100000000 && $date < 2000000000;
    }

    /**
     * @param $age int
     * @return int
     */
    public static function smart_age_format($age)
    {
        if ($age < 100) {
            return $age > 0? $age : 0;
        }
        if ($age < 3000) {
            $result = date('Y') - $age;
            return $result > 0? $result : 0;
        }
        if ($age > 3000) {
            $y = date('Y') - date('Y', $age);
            $m = date('m') - date('m', $age);
            if ($m < 0) {
                $y--;
            } elseif ($m == 0) {
                $d = date('d') - date('d', $age);
                if ($d < 0) {
                    $y--;
                }
            }
            return $y > 0? $y : 0;
        }
        return $age > 0? $age : 0;
    }
}