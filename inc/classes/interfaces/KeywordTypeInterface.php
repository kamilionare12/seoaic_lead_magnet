<?php

namespace SEOAIC\interfaces;

interface KeywordTypeInterface
{
    public function getName(): string;
    public function getTitle(): string;
    public function makeOptionTag(): string;
}
