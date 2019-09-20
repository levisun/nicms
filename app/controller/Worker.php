<?php

/**
 *
 * ????
 *
 * @package   NICMS
 * @category  app\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\controller;

use think\worker\Server;

class Worker extends Server
{
    protected $socket = 'http://0.0.0.0:2346';

    public function onWorkerStart($worker)
    {
        # code...
    }

    public function onMessage($connection, $data)
    {
        $connection->send(json_encode($data));
    }
}
