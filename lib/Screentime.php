<?php

class Screentime
{
    private $data = [];
    public $day_offset = 0;

    public function __construct($device_id, $account_id)
    {
        $this->add_account($device_id, $account_id);
    }

    public function __get($name)
    {
        if ($name == 'data') {
            return $this->data;
        }
    }

    private function add_account($device_id, $account_id)
    {
        $key = sprintf('screentime.%s.%s', $device_id, $account_id);
        $json = RedisDB::instance()->get($key);

        if (!($data = json_decode($json, true))) {
            $data = [];
        }
        if (!$this->data) {
            $this->data = $data;
            return;
        }

        if (isset($data['range'])) {
            if (!isset($this->data['range'])) {
                $this->data['range'] = [];
            }
            if (!isset($this->data['total'])) {
                $this->data['total'] = [];
            }
            foreach ($data['range'] as $what => $ranges) {
                if (!isset($this->data['total'][$what])) {
                    $this->data['total'][$what] = $data['total'][$what];
                }
                if (!isset($this->data['range'][$what])) {
                    $this->data['range'][$what] = $ranges;
                    continue;
                }
                foreach ($ranges as $range) {
                    $duration = $range[1] - $range[0];
                    $found = false;
                    foreach ($this->data['range'][$what] as $index => $p) {
                        if ($p[1] < $range[0] || $p[0] > $range[1]) continue;
                        if ($range[0] >= $p[0] && $range[0] <= $p[1]) { // inside
                            $found = true;
                            if ($range[1] > $p[1]) {
                                $duration = $range[1] - $p[1];
                                $this->data['range'][$what][$index][1] = $range[1];
                            } else {
                                $duration = 0;
                            }
                        } else
                        if ($range[1] >= $p[0] && $range[1] <= $p[1]) { // outside
                            $found = true;
                            if ($range[0] < $p[0]) {
                                $duration = $p[0] - $range[0];
                                $this->data['range'][$what][$index][0] = $range[0];
                            } else {
                                $duration = 0;
                            }
                        } else
                        if ($range[0] <= $p[0] && $range[1] >= $p[1]) { // around
                            $found = true;
                            $duration = ($p[0] - $range[0]) + ($range[1] - $p[1]);
                            $this->data['range'][$what][$index] = $range;
                        }
                        if ($found) {
                            break;
                        }
                    }
                    if (!$found) {
                        $this->data['range'][$what][] = $range;
                    }

                    $day = Date::to_days($range[0] + $this->day_offset);
                    if (!isset($this->data['total'][$what][$day])) {
                        $this->data['total'][$what][$day] = 0;
                    }
                    $this->data['total'][$what][$day] += $duration;
                }
                usort($this->data['range'][$what], function($a, $b){return $a[0] == $b[0]? 0 : ($a[0] > $b[0]? 1 : -1);});
                $this->data['range'][$what] = array_values($this->data['range'][$what]);
            }
        }
    }

    /**
     * @param $range int|array
     * @param string $what
     * @return int
     */
    public function duration($range, $what = 'other')
    {
        if (is_array($range)) {
            $dx = $range[1] - $range[0];
            if ($dx >= 86400 && ($dx % 86400) < 10) {
                $n = ceil($dx/86400);
                $total = 0;
                for ($i = 0; $i < $n; $i++) {
                    $day = Date::to_days($range[0] + 86400*$i + $this->day_offset);
                    $total += isset($this->data['total'][$what][$day])? $this->data['total'][$what][$day] : 0;
                }
                return $total;
            } else {
                if (isset($this->data['range'][$what])) {
                    $total = 0;
                    foreach ($this->data['range'][$what] as $p) {
                        if ($range[0] >= $p[0] && $range[0] <= $p[1]) {
                            if ($p[1] <= $range[1]) { // outside
                                $total += $p[1] - $range[0];
                            } else { // inside
                                $total += $range[1] - $range[0];
                            }
                        } else
                        if ($range[1] >= $p[0] && $range[1] <= $p[1]) {
                            if ($range[0] < $p[0]) {
                                $total += $range[1] - $p[0];
                            } else {
                                $total += $range[1] - $range[0];
                            }
                        } else
                        if ($range[0] <= $p[0] && $range[1] >= $p[1]) {
                            $total += $p[1] - $p[0];
                        }
                    }
                    return $total;
                } else {
                    return 0;
                }
            }
        } else {
            $day = Date::is_days($range)? $range : Date::to_days($range + $this->day_offset);
            return isset($this->data['total'][$what][$day])? $this->data['total'][$what][$day] : 0;
        }
    }
}