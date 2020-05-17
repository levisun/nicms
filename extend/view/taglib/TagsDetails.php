<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsDetails extends Taglib
{

    public function alone(): string
    {
        $parseStr  = '<?php $details = app(\'\app\cms\logic\article\Details\')->query();';
        $parseStr .= 'if (empty($details[\'data\'])): miss(404, true, true); endif;';
        $parseStr .= '$details = !empty($details[\'data\']) ? $details[\'data\'] : []; ?>';
        return $parseStr;
    }
}
