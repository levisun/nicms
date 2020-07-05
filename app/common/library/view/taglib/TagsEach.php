<?php

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsEach extends Taglib
{

    public function closed(): string
    {
        return '<?php foreach (' . $this->params['expression'] . '): ?>';
    }

    public function end(): string
    {
        return '<?php endforeach; ?>';
    }
}