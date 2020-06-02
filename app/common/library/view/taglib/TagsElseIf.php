<?php

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsElseIf extends Taglib
{

    public function closed(): string
    {
        // print_r($this->params);die();
        return '<?php elseif (' . $this->params['expression'] . '): ?>';
    }

    public function end(): string
    {
        return '<?php endif; ?>';
    }
}
