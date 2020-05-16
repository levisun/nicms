<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

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
