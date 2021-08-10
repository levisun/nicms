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
use app\common\library\Rbac;

class Auth extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 权限
     * @access public
     * @return array
     */
    public function query(): array
    {
        if (!$this->cache->has('AUTH' . $this->userId) || !$result = $this->cache->get('AUTH' . $this->userId)) {
            $result = (new Rbac)->setUserId($this->userId)->getAuth();
            $result = $result['admin'];
            foreach ($result as $key => $value) {
                $result[$key] = [
                    'name' => $key,
                    'lang' => $this->lang->get('auth.' . $key),
                ];
                foreach ($value as $k => $val) {
                    $result[$key]['child'][$k] = [
                        'name' => $k,
                        'lang' => $this->lang->get('auth.' . $key . '_' . $k),
                        'url'  => url($key . '/' . $k . '/index')
                    ];
                }
            }
            $this->cache->set('AUTH' . $this->userId, $result);
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'success',
            'data'  => $result
        ];
    }
}
