<?php

namespace SEOAIC\keyword_types;

use SEOAIC\interfaces\KeywordTypeInterface;

class KeywordLongTailTermType extends KeywordBaseType implements KeywordTypeInterface
{
    protected $name = 'long_tail_term';
    protected $title = 'Long-Tail Term';
}
