<?php

/**
 *
 * API接口层
 * 登录
 *
 * @package   NICMS
 * @category  app\admin\logic\account
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\account;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Canvas;
use app\common\library\Ipinfo;
use app\common\library\Rbac;
use app\common\model\Admin as ModelAdmin;

class User extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 登录
     * @access public
     * @return array
     */
    public function login(): array
    {
        $receive_data = [
            'username' => $this->request->param('username'),
            'password' => $this->request->param('password'),
        ];

        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        $user = (new ModelAdmin)
            ->view('admin', ['id', 'username', 'password', 'salt', 'flag'])
            ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
            ->view('role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
            ->where([
                ['admin.status', '=', 1],
                ['admin.username', '=', $this->request->param('username')]
            ])
            ->whereOr([
                ['admin.phone', '=', $this->request->param('username')]
            ])
            ->whereOr([
                ['admin.email', '=', $this->request->param('username')]
            ])
            ->find();
        if (!is_null($user) && $new_pw = Base64::verifyPassword($this->request->param('password'), $user['salt'], $user['password'])) {
            // 更新登录信息
            $info = (new Ipinfo)->get($this->request->ip());
            (new ModelAdmin)
                ->where([
                    ['id', '=', $user['id']]
                ])
                ->data([
                    'flag'               => $this->session->getId(false),
                    'last_login_time'    => time(),
                    'last_login_ip'      => $info['ip'],
                    'last_login_ip_attr' => isset($info['country_id']) ? $info['region'] . $info['city'] . $info['area'] : ''
                ])
                ->update();

            // 唯一登录
            if ($user['flag'] && $user['flag'] !== $this->session->getId(false)) {
                $this->session->delete($user['flag']);
            }

            // 登录令牌
            $this->session->set($this->authKey, $user['id']);
            $this->session->set($this->authKey . '_role', $user['role_id']);
            $this->session->delete('login_lock');

            $this->uid = $user['id'];
            $this->urole = $user['role_id'];
            $this->actionLog(__METHOD__, 'admin user login');
        } else {
            // 记录登录错误次数
            $login_lock = $this->session->has('login_lock') ? $this->session->get('login_lock') : 0;
            ++$login_lock;

            // 错误次数超过5次锁定IP
            // 锁定方法在[\app\common\event\CheckRequestCache::lock]
            if ($login_lock >= 5) {
                $this->session->delete('login_lock');
                $lock = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
                $lock .= md5($this->request->ip()) . '.lock';
                file_put_contents($lock, 'lock');
            } else {
                $this->session->set('login_lock', $login_lock);
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'code'  => !is_null($user) && $new_pw ? 10000 : 40009,
            'msg'   => !is_null($user) && $new_pw ? 'success' : 'error'
        ];
    }

    /**
     * 用户注销
     * @access public
     * @return array
     */
    public function logout(): array
    {
        $this->actionLog(__METHOD__, 'admin user logout');

        $this->cache->delete('AUTH' . $this->uid);
        $this->session->delete($this->authKey);
        $this->session->delete($this->authKey . '_role');

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 找回密码
     * @access public
     * @return array
     */
    public function forget()
    {
        $this->actionLog(__METHOD__, 'admin user forget');
        # code...
    }

    /**
     * 权限
     * @access public
     * @return array
     */
    public function auth(): array
    {
        if (!$this->cache->has('AUTH' . $this->uid) || !$result = $this->cache->get('AUTH' . $this->uid)) {
            $result = (new Rbac)->getAuth($this->uid);
            $result = $result['admin'];
            foreach ($result as $key => $value) {
                $result[$key] = [
                    'name' => $key,
                    'lang' => $this->lang->get('auth ' . $key),
                ];
                foreach ($value as $k => $val) {
                    $result[$key]['child'][$k] = [
                        'name' => $k,
                        'lang' => $this->lang->get('auth ' . $k),
                        'url'  => url($key . '/' . $k . '/index')
                    ];
                }
            }
            $this->cache->set('AUTH' . $this->uid, $result);
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'success',
            'data'  => $result
        ];
    }

    /**
     * 用户信息
     * @access public
     * @return array
     */
    public function profile(): array
    {
        $result = null;

        if ($this->uid) {
            $result = (new ModelAdmin)
                ->view('admin', ['id', 'username', 'email', 'last_login_ip', 'last_login_ip_attr', 'last_login_time'])
                ->view('role_admin', ['role_id'], 'role_admin.user_id=admin.id')
                ->view('role role', ['name' => 'role_name'], 'role.id=role_admin.role_id')
                ->where([
                    ['admin.id', '=', $this->uid]
                ])
                ->cache('PROFILE' . $this->uid, 300, 'admin')
                ->find();

            if (null !== $result && $result = $result->toArray()) {
                $result['last_login_time'] = date('Y-m-d H:i:s', (int) $result['last_login_time']);
                $result['avatar'] = (new Canvas)->avatar('', $result['username']);
                $result['id'] = Base64::encrypt((string) $result['id'], 'uid');
                $result['role_id'] = Base64::encrypt((string) $result['role_id'], 'roleid');
                $result['app_debug'] = (int) $this->config->get('app.debug');
                // unset($result['id'], $result['role_id']);
            }
        }

        return [
            'debug'  => false,
            'cache'  => false,
            'msg'    => 'success',
            'data'   => $result
        ];
    }

    public function notice(): array
    {
        $result = [];

        // 验证备份状态
        $status = true;
        $file = (array) glob($this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . '*');
        if (count($file) >= 2) {
            foreach ($file as $value) {
                if (filectime($value) >= strtotime('-7 days')) {
                    $status = true;
                    continue;
                }
            }
        } else {
            $status = false;
        }
        if ($status === false) {
            $result[] = [
                'title' => $this->lang->get('please make a database backup'),
                'url'   => url('expand/database/index')
            ];
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'success',
            'data'  => [
                'list'  => $result,
                'total' => count($result),
            ]
        ];
    }
}
