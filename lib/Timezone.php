<?php

class Timezone 
{
    public static function timezones()
    {
        return [
            'Pacific/Kwajalein' => '(UTC-12:00) Eniwetok, Kwajalein',
            'US/Samoa' => '(UTC-11:00) Midway Island, Samoa',
            'US/Hawaii' => '(UTC-10:00) Hawaii',
            'US/Alaska' => '(UTC-09:00) Alaska',
            'US/Pacific' => '(UTC-08:00) Pacific Time (US &amp; Canada); Tijuana',
            'US/Arizona' => '(UTC-07:00) Arizona',
            'US/Mountain' => '(UTC-07:00) Mountain Time (US &amp; Canada)',
            'America/El_Salvador' => '(UTC-06:00) Central America',
            'US/Central' => '(UTC-06:00) Central Time (US &amp; Canada)',
            'America/Mexico_City' => '(UTC-06:00) Mexico City',
            'Canada/Saskatchewan' => '(UTC-06:00) Saskatchewan',
            'America/Bogota' => '(UTC-05:00) Bogota, Lima, Quito',
            'US/Eastern' => '(UTC-05:00) Eastern Time (US &amp; Canada)',
            'America/Indiana/Indianapolis' => '(UTC-05:00) Indiana (East)',
            'Canada/Atlantic' => '(UTC-04:00) Atlantic Time (Canada)',
            'America/Caracas' => '(UTC-04:00) Caracas, La Paz',
            'America/Santiago' => '(UTC-04:00) Santiago',
            'America/St_Johns' => '(UTC-03:30) Newfoundland',
            'Brazil/East' => '(UTC-03:00) Brasilia',
            'America/Buenos_Aires' => '(UTC-03:00) Buenos Aires, Georgetown',
            'America/Godthab' => '(UTC-03:00) Greenland',
            'Atlantic/South_Georgia' => '(UTC-02:00) Mid-Atlantic',
            'Atlantic/Azores' => '(UTC-01:00) Azores',
            'Atlantic/Cape_Verde' => '(UTC-01:00) Cape Verde Is.',
            //'GMT' => '(UTC) Casablanca, Monrovia',
            'GMT' => '(UTC) Greenwich Mean Time: Dublin, Lisbon',
            'Europe/London' => '(UTC) London',
            'Europe/Amsterdam' => '(UTC+01:00) Amsterdam, Berlin, Rome, Stockholm',
            'Europe/Belgrade' => '(UTC+01:00) Belgrade, Bratislava, Budapest, Prague',
            'Europe/Brussels' => '(UTC+01:00) Brussels, Copenhagen, Madrid, Paris',
            'Europe/Sarajevo' => '(UTC+01:00) Sarajevo, Vilnius, Warsaw, Zagreb',
            'Africa/Bangui' => '(UTC+01:00) West Central Africa',
            'Europe/Athens' => '(UTC+02:00) Athens, Istanbul, Minsk',
            'Europe/Bucharest' => '(UTC+02:00) Bucharest',
            'Egypt' => '(UTC+02:00) Cairo',
            'Africa/Harare' => '(UTC+02:00) Harare, Pretoria',
            'Europe/Helsinki' => '(UTC+02:00) Helsinki, Riga, Tallinn',
            'Asia/Jerusalem' => '(UTC+02:00) Jerusalem',
            'Asia/Baghdad' => '(UTC+03:00) Baghdad',
            'Asia/Kuwait' => '(UTC+03:00) Kuwait, Riyadh',
            'Europe/Moscow' => '(UTC+03:00) Moscow, St. Petersburg, Volgograd',
            'Africa/Nairobi' => '(UTC+03:00) Nairobi',
            'Asia/Tehran' => '(UTC+03:30) Tehran',
            'Asia/Muscat' => '(UTC+04:00) Abu Dhabi, Muscat',
            'Asia/Baku' => '(UTC+04:00) Baku, Tbilisi, Yerevan',
            'Asia/Kabul' => '(UTC+04:30) Kabul',
            'Asia/Yekaterinburg' => '(UTC+05:00) Ekaterinburg',
            'Asia/Karachi' => '(UTC+05:00) Islamabad, Karachi, Tashkent',
            'Asia/Calcutta' => '(UTC+05:30) Calcutta, Chennai, Mumbai, New Delhi',
            'Asia/Katmandu' => '(UTC+05:45) Kathmandu',
            'Asia/Almaty' => '(UTC+06:00) Almaty, Novosibirsk',
            'Asia/Dhaka' => '(UTC+06:00) Astana, Dhaka',
            'Asia/Colombo' => '(UTC+06:00) Sri Jayawardenepura',
            'Asia/Rangoon' => '(UTC+06:30) Rangoon',
            'Asia/Bangkok' => '(UTC+07:00) Bangkok, Hanoi, Jakarta',
            'Asia/Krasnoyarsk' => '(UTC+07:00) Krasnoyarsk',
            'Asia/Hong_Kong' => '(UTC+08:00) Beijing, Chongqing, Hong Kong, Urumqi',
            'Asia/Irkutsk' => '(UTC+08:00) Irkutsk, Ulaan Bataar',
            'Asia/Kuala_Lumpur' => '(UTC+08:00) Kuala Lumpur, Singapore',
            'Australia/Perth' => '(UTC+08:00) Perth',
            'Asia/Taipei' => '(UTC+08:00) Taipei',
            'Asia/Tokyo' => '(UTC+09:00) Osaka, Sapporo, Tokyo',
            'Asia/Seoul' => '(UTC+09:00) Seoul',
            'Asia/Yakutsk' => '(UTC+09:00) Yakutsk',
            'Australia/Adelaide' => '(UTC+09:30) Adelaide',
            'Australia/Darwin' => '(UTC+09:30) Darwin',
            'Australia/Brisbane' => '(UTC+10:00) Brisbane',
            'Australia/Melbourne' => '(UTC+10:00) Canberra, Melbourne, Sydney',
            'Pacific/Guam' => '(UTC+10:00) Guam, Port Moresby',
            'Australia/Hobart' => '(UTC+10:00) Hobart',
            'Asia/Vladivostok' => '(UTC+10:00) Vladivostok',
            'Asia/Magadan' => '(UTC+11:00) Magadan, Solomon Is., New Caledonia',
            'Pacific/Auckland' => '(UTC+12:00) Auckland, Wellington',
            'Pacific/Fiji' => '(UTC+12:00) Fiji, Kamchatka, Marshall Is.',
            'Pacific/Tongatapu' => '(UTC+13:00) Nuku\'alofa',
        ];
    }
    
    public static function offsets()
    {
        return [
            'Pacific/Kwajalein' => -12,
            'US/Samoa' => -11,
            'US/Hawaii' => -10,
            'US/Alaska' => -9,
            'US/Pacific' => -8,
            'US/Arizona' => -7,
            'US/Mountain' => -7,
            'America/El_Salvador' => -6,
            'US/Central' => -6,
            'America/Mexico_City' => -6,
            'Canada/Saskatchewan' => -6,
            'America/Bogota' => -5,
            'US/Eastern' => -5,
            'America/Indiana/Indianapolis' => -5,
            'Canada/Atlantic' => -4,
            'America/Caracas' => -4,
            'America/Santiago' => -4,
            'America/St_Johns' => -3.5,
            'Brazil/East' => -3,
            'America/Buenos_Aires' => -3,
            'America/Godthab' => -3,
            'Atlantic/South_Georgia' => -2,
            'Atlantic/Azores' => -1,
            'Atlantic/Cape_Verde' => -1,
            //'GMT' => '(UTC) Casablanca, Monrovia',,
            'GMT' => 0,
            'Europe/London' => 0,
            'Europe/Amsterdam' => 1,
            'Europe/Belgrade' => 1,
            'Europe/Brussels' => 1,
            'Europe/Sarajevo' => 1,
            'Africa/Bangui' => 1,
            'Europe/Athens' => 2,
            'Europe/Bucharest' => 2,
            'Egypt' => 2,
            'Africa/Harare' => 2,
            'Europe/Helsinki' => 2,
            'Asia/Jerusalem' => 2,
            'Asia/Baghdad' => 3,
            'Asia/Kuwait' => 3,
            'Europe/Moscow' => 3,
            'Africa/Nairobi' => 3,
            'Asia/Tehran' => 3.5,
            'Asia/Muscat' => 4,
            'Asia/Baku' => 4,
            'Asia/Kabul' => 4.5,
            'Asia/Yekaterinburg' => 5,
            'Asia/Karachi' => 5,
            'Asia/Calcutta' => 5.5,
            'Asia/Katmandu' => 5.75,
            'Asia/Almaty' => 6,
            'Asia/Dhaka' => 6,
            'Asia/Colombo' => 6,
            'Asia/Rangoon' => 6.5,
            'Asia/Bangkok' => 7,
            'Asia/Krasnoyarsk' => 7,
            'Asia/Hong_Kong' => 8,
            'Asia/Irkutsk' => 8,
            'Asia/Kuala_Lumpur' => 8,
            'Australia/Perth' => 8,
            'Asia/Taipei' => 8,
            'Asia/Tokyo' => 9,
            'Asia/Seoul' => 9,
            'Asia/Yakutsk' => 9,
            'Australia/Adelaide' => 9.5,
            'Australia/Darwin' => 9.5,
            'Australia/Brisbane' => 10,
            'Australia/Melbourne' => 10,
            'Pacific/Guam' => 10,
            'Australia/Hobart' => 10,
            'Asia/Vladivostok' => 10,
            'Asia/Magadan' => 11,
            'Pacific/Auckland' => 12,
            'Pacific/Fiji' => 12,
            'Pacific/Tongatapu' => 13,
        ];
    }
} 