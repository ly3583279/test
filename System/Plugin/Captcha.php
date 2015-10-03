<?php
namespace System\Plugin;

use System\Core\Request;

/**
 * Class Captcha
 * @package System\Plugin
 */
class Captcha
{

    /**
     * load
     * @param int $width
     * @param int $height
     * @param string $imageType
     */
    public function load($width = 80, $height = 24, $imageType = 'gif')
    {
        if (isset($_GET['w'])) {
            $width = Request::get_int($_GET['w']);
        }
        if (isset($_GET['h'])) {
            $height = Request::get_int($_GET['h']);
        }
        if (isset($_GET['t'])) {
            if ($_GET['t'] == 'gif' || $_GET['t'] == 'png' || $_GET['t'] == 'jpeg') {
                $imageType = $_GET['t'];
            } else {
                $imageType = 'gif';
            }
        }

        $this->createCaptchaImage($width, $height, $imageType);
    }

    /**
     * createCaptchaImage
     * @param int $width
     * @param int $height
     * @param string $imageType
     */
    public function createCaptchaImage($width = 80, $height = 24, $imageType = 'gif')
    {
        //* Set the content-type
        header("Content-type: image/" . $imageType);
        header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        //*/

        //$w = $width; $h = $height;
        $image = imagecreate($width, $height);
        // imageantialias($image, true);

        $white = imagecolorallocate($image, 255, 255, 255);
        // $black = imagecolorallocate($im, 0, 0, 0);
        // $gray  = imagecolorallocate($image, 192, 192, 192);
        // $red   = imagecolorallocate($im, 255, 000, 132);

        $backgroundColor = $white; // imagecolorallocate($im, 255, 255, 255);
        $textColor = $white;
        imagefill($image, 0, 0, $backgroundColor);

        $count = $width > 200 ? 200 : $width;
        for ($i = 0; $i < $count; $i++) {
            $textColor = $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $color);
        }

        $count = round($width / 10);
        for ($i = 0; $i < $count; $i++) {
            $textColor = $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $color);
        }

        // $strArr = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $strArr = "23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz"; // 剔除掉易混淆的字符
        $captchaText = '';
        $strLen = strlen($strArr) - 1;
        for ($i = 0; $i < 4; $i++) {
            $captchaText .= $strArr[rand(0, $strLen)];
        }

        // 设置cookie
        Request::setCookie("captcha", Request::getCaptchaHash($captchaText), 0);

        // 输出图像
        $font = SYS_DIR . '/Library/Fonts/incite.ttf';//echo $font;exit;
        if (file_exists($font)) {
            $size = round($width / 5);
            $x = rand(5, $size - 5);
            $y = round($height * 0.8);
            imagettftext($image, $size, rand(-5, 5), $x, $y, $textColor, $font, $captchaText);
        } else {
            imagestring($image, 5, 12, 3, $captchaText, $textColor);
        }

        if ($imageType == 'png') {
            imagepng($image);
        } elseif ($imageType == 'jpeg') {
            imagejpeg($image, null, 90);
        } else { // gif
            imagegif($image);
        }

        imagedestroy($image);
    }
}