<?php

/**
 *
 * 解析
 *
 * @package   NICMS
 * @category  app\api\logic
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\lib;

use think\App;
use app\api\lib\BaseLogic;
use app\api\lib\Analytical;
use app\api\lib\Validate;

class Async extends BaseLogic
{
    public function exec()
    {
        $analytical = new Analytical($this->app);
        $analytical->openVersion = false;
        $analytical->authorization();
        $analytical->accept();
        $analytical->appId();
        $analytical->loadLang();
        $analytical->method();

        $validate = new Validate($this->app);
        $validate->sign($analytical->appSecret);
        $validate->RBAC($analytical->appName, $analytical->appMethod, $analytical->uid);


    }
}
