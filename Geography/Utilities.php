<?php

namespace Sevenedge\Geography;

class Utilities {

	const PRECISION = 100000;

	public static function decodePolyline($string)
	{
		$points = array();
		$index = $i = 0;
		$previous = array(0,0);
		while ($i < strlen($string)) {
			$shift = $result = 0x00;
			$bit = 0x21;
			while ($bit >= 0x20) {
				$bit = ord(substr($string, $i++)) - 63;
				$result |= ($bit & 0x1f) << $shift;
				$shift += 5;
			}
			$diff = ($result & 1) ? ~($result >> 1) : ($result >> 1);
			$number = $previous[$index % 2] + $diff;
			$previous[$index % 2] = $number;
			if ($index % 2 === 0) {
				$points[] = $number * 1 / self::PRECISION;
			} else {
				$points[floor($index/2)] .= ',' . $number * 1 / self::PRECISION;
			}
			$index++;
		}
		return $points;
	}
}