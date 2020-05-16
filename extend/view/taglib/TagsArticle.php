<?php

declare(strict_types=1);

namespace view\taglib;

use view\Taglib;

class TagsArticle extends Taglib
{

    public function alone(): string
    {
        $parseStr  = '<?php $article = app(\'\app\cms\logic\article\Details\')->query();';
        $parseStr .= 'if (empty($article[\'data\'])): miss(404, true, true); endif;';
        $parseStr .= '$article = !empty($article[\'data\']) ? $article[\'data\'] : []; ?>';
        return $parseStr;
    }
}
