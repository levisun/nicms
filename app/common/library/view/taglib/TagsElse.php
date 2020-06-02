<?php

declare(strict_types=1);

namespace app\common\library\view\taglib;

use app\common\library\view\Taglib;

class TagsElse extends Taglib
{

    public function alone(): string
    {
        return '<?php else: ?>';
    }
}
