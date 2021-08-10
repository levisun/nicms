<?php

/**
 *
 * API接口层
 * 登录
 *
 * @package   NICMS
 * @category  app\admin\logic\account
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\account;

use app\common\controller\BaseLogic;
use app\common\library\IpV4;
use app\common\library\Base64;
use app\common\model\Admin as ModelAdmin;

class Forget extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

}
