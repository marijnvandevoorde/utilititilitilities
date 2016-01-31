<?php

namespace Marijnworks\Utilities\Utilities;

if (!defined('IMAGETOOLS_LIB')) {
	define('IMAGETOOLS_LIB', 'GD');
}

/**
 * Class ImageTools
 * @author Marijn Vandevoorde <marijn@marijnworks.be>
 * @link http://www.marijnworks.be marijnworks.be
 * @copyright marijnworks 2013
 * @license some MIT'ish like thing. Any usage is encouraged and comes free of charge (and free of any guarantee that it'll actually work for you).
 *
 * Basic class with some image uploading, resizeing, cropping,... functionality
 */
class ImageTools {

	CONST
		RESULT_ERROR = -1,
		RESULT_NOACTION = 0,
		RESULT_SUCCESS = 1;

	CONST
		TYPE_JPEG = 'image/jpeg',
		TYPE_PNG = 'image/png',
		TYPE_GIF = 'image/gif',
		TYPE_PDF = 'application/pdf';

	private static $_extensionmapping = array(
		self::TYPE_JPEG => '.jpg',
		self::TYPE_PNG => '.png',
		self::TYPE_GIF => '.gif',
		self::TYPE_PDF => '.pdf'
	);

	private static $_mimetypemapping = array(
		'.jpg' => self::TYPE_JPEG,
		'.jpeg' => self::TYPE_JPEG,
		'.png' => self::TYPE_PNG,
		'.gif' => self::TYPE_GIF,
		'.pdf' => self::TYPE_PDF
	);



	/**
	 * A rather robust method for the lazy, to handle uploaded files.
	 * @param array $data the file data array, straight from the _POST data!
	 * @param String $destination  the path to store the image to. This can be either an existing directory, in which case a random filename will be generated, or the file path you want for this file
	 * @param int $width if you want to resize the image, give a width if you want to resize to a certain width
	 * @param int $height if you want to resize the image, give a height if you want to resize to a certain height
	 * @param bool $constrain do you want to constrain proportions? if true, the resulting image will either match the dimension you gave (width/height) or, if you gave both, match inside the dimensions or get cropped
	 * @param bool $crop true to crop, false if you don't want to crop. see $constrain
	 * @param int $quality jpeg quality
	 * @param bool $enlarge true if you want to enlarge images smaller than given dimensions
	 * @return array will return an associative array. the 'result'
	 */
	public static function handleUpload($data, $destination, $width = null, $height = null, $constrain = true, $crop = false, $quality = 85, $enlarge = false) {
		// minimum amount of info is required, and the file should not be empty
		if (isset($data['tmp_name']) && isset($data['name']) && $data['size'] > 0) {
			// is it even worth trying :-)
			// just upload it as is. no more, no less.
			if (is_null($width) && is_null($height)) {
				// we'll do these checks here
				$source = self::_analyzeFile($data);
				if (!isset($source['type']) || !isset($source['path'])) {
					return array('result' => self::RESULT_ERROR, 'error' => 'invalid source file, not quite sure what to do with it');
				}

				// check target file
				$destination = self::_calculateTarget($source, $destination);

				if ($destination['result'] === self::RESULT_ERROR) {
					return $destination;
				}

				$res = move_uploaded_file($data['tmp_name'], $destination['path']);
				if ($res === true) {
					// we still want to give you the dimensions of this file too
					$dimensions = @getimagesize($destination['path']);
					$destination['width'] = $dimensions[0];
					$destination['height'] = $dimensions[1];
					$destination['result'] = self::RESULT_SUCCESS;
					return $destination;

				} else {
					return array('result' => self::RESULT_ERROR, 'error' => 'unable to store the file');

				}
			}

			// but this method can do moar than just upload!
			//do the resize!
			$result = ImageTools::resize($data, $destination, null, $width, $height, $constrain, $crop, $quality, $enlarge);
			// this isn't enitrely correct. it is if you're just resizing, but not in the case of handle upload, so let's fix this
			if ($result['result'] === self::RESULT_NOACTION) {
				$result['result'] === self::RESULT_SUCCESS;
			}

			return $result;
		}
		return array('result' => self::RESULT_NOACTION);
	}


	/**
	 * @param String $source path of the original image
	 * @param String $destination target file path
	 * @param int $percent percentual resize factor. 1 to 100 kind of value.
	 * @param int $width if you want to resize the image, give a width if you want to resize to a certain width
	 * @param int $height if you want to resize the image, give a height if you want to resize to a certain height
	 * @param bool $constrain do you want to constrain proportions? if true, the resulting image will either match the dimension you gave (width/height) or, if you gave both, match inside the dimensions or get cropped
	 * @param bool $crop: wether to crop if the resizing has to happen in a constrained way and both target width & height were given.
	 * @param int $quality jpeg quality
	 * @param bool $enlarge true if you want to enlarge images smaller than given dimensions
	 * @param bool $deleteOriginal set true if you want to delete the original file. keep in mind that, if source & destination are the same, this will be overridden!!!
	 * @return int result code
	 */

	public static function resize($source, $destination = null, $percent = null, $width = null, $height = null, $constrain = true, $crop = false, $quality = 85,$enlarge = false, $deleteOriginal = false) {
		// check source file
		$source = self::_analyzeFile($source);
		if (!isset($source['type']) || !isset($source['path'])) {
			return array('result' => self::RESULT_ERROR, 'error' => 'invalid source file, not quite sure what to do with it');
		}

		// check target file
		$destination = self::_calculateTarget($source, $destination);

		if ($destination['result'] === self::RESULT_ERROR) {
			return $destination;
		}


		// get the source image size of img
		$x = @getimagesize($source['path']);
		// image width
		$sw = $x[0];
		// image height
		$sh = $x[1];
		//default, no cropping:
		$cropx = 0; $cropy = 0;

		// do we actually have to do something? Well, not if the size is already right or. I'm 100% sure this check isn't perfect. like if we constrain, don't crop and width matches and height is smaller, etc, etc.
		if (
			$percent == 100 // that's obvious
			|| (!$enlarge && ($width == null || $width >= $sw) && ($height == null || $height >= $sh)) //we're not enlarging!
			|| ($width == $sw && $height = $sh) // rather obvious as well
		) {
			if ($source['path'] !== $destination['path']) {
				if ($deleteOriginal) {
					rename($source['path'], $destination['path']);
				} else {
					copy($source['path'], $destination['path']);
				}
			}
			$destination['width'] = $sw;
			$destination['height'] = $sh;
			$destination['result'] = self::RESULT_NOACTION;
		}

		switch($source['type']) {
			case (ImageTools::TYPE_JPEG):
				$im = @ImageCreateFromJPEG($source['path']); // Read JPEG Image
				break;
			case ImageTools::TYPE_PNG:
				$im = @ImageCreateFromPNG($source['path']); // Read PNG
				break;
			case ImageTools::TYPE_GIF:
				$im = @ImageCreateFromGIF($source['path']); // Read GIF
				break;

		}

		if (!$im) {
			return array('result' => self::RESULT_ERROR, 'error' => 'Failed to open file');
		} else {
			// here comes the math part
			if (!$percent) {
				if (isset($width) && $height == null) {
					// autocompute height if only width is set. we'll constrain here. crop makes no sense.
					$height = @round($sh * $width / $sw);
				} elseif (isset($height) && $width == null) {
					// autocompute width if only height is set. we'll constrain here. crop makes no sense.
					$width = @round($sw * $height / $sh);
				} elseif (isset($height) && isset($width) && $constrain) {
					// calculate the resize factor if both are set and constrain is also set.
					$wf = $width / $sw;
					$hf = $height / $sh;
					if ($crop) {
						// if the height factor is smaller than the width factor,
						if ($hf < $wf) {
							$ch = @round($height / $wf);
							$cropy = @round(($sh - $ch)/2);
							$sh = $ch;
						} else {
							$cw = @round($width / $hf);
							$cropx = @round(($sw -$cw)/2);
							$sw = $cw;
						}
					} else {
						// biggest factor so the image fits inside the bounding box
						if ($hf < $wf) {
							$width = @round($sw * $hf);
						} else {
							$height = @round($sh * $wf);
						}

					}
				}
				else {
					// well, we're all set. width & height are the requested once, no mathemagical formulas needed
				}
			} else {
				// calculate resized height and width if percent is defined
				$percent = $percent * 0.01;
				$width = $sw * $percent;
				$height = $sh * $percent;
				// percent will always keep aspect ratio. cropping makes no sense here. our job here is done!
			}

			if (IMAGETOOLS_LIB === 'GD') {
				// Create the resized image destination
				$resized = @ImageCreateTrueColor($width, $height);
				// Copy from image source, resize it, and paste to image destination

				@ImageCopyResampled($resized, $im, 0, 0, $cropx, $cropy, $width, $height, $sw, $sh);
				// Output resized image


				// just making sure we don't do anything stupid here in case we'll be losing the original file.
				$temp = $deleteOriginal ? $source['path'] : false;
				if ($destination['path'] === $source['path']) {
					$temp = tempnam(null, null);
					rename($source['path'], $temp);
				}

				switch ($destination['type']) {
					case self::TYPE_JPEG:
						$result = @imagejpeg($resized, $destination['path'], $quality);
						break;
					case self::TYPE_PNG:
						$result = @imagepng($resized, $destination['path'], ceil($quality*9 / 100));
						break;
					case self::TYPE_GIF:
						$result = @imagegif($resized, $destination['path'], $quality);
						break;
				}

				if ($result === true) {
					$destination['result'] = self::RESULT_SUCCESS;
					$destination['width'] = $width;
					$destination['height'] = $height;
					if ($temp) {
						unlink($temp);
					}
					return $destination;
				} else {
					if ($temp && $temp !== $source['path']) {
						rename($temp, $source['path']);
						return $destination;
					}

				}
				return array('result' => self::RESULT_ERROR, 'error' => 'failed to write to file.');
			} else if (IMAGETOOLS_LIB === 'IMAGICK') {
				return array('result' => self::RESULT_ERROR, 'error' => 'imagick not implemented.');
			}
		}
	}

	public static function watermark($image, $watermark, $top = 0, $left = 0, $destination = null, $makeItFit = false, $quality = 90) {
		$source = self::_analyzeFile($image);
		$wm = self::_analyzeFile($watermark);


		// check target file
		$destination = self::_calculateTarget($source, $destination);

		$x = @getimagesize($wm['path']);
		// image width
		$ww = $x[0];
		// image height
		$wh = $x[1];

		// if negative, count from bottom right en al
		if ($top < 0 || $left < 0 || $makeItFit) {
			// get the source image size of img
			$x = @getimagesize($source['path']);
			// image width
			$sw = $x[0];
			// image height
			$sh = $x[1];

			if ($top < 0) {
				$top = $sh - $wh + $top;
			}
			if ($left < 0) {
				$left = $sw - $ww + $left;
			}
			

			if ($makeItFit) {
				$temp_file = tempnam(APP . 'tmp' . DS, 'SEIT_') . $source['extension'];
				$result = self::resize($image, $temp_file, null, $ww, $wh, true, true, 100, false, false);

				$source = self::_analyzeFile($temp_file);
			}
		}

		switch($source['type']) {
			case (ImageTools::TYPE_JPEG):
				$im = @ImageCreateFromJPEG($source['path']); // Read JPEG Image
				break;
			case ImageTools::TYPE_PNG:
				$im = @ImageCreateFromPNG($source['path']); // Read PNG
				break;
			case ImageTools::TYPE_GIF:
				$im = @ImageCreateFromGIF($source['path']); // Read GIF
				break;

		}

		switch($wm['type']) {
			case (ImageTools::TYPE_JPEG):
				$wm = @ImageCreateFromJPEG($wm['path']); // Read JPEG Image
				break;
			case ImageTools::TYPE_PNG:
				$wm = @ImageCreateFromPNG($wm['path']); // Read PNG
				break;
			case ImageTools::TYPE_GIF:
				$wm = @ImageCreateFromGIF($wm['path']); // Read GIF
				break;

		}

		imagecopy($im, $wm, $left, $top, 0, 0, $ww, $wh);

		switch ($destination['type']) {
			case self::TYPE_JPEG:
				$result = @imagejpeg($im, $destination['path'], $quality);
				break;
			case self::TYPE_PNG:
				$result = @imagepng($im, $destination['path'], ceil($quality*9 / 100));
				break;
			case self::TYPE_GIF:
				$result = @imagegif($im, $destination['path'], $quality);
				break;
		}

		if ($result === true) {
			$destination['result'] = self::RESULT_SUCCESS;
			$x = @getimagesize($destination['path']);
			// image width
			$sw = $x[0];
			// image height
			$sh = $x[1];

			$destination['width'] = $sw;
			$destination['height'] = $sh;
			if (isset($temp_file)) {
				unlink($temp_file);
			}
			return $destination;
		}
		return array('result' => self::RESULT_ERROR, 'error' => 'something went wrong.');
	}


	public static function frame($image, $frame, $destination, $crop = false, $hborder = 0, $wborder = 0, $quality = 90) {
		$source = self::_analyzeFile($image);
		$fr = self::_analyzeFile($frame);


		// check target file
		$destination = self::_calculateTarget($source, $destination);

		$x = @getimagesize($fr['path']);
		// image width
		$fw = $x[0];
		// image height
		$fh = $x[1];

		$x = @getimagesize($source['path']);
		// image width
		$sw = $x[0];
		// image height
		$sh = $x[1];

		if ($crop) {
			$minw = $fw - 2 * $wborder;
			$minh = $fh - 2 * $hborder;

		}

		$temp_file = tempnam(APP . 'tmp' . DS, 'SEIT_') . $source['extension'];

		$result = self::resize($image, $temp_file, null, $minw, $minh, true, true, 100, true, false);


		$source = self::_analyzeFile($temp_file);
		$source['width'] = $result['width'];
		$source['height'] = $result['height'];

		switch($source['type']) {
			case (ImageTools::TYPE_JPEG):
				$im = @ImageCreateFromJPEG($source['path']); // Read JPEG Image
				break;
			case ImageTools::TYPE_PNG:
				$im = @ImageCreateFromPNG($source['path']); // Read PNG
				break;
			case ImageTools::TYPE_GIF:
				$im = @ImageCreateFromGIF($source['path']); // Read GIF
				break;

		}

		switch($fr['type']) {
			case (ImageTools::TYPE_JPEG):
				$fr = @ImageCreateFromJPEG($fr['path']); // Read JPEG Image
				break;
			case ImageTools::TYPE_PNG:
				$fr = @ImageCreateFromPNG($fr['path']); // Read PNG
				break;
			case ImageTools::TYPE_GIF:
				$fr = @ImageCreateFromGIF($fr['path']); // Read GIF
				break;

		}
		$framed = imagecreatetruecolor($fw, $fh);
		imagecopy($framed, $im, ($fw - $source['width']) / 2, ($fh - $source['height']) / 2, 0, 0, $source['width'], $source['height']);
		imagecopy($framed, $fr, 0, 0, 0, 0, $fw, $fh);

		switch ($destination['type']) {
			case self::TYPE_JPEG:
				$result = @imagejpeg($framed, $destination['path'], $quality);
				break;
			case self::TYPE_PNG:
				$result = @imagepng($framed, $destination['path'], ceil($quality*9 / 100));
				break;
			case self::TYPE_GIF:
				$result = @imagegif($framed, $destination['path'], $quality);
				break;
		}

		if ($result === true) {
			$destination['result'] = self::RESULT_SUCCESS;
			$x = @getimagesize($destination['path']);
			// image width
			$sw = $x[0];
			// image height
			$sh = $x[1];

			$destination['width'] = $sw;
			$destination['height'] = $sh;
			if (isset($temp_file)) {
				unlink($temp_file);
			}
			return $destination;
		}
		return array('result' => self::RESULT_ERROR, 'error' => 'something went wrong.');
	}

	public static function getRotation($filePath) {
		$exif = exif_read_data($filePath);
		$orientation = (int)@$exif['Orientation'];
		if ($orientation < 2 || $orientation > 8) {
			return false;
		}
		/*
		switch ($orientation) {
			case 2:
				$new_img = $this->gd_imageflip(
					$src_img,
					defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
				);
				break;
			case 3:
				$new_img = imagerotate($src_img, 180, 0);
				break;
			case 4:
				$new_img = $this->gd_imageflip(
					$src_img,
					defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
				);
				break;
			case 5:
				$tmp_img = $this->gd_imageflip(
					$src_img,
					defined('IMG_FLIP_HORIZONTAL') ? IMG_FLIP_HORIZONTAL : 1
				);
				$new_img = imagerotate($tmp_img, 270, 0);
				imagedestroy($tmp_img);
				break;
			case 6:

				$new_img = imagerotate($src_img, 270, 0);
				break;
			case 7:
				$tmp_img = $this->gd_imageflip(
					$src_img,
					defined('IMG_FLIP_VERTICAL') ? IMG_FLIP_VERTICAL : 2
				);
				$new_img = imagerotate($tmp_img, 270, 0);
				imagedestroy($tmp_img);
				break;
			case 8:
				$new_img = imagerotate($src_img, 90, 0);
				break;
			default:
				return false;
		}
		*/
	}


	/********************************************************************
	 * 					private helper methods							*
	 ********************************************************************/


	/**
	 * Calculates all data for the target file, based on the destination and the source file.
	 * @param array $source The source file data. should be generated by the _analyzeSource method first or in the same output format.
	 * @param $destination $destination for the file. could be either a directory or a specific path
	 * @return array with 'path', 'extension' & 'type'. what they contain is trivial.
	 */
	private static function _calculateTarget($source, $destination, $overwrite = true) {
		// first let's check what this destination parameter really is
		if (is_array($source) && $source['path']!== $destination && file_exists($destination)) {
			// it exists. let's hope it's a directory then. or if it's the same as the source, we don't care.
			if (is_dir($destination) && is_writable($destination)) {
				if (!substr($destination, -1) === DIRECTORY_SEPARATOR) {
					$destination .= DIRECTORY_SEPARATOR;
				}

				// create a totally random filename!
				$targetName = sha1(uniqid("", true) . mt_rand(100000000, 999999999)) . $source['extension'];
				while (file_exists($destination . $targetName)) {
					$targetName = sha1(uniqid("", true) . mt_rand(100000000, 999999999)) . $source['extension'];
				}
				$destination .= $targetName;
				$extension = $source['extension'];
			} else if (!$overwrite) {
				return array('result' => self::RESULT_ERROR, 'error' => 'file exists or, if destination is a directory, directory is not writable');
			} else {
				$dot = strrpos($destination, ".");
				if ($dot !== false) {
					$extension = strtolower(substr($destination, $dot));
				} else {
					// ok dude, i'm out of ideas. fuck this.
					return array('result' => self::RESULT_ERROR, 'error' => 'file exists or, if destination is a directory, directory is not writable');
				}
			}
		} else if (!is_dir(dirname($destination)) || !is_writable(dirname($destination))) {
			return array('result' => self::RESULT_ERROR, 'error' => 'destination did not exist but the path was invalid or not writable');
		} else {
			$dot = strrpos($destination, ".");
			if ($dot !== false) {
				$extension = strtolower(substr($destination, $dot));
			} else {
				// ok dude, i'm out of ideas. fuck this.
				return array('result' => self::RESULT_ERROR, 'error' => 'file exists or, if destination is a directory, directory is not writable');
			}
		}



		$result = array ('path' => $destination, 'extension' => $extension, 'result' => self::RESULT_SUCCESS, 'name' => basename($destination));
		if (isset(self::$_mimetypemapping[$extension])) {
			$result['type'] = self::$_mimetypemapping[$extension];
		}

		return $result;
	}

	/**
	 * Analyzes the source file and brings some uniformity to the party
	 * @param $data the source file data. can be either an array straight from a file upload, or just a path.
	 * @return array array containing the path, filename, extension, type
	 */
	private static function _analyzeFile($data) {
		$analysis = array();
		if (is_array($data)) {
			if (isset($data['type']) || isset($data['tmp_name'])) {
				// we rely mostly on mime types or file analysis
				// fetch it from the mime type.
				if (!isset($data['type'])) {
					$fileInfo = new \finfo(FILEINFO_MIME);
					$data['type'] = $fileInfo->file($data['tmp_name']);
					$data['type'] = preg_replace('/;? .*/', '', $data['type']);
				}

				if (isset($data['tmp_name'])) {
					$analysis['path'] = $data['tmp_name'];
				}
				$analysis['type'] = $data['type'];
				if (isset(self::$_extensionmapping[$analysis['type']])) {
					$analysis['extension'] = self::$_extensionmapping[$analysis['type']];
				}
			} elseif (isset($data['extension'])) {
				// if that isn't available, go for the extension.

				// sanitize extension
				$analysis['extension'] = strtolower($analysis['extension']);
				if ($analysis['extension'] === '.jpeg') {
					$analysis['extension'] = '.jpg';

				}

				if (isset(self::$_mimetypemapping[$data['extension']])) {
					$analysis['type'] = self::$_mimetypemapping[$data['extension']];
				}
			}
			if (isset($data['name'])) {
				if (empty($analysis)) {
					// fallback. we have nothing except a name.
					$data = $data['name'];
				} else {
					$analysis['name'] = $data['name'];
				}
			}
		}
		// $data is a filename or
		if (is_string($data)) {
			$dot = strrpos($data, ".");
			if ($dot !== false) {
				$analysis['extension'] = strtolower(substr($data, $dot));
				$analysis['type'] = self::$_mimetypemapping[$analysis['extension']];
				$analysis['name'] = $data;
				if (is_file($data)) {
					$analysis['path'] = $data;
				}
			}
		}

		return $analysis;
	}

	/**
	 * Wrapper for imageflip in case php < 5.5
	 * Thanks Sebastian Tschan
	 * @copyright https://blueimp.net
	 */
	private static function _imageflip($image, $mode)
	{
		if (function_exists('imageflip')) {
			return imageflip($image, $mode);
		}
		$new_width = $src_width = imagesx($image);
		$new_height = $src_height = imagesy($image);
		$new_img = imagecreatetruecolor($new_width, $new_height);
		$src_x = 0;
		$src_y = 0;
		switch ($mode) {
			case '1': // flip on the horizontal axis
				$src_y = $new_height - 1;
				$src_height = -$new_height;
				break;
			case '2': // flip on the vertical axis
				$src_x = $new_width - 1;
				$src_width = -$new_width;
				break;
			case '3': // flip on both axes
				$src_y = $new_height - 1;
				$src_height = -$new_height;
				$src_x = $new_width - 1;
				$src_width = -$new_width;
				break;
			default:
				return $image;
		}
		imagecopyresampled(
			$new_img,
			$image,
			0,
			0,
			$src_x,
			$src_y,
			$new_width,
			$new_height,
			$src_width,
			$src_height
		);
		return $new_img;
	}
}
