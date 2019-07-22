<?php
/**
 *
 * API接口层
 * 缓存
 *
 * @package   NICMS
 * @category  app\service\admin\content
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\admin\content;

use app\service\BaseService;
use app\library\ReGarbage;

class Cache extends BaseService
{
    protected $authKey = 'admin_auth_key';

    /**
     * 清除数据缓存
     * @access public
     * @param
     * @return array
     */
    public function reCache(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin content cache reomve')) {
            return $result;
        }

        $this->cache->clear(); // 方法无法清除全部缓存

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove cache success'
        ];
    }

    /**
     * 清除模板编译
     * @access public
     * @param
     * @return array
     */
    public function reCompile(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin content compile reomve')) {
            return $result;
        }


        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove compile success'
        ];
    }
}
