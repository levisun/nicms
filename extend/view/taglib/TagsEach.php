<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsEach extends Taglib
{

    public function closed(): string
    {
        $parseStr  = '<?php ' . key($this->params);
        $parseStr .= ' = isset(' . key($this->params) . ') && is_array(' . key($this->params) . ')';
        $parseStr .= ' ? ' . key($this->params) . ' : []; ';
        $parseStr .= 'foreach (' . $this->params['expression'] . '): ?>';

        return $parseStr;
    }

    public function end()
    {
        return '<?php endforeach; ?>';
    }
}
