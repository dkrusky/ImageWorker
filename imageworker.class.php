<?php
/*
   Class to manipulate images of 'png' or 'jpg' while retaining transparency.

   Requires:
	PHP:	gd, exif
	CLI:	optipng, jpegoptim

   Methods:

	compress ( <file> )

	info ( <file> )
		returns object
			width	The width of the image
			height	The height of the image
			ratio	Aspect ratio of the width and height (int)
			type	Returns either 'png' or 'jpg'
			larger	Returns which dimension is larger. ( 'width', 'height',  '' )
			file	The filename without the path or extension
			path	The path where the file is stored

	crop ( <file>, <width>, <height>, <clip> [default: false] )
		returns <file>  { full location on disk of the new file }

	scale ( <file>, <max>, <clip> )
                returns <file>  { full location on disk of the new file }

	scaleWidth ( <file>, <maxWidth>, <clip> )
                returns <file>  { full location on disk of the new file }

	scaleHeight ( <file>, <maxHeight>, <clip> )
                returns <file>  { full location on disk of the new file }

*/

class ImageWorker {

	public static $extensions = Array('png', 'jpg', 'jpeg');

	// Compress the image using optipng or jpegtran depending on image to compress
	public static function compress($file) {
		$info = self::info($file);
		switch($info->type) {
			case 'png':
				exec(
					sprintf('optipng -o2 -strip all %s 2>&1',
							$file
						   ),
					$output,
					$returnlevel
				);
				break;
			case 'jpg':
				$exif = exif_read_data($file);
				if(!empty($exif['Orientation'])) {
					$image = imagecreatefromjpeg($file);
					switch($exif['Orientation']) {
						case 8:
							$image = imagerotate($image,90,0);
							break;
						case 3:
							$image = imagerotate($image,180,0);
							break;
						case 6:
							$image = imagerotate($image,-90,0);
							break;
					}
					imagejpeg($image, $file);
					imagedestroy($image);
				}

				exec(
					sprintf('jpegoptim --strip-all %s 2>&1',
							$file
						   ),
					$output,
					$returnlevel
				);
				break;
			default:
				return;
		}

	}

	// Obtain information about the image
	public static function info($file) {
		$ratio = 1;
		$largerside = '';
		$width = 0;
		$height = 0;
		$type = 0;
		$etype = exif_imagetype($file);
		$info = pathinfo($file);
		switch($etype) {
			case IMAGETYPE_JPEG:
				$type = 'jpg';
				break;
			case IMAGETYPE_PNG:
				$type = 'png';
				break;
			default:
				return false;
		}
 		list($width, $height) = getimagesize($file);
		if($width > $height) {
			$ratio = ($width / $height);
			$largerside = 'width';
		} elseif( $height > $width) {
			$ration = ($height / $width);
			$largerside = 'height';
		}
		$result = (object)Array(
			'width'		=>	$width,
			'height'	=>	$height,
			'ratio'		=>	$ratio,
			'type'		=>	$type,
			'larger'	=>	$largerside,
			'file'		=>	$info['filename'],
			'path'		=>	$info['dirname']
		);

		return $result;
	}

	// Scale and crop image to the $width and $height values retaining as much of the original as possible
 	public static function crop($file, $width = 400, $height = 300, $clip = false) {

		$info = self::info($file);
		if(in_array($info->type, self::$extensions)) {
			$image = ($info->type == 'jpg') ? imagecreatefromjpeg($file) : imagecreatefrompng($file);
			$filename = $info->path . DIRECTORY_SEPARATOR . $info->file . '_' . $width . 'x' . $height . '.' . $info->type;

			$oWidth = imagesx($image);
			$oHeight = imagesy($image);

			// Get crop ratio
			$ratio = 1;
			if($width <> $height) { $ratio = ( $width / $height ); }

			if ( $info->ratio >= $ratio ) {
				// If image is wider than thumbnail (in aspect ratio sense)
				$new_height = $height;
				$new_width = $oWidth / ($oHeight / $height);
			} else {
				// If the thumbnail is wider than the image
				$new_width = $width;
				$new_height = $oHeight / ($oWidth / $width);
			}

			// scale dimensions to do a perfect center clip with no black borders
			if($clip === true) {
				if($new_height < $height) {
					$tmp_scale = ($height / $new_height);
					$new_height = ($new_height * $tmp_scale);
					$new_width = ($new_width * $tmp_scale);
				}
				if($new_width < $width) {
					$tmp_scale = ($width / $new_width);
					$new_height = ($new_height * $tmp_scale);
					$new_width = ($new_width * $tmp_scale);
				}
			}

			$thumb = imagecreatetruecolor( $width, $height );

			switch($info->type) {
				case 'png':
					$background = imagecolorallocate($image, 0, 0, 0);
					// removing the black from the placeholder
					imagecolortransparent($thumb, $background);
					imagecolortransparent($image, $background);

					// turning off alpha blending (to ensure alpha channel information 
					// is preserved, rather than removed (blending with the rest of the 
					// image in the form of black))
					imagealphablending($thumb, false);
					imagealphablending($image, false);

					// turning on alpha channel information saving (to ensure the full range 
					// of transparency is preserved)
					imagesavealpha($thumb, true);
					imagesavealpha($image, true);

					// Resize and crop
					imagecopyresampled($thumb,
									   $image,
									   0 - ($new_width - $width) / 2, // Center the image horizontally
									   0 - ($new_height - $height) / 2, // Center the image vertically
									   0, 0,
									   $new_width, $new_height,
									   $oWidth, $oHeight);

					imagepng($thumb, $filename, 8);
					break;
				case 'jpg':
					// Resize and crop
					imagecopyresampled($thumb,
									   $image,
									   0 - ($new_width - $width) / 2, // Center the image horizontally
									   0 - ($new_height - $height) / 2, // Center the image vertically
									   0, 0,
									   $new_width, $new_height,
									   $oWidth, $oHeight);

					imagejpeg($thumb, $filename, 80);
					break;
			}

			imagedestroy($image);
			imagedestroy($thumb);

			return $filename;
		} else {
			return false;
		}

	}

	public static function orient($image, $exif) {
		if(!empty($exif['Orientation'])) {
			switch($exif['Orientation']) {
				case 8:
					$image = imagerotate($image,90,0);
					break;
				case 3:
					$image = imagerotate($image,180,0);
					break;
				case 6:
					$image = imagerotate($image,-90,0);
					break;
			}
		}
		return $image;
	}

	// Automatically scale based on largest side to $max
	public static function scale($file, $max, $clip = false) {
		$info = self::info($file);
		if($info->width > $info->height) {
			return self::scaleWidth($file, $max, $clip);
		} elseif ( $info->width < $info->height ) {
			return self::scaleHeight($file, $max, $clip);
		}
	}

	// Scale image based on width to $maxWidth
	public static function scaleWidth($file, $maxWidth, $clip = false) {
		$info = self::info($file);

		if($info->width < $maxWidth || $info->width > $maxWidth) {
			$ratio = ($maxWidth / $info->width);
		}

		$info->width = ($info->width * $ratio);
		$info->height = ($info->height * $ratio);

		return self::crop($file, $info->width, $info->height, $clip);
	}

	// Scale image based on height to $maxHeight
	public static function scaleHeight($file, $maxHeight, $clip = false) {
		$info = self::info($file);

		if($info->height < $maxHeight || $info->height > $maxHeight) {
			$ratio = ($maxHeight / $info->height);
		}

		$info->width = ($info->width * $ratio);
		$info->height = ($info->height * $ratio);

		return self::crop($file, $info->width, $info->height, $clip);
	}

}