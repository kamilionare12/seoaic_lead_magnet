<?php

namespace SEOAIC\keyword_types;

use SEOAIC\interfaces\KeywordTypeInterface;

class KeywordMidTailTermType extends KeywordBaseType implements KeywordTypeInterface
{
    protected $name = 'mid_tail_term';
    protected $title = 'Mid-Tail Term';
}
