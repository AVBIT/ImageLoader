## ImageLoader
Test composer package ImageLoader. 

Downloading images from a remote host and saving them to the file system.

##### THE BEER-WARE LICENSE:
> This project is licensed under the "THE BEER-WARE LICENSE":
> As long as you retain this notice you can do whatever you want with this stuff.
> If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.


#### Requires
PHP-extensions:
1. CURL
2. EXIF

#### Installation
```
composer require avbit/imageloader dev-master
```

#### Basic Usage
```
$loader = new ImageLoader('/path/to/uploads_directory');
```

```
// Optional.
// Allowed types of uploaded images
// Array of predefined PHP constants: http://php.net/manual/ru/image.constants.php
// Defaults: [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG]
// This can be changed by the method:
$loader->setAllowedImageTypes([IMAGETYPE_GIF, IMAGETYPE_JPEG]); // denied IMAGETYPE_PNG
```

```
// Optional.
// Set default CURL certificate.
// Certificate which is provided by the cURL creator: http://curl.haxx.se/ca/cacert.pem
// This option determines whether curl verifies the authenticity of the peer's certificate.
//
// This can be set globally by adding the following to your php.ini:
// curl.cainfo=/path/to/cacert.pem
//
// This can be changed by the method:
$loader->setCurlCaCertPath('/path/to/cacert.pem');
```
```
$urls = [
    'https://img.atbrovary.org/uploads/2017-10-25/11793orig.jpg',
    'https://img.atbrovary.org/uploads/2017-10-25/ruki_kruki',      // file not exists
    'https://isp.brovis.net.ua/assets/img/day_banner_full.jpg',
    'https://isp.brovis.net.ua/assets/img/isp_logo.png',
	];

$response = $loader->loadImages($urls);
print_r($response);
```
Output:
```
Array
(
    [0] => Array
        (
            [url] => https://img.atbrovary.org/uploads/2017-10-25/11793orig.jpg
            [result] => Array
                (
                    [err_code] => 0
                    [msg] => success
                    [filename] => D:\xampp711\htdocs\ImageLoader\tests/../tests_upload\2017-12-02\23\b026b3b5c18477816a297170fdcdd39b61df526d.jpeg
                    [filesize] => 856002
                )

        )

    [1] => Array
        (
            [url] => https://img.atbrovary.org/uploads/2017-10-25/ruki_kruki
            [result] => Array
                (
                    [err_code] => 4
                    [msg] => AVBIT\Tools\Images\ImageLoader::loadImage CURL FAIL: https://img.atbrovary.org/uploads/2017-10-25/ruki_kruki TIMEOUT=0, CURL_ERRNO=47
                )

        )

    [2] => Array
        (
            [url] => https://isp.brovis.net.ua/assets/img/day_banner_full.jpg
            [result] => Array
                (
                    [err_code] => 0
                    [msg] => success
                    [filename] => D:\xampp711\htdocs\ImageLoader\tests/../tests_upload\2017-12-02\23\56f1a839ce71d310dcf6a7b163d1e7ab9b793f45.jpeg
                    [filesize] => 307221
                )

        )

    [3] => Array
        (
            [url] => https://isp.brovis.net.ua/assets/img/isp_logo.png
            [result] => Array
                (
                    [err_code] => 0
                    [msg] => success
                    [filename] => D:\xampp711\htdocs\ImageLoader\tests/../tests_upload\2017-12-02\23\430db761749d2ab0742ec99d77bee57e693400e0.png
                    [filesize] => 17235
                )

        )

)
```

Best regards...