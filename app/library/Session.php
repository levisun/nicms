<?php
/**
 *
 * 数据安全过滤
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\library;

use think\facade\Config;
use think\session\SessionHandler;
use app\model\Session as ModelSession;

class Session implements SessionHandler
{
    private $config = [
        'expire' => 0,
        'prefix' => '',
    ];

    /**
     * 构造方法
     * @access public
     * @param
     * @return void
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 读取Session
     * @access public
     * @param  string $sessID
     * @return string
     */
    public function read(string $sessID): string
    {
        $map = [
            ['session_id', '=', $this->config['prefix'] . $sessID]
        ];

        if ($this->config['expire'] != 0) {
            $map[] = ['update_time', '>=', time() - $this->config['expire']];
        }

        return
        ModelSession::where($map)
        ->value('data', '');
    }

    /**
     * 写入Session
     * @access public
     * @param  string $sessID
     * @param  string $data
     * @return array
     */
    public function write(string $sessID, string $data): bool
    {
        $result =
        ModelSession::where([
            ['session_id', '=', $this->config['prefix'] . $sessID]
        ])
        ->find();

        $data = [
            'session_id'  => $this->config['prefix'] . $sessID,
            'data'        => $data ? $data : '',
            'update_time' => time()
        ];

        if (!empty($result)) {
            ModelSession::where([
                ['session_id', '=', $this->config['prefix'] . $sessID],
            ])
            ->update($data);
            $result = !!ModelSession::getNumRows();
        } else {
            ModelSession::insert($data);
            $result = !!ModelSession::getNumRows();
        }

        return !!$result;
    }

    /**
     * 删除Session
     * @access public
     * @param  string $sessID
     * @return bool
     */
    public function delete(string $sessID): bool
    {
        ModelSession::where([
            ['session_id', '=', $this->config['prefix'] . $sessID]
        ])
        ->delete();
        return !!ModelSession::getNumRows();
    }
}
