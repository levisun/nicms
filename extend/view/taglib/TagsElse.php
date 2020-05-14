<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsElse extends Taglib
{

    public function alone()
    {
        return '<?php else: ?>';
    }
}

