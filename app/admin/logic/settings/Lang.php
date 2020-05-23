<?php

/**
 *
 * API接口层
 * 安全设置
 *
 * @package   NICMS
 * @category  app\admin\logic\settings
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\settings;

use app\common\controller\BaseLogic;

class Lang extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $app_name = ['admin', 'cms', 'user'];
        $lang = [];

        $path = $this->app->getBasePath();
        foreach ($app_name as $value) {
            $dir = $path . $value . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
            if (is_dir($dir) && $index = glob($dir . '*')) {
                foreach ($index as $file) {
                    $file = str_replace('.php', '', basename($file));
                    $lang[$file][] = $value;
                }
            }
        }
        halt($lang);
        return $lang;
    }
}
