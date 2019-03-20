<?php
class Crop {
	function templatesUpload($metadata, $minWidthHeight = array(), $destination = 'import') {
		
		// echo "<pre>";print_r($_REQUEST);//exit;
		if (isset ( $_REQUEST ['is_automatic'] ) && $_REQUEST ['is_automatic'] == true) {
			$tmpfile = array ();
			$tmpfile ['filename'] = public_path () . "/" . $destination . "/" . basename ( $_REQUEST ['filename'] );
			$tmpfile ['url'] = $_REQUEST ['filename'];
			$tmpfile ['original_filename'] = isset ( $_REQUEST ['orginal_filename'] ) ? $_REQUEST ['orginal_filename'] : $_REQUEST ['filename'];
			$tmpfile ['error'] = 'success';
			// echo "<pre>";print_r($tmpfile);exit;
		} else {
			if (isset ( $_FILES ['filename'] ['tmp_name'] )) {
				
				$upload = UploadType::Factory ( 'Regular' );
				$files = $_FILES ['filename'];
			} else {
				if (! empty ( $_SERVER ['HTTP_X_FILE_NAME'] )) {
					$files = $_SERVER ['HTTP_X_FILE_NAME'];
				} else {
					$files = $_REQUEST ['filename'];
				}
				$upload = UploadType::Factory ( 'Stream' );
			}
			if (isset ( $metadata ['size'] ))
				$upload->setSize ( $metadata ['size'] );
			if (isset ( $metadata ['type'] ))
				$upload->setAllowedTypes ( $metadata ['type'] );
			$upload->setContainer ( public_path () . '/' . $destination . "/" );
			if (! empty ( $minWidthHeight )) {
				// $upload->setMinWidthHeight($minWidthHeight['width'],$minWidthHeight['height']);
			}
			$tmpfile = $upload->save ( $files );
		}
		
		$uploadResponse = array ();
		// return $tmpfile;
		if (isset ( $tmpfile ['error'] ) && $tmpfile ['error'] == 'success') {
			$source = basename ( $tmpfile ['filename'] );
			$uploadResponse ['success'] = 'true';
			$uploadResponse ['filename'] = $tmpfile ['filename'];
			$uploadResponse ['original_filename'] = $tmpfile ['original_filename'];
			$pathinfo = pathinfo ( $tmpfile ['filename'] );
			
			// if($pathinfo['extension']=='tiff')
			if (strtolower ( $pathinfo ['extension'] ) == 'tif' || strtolower ( $pathinfo ['extension'] ) == 'tiff') {
				$extension = "jpg";
				$attachobject = new Attachments ();
				$attachobject->imTiffToJpg ( $tmpfile ['filename'] );
			} else {
				$extension = $pathinfo ['extension'];
			}
			$lessQualityImage = $pathinfo ['filename'] . "_less" . "." . $extension;
			$less_quality_image = $pathinfo ['dirname'] . "/" . $lessQualityImage;
			
			$cmd = $this->imageMagickConversion ( $uploadResponse ['filename'], $less_quality_image, $metadata ['window_width'], $metadata ['window_height'] );
			
			// $uploadResponse['src']=URL::to("public/".$destination."/".$source);
			
			$uploadResponse ['newimage'] = 'true';
			if (file_exists ( $less_quality_image )) {
				$less_image_info = getimagesize ( $less_quality_image );
			} else {
				$uploadResponse ['success'] = 'false';
				$uploadResponse ['error'] = "Oops! Something went wrong. Please can you upload image again";
				return $uploadResponse;
			}
			// $cropWidth=round(($less_image_info[0]/$minWidthHeight['width'])*$minWidthHeight['crop_width']);
			// $cropHeight=round(($less_image_info[1]/$minWidthHeight['height'])*$minWidthHeight['crop_height']);
			
			$original_image_info = getimagesize ( $tmpfile ['filename'] );
			if ($minWidthHeight ['width'] >= $original_image_info [0] && $minWidthHeight ['height'] >= $original_image_info [1]) {
				$minWidthHeight ['width'] = $less_image_info [0];
				$minWidthHeight ['height'] = $less_image_info [1];
			}
			
			$aspectRatio = round ( $minWidthHeight ['width'] / $minWidthHeight ['height'], 2 );
			
			$uploadResponse ['less_quality_image_properties'] = array (
					'width' => $less_image_info [0],
					'height' => $less_image_info [1],
					'crop_width' => $minWidthHeight ['width'],
					'crop_height' => $minWidthHeight ['height'],
					'src' => URL::to ( "public/" . $destination . "/" . $lessQualityImage ),
					'aspect_ratio' => $aspectRatio,
					'path' => $less_quality_image 
			);
		} else {
			
			$uploadResponse ['success'] = 'false';
			$uploadResponse ['error'] = $tmpfile ['error'];
		}
		return $uploadResponse;
	}
	function aspectRatio($post) {
		$cropParams = array ();
		$x_coordinate = $post ['x_img'];
		$y_coordinate = $post ['y_img'];
		
		$crop_width = $post ['w_img'];
		$crop_height = $post ['h_img'];
		
		$sourceWidth = $post ['img_width'];
		$sourceHeight = $post ['img_height'];
		
		$file = $post ['name'];
		$size = getimagesize ( $file );
		
		$targetWidth = $size [0];
		$targetHeight = $size [1];
		
		$sourceRatio = @($sourceWidth / $sourceHeight);
		$targetRatio = @($targetWidth / $targetHeight);
		
		if ($sourceRatio < $targetRatio) {
			$scale = $sourceWidth / $targetWidth;
		} else {
			$scale = $sourceHeight / $targetHeight;
		}
		
		$cropParams ['resizeWidth'] = ( int ) ($crop_width / $scale);
		$cropParams ['resizeHeight'] = ( int ) ($crop_height / $scale);
		
		$cropParams ['cropLeft'] = ( int ) ($x_coordinate / $scale);
		$cropParams ['cropTop'] = ( int ) ($y_coordinate / $scale);
		return $cropParams;
	}
	function imageMagickConversion($source, $destination, $window_width = 0, $window_height = 0) {
		$info = getimagesize ( $source );
		
		if (! empty ( $info )) {
			$width = $info [0];
			$height = $info [1];
		} else {
			$width = 0;
			$height = 0;
		}
		
		$resize = '';
		$image_margin_width_pixel = 30;
		$image_margin_height_pixel = 120;
		
		$image_margin_width_pixel = 0;
		$image_margin_height_pixel = 0;
		
		/*
		 * if($width>=$window_width && $height>=$window_height)
		 * {
		 * if($window_width>$window_height)
		 * { // echo 1;
		 * // echo $width."/".$height;
		 * if($width>$height){
		 *
		 *
		 * $resize=" -resize ".($window_width-$image_margin_width_pixel)."x ";
		 * }
		 * else{
		 *
		 * $resize=" -resize x".($window_height-$image_margin_height_pixel);
		 * }
		 * }else{
		 *
		 * if($width<$height)
		 * $resize=" -resize x".($window_height-$image_margin_height_pixel);
		 * else
		 * $resize=" -resize ".($window_width-$image_margin_width_pixel)."x ";
		 *
		 * }
		 * }elseif($width<=$window_width && $height>=$window_height){
		 *
		 *
		 * $resize=" -resize x".($window_height-$image_margin_height_pixel);
		 *
		 * }elseif($width>=$window_width && $height<=$window_height){
		 * $resize=" -resize ".($window_width-$image_margin_width_pixel)."x ";
		 * // $resize=" -resize x".($window_height-30);
		 *
		 * }
		 * else if($width<=$window_width && $height<=$window_height)
		 * {
		 *
		 * $resize='';
		 * }
		 */
		if (($window_width / $window_height) < ($width / $height)) {
			$resize = " -resize x" . $window_height;
		} else {
			
			$resize = " -resize " . $window_width . "x";
		}
		// $resize=" -resize ".$window_width;
		
		putenv ( 'TMPDIR=' . public_path () . "/conversion" );
		putenv ( "PATH=/usr/local/bin:/usr/bin:/bin" );
		
		// Imagic Command For Conversion PDF to Images
		// echo $cmd="convert -limit memory 128 -limit map 256 -quality 80% -density 150 -colorspace RGB ".$resize.$source." ".$destination;
		$cmd = "convert -background white -alpha remove  -strip -limit memory 128 -limit map 256  -quality 50% -density 300x300 -colorspace RGB " . $resize . $source . " " . $destination . "  2>&1";
		
		$cmd = "convert -quality 90% -colorspace RGB " . $resize . " " . $source . " " . $destination . "  2>&1"; // off
		                                                                                                          // echo "<br>";
		@exec ( $cmd, $output, $retval );
		
		return $output;
	}
	function resolutionImages($original, $source, $type = 'properities_attachments', $resize = true) {
		$resolution = Resolution::DeatailByName ( $type );
		if ($resolution && file_exists ( $source )) {
			$container = $resolution->container;
			$settings = json_decode ( $resolution->settings );
			$delList = array ();
			$urlList = array ();
			$source_parts = pathinfo ( $original );
			$urlList ['original'] = URL::to ( "public/import/" . $source_parts ['filename'] . "." . $source_parts ['extension'] );
			$delList ['original'] = $original;
			
			if (strtolower ( $source_parts ['extension'] ) == 'tif' || strtolower ( $source_parts ['extension'] ) == 'tiff') {
				$source_parts ['extension'] = 'jpg';
			}
			foreach ( $settings as $size => $options ) {
				if ($size == 'large') {
					$new_filename = basename ( $source );
					$urlList [$size] = URL::to ( "public/import/" . $new_filename );
					$delList [$size] = $source;
				} else {
					$new_filename = $source_parts ['filename'] . "_" . $size . "." . $source_parts ['extension'];
					$destination_path = $source_parts ['dirname'] . "/" . $new_filename;
					$delList [$size] = $destination_path;
					$urlList [$size] = URL::to ( "public/import/" . $new_filename );
					if ($resize) {
						
						// Image::make($source)->resize($options->width,$options->height)
						// ->save($destination_path);
						Image::make ( $source )->resize ( $options->width, null, function ($constraint) {
							$constraint->aspectRatio ();
						} )->save ( $destination_path );
					}
				}
			}
			
			return array (
					'path' => $delList,
					'url' => $urlList 
			);
		} else {
			return false;
		}
	}
	function cropImage($cropParams, $source) {
		$pathinfo = pathinfo ( $source );
		if (strtolower ( $pathinfo ['extension'] ) == 'tif' || strtolower ( $pathinfo ['extension'] ) == 'tiff') {
			$extension = "jpg";
		} else {
			$extension = $pathinfo ['extension'];
		}
		
		$destination_filename = $pathinfo ['filename'] . "_large" . "." . $extension;
		$destination = $pathinfo ['dirname'] . "/" . $destination_filename;
		
		$resizeWidth = $cropParams ['resizeWidth'];
		$resizeHeight = $cropParams ['resizeHeight'];
		$cropLeft = $cropParams ['cropLeft'];
		$cropTop = $cropParams ['cropTop'];
		
		if (FALSE) {
			putenv ( 'TMPDIR=' . public_path () . "/conversion" );
			putenv ( "PATH=/usr/local/bin:/usr/bin:/bin" );
			$crop_params = $resizeWidth . "x" . $resizeHeight . "+" . $cropLeft . "x" . $cropTop;
			$cmd = "convert -crop " . $crop_params . " " . $source . " " . $destination;
			exec ( $cmd );
		} else {
			if (strtolower ( $pathinfo ['extension'] ) == 'tif' || strtolower ( $pathinfo ['extension'] ) == 'tiff') {
				putenv ( 'TMPDIR=' . public_path () . "/conversion" );
				putenv ( "PATH=/usr/local/bin:/usr/bin:/bin" );
				$source2 = $pathinfo ['dirname'] . "/" . $pathinfo ['filename'] . "." . $extension;
				$cmd = "convert " . $source . " " . $source2;
				exec ( $cmd );
				$source = $source2;
			}
			$img = Image::make ( $source );
			$img->crop ( $resizeWidth, $resizeHeight, $cropLeft, $cropTop )->save ( $destination );
		}
		
		return $destination;
	}
	function multipleCrop($info) {
		$fileNames = array ();
		$cropImages = array ();
		if (isset ( $info ['filename'] )) {
			foreach ( $info ['filename'] as $l => $m ) {
				if ($m != 'undefined') {
					$cropImages [] = $m;
				}
				// $fileNames[$m]=$m;
			}
			if (isset ( $info ['x'] )) {
				foreach ( $cropImages as $k => $file ) {
					
					$post = array ();
					$post ['name'] = $file;
					$post ['x_img'] = $info ['x'] [$k];
					$post ['y_img'] = $info ['y'] [$k];
					$post ['w_img'] = $info ['width'] [$k];
					$post ['h_img'] = $info ['height'] [$k];
					$post ['img_width'] = $info ['img_width'] [$k];
					$post ['img_height'] = $info ['img_height'] [$k];
					
					$cropParams = $this->aspectRatio ( $post );
					$cropImage = $this->cropImage ( $cropParams, $file );
					$data = $this->resolutionImages ( $file, $cropImage, 'properities_attachments' );
					// echo "<pre>";print_r($data);//exit;
				}
			}
		}
	}
}
