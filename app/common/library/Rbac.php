<?php

/**
 *
 * 权限校验类
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use app\common\model\Node as ModelNode;

class Rbac
{
    private static $config = [
        'auth_founder'     => 1,        // 超级管理员ID
        'auth_type'        => false,    // 实时验证方式
        'not_auth_app'     => [],
        'not_auth_service' => [],
        'not_auth_logic'   => [],
        'not_auth_action'  => [],
    ];

    /**
     * 审核用户操作权限
     * @access public
     * @static
     * @param  int    $_uid     用户ID
     * @param  string $_app     应用名
     * @param  string $_logic   业务层名
     * @param  string $_action  控制器名
     * @param  string $_method  方法名
     * @return boolean
     */
    public static function authenticate(int $_uid, string $_app, string $_logic, string $_action, string $_method, array $_config = []): bool
    {
        if (!empty($_config)) {
            self::$config = array_merge(self::$config, $_config);
        }

        $_uid = (int) $_uid;

        // 登录并请求方法需要审核
        if ($_uid && self::checkAccess($_app, $_logic, $_action, $_method)) {
            // 实时检验权限
            if (true === self::$config['auth_type']) {
                $__authenticate_list = self::accessDecision($_uid);
            }

            // 非实时校验
            // 权限写入session
            else {
                if (session('?__authenticate_list')) {
                    $__authenticate_list = session('__authenticate_list');
                } else {
                    $__authenticate_list = self::accessDecision($_uid);
                    session('__authenticate_list', $__authenticate_list);
                }
            }

            return isset($__authenticate_list[$_app][$_logic][$_action][$_method]);
        } else {
            return $_uid ? true : false;
        }
    }

    /**
     * 获得用户权限
     * @access public
     * @static
     * @param  int   $_uid
     * @return array
     */
    public static function getAuth(int $_uid): array
    {
        $_uid = (int) $_uid;
        if (true === self::$config['auth_type']) {
            $result = self::accessDecision($_uid);
        } elseif (session('?__authenticate_list')) {
            $result = session('__authenticate_list');
        } else {
            $result = self::accessDecision($_uid);
            session('__authenticate_list', $result);
        }
        return $result;
    }

    /**
     * 检查当前操作是否需要认证
     * @access private
     * @static
     * @param  string $_app     应用名
     * @param  string $_service 业务层名
     * @param  string $_logic   控制器名
     * @param  string $_method  方法名
     * @return boolean
     */
    private static function checkAccess(string $_app, string $_service, string $_logic, string $_method): bool
    {
        if (!empty(self::$config['not_auth_app'])) {
            self::$config['not_auth_app'] = array_map('strtolower', self::$config['not_auth_app']);
            if (in_array($_app, self::$config['not_auth_app'])) {
                return false;
            }
        } elseif (!empty(self::$config['not_auth_service'])) {
            self::$config['not_auth_service'] = array_map('strtolower', self::$config['not_auth_service']);
            if (in_array($_service, self::$config['not_auth_service'])) {
                return false;
            }
        } elseif (!empty(self::$config['not_auth_logic'])) {
            self::$config['not_auth_logic'] = array_map('strtolower', self::$config['not_auth_logic']);
            if (in_array($_logic, self::$config['not_auth_logic'])) {
                return false;
            }
        } elseif (!empty(self::$config['not_auth_action'])) {
            self::$config['not_auth_action'] = array_map('strtolower', self::$config['not_auth_action']);
            if (in_array($_method, self::$config['not_auth_action'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查当前操作是否需要认证
     * @access private
     * @static
     * @param  int    $_uid 用户ID
     * @return array
     */
    private static function accessDecision(int $_uid): array
    {
        $access = [];

        $app_list = self::getNode($_uid);
        foreach ($app_list as $app_name) {
            $app_name['name'] = strtolower($app_name['name']);

            $logic_list = self::getNode($_uid, 2, (int) $app_name['id']);
            foreach ($logic_list as $logic_name) {
                $logic_name['name'] = strtolower($logic_name['name']);

                $controller_list = self::getNode($_uid, 3, (int) $logic_name['id']);
                foreach ($controller_list as $controller_name) {
                    $controller_name['name'] = strtolower($controller_name['name']);

                    $access[$app_name['name']][$logic_name['name']][$controller_name['name']] = [
                        'index' => true,
                        'query' => true,
                        'find'  => true,
                    ];

                    $action_list = self::getNode($_uid, 4, (int) $controller_name['id']);
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
     * @static
     * @param  int $_uid
     * @param  int $_level
     * @param  int $_pid
     * @return array
     */
    private static function getNode(int $_uid, int $_level = 1, int $_pid = 0): array
    {
        if (self::$config['auth_founder'] == $_uid) {
            $result = ModelNode::field(['id', 'name'])
                ->where([
                    ['status', '=', 1],
                    ['level', '=', $_level],
                    ['pid', '=', $_pid],
                ])
                ->cache('NODE_FOUNDER' . $_uid . $_level . $_pid, null, 'SYSTEM')
                ->select();
        } else {
            $result = ModelNode::view('node', ['id', 'name'])
                ->view('role_admin', [], 'role_admin.user_id=' . $_uid . '')
                ->view('role', [], 'role.status=1 AND role.id=role_admin.role_id')
                ->view('access', [], 'access.role_id=role.id AND access.node_id=node.id')
                ->where([
                    ['node.status', '=', 1],
                    ['node.level', '=', $_level],
                    ['node.pid', '=', $_pid],
                ])
                ->cache('NODE' . $_uid . $_level . $_pid, null, 'SYSTEM')
                ->select();
        }

        return $result ? $result->toArray() : [];
    }
}
