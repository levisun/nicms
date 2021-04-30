<?php

/**
 *
 * API接口层
 * 安全设置
 *
 * @package   NICMS
 * @category  app\admin\logic\settings
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\settings;

use app\common\controller\BaseLogic;

class Safe extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 允许上传文件后缀,避免恶意修改配置文件导致的有害文件上传
     * @var array
     */
    private $fileExtension = [
        'jpg', 'gif', 'png', 'webp',
        'mp3', 'mp4',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf',
        'zip'
    ];

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $env = $this->app->env;

        $result = [
            'app_debug' => $env->get('app_debug'),
            'app_maintain' => $env->get('app_maintain'),
            'app' => [
                'upload_size' => $env->get('app.upload_size'),
                'upload_type' => $env->get('app.upload_type'),
            ],

            'database' => [
                'type'     => $env->get('database.type'),
                'hostname' => $env->get('database.hostname'),
                'database' => $env->get('database.database'),
                'username' => $env->get('database.username'),
                // 'password' => $env->get('database.password'),
                // 'hostport' => $env->get('database.hostport'),
                'prefix'   => $env->get('database.prefix'),
            ],
            'cache' => [
                'type'   => $env->get('cache.type'),
                'expire' => $env->get('cache.expire'),
            ],
            'admin' => [
                'entry'   => $this->config->get('app.entry') . '.' . $this->request->rootDomain(),
                'theme'   => $this->config->get('app.theme'),
            ]
        ];

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => $result
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor(): array
    {
        $this->actionLog('admin safe editor');

        $receive_data = [
            'app_upload_size' => $this->request->param('app.upload_size/d', 1, 'abs'),
            'app_upload_type' => $this->request->param('app.upload_type'),
            'cache_expire'    => $this->request->param('cache.expire/d', 2880, 'abs'),
            'app_debug'       => $this->request->param('app_debug/d', 0, 'abs'),
            'app_maintain'    => $this->request->param('app_maintain/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        // 允许上传文件后缀,避免恶意修改配置文件导致的有害文件上传
        $receive_data['app_upload_type'] = explode(',', $receive_data['app_upload_type']);
        foreach ($receive_data['app_upload_type'] as $key => $value) {
            if (!in_array($value, $this->fileExtension)) {
                unset($receive_data['app_upload_type'][$key]);
            }
        }
        $receive_data['app_upload_type'] = implode(',', $receive_data['app_upload_type']);

        $receive_data['app_debug'] = $receive_data['app_debug'] ? 'true' : 'false';
        $receive_data['app_maintain'] = $receive_data['app_maintain'] ? 'true' : 'false';

        $env_config = file_get_contents(root_path() . '.env');
        $env_config = preg_replace([
            '/(APP_DEBUG) = [^\r\n]+[\r\n]+/i',
            '/(APP_MAINTAIN) = [^\r\n]+[\r\n]+/i',
            '/(UPLOAD_SIZE) = [^\r\n]+[\r\n]+/i',
            '/(UPLOAD_TYPE) = [^\r\n]+[\r\n]+/i',
        ], [
            '$1 = ' . $receive_data['app_debug'] . PHP_EOL,
            '$1 = ' . $receive_data['app_maintain'] . PHP_EOL,
            '$1 = ' . $receive_data['app_upload_size'] . PHP_EOL,
            '$1 = ' . $receive_data['app_upload_type'] . PHP_EOL,
        ], $env_config);
        file_put_contents($this->app->getRootPath() . '.env', $env_config);

        $this->cache->tag('system')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
