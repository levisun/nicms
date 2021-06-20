<?php

/**
 *
 * 权限校验类
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use app\common\model\Node as ModelNode;

class Rbac
{
    private $config = [
        'auth_founder'     => 1,        // 超级管理员ID
        'auth_type'        => false,    // 实时验证方式
        'not_auth_app'     => [],
        'not_auth_service' => [],
        'not_auth_logic'   => [],
        'not_auth_action'  => [],
    ];

    private $userID = 0;
    private $appName = '';

    public function setConfig(array $_config = [])
    {
        if (!empty($_config)) {
            $this->config = array_merge($this->config, $_config);
        }
        return $this;
    }

    public function setUserId(int $_uid)
    {
        $this->userID = $_uid;
        return $this;
    }

    public function setAppName(string $_app)
    {
        $this->appName = $_app;
        return $this;
    }

    /**
     * 审核用户操作权限
     * @access public
     * @param  string $_logic   业务层名
     * @param  string $_action  控制器名
     * @param  string $_method  方法名
     * @return boolean
     */
    public function authenticate(string $_logic, string $_action, string $_method): bool
    {
        // 登录并请求方法需要审核
        if ($this->userID && $this->checkAccess($_logic, $_action, $_method)) {
            // 实时检验权限
            if (true === $this->config['auth_type']) {
                $__authenticate_list = $this->accessDecision();
            }

            // 非实时校验
            // 权限写入session
            else {
                if (session('?__authenticate_list')) {
                    $__authenticate_list = session('__authenticate_list');
                } else {
                    $__authenticate_list = $this->accessDecision();
                    session('__authenticate_list', $__authenticate_list);
                }
            }

            return isset($__authenticate_list[$this->appName][$_logic][$_action][$_method]);
        } else {
            return $this->userID ? true : false;
        }
    }

    /**
     * 获得用户权限
     * @access public
     * @param  int   $_uid
     * @return array
     */
    public function getAuth(): array
    {
        if (true === $this->config['auth_type']) {
            $result = $this->accessDecision();
        } elseif (session('?__authenticate_list')) {
            $result = session('__authenticate_list');
        } else {
            $result = $this->accessDecision();
            session('__authenticate_list', $result);
        }
        return $result;
    }

    /**
     * 检查当前操作是否需要认证
     * @access private
     * @param  string $_app     应用名
     * @param  string $_service 业务层名
     * @param  string $_logic   控制器名
     * @param  string $_method  方法名
     * @return boolean
     */
    private function checkAccess(string $_service, string $_logic, string $_method): bool
    {
        if (!empty($this->config['not_auth_app'])) {
            $this->config['not_auth_app'] = array_map('strtolower', $this->config['not_auth_app']);
            if (in_array($this->appName, $this->config['not_auth_app'])) {
                return false;
            }
        } elseif (!empty($this->config['not_auth_service'])) {
            $this->config['not_auth_service'] = array_map('strtolower', $this->config['not_auth_service']);
            if (in_array($_service, $this->config['not_auth_service'])) {
                return false;
            }
        } elseif (!empty($this->config['not_auth_logic'])) {
            $this->config['not_auth_logic'] = array_map('strtolower', $this->config['not_auth_logic']);
            if (in_array($_logic, $this->config['not_auth_logic'])) {
                return false;
            }
        } elseif (!empty($this->config['not_auth_action'])) {
            $this->config['not_auth_action'] = array_map('strtolower', $this->config['not_auth_action']);
            if (in_array($_method, $this->config['not_auth_action'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查当前操作是否需要认证
     * @access private
     * @param  int    $_uid 用户ID
     * @return array
     */
    private function accessDecision(): array
    {
        $access = [];

        $app_list = $this->getNode($this->userID);
        foreach ($app_list as $app_name) {
            $app_name['name'] = strtolower($app_name['name']);

            $logic_list = $this->getNode($this->userID, 2, (int) $app_name['id']);
            foreach ($logic_list as $logic_name) {
                $logic_name['name'] = strtolower($logic_name['name']);

                $controller_list = $this->getNode($this->userID, 3, (int) $logic_name['id']);
                foreach ($controller_list as $controller_name) {
                    $controller_name['name'] = strtolower($controller_name['name']);

                    $access[$app_name['name']][$logic_name['name']][$controller_name['name']] = [
                        'index' => true,
                        'query' => true,
                        'find'  => true,
                    ];

                    $action_list = $this->getNode($this->userID, 4, (int) $controller_name['id']);
                    foreach ($action_list as $action_name) {
                        $action_name['name'] = strtolower($action_name['name']);
                        $access[$app_name['name']][$logic_name['name']][$controller_name['name']][$action_name['name']] = true;
                    }
                }
            }
        }

        return $access;
    }

    /**
     * 获得当前认证号对应权限
     * @access private
     * @param  int $_uid
     * @param  int $_level
     * @param  int $_pid
     * @return array
     */
    private function getNode(int $_uid, int $_level = 1, int $_pid = 0): array
    {
        if ($this->config['auth_founder'] == $_uid) {
            $result = ModelNode::field(['id', 'name'])
                ->where('status', '=', 1)
                ->where('level', '=', $_level)
                ->where('pid', '=', $_pid)
                ->cache('NODE_FOUNDER' . $_uid . $_level . $_pid, null, 'system')
                ->select();
        } else {
            $result = ModelNode::view('node', ['id', 'name'])
                ->view('role_admin', [], 'role_admin.user_id=' . $_uid . '')
                ->view('role', [], 'role.status=1 AND role.id=role_admin.role_id')
                ->view('access', [], 'access.role_id=role.id AND access.node_id=node.id')
                ->where('node.status', '=', 1)
                ->where('node.level', '=', $_level)
                ->where('node.pid', '=', $_pid)
                ->cache('NODE' . $_uid . $_level . $_pid, null, 'system')
                ->select();
        }

        return $result ? $result->toArray() : [];
    }
}
