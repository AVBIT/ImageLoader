<?php
/**
 *  Class ImageLoaderTest
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 02.12.2017. Last modified on 02.12.2017
 * ----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use AVBIT\Tools\Images\ImageLoader;


class ImageLoaderTest extends TestCase
{
    private $uploads_directory = __DIR__ . '/../tests_upload';

    /**
     * Test ImageLoader argument as string
     */
    public function testloadImagesStringArg()
    {
        $my = new ImageLoader($this->uploads_directory);
        $my->setCurlCaCertPath('cacert.pem'); // curl verifies the authenticity of the peer's certificate.

        $url = 'https://img.atbrovary.org/uploads/2017-10-25/11793orig.jpg'; // known successful url
        $response = $my->loadImages($url);
        //print_r($response);

        $this->assertTrue(true, $response);
        $this->assertInternalType('array', $response);

        $this->assertArrayHasKey('url', $response[0]);
        $this->assertEquals($url, $response[0]['url']);

        $this->assertArrayHasKey('result', $response[0]);
        $this->assertArrayHasKey('err_code', $response[0]['result']);
        $this->assertArrayHasKey('msg', $response[0]['result']);
        if ($response[0]['result']['err_code'] == 0) {
            // success
            $this->assertArrayHasKey('filename', $response[0]['result']);
            $this->assertArrayHasKey('filesize', $response[0]['result']);
        } else {
            $this->assertArrayNotHasKey('filename', $response[0]['result']);
            $this->assertArrayNotHasKey('filesize', $response[0]['result']);
        }
    }

    /**
     * Test ImageLoader argument as array
     */
    public function testloadImagesArrayArg()
    {
        $my = new ImageLoader($this->uploads_directory);
        $my->setCurlCaCertPath('cacert.pem');
        $urls = [
            'https://img.atbrovary.org/uploads/2017-10-25/11793orig.jpg',   // file exists
            'https://img.atbrovary.org/uploads/2017-10-25/ruki_kruki',      // file not exists
            'https://isp.brovis.net.ua/assets/img/day_banner_full.jpg',
            'https://isp.brovis.net.ua/assets/img/isp_logo.png',
        ];

        $response = $my->loadImages($urls);
        //print_r($response); exit;

        foreach ($response as $key => $value) {
            $this->assertTrue(true, $value);
            $this->assertInternalType('array', $value);

            $this->assertArrayHasKey('url', $value);
            $this->assertEquals($urls[$key], $value['url']);

            $this->assertArrayHasKey('result', $value);
            $this->assertArrayHasKey('err_code', $value['result']);
            $this->assertArrayHasKey('msg', $value['result']);
            if ($value['result']['err_code'] == 0) {
                // success
                $this->assertArrayHasKey('filename', $value['result']);
                $this->assertArrayHasKey('filesize', $value['result']);
            } else {
                $this->assertArrayNotHasKey('filename', $value['result']);
                $this->assertArrayNotHasKey('filesize', $value['result']);
            }
        }

    }

    public function testloadImagesBadArgType()
    {
        $this->expectException(InvalidArgumentException::class);

        $my = new ImageLoader($this->uploads_directory);
        $my->setCurlCaCertPath('cacert.pem');

        $this->assertFalse(false, $my->loadImages(''));
        $this->assertFalse(false, $my->loadImages([]));
        $this->assertFalse(false, $my->loadImages());
        $this->assertFalse(false, $my->loadImages(null));
        $this->assertFalse(false, $my->loadImages(true));
        $this->assertFalse(false, $my->loadImages(8));
        $this->assertFalse(false, $my->loadImages(8.88));
    }


    public function testloadImagesErrCode()
    {
        $my = new ImageLoader($this->uploads_directory);

        $my->setAllowedImageTypes([IMAGETYPE_GIF, IMAGETYPE_JPEG]); // denied IMAGETYPE_PNG, for test only!
        $my->setCurlCaCertPath('cacert.pem');

        $urls = [
            0 => 'https://img.atbrovary.org/uploads/2017-10-25/11793orig.jpg',   // file exists
            1 => 'http://dummy_net',                                             // not valid URL
            2 => 'https://img.atbrovary.org/uploads/2017-10-25/ruki_kruki',      // file not exists ImageLoader::ERR_GET_CONTENTS (CURL_ERRNO=47 (CURLE_TOO_MANY_REDIRECTS => 47))
            3 => 'http://www.pdf995.com/samples/pdf.pdf',                        // wrong image type (can't determine the type of image)
            4 => 'https://atbrovary.org/assets/img/atbrovary_logo.png',          // denied image type PNG, for test only!
            5 => 'http://img.atbrovary.org/uploads/2017-10-25/11791orig.jpg',     // file exists
            6 => 'http://www.fileformat.info/format/tiff/sample/3794038f08df403bb446a97f897c578d/download', // denied image type TIFF
        ];

        $response = $my->loadImages($urls);
        print_r($response);

        foreach ($response as $key => $value) {
            $this->assertTrue(true, $value);
            $this->assertInternalType('array', $value);

            $this->assertArrayHasKey('url', $value);
            $this->assertEquals($urls[$key], $value['url']);

            $this->assertArrayHasKey('result', $value);
            $this->assertArrayHasKey('err_code', $value['result']);
            $this->assertArrayHasKey('msg', $value['result']);
            if ($value['result']['err_code'] == 0) {
                // success
                $this->assertArrayHasKey('filename', $value['result']);
                $this->assertArrayHasKey('filesize', $value['result']);
            } else {
                $this->assertArrayNotHasKey('filename', $value['result']);
                $this->assertArrayNotHasKey('filesize', $value['result']);
            }

            switch ($key) {
                case 0:
                    $this->assertEquals(0, $value['result']['err_code']);
                    break;
                case 1:
                    $this->assertEquals($my::ERR_NOT_VALID_URL, $value['result']['err_code']);
                    break;
                case 2:
                    $this->assertEquals($my::ERR_GET_CONTENTS, $value['result']['err_code']);
                    break;
                case 3:
                    $this->assertEquals($my::ERR_DETERMINATE_TYPE, $value['result']['err_code']);
                    break;
                case 4:
                    $this->assertEquals($my::ERR_NOT_ALLOWED_IMAGE_TYPE, $value['result']['err_code']);
                    break;
                case 5:
                    $this->assertEquals(0, $value['result']['err_code']);
                    break;
            }

        }
    }

}