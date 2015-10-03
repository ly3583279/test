<?php
namespace System\Shell\Controller;

use System\Core\Request;

/**
 * Class Test
 * @package System\Shell\Controller
 */
class Test extends BaseController
{

    public function tokenMethod()
    {
        $appId = 'coolmarket';
        $appKey = 'e85188a3bf413a7d0d3abe80a3b9f36d';
        $deviceId = strtolower('B2D9A8CE-BE0D-4587-BCB3-4C23CFBCB5CC');
        $tokenTime = Request::getRequestTime();
        echo 'token: ', md5(base64_encode('token://' . $appId . '/' . $appKey . '?' . md5($tokenTime) . '$' . $deviceId)) . $deviceId . '0x' . dechex($tokenTime);
    }

}