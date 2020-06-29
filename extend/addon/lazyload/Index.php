<?php

declare(strict_types=1);

namespace addon\lazyload;

use \addon\Base;

class Index extends Base
{

    public function run(): void
    {
        $script = '<script src="https://cdn.jsdelivr.net/npm/lazyload@2.0.0-rc.2/lazyload.min.js"></script>';

    }
}
