<?php

namespace AVBIT\Tools\Images;

/**
 *  Class ImageLoader
 *  @package AVBIT\Tools\ImageLoader;
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 02.12.2017. Last modified on 02.12.2017
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

use AVBIT\Tools\Images\ILException;

class ImageLoader
{
	private $uploads_directory;

	const ERR_UNDEFINED  = -1;
	const ERR_UPLOAD_DIR_NOT_FOUND   = 1;
	const ERR_CREATE_UPLOAD_DIR      = 2;
    const ERR_NOT_VALID_URL          = 3;
	const ERR_GET_CONTENTS           = 4;
	const ERR_PUT_CONTENTS           = 5;
	const ERR_NOT_ALLOWED_IMAGE_TYPE = 6;
	const ERR_DETERMINATE_TYPE       = 7;
	const ERR_REPAIR_FILE_EXTENSIONS = 8;
	const ERR_CREATE_RANDOM_FILENAME = 9;
	const ERR_CHMOD                  = 10;

    /**
     * Allowed types of uploaded images
     * Array of predefined PHP constants: http://php.net/manual/ru/image.constants.php
     *
     * This can be changed by the method:
     * $this->setAllowedImageTypes([IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])
     *
	 * @var array
	 */
	private $allowed_imagetypes;

    /**
     * Set default CURL certificate.
     * Certificate which is provided by the cURL creator: http://curl.haxx.se/ca/cacert.pem
     * This option determines whether curl verifies the authenticity of the peer's certificate.
     *
     * This can be set globally by adding the following to your php.ini:
     * curl.cainfo=/path/to/cacert.pem
     *
     * This can be changed by the method:
     * $this->setCurlCaCertPath('/path/to/cacert.pem')
     */
	private $curl_cacert_path;

	/**
	 * ImageLoader constructor.
	 *
	 * @param string    $uploads_directory
	 * @param bool      $create_subdir
	 */
	public function __construct($uploads_directory, $create_subdir = true)
    {
	    $this->uploads_directory = preg_replace("#/$#", "", $uploads_directory);
	    if (!is_dir($this->uploads_directory)) {
		    throw new ILException(__METHOD__ . ' Upload directory not found: '.$this->uploads_directory, self::ERR_UPLOAD_DIR_NOT_FOUND);
	    }

	    if ($create_subdir){
		    $subdir = date('Y-m-d', time()). DIRECTORY_SEPARATOR . date('H', time());
		    $this->uploads_directory = $this->uploads_directory . DIRECTORY_SEPARATOR . $subdir;
	    }

	    if (!file_exists($this->uploads_directory) && !is_dir($this->uploads_directory)) {
	    	if(mkdir($this->uploads_directory, 0755, true)===false) {
	    	    throw new ILException(__METHOD__ . ' Can\'t create directory: '.$this->uploads_directory, self::ERR_CREATE_UPLOAD_DIR);
            }
	    }

        // Set default the allowed types of uploaded images
	    $this->allowed_imagetypes = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG];

        // Set default CURL certificate.
        $this->curl_cacert_path = ini_get('curl.cainfo');
    }


	/**
	 * Setting the allowed types of uploaded images
	 * (array of predefined PHP constants (http://php.net/manual/ru/image.constants.php))
	 *
	 * @param $arr_imagetypes
	 */
	public function setAllowedImageTypes($arr_imagetypes)
	{
		if (!is_array($arr_imagetypes)){
			throw new \InvalidArgumentException(__METHOD__ . ' Only accepts array. Input was: ' . gettype($arr_imagetypes));
		}
		$this->allowed_imagetypes = $arr_imagetypes;
	}

    public function setCurlCaCertPath($path)
    {
        $this->curl_cacert_path = $path;
    }


	/**
     * The main method.
     * Uploading one or a group of images
     *
	 * @param array|string $urls
	 *
	 * @return array|bool
	 */
	public function loadImages($urls=null)
    {
    	$result = false;
    	if (empty($urls)) {
		    throw new \InvalidArgumentException(__METHOD__ . ' Only accepts not empty array or string.');
	    }

	    $arr_urls = [];
	    if (!is_array($urls) && !is_string($urls)){
		    throw new \InvalidArgumentException(__METHOD__ . ' Only accepts array or string. Input was: ' . gettype($urls));
	    } else if (is_array($urls)){
		    $arr_urls = $urls;
	    } else if (is_string($urls)){
		    $arr_urls[] = $urls;
	    }

	    foreach ($arr_urls as $url) {
		    $one = [];
		    $one['url'] = $url;
		    try{
			    $one['result']['err_code'] = self::ERR_UNDEFINED;
			    $one['result']['msg'] = '';
			    $one['result']['filename'] = $this->loadImage($url);
			    $one['result']['filesize'] = filesize($one['result']['filename']);

			    $one['result']['err_code'] = 0;
			    $one['result']['msg'] = 'success';

		    } catch (ILException $e) {
			    // Custom ImageLoader exception
			    $one['result']['err_code'] = $e->getCode();
			    $one['result']['msg'] = $e->getMessage();

		    } catch (\Throwable $t) {
			    // Executed only in PHP 7, will not match in PHP 5.x
			    $one['result']['err_code'] = $t->getCode();
			    $one['result']['msg'] = $t->getMessage();

		    } catch (\Exception $e) {
			    // Executed only in PHP 5.x, will not be reached in PHP 7
			    $one['result']['err_code'] = $e->getCode();
			    $one['result']['msg'] = $e->getMessage();
		    }

		    $result[]=$one;
	    }
	    return $result;
    }


	/**
	 * Uploading one image
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private function loadImage($url)
    {
	    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
		    throw new ILException(__METHOD__ . " $url is not a valid URL", self::ERR_NOT_VALID_URL);
	    }

        // Нeaders and options appear to be a firefox browser referred by google
        $header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: "; // browsers usually leave blank

        $curl = curl_init();
        $timeout = 0;

        // Set the curl options - see http://php.net/manual/en/function.curl-setopt.php
        curl_setopt($curl, CURLOPT_URL,            $url  );
        curl_setopt($curl, CURLOPT_USERAGENT,      'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6'  );
        curl_setopt($curl, CURLOPT_HTTPHEADER,     $header  );
        curl_setopt($curl, CURLOPT_REFERER,        'http://www.google.com'  );
        curl_setopt($curl, CURLOPT_ENCODING,       'gzip,deflate'  );
        curl_setopt($curl, CURLOPT_AUTOREFERER,    true  );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true  );
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true  );
        curl_setopt($curl, CURLOPT_TIMEOUT,        $timeout  );
        if (!empty($this->curl_cacert_path)){
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_CAINFO, $this->curl_cacert_path);
        } else {
            curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        }

        // Run the curl request and get the results
        $file_content = curl_exec($curl);
        if ($file_content === false) {
            $err = curl_errno($curl);
            //$inf = curl_getinfo($curl);  var_dump($inf);
            curl_close($curl);
            throw new ILException(__METHOD__ . " CURL FAIL: $url TIMEOUT=$timeout, CURL_ERRNO=$err", self::ERR_GET_CONTENTS);
        }
        curl_close($curl);

        $filename = $this->getRandomFileName();
        if (file_put_contents($filename, $file_content,LOCK_EX )===false) {
            @unlink($filename);
            throw new ILException(__METHOD__ . ' Can\'t save contents from url: '.$url, self::ERR_PUT_CONTENTS);
        }

        // Validate image by type
        $imagetype = exif_imagetype($filename);
        if ($imagetype === false) {
            @unlink($filename);
            throw new ILException(__METHOD__ . ' It is not possible to determine the type of image. WTF? Is this an image?', self::ERR_DETERMINATE_TYPE);
        }
        $is_allowed = false;
        foreach ($this->allowed_imagetypes as $type) {
            if ($imagetype === $type){
                $is_allowed = true;
                break;
            }
        }
        if (!$is_allowed) {
            @unlink($filename);
            throw new ILException(__METHOD__ . ' Not allowed image type.', self::ERR_NOT_ALLOWED_IMAGE_TYPE);
        }

        // Repair file extension (change the file extension according to its type)
        $newname = $this->uploads_directory . DIRECTORY_SEPARATOR . basename($filename) . image_type_to_extension($imagetype, true);
        if (rename($filename, $newname)===false){
            @unlink($filename);
            throw new ILException(__METHOD__ . ' It is not possible to change the file extension according to its type.', self::ERR_REPAIR_FILE_EXTENSIONS);
        }

		return $newname;
	}


	/**
	 * Creating a file with a random name for future upload
	 *
	 * @return string (full path to the file)
	 */
	private function getRandomFileName()
    {
	    foreach (range(0, 10) as $number) {
		    $hash = hash('sha1', $number . mt_rand(1,1000000));
		    $random_filename = $this->uploads_directory . DIRECTORY_SEPARATOR . $hash;
		    if (!file_exists($random_filename)){
			    if (file_put_contents($random_filename, '', LOCK_EX )!==false) {
                    if (stristr(PHP_OS, 'WIN')===false){
                        if (chmod($random_filename, 0644)===false) {
                            @unlink($random_filename);
                            throw new ILException(__METHOD__ . ' Can\'t changing the mode of access to the file!', self::ERR_CHMOD);
                        }
                    }
				    return $random_filename;
			    }
		    }
	    }
	    throw new ILException(__METHOD__ . ' Can\'t create a file with a random name', self::ERR_CREATE_RANDOM_FILENAME);
	}

}