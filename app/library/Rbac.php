<?php
/**
 *
 * 权限校验类
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\library;

use app\model\Node as ModelNode;

class Rbac
{
    private $config = [
        'auth_founder'     => 1,                                             // 超级管理员ID
        'auth_type'        => 1,                                             // 验证方式
        'not_auth_app'     => [],
        'not_auth_service' => [],
        'not_auth_logic'   => [],
        'not_auth_action'  => [],
    ];

    /**
     * 审核用户操作权限
     * @access public
     * @param  int    $_uid     用户ID
     * @param  string $_app     应用名
     * @param  string $_service 业务层名
     * @param  string $_logic   控制器名
     * @param  string $_action  方法名
     * @return boolean
     */
    public function authenticate($_uid, string $_app, string $_service, string $_logic, string $_action, array $_config = []): bool
    {
        $_uid = (int)$_uid;

        $this->config = array_merge($this->config, $_config);

        // 登录并请求方法需要审核
        if ($_uid && $this->checkAccess($_app, $_service, $_logic, $_action)) {
            // 实时检验权限
            if ($this->config['auth_type'] == 1) {
                $__authenticate_list = $this->accessDecision($_uid);
            }

            // 非实时校验
            // 权限写入session
            else {
                if (session('?__authenticate_list')) {
                    $__authenticate_list = session('__authenticate_list');
                } else {
                    $__authenticate_list = $this->accessDecision($_uid);
                    session('__authenticate_list', $__authenticate_list);
                }
            }

            return isset($__authenticate_list[$_app][$_service][$_logic][$_action]);
        } else {
            return $_uid ? true : false;
        }
    }

    /**
     * 获得用户权限
     * @param  int   $_uid
     * @return array
     */
    public function getAuth($_uid): array
    {
        $_uid = (int)$_uid;
        if ($this->config['auth_type'] == 1) {
            $result = $this->accessDecision($_uid);
        } elseif (session('?__authenticate_list')) {
            $result = session('?__authenticate_list');
        } else {
            $result = $this->accessDecision($_uid);
            session('__authenticate_list', $result);
        }
        return $result;
    }

    /**
     * 检查当前操作是否需要认证
     * @access private
     * @param  string $_app        应用名
     * @param  string $_service      业务层名
     * @param  string $_logic 控制器名
     * @param  string $_action     方法名
     * @return boolean
     */
    private  function checkAccess(string $_app, string $_service, string $_logic, string $_action): bool
    {
        if (!empty($this->config['not_auth_app'])) {
            $this->config['not_auth_app'] = array_map('strtolower', $this->config['not_auth_app']);
            if (in_array($_app, $this->config['not_auth_app'])) {
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
            if (in_array($_action, $this->config['not_auth_action'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查当前操作是否需要认证
     * @access private
     * @param  int    $_uid     用户ID
     * @return array
     */
    private function accessDecision(int $_uid): array
    {
        $access = [];

        $app = $this->getNode($_uid);
        foreach ($app as $a) {
            $a['name'] = strtolower($a['name']);
            $logic = $this->getNode($_uid, 2, $a['id']);

            foreach ($logic as $l) {
                $l['name'] = strtolower($l['name']);
                $controller = $this->getNode($_uid, 3, $l['id']);

                foreach ($controller as $c) {
                    $c['name'] = strtolower($c['name']);
                    $action = $this->getNode($_uid, 4, $c['id']);

                    $access[$a['name']][$l['name']][$c['name']]['index'] = true;
                    $access[$a['name']][$l['name']][$c['name']]['query'] = true;
                    $access[$a['name']][$l['name']][$c['name']]['find'] = true;

                    foreach ($action as $act) {
                        $access[$a['name']][$l['name']][$c['name']][$act['name']] = true;
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
            $result = (new ModelNode)
                ->field(['id', 'name'])
                ->where([
                    ['status', '=', 1],
                    ['level', '=', $_level],
                    ['pid', '=', $_pid],
                ])
                // ->cache(__METHOD__ . 'founder' . $_uid . $_level . $_pid, 28800, 'library')
                ->select()
                ->toArray();
        } else {
            $result = (new ModelNode)
                ->view('node', ['id', 'name'])
                ->view('role_admin', [], 'role_admin.user_id=' . $_uid . '')
                ->view('role', [], 'role.status=1 AND role.id=role_admin.role_id')
                ->view('access', [], 'access.role_id=role.id AND access.node_id=node.id')
                ->where([
                    ['node.status', '=', 1],
                    ['node.level', '=', $_level],
                    ['node.pid', '=', $_pid],
                ])
                // ->cache(__METHOD__ . $_uid . $_level . $_pid, 28800, 'library')
                ->select()
                ->toArray();
        }

        return $result;
    }
}
