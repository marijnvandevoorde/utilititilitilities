<?php

namespace Sevenedge\Lib\Utilities;

class Conversion
{
	const
		UNIT_M = 1,
		UNIT_CM = 2,
		UNIT_INCH = 3,
		UNIT_FT = 4,
		UNIT_FT_INCH = 100;

	private static $_units = array(
		self::UNIT_M => 'm',
		self::UNIT_CM => 'cm',
		self::UNIT_FT => '\'',
		self::UNIT_INCH => '"'
	);


	public static $_CONVERSIONRATES = array(
		self::UNIT_CM => array(
			self::UNIT_INCH => 0.393701,
			self::UNIT_FT => 0.0328084
		),
		self::UNIT_INCH => array(
			self::UNIT_CM => 2.54,
			self::UNIT_FT => 0.0833333
		),
		self::UNIT_FT => array(
			self::UNIT_CM => 30.48,
			self::UNIT_INCH => 12
		)
	);

	public static function convert($value, $unit, $toUnit, $round = 'integer')
	{
		switch ($toUnit)
		{
			case self::UNIT_FT_INCH;
				$ft = self::convert($value, $unit, self::UNIT_FT, false);
				$response = array(self::UNIT_FT => floor($ft));
				$ft = $ft - $response[self::UNIT_FT];
				$response[self::UNIT_INCH] = self::_round(self::convert($ft, self::UNIT_FT, self::UNIT_INCH), $round);
				return $response;
			default:
				if (isset(self::$_CONVERSIONRATES[$unit]) && isset(self::$_CONVERSIONRATES[$unit][$toUnit]))
				{
					return  self::_round($value * self::$_CONVERSIONRATES[$unit][$toUnit], $round);
				}
		}

		return 0;
	}

	private static function _round($value, $type = false)
	{
		switch($type)
		{
			case false:
				return $value;
			case 'integer':
				return round($value);
			case 'half':
				return floor($value * 2) / 2;
			default:
				if (is_numeric($type) && $type >= 0)
				{
					return number_format($value, $type);
				}
		}
	}

	public static function toString($value, $unit, $toUnit, $round = 'integer')
	{
		$converted = self::convert($value, $unit, $toUnit, $round);
		if (!is_array($converted))
		{
			return $converted . self::$_units[$toUnit];
		}
		$string = '';
		foreach ($converted as $unit => $value)
		{
			$string .= $value . self::$_units[$unit] . ' ';
		}
		return rtrim($string);
	}
}