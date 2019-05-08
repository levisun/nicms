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

use think\session\SessionHandlerInterface;
use app\model\Session as ModelSession;

class Session implements SessionHandlerInterface
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

        if (rand(1, 10) === 1) {
            (new ModelSession)
                ->where([
                    ['update_time', '<=', strtotime('-3 days')]
                ])
                ->delete();
        }
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

        $result = (new ModelSession)
            ->where($map)
            ->value('data', '');

        if ($result) {
            (new ModelSession)
                ->where($map)
                ->update([
                    'update_time' => time()
                ]);
        }

        return $result;
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
        $has = (new ModelSession)
            ->where([
                ['session_id', '=', $this->config['prefix'] . $sessID]
            ])
            ->find();

        $data = [
            'session_id'  => $this->config['prefix'] . $sessID,
            'data'        => $data ? $data : '',
            'update_time' => time()
        ];

        if (!empty($has)) {
            (new ModelSession)
                ->where([
                    ['session_id', '=', $this->config['prefix'] . $sessID],
                ])
                ->update($data);
            $result = (new ModelSession)->getNumRows();
        } else {
            (new ModelSession)->create($data);
            $result = (new ModelSession)->getNumRows();
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
        (new ModelSession)->where([
            ['session_id', '=', $this->config['prefix'] . $sessID]
        ])
            ->delete();
        return !!(new ModelSession)->getNumRows();
    }
}
