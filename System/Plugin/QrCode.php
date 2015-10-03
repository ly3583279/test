<?php
namespace System\Plugin;

use System\Core\App;
use System\Core\Request;
use System\Util\TextUtils;

define('QRCODE_DATA_PATH', SYS_DIR . '/Library/QrCode/qrcode_data');

/**
 * Class QrCode
 * @package System\Plugin
 */
class QrCode extends BaseQrCode
{
    private $qrCodeModuleSize;
    private $qrCodeQuietZone;

    function __construct()
    {
        parent::__construct();
        $this->qrCodeModuleSize = 4;
        $this->qrCodeQuietZone = 4;
    }

    public function load($data = '')
    {
        $uploadRoot = App::conf('sys.upload_root');
        if (empty($uploadRoot)) {
            $qrUploadRoot = ABS_ROOT . '/Public/Upload/qr';
        } else {
            $qrUploadRoot = $uploadRoot . '/qr';
        }

        $dataHash = Request::get('h');
        if ($dataHash != Request::getDataHash($data)) {
            return null;
        }

        $type = strtolower(Request::get('t'));
        if ($type == 'apk' || $type == 'game') {
            $key = sprintf('%08d', TextUtils::decodeBase64($data));
            $fileName = '/apk/' . substr($key, 0, 3) . '/' . substr($key, 3, 2) . '/' . substr($key, -3) . '.png';
            $data = 'http://m.coolapk.com/dl?qr=' . $data;
        } else {
            $key = md5($data);
            $fileName = '/' . $type . '/' . substr($key, 0, 2) . '/' . $key . '.png';
        }

        $imgUrl = App::conf('sys.image_base_url') . '/qr' . $fileName . '?v5';

        $filePath = $qrUploadRoot . $fileName;
        if (file_exists($filePath) && (time() - filemtime($filePath)) < 864000) {
            // if( file_exists($filePath) ) {
            header('location: ' . $imgUrl);
            return;
        }

        // header("Content-type: image/png"); $filePath = '';

        $this->setQrCodeVersion(4);
        $this->setQrCodeErrorCorrect('M');
        $this->setQrCodeModuleSize(3);

        $this->outputImage($data, 'png', $filePath);

        // exit;

        // 同步QR到图片服务器
        //$uploadDir = $qrUploadRoot . '/';

        //$uploadHelper = new Helper\UploadHelper($uploadRoot);
        //$uploadHelper->syncToUploadRemoteServer($uploadDir, 'coolapk_uploadserver');

        //AppHelper::syncToUploadCenter($imgUrl);

        header('location: ' . $imgUrl);
        // exit;
    }

    function setQrCodeModuleSize($z)
    {
        if ($z > 0 && $z < 9) {
            $this->qrCodeModuleSize = $z;
        }
    }

    function setQrCodeQuietZone($z)
    {
        if ($z > 0 && $z < 9) {
            $this->qrCodeQuietZone = $z;
        }
    }

    function outputImage($data, $imageType = 'png', $filename = '')
    {
        $im = $this->makeImage($this->calQrCode($data));

        if (!empty($filename)) {
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        if ($imageType == "jpeg") {
            if (strlen($filename) > 0) {
                ImageJPEG($im, $filename);
            } else {
                ImageJPEG($im);
            }
        } else {
            if (strlen($filename) > 0) {
                ImagePNG($im, $filename);
            } else {
                ImagePNG($im);
            }
        }
    }

    function makeImage($data)
    {
        $dataArray = explode("\n", $data);
        $c = count($dataArray) - 1;
        $imageSize = $c;
        $outputSize = ($c + ($this->qrCodeQuietZone) * 2) * $this->qrCodeModuleSize;

        $img = ImageCreate($imageSize, $imageSize);
        $white = ImageColorAllocate($img, 255, 255, 255);
        $black = ImageColorAllocate($img, 0, 0, 0);

        $im = ImageCreate($outputSize, $outputSize);

        $white2 = ImageColorAllocate($im, 255, 255, 255);
        ImageFill($im, 0, 0, $white2);

        $y = 0;
        foreach ($dataArray as $row) {
            $x = 0;
            while ($x < $imageSize) {
                if (substr($row, $x, 1) == "1") {
                    ImageSetPixel($img, $x, $y, $black);
                }
                $x++;
            }
            $y++;
        }
        $quietZoneOffset = ($this->qrCodeQuietZone) * ($this->qrCodeModuleSize);
        $imageWidth = $imageSize * ($this->qrCodeModuleSize);

        ImageCopyResized($im, $img, $quietZoneOffset, $quietZoneOffset, 0, 0, $imageWidth, $imageWidth, $imageSize, $imageSize);

        return ($im);
    }
}


/**
 * Class BaseQrCode
 * @package System\Plugin
 */
class BaseQrCode
{
    public $qrCodeErrorCorrect;
    public $qrCodeVersion;
    public $qrCodeStructureAppend_n;
    public $qrCodeStructureAppend_m;
    public $qrCodeStructureAppend_parity;
    public $qrCodeStructureAppend_original;

    function __construct()
    {
        $this->qrCodeErrorCorrect = "M";
        $this->qrCodeVersion = 0;
        $this->qrCodeStructureAppend_n = 0;
        $this->qrCodeStructureAppend_m = 0;
        $this->qrCodeStructureAppend_parity = "";
        $this->qrCodeStructureAppend_original = "";
    }

    function setQrCodeVersion($z)
    {
        if ($z >= 0 && $z <= 40) {
            $this->qrCodeVersion = $z;
        }
    }

    function getQrCodeVersion()
    {
        return ($this->qrCodeVersion);
    }

    function setQrCodeErrorCorrect($z)
    {
        $this->qrCodeErrorCorrect = $z;
    }

    function setStructureAppend($m, $n, $p)
    {
        if ($n > 1 && $n <= 16 && $m > 0 && $m <= 16 && $p >= 0 && $p <= 255) {
            $this->qrCodeStructureAppend_m = $m;
            $this->qrCodeStructureAppend_n = $n;
            $this->qrCodeStructureAppend_parity = $p;
        }
    }

    function calStructureAppendParity($originalData)
    {
        $originalDataLength = strlen($originalData);
        if ($originalDataLength > 1) {
            $structureAppendParity = 0;
            $i = 0;
            while ($i < $originalDataLength) {
                $structureAppendParity = ($structureAppendParity ^ ord(substr($originalData, $i, 1)));
                $i++;
            }
            return ($structureAppendParity);
        }
    }


    function calQrCode($qrCodeDataString)
    {

        $dataLength = strlen($qrCodeDataString);
        if ($dataLength <= 0) {
            trigger_error("Data do not exist.", E_USER_ERROR);
            exit;
        }
        $dataCounter = 0;
        if ($this->qrCodeStructureAppend_n > 1) {

            $dataValue[0] = 3;
            $dataBits[0] = 4;

            $dataValue[1] = $this->qrCodeStructureAppend_m - 1;
            $dataBits[1] = 4;

            $dataValue[2] = $this->qrCodeStructureAppend_n - 1;
            $dataBits[2] = 4;

            $dataValue[3] = $this->qrCodeStructureAppend_parity;
            $dataBits[3] = 8;

            $dataCounter = 4;
        }

        $dataBits[$dataCounter] = 4;

        /*  --- determine encode mode */

        if (preg_match("/[^0-9]/", $qrCodeDataString) != 0) {
            if (preg_match("/[^0-9A-Z \$\*\%\+\.\/\:\-]/", $qrCodeDataString) != 0) {

                /*  --- 8bit byte mode */

                $codewordNumPlus = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                    8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8,
                    8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8);

                $dataValue[$dataCounter] = 4;
                $dataCounter++;
                $dataValue[$dataCounter] = $dataLength;
                $dataBits[$dataCounter] = 8;   /* #version 1-9 */
                $codewordNumCounterValue = $dataCounter;

                $dataCounter++;
                $i = 0;
                while ($i < $dataLength) {
                    $dataValue[$dataCounter] = ord(substr($qrCodeDataString, $i, 1));
                    $dataBits[$dataCounter] = 8;
                    $dataCounter++;
                    $i++;
                }
            } else {

                /* ---- alphanumeric mode */

                $codewordNumPlus = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                    2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2,
                    4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4);

                $dataValue[$dataCounter] = 2;
                $dataCounter++;
                $dataValue[$dataCounter] = $dataLength;
                $dataBits[$dataCounter] = 9;  /* #version 1-9 */
                $codewordNumCounterValue = $dataCounter;

                $alphanumericCharacterHash = array("0" => 0, "1" => 1, "2" => 2, "3" => 3,
                    "4" => 4, "5" => 5, "6" => 6, "7" => 7, "8" => 8, "9" => 9, "A" => 10, "B" => 11, "C" => 12, "D" => 13,
                    "E" => 14, "F" => 15, "G" => 16, "H" => 17, "I" => 18, "J" => 19, "K" => 20, "L" => 21, "M" => 22,
                    "N" => 23, "O" => 24, "P" => 25, "Q" => 26, "R" => 27, "S" => 28, "T" => 29, "U" => 30, "V" => 31,
                    "W" => 32, "X" => 33, "Y" => 34, "Z" => 35, " " => 36, "$" => 37, "%" => 38, "*" => 39,
                    "+" => 40, "-" => 41, "." => 42, "/" => 43, ":" => 44);

                $i = 0;
                $dataCounter++;
                while ($i < $dataLength) {
                    if (($i % 2) == 0) {
                        $dataValue[$dataCounter] = $alphanumericCharacterHash[substr($qrCodeDataString, $i, 1)];
                        $dataBits[$dataCounter] = 6;
                    } else {
                        $dataValue[$dataCounter] = $dataValue[$dataCounter] * 45 + $alphanumericCharacterHash[substr($qrCodeDataString, $i, 1)];
                        $dataBits[$dataCounter] = 11;
                        $dataCounter++;
                    }
                    $i++;
                }
            }
        } else {

            /* ---- numeric mode */

            $codewordNumPlus = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2,
                4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4);

            $dataValue[$dataCounter] = 1;
            $dataCounter++;
            $dataValue[$dataCounter] = $dataLength;
            $dataBits[$dataCounter] = 10;   /* #version 1-9 */
            $codewordNumCounterValue = $dataCounter;

            $i = 0;
            $dataCounter++;
            while ($i < $dataLength) {
                if (($i % 3) == 0) {
                    $dataValue[$dataCounter] = substr($qrCodeDataString, $i, 1);
                    $dataBits[$dataCounter] = 4;
                } else {
                    $dataValue[$dataCounter] = $dataValue[$dataCounter] * 10 + substr($qrCodeDataString, $i, 1);
                    if (($i % 3) == 1) {
                        $dataBits[$dataCounter] = 7;
                    } else {
                        $dataBits[$dataCounter] = 10;
                        $dataCounter++;
                    }
                }
                $i++;
            }
        }

        if (@$dataBits[$dataCounter] > 0) {
            $dataCounter++;
        }
        $i = 0;
        $totalDataBits = 0;
        while ($i < $dataCounter) {
            $totalDataBits += $dataBits[$i];
            $i++;
        }


        $eccCharacterHash = array("L" => "1",
            "l" => "1",
            "M" => "0",
            "m" => "0",
            "Q" => "3",
            "q" => "3",
            "H" => "2",
            "h" => "2");

        $ec = @$eccCharacterHash[$this->qrCodeErrorCorrect];

        if (!$ec) {
            $ec = 0;
        }

        $maxDataBitsArray = array(
            0, 128, 224, 352, 512, 688, 864, 992, 1232, 1456, 1728,
            2032, 2320, 2672, 2920, 3320, 3624, 4056, 4504, 5016, 5352,
            5712, 6256, 6880, 7312, 8000, 8496, 9024, 9544, 10136, 10984,
            11640, 12328, 13048, 13800, 14496, 15312, 15936, 16816, 17728, 18672,

            152, 272, 440, 640, 864, 1088, 1248, 1552, 1856, 2192,
            2592, 2960, 3424, 3688, 4184, 4712, 5176, 5768, 6360, 6888,
            7456, 8048, 8752, 9392, 10208, 10960, 11744, 12248, 13048, 13880,
            14744, 15640, 16568, 17528, 18448, 19472, 20528, 21616, 22496, 23648,

            72, 128, 208, 288, 368, 480, 528, 688, 800, 976,
            1120, 1264, 1440, 1576, 1784, 2024, 2264, 2504, 2728, 3080,
            3248, 3536, 3712, 4112, 4304, 4768, 5024, 5288, 5608, 5960,
            6344, 6760, 7208, 7688, 7888, 8432, 8768, 9136, 9776, 10208,

            104, 176, 272, 384, 496, 608, 704, 880, 1056, 1232,
            1440, 1648, 1952, 2088, 2360, 2600, 2936, 3176, 3560, 3880,
            4096, 4544, 4912, 5312, 5744, 6032, 6464, 6968, 7288, 7880,
            8264, 8920, 9368, 9848, 10288, 10832, 11408, 12016, 12656, 13328
        );

        if (!$this->qrCodeVersion) {
            /* #--- auto version select */
            $i = 1 + 40 * $ec;
            $j = $i + 39;
            $this->qrCodeVersion = 1;
            while ($i <= $j) {
                if (($maxDataBitsArray[$i]) >= $totalDataBits + $codewordNumPlus[$this->qrCodeVersion]) {
                    $maxDataBits = $maxDataBitsArray[$i];
                    break;
                }
                $i++;
                $this->qrCodeVersion++;
            }
        } else {
            $maxDataBits = $maxDataBitsArray[$this->qrCodeVersion + 40 * $ec];
        }

        $totalDataBits += $codewordNumPlus[$this->qrCodeVersion];
        $dataBits[$codewordNumCounterValue] += $codewordNumPlus[$this->qrCodeVersion];

        $maxCodewordsArray = array(0, 26, 44, 70, 100, 134, 172, 196, 242,
            292, 346, 404, 466, 532, 581, 655, 733, 815, 901, 991, 1085, 1156,
            1258, 1364, 1474, 1588, 1706, 1828, 1921, 2051, 2185, 2323, 2465,
            2611, 2761, 2876, 3034, 3196, 3362, 3532, 3706);

        $maxCodewords = $maxCodewordsArray[$this->qrCodeVersion];
        $maxModulesSide1 = 17 + ($this->qrCodeVersion << 2);

        $matrixRemainBit = array(0, 0, 7, 7, 7, 7, 7, 0, 0, 0, 0, 0, 0, 0, 3, 3, 3, 3, 3, 3, 3,
            4, 4, 4, 4, 4, 4, 4, 3, 3, 3, 3, 3, 3, 3, 0, 0, 0, 0, 0, 0);

        /* ---- read version ECC data file */

        $byteNum = $matrixRemainBit[$this->qrCodeVersion] + ($maxCodewords << 3);
        $filename = QRCODE_DATA_PATH . "/qrv" . $this->qrCodeVersion . "_" . $ec . ".dat";
        $fp1 = fopen($filename, "rb");
        $matX = fread($fp1, $byteNum);
        $matY = fread($fp1, $byteNum);
        $masks = fread($fp1, $byteNum);
        $fiX = fread($fp1, 15);
        $fiY = fread($fp1, 15);
        $rsEccCodewords = ord(fread($fp1, 1));
        $rso = fread($fp1, 128);
        fclose($fp1);

        $matrixArrayX = unpack("C*", $matX);
        $matrixArrayY = unpack("C*", $matY);
        $maskArray = unpack("C*", $masks);

        $rsBlockOrder = unpack("C*", $rso);

        $formatInformationX2 = unpack("C*", $fiX);
        $formatInformationY2 = unpack("C*", $fiY);

        $formatInformationX1 = array(0, 1, 2, 3, 4, 5, 7, 8, 8, 8, 8, 8, 8, 8, 8);
        $formatInformationY1 = array(8, 8, 8, 8, 8, 8, 8, 8, 7, 5, 4, 3, 2, 1, 0);

        $maxDataCodewords = ($maxDataBits >> 3);

        $filename = QRCODE_DATA_PATH . "/rsc" . $rsEccCodewords . ".dat";
        $fp0 = fopen($filename, "rb");
        $i = 0;
        while ($i < 256) {
            $rsCalTableArray[$i] = fread($fp0, $rsEccCodewords);
            $i++;
        }
        fclose($fp0);

        /* -- read frame data  -- */

        $filename = QRCODE_DATA_PATH . "/qrvfr" . $this->qrCodeVersion . ".dat";
        $fp0 = fopen($filename, "rb");
        $frameData = fread($fp0, filesize($filename));
        fclose($fp0);

        /*  --- set terminator */

        if ($totalDataBits <= $maxDataBits - 4) {
            $dataValue[$dataCounter] = 0;
            $dataBits[$dataCounter] = 4;
        } else {
            if ($totalDataBits < $maxDataBits) {
                $dataValue[$dataCounter] = 0;
                $dataBits[$dataCounter] = $maxDataBits - $totalDataBits;
            } else {
                if ($totalDataBits > $maxDataBits) {
                    trigger_error("Overflow error", E_USER_ERROR);
                    exit;
                }
            }
        }

        /* ----divide data by 8bit */

        $i = 0;
        $codewordsCounter = 0;
        $codewords[0] = 0;
        $remainingBits = 8;

        while ($i <= $dataCounter) {
            $buffer = @$dataValue[$i];
            $bufferBits = @$dataBits[$i];

            $flag = 1;
            while ($flag) {
                if ($remainingBits > $bufferBits) {
                    $codewords[$codewordsCounter] = ((@$codewords[$codewordsCounter] << $bufferBits) | $buffer);
                    $remainingBits -= $bufferBits;
                    $flag = 0;
                } else {
                    $bufferBits -= $remainingBits;
                    $codewords[$codewordsCounter] = (($codewords[$codewordsCounter] << $remainingBits) | ($buffer >> $bufferBits));

                    if ($bufferBits == 0) {
                        $flag = 0;
                    } else {
                        $buffer = ($buffer & ((1 << $bufferBits) - 1));
                        $flag = 1;
                    }

                    $codewordsCounter++;
                    if ($codewordsCounter < $maxDataCodewords - 1) {
                        $codewords[$codewordsCounter] = 0;
                    }
                    $remainingBits = 8;
                }
            }
            $i++;
        }
        if ($remainingBits != 8) {
            $codewords[$codewordsCounter] = $codewords[$codewordsCounter] << $remainingBits;
        } else {
            $codewordsCounter--;
        }

        /* ----  set padding character */

        if ($codewordsCounter < $maxDataCodewords - 1) {
            $flag = 1;
            while ($codewordsCounter < $maxDataCodewords - 1) {
                $codewordsCounter++;
                if ($flag == 1) {
                    $codewords[$codewordsCounter] = 236;
                } else {
                    $codewords[$codewordsCounter] = 17;
                }
                $flag = $flag * (-1);
            }
        }

        /* ---- RS-ECC prepare */

        $i = 0;
        $j = 0;
        $rsBlockNumber = 0;
        $rsTemp[0] = "";

        while ($i < $maxDataCodewords) {

            $rsTemp[$rsBlockNumber] .= chr($codewords[$i]);
            $j++;

            if ($j >= $rsBlockOrder[$rsBlockNumber + 1] - $rsEccCodewords) {
                $j = 0;
                $rsBlockNumber++;
                $rsTemp[$rsBlockNumber] = "";
            }
            $i++;
        }


        /*
        #
        # RS-ECC main
        #
        */

        $rsBlockNumber = 0;
        $rsBlockOrderNum = count($rsBlockOrder);

        while ($rsBlockNumber < $rsBlockOrderNum) {

            $rsCodewords = $rsBlockOrder[$rsBlockNumber + 1];
            $rsDataCodewords = $rsCodewords - $rsEccCodewords;

            $rsTemp2 = $rsTemp[$rsBlockNumber] . str_repeat(chr(0), $rsEccCodewords);
            $paddingData = str_repeat(chr(0), $rsDataCodewords);

            $j = $rsDataCodewords;
            while ($j > 0) {
                $first = ord(substr($rsTemp2, 0, 1));

                if ($first) {
                    $leftChr = substr($rsTemp2, 1);
                    $cal = $rsCalTableArray[$first] . $paddingData;
                    $rsTemp2 = $leftChr ^ $cal;
                } else {
                    $rsTemp2 = substr($rsTemp2, 1);
                }

                $j--;
            }

            $codewords = array_merge($codewords, unpack("C*", $rsTemp2));

            $rsBlockNumber++;
        }

        /* ---- flash matrix */

        $i = 0;
        while ($i < $maxModulesSide1) {
            $j = 0;
            while ($j < $maxModulesSide1) {
                $matrixContent[$j][$i] = 0;
                $j++;
            }
            $i++;
        }

        /* --- attach data */

        $i = 0;
        while ($i < $maxCodewords) {
            $codeword_i = $codewords[$i];
            $j = 8;
            while ($j >= 1) {
                $codeword_bits_number = ($i << 3) + $j;
                $matrixContent[$matrixArrayX[$codeword_bits_number]][$matrixArrayY[$codeword_bits_number]] = ((255 * ($codeword_i & 1)) ^ $maskArray[$codeword_bits_number]);
                $codeword_i = $codeword_i >> 1;
                $j--;
            }
            $i++;
        }

        $matrixRemain = $matrixRemainBit[$this->qrCodeVersion];
        while ($matrixRemain) {
            $remainBitTemp = $matrixRemain + ($maxCodewords << 3);
            $matrixContent[$matrixArrayX[$remainBitTemp]][$matrixArrayY[$remainBitTemp]] = (0 ^ $maskArray[$remainBitTemp]);
            $matrixRemain--;
        }

        #--- mask select

        $minDemeritScore = 0;
        $horMaster = "";
        $verMaster = "";
        $k = 0;
        while ($k < $maxModulesSide1) {
            $l = 0;
            while ($l < $maxModulesSide1) {
                $horMaster = $horMaster . chr($matrixContent[$l][$k]);
                $verMaster = $verMaster . chr($matrixContent[$k][$l]);
                $l++;
            }
            $k++;
        }
        $i = 0;
        $allMatrix = $maxModulesSide1 * $maxModulesSide1;

        while ($i < 8) {
            $demeritN1 = 0;
            $ptnTemp = array();
            $bit = 1 << $i;
            $bitR = (~$bit) & 255;
            $bitMask = str_repeat(chr($bit), $allMatrix);
            $hor = $horMaster & $bitMask;
            $ver = $verMaster & $bitMask;

            $verShift1 = $ver . str_repeat(chr(170), $maxModulesSide1);
            $verShift2 = str_repeat(chr(170), $maxModulesSide1) . $ver;
            $verOr = chunk_split(~($verShift1 | $verShift2), $maxModulesSide1, chr(170));
            $verAnd = chunk_split(~($verShift1 & $verShift2), $maxModulesSide1, chr(170));

            $hor = chunk_split(~$hor, $maxModulesSide1, chr(170));
            $ver = chunk_split(~$ver, $maxModulesSide1, chr(170));
            $hor = $hor . chr(170) . $ver;

            $searchN1 = "/" . str_repeat(chr(255), 5) . "+|" . str_repeat(chr($bitR), 5) . "+/";
            $searchN3 = chr($bitR) . chr(255) . chr($bitR) . chr($bitR) . chr($bitR) . chr(255) . chr($bitR);

            $demeritN3 = substr_count($hor, $searchN3) * 40;
            $demeritN4 = floor(abs(((100 * (substr_count($ver, chr($bitR)) / ($byteNum))) - 50) / 5)) * 10;

            $searchN2_1 = "/" . chr($bitR) . chr($bitR) . "+/";
            $searchN2_2 = "/" . chr(255) . chr(255) . "+/";
            $demeritN2 = 0;
            preg_match_all($searchN2_1, $verAnd, $ptnTemp);
            foreach ($ptnTemp[0] as $str_temp) {
                $demeritN2 += (strlen($str_temp) - 1);
            }
            $ptnTemp = array();
            preg_match_all($searchN2_2, $verOr, $ptnTemp);
            foreach ($ptnTemp[0] as $str_temp) {
                $demeritN2 += (strlen($str_temp) - 1);
            }
            $demeritN2 *= 3;

            $ptnTemp = array();

            preg_match_all($searchN1, $hor, $ptnTemp);
            foreach ($ptnTemp[0] as $str_temp) {
                $demeritN1 += (strlen($str_temp) - 2);
            }

            $demeritScore = $demeritN1 + $demeritN2 + $demeritN3 + $demeritN4;

            if ($demeritScore <= $minDemeritScore || $i == 0) {
                $maskNumber = $i;
                $minDemeritScore = $demeritScore;
            }

            $i++;
        }

        $maskContent = 1 << $maskNumber;

        # --- format information

        $formatInformationValue = (($ec << 3) | $maskNumber);
        $formatInformationArray = array("101010000010010", "101000100100101",
            "101111001111100", "101101101001011", "100010111111001", "100000011001110",
            "100111110010111", "100101010100000", "111011111000100", "111001011110011",
            "111110110101010", "111100010011101", "110011000101111", "110001100011000",
            "110110001000001", "110100101110110", "001011010001001", "001001110111110",
            "001110011100111", "001100111010000", "000011101100010", "000001001010101",
            "000110100001100", "000100000111011", "011010101011111", "011000001101000",
            "011111100110001", "011101000000110", "010010010110100", "010000110000011",
            "010111011011010", "010101111101101");
        $i = 0;
        while ($i < 15) {
            $content = substr($formatInformationArray[$formatInformationValue], $i, 1);

            $matrixContent[$formatInformationX1[$i]][$formatInformationY1[$i]] = $content * 255;
            $matrixContent[$formatInformationX2[$i + 1]][$formatInformationY2[$i + 1]] = $content * 255;
            $i++;
        }

        $out = "";
        $mxe = $maxModulesSide1;
        $i = 0;
        while ($i < $mxe) {
            $j = 0;
            while ($j < $mxe) {
                if ($matrixContent[$j][$i] & $maskContent) {
                    $out .= "1";
                } else {
                    $out .= "0";
                }
                $j++;
            }
            $out .= "\n";
            $i++;
        }


        $out = $out | $frameData;
        return ($out);

    }
}