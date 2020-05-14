<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsIf extends Taglib
{

    public function closed(): string
    {
        return '<?php if (' . $this->params['expression'] . '): ?>';
    }

    public function end()
    {
        return '<?php endif; ?>';
    }
}
