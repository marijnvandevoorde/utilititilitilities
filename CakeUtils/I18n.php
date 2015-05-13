<?php

namespace Sevenedge\CakeUtils;


class I18n {

	private static $_languageMapping = array(
		'en' => 'eng',
		'de' => 'deu',
		'fr' => 'fra',
		'it' => 'ita',
		'es' => 'spa',
		'ru' => 'rus',
		'tr' => 'tur',
		'nl' => 'nld'
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
					$errHandler(E_USER_WARNING, "Language $header could not be mapped to a 3-letter ISO 639-2 code");
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
					if (!empty($copy)) {
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