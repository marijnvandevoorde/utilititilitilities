<?php


namespace Sevenedge\Geography;

class CoordinateSystems {
	CONST
		SYSTEM_WGS84 = 0,
		SYSTEM_LAMBERT1972 = 1;

	public static function convert($from, $to, $coordinates = array()) {

		$middle = self::_toWGS84($from, $coordinates);
		return ($to === self::SYSTEM_WGS84 ? $middle : self::_fromWGS84($to, $middle));
	}

	private static function _toWGS84 ($from, $coordinates) {
		switch ($from) {
			case self::SYSTEM_LAMBERT1972:
				if (!isset($coordinates['x']) || !isset($coordinates['y'])) {
					throw new \InvalidArgumentException("Coordinates in Lambert 1972 need X & Y values");
				}
				$longref = 0.076042943; //4°21'24"983
				$nLamb = 0.7716421928;
				$aCarre = pow(6378388, 2);
				$bLamb = 6378388 * (1 - (1 / 297));
				$eCarre = ($aCarre - pow($bLamb, 2)) / $aCarre;
				$KLamb = 11565915.812935;
				$eLamb = sqrt($eCarre);

				$Tan1 = ($coordinates['x'] - 150000.01256) / (5400088.4378 - $coordinates['y']);
				$Lambda = $longref + (1 / $nLamb) * (0.000142043 + atan($Tan1));
				$RLamb = sqrt(pow($coordinates['x'] - 150000.01256, 2) + pow(5400088.4378 - $coordinates['y'], 2));

				$TanZDemi = pow($RLamb / $KLamb, 1 / $nLamb);
				$Lati1 = 2 * atan($TanZDemi);

				$diff = 10;
				while (abs($diff >  0.0000000277777)) {
					$eSin = $eLamb * sin($Lati1);
					$Mult1 = 1 - $eSin;
					$Mult2 = 1 + $eSin;
					$Mult = pow($Mult1 / $Mult2, $eLamb / 2);
					$LatiN = (M_PI_2) - (2 * atan($TanZDemi * $Mult));
					$diff = $LatiN - $Lati1;
					$Lati1 = $LatiN;
				}
				return array(
					'lat' => $LatiN * 180 / M_PI,
					'lon' => $Lambda * 180 / M_PI
				);
			default:
				throw new \NotImplementedException("conversion from $from is not implemented");
		}
	}

	private static function _fromWGS84 ($to, $coordinates) {
		throw new \NotImplementedException("conversion to $to is not implemented");
	}

}