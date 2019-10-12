<?php

/**
 *
 * 数据安全过滤
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

namespace app\common\library;

use think\contract\SessionHandlerInterface;
use app\common\model\Session as ModelSession;

class Session implements SessionHandlerInterface
{
    private $config = [
        'expire' => 0,
        'prefix' => '',
    ];
    private $model = null;

    /**
     * 构造方法
     * @access public
     * @param
     * @return void
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->model = new ModelSession;
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param
     * @return void
     */
    public function gc(): void
    {
        $maxlifetime = (int) $this->config['gc_maxlifetime'];
        $this->model->where([
                ['update_time', '<=', time() - $maxlifetime]
            ])
            ->delete();
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

        if (0 !== $this->config['expire']) {
            $map[] = ['update_time', '>=', time() - $this->config['expire']];
        }

        $result = $this->model->field(['data', 'update_time'])
            ->where($map)
            ->cache($sessID)
            ->find();

        if (null !== $result && $result['update_time'] <= strtotime('-10 minute')) {
            $this->model->where($map)
                ->cache($sessID)
                ->update([
                    'update_time' => time()
                ]);
        }

        return null !== $result ? $result['data'] : '';
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
        $has = $this->model->where([
                ['session_id', '=', $this->config['prefix'] . $sessID]
            ])
            ->find();

        $data = [
            'session_id'  => $this->config['prefix'] . $sessID,
            'data'        => $data ? $data : '',
            'update_time' => time()
        ];

        if (null !== $has) {
            $this->model->where([
                    ['session_id', '=', $this->config['prefix'] . $sessID],
                ])
                ->cache($sessID)
                ->update($data);
        } else {
            $this->model->create($data);
        }

        return !!$this->model->getNumRows();
    }

    /**
     * 删除Session
     * @access public
     * @param  string $sessID
     * @return bool
     */
    public function delete(string $sessID): bool
    {
        $this->model->where([
                ['session_id', '=', $this->config['prefix'] . $sessID]
            ])
            ->cache($sessID)
            ->delete();
        return $this->model->getNumRows();
    }
}
