<?php

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsIf extends Taglib
{

    public function closed(): string
    {
        return '<?php if (' . $this->params['expression'] . '): ?>';
    }

    public function end(): string
    {
        return '<?php endif; ?>';
    }
}
