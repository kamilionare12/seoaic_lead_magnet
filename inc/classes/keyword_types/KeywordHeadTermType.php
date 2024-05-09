<?php

namespace SEOAIC\keyword_types;

use SEOAIC\interfaces\KeywordTypeInterface;

class KeywordHeadTermType extends KeywordBaseType implements KeywordTypeInterface
{
    protected $name = 'head_term';
    protected $title = 'Head Term';
}
