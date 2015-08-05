<?php

namespace Sevenedge\CakeUtils;


class I18n {

	private static $_languageMapping = array(
		'af' => 'af-ZA',
		'ar' => 'ar-AE', // united arabic emirates
//		'ar' => 'ar-BH',
//		'ar' => 'ar-DZ',
//		'ar' => 'ar-EG',
//		'ar' => 'ar-IQ',
//		'ar' => 'ar-JO',
//		'ar' => 'ar-KW',
//		'ar' => 'ar-LB',
//		'ar' => 'ar-LY',
//		'ar' => 'ar-MA',
//		'ar' => 'ar-OM',
//		'ar' => 'ar-QA',
//		'ar' => 'ar-SA',
//		'ar' => 'ar-SY',
//		'ar' => 'ar-TN',
//		'ar' => 'ar-YE',
//		'az' => 'Cy-az-AZ', // cyrilic. let's pick latin below
		'az' => 'Lt-az-AZ',
		'be' => 'be-BY',
		'bg' => 'bg-BG',
		'ca' => 'ca-ES',
		'cs' => 'cs-CZ',
		'da' => 'da-DK',
//		'de' => 'de-AT',
//		'de' => 'de-CH',
		'de' => 'de-DE',
//		'de' => 'de-LI',
//		'de' => 'de-LU',
		'el' => 'el-GR',
//		'en' => 'en-AU',
//		'en' => 'en-BZ',
//		'en' => 'en-CA',
//		'en' => 'en-CB',
		'en' => 'en-GB',
//		'en' => 'en-IE',
//		'en' => 'en-JM',
//		'en' => 'en-NZ',
//		'en' => 'en-PH',
//		'en' => 'en-TT',
//		'en' => 'en-US',
//		'en' => 'en-ZA',
//		'en' => 'en-ZW',
//		'es' => 'es-AR',
//		'es' => 'es-BO',
//		'es' => 'es-CL',
//		'es' => 'es-CO',
//		'es' => 'es-CR',
//		'es' => 'es-DO',
//		'es' => 'es-EC',
		'es' => 'es-ES',
//		'es' => 'es-GT',
//		'es' => 'es-HN',
//		'es' => 'es-MX',
//		'es' => 'es-NI',
//		'es' => 'es-PA',
//		'es' => 'es-PE',
//		'es' => 'es-PR',
//		'es' => 'es-PY',
//		'es' => 'es-SV',
//		'es' => 'es-UY',
//		'es' => 'es-VE',
		'et' => 'et-EE',
		'eu' => 'eu-ES',
		'fa' => 'fa-IR',
		'fi' => 'fi-FI',
		'fo' => 'fo-FO',
//		'fr' => 'fr-BE',
//		'fr' => 'fr-CA',
//		'fr' => 'fr-CH',
		'fr' => 'fr-FR',
//		'fr' => 'fr-LU',
//		'fr' => 'fr-MC',
		'gl' => 'gl-ES',
		'gu' => 'gu-IN',
		'he' => 'he-IL',
		'hi' => 'hi-IN',
		'hr' => 'hr-HR',
		'hu' => 'hu-HU',
		'hy' => 'hy-AM',
		'id' => 'id-ID',
		'is' => 'is-IS',
//		'it' => 'it-CH',
		'it' => 'it-IT',
		'iv' => 'div-MV',
		'ja' => 'ja-JP',
		'ka' => 'ka-GE',
		'kk' => 'kk-KZ',
		'kn' => 'kn-IN',
		'ko' => 'ko-KR',
		'ky' => 'ky-KZ',
		'lt' => 'lt-LT',
		'lv' => 'lv-LV',
		'mk' => 'mk-MK',
		'mn' => 'mn-MN',
		'mr' => 'mr-IN',
//		'ms' => 'ms-BN',
		'ms' => 'ms-MY', // malaysia
		'nb' => 'nb-NO',
//		'nl' => 'nl-BE',
		'nl' => 'nl-NL',
		'nn' => 'nn-NO',
		'ok' => 'kok-IN',
		'pa' => 'pa-IN',
		'pl' => 'pl-PL',
//		'pt' => 'pt-BR',
		'pt' => 'pt-PT',
		'ro' => 'ro-RO',
		'ru' => 'ru-RU',
		'sa' => 'sa-IN',
		'sk' => 'sk-SK',
		'sl' => 'sl-SI',
		'sq' => 'sq-AL',
//		'sr' => 'Cy-sr-SP', // cyrilic, no tnx, let's pick latin below
		'sr' => 'Lt-sr-SP',
//		'sv' => 'sv-FI',
		'sv' => 'sv-SE', // swedish
		'sw' => 'sw-KE',
		'syr' => 'syr-SY',
		'ta' => 'ta-IN',
		'te' => 'te-IN',
		'th' => 'th-TH',
		'tr' => 'tr-TR',
		'tt' => 'tt-RU',
		'uk' => 'uk-UA',
		'ur' => 'ur-PK',
//		'uz' => 'Cy-uz-UZ',
		'uz' => 'Lt-uz-UZ',
		'vi' => 'vi-VN',
//		'zh' => 'zh-CHS',
//		'zh' => 'zh-CHT',
		'zh' => 'zh-CN', // chinese is from china
//		'zh' => 'zh-HK',
//		'zh' => 'zh-MO',
//		'zh' => 'zh-SG',
//		'zh' => 'zh-TW',
	);

	private static $_poHeader = '# LANGUAGE translation for CakePHP Application
# Copyright 2015 SEVENEDGE info@sevenedge.be
#
msgid ""
msgstr ""
"PO-Revision-Date: [date]\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\n"
"POT-Creation-Date: \n"
"X-Generator: Sevenedge I18n utils v0.1\n"
"Language: [language]\n"
';

	/**
	 * @param $sourceFile The file containing the translations.
	 * @param null $errHandler method to handle errors. 2 params, a level and message.
	 * @throws \Exception A general exception when something goes so bad there's no use trying
	 */
	public static function extract($sourceFile, $errHandler = null) {
		if ($errHandler == null) {
			$errHandler = function($level, $message) {
				error_log($level . ' - ' .$message);
			};
		}

		ini_set('auto_detect_line_endings', true);
		if (($input = fopen($sourceFile, 'r')) !== FALSE) {
			$headers = fgetcsv($input, 5000, "\t");

			if ($headers === FALSE || count($headers) < 2) {
				throw new \Exception(__("No headers or less than 2 columns in the header."));
			}
			// first should ALWAYS contain id. let's throw it away.
			array_shift($headers);

			self::$_poHeader = str_replace("[date]", date('Y-m-j H:iO'), self::$_poHeader);
			$poFiles = array();
			foreach ($headers as $idx => $header) {
				$iso6391 = strtolower(trim($header));
				if (!isset(self::$_languageMapping[$iso6391])) {
					$errHandler(E_USER_WARNING, "Language \"$header\" could not be mapped to a 3-letter ISO 639-2 code");
					$headers[$idx] = 0;
					continue;
				}
				$iso6392 = self::$_languageMapping[$iso6391];
				$headers[$idx] = $iso6392;
				if (!file_exists(APP . 'Locale' . DS . $iso6392 . DS . 'LC_MESSAGES')) {
					mkdir(APP . 'Locale' . DS . $iso6392 . DS . 'LC_MESSAGES', 0777, TRUE);
				}
				$poFiles[$iso6392] = fopen(APP . 'Locale' . DS . $iso6392 . DS . 'LC_MESSAGES' . DS . 'default.po', 'w');
				fwrite($poFiles[$iso6392], str_replace('[language]', $iso6392, self::$_poHeader));
			}

			$lnr = 1;

			while (($data = fgetcsv($input, 5000, "\t")) !== FALSE) {
				$lnr++;
				// first col should ALWAYS contain id
				$id = array_shift($data);

				// if empty, skip this one.
				if ($data === FALSE || count($data) < 1 || !array_filter($data)) {
					continue;
				}

				$data = array_combine($headers, $data);
				foreach ($data as $iso6392 => $copy) {
					if ($iso6392 && !empty($copy)) {
						fwrite($poFiles[$iso6392], "\n");
						fwrite($poFiles[$iso6392], "#: $sourceFile:$lnr\n");
						fwrite($poFiles[$iso6392], 'msgid "' . str_replace('"', '\"', $id) . "\"\n");
						fwrite($poFiles[$iso6392], 'msgstr "' . str_replace('"', '\"', $copy) . "\"\n");
					}
				}

			}
			fclose($input);
			foreach ($poFiles as $iso6392 => $resource) {
				fclose($resource);
			}
		}

	}
}