<?php

namespace SEOAIC\keyword_types;

use SEOAIC\interfaces\KeywordTypeInterface;

abstract class KeywordBaseType implements KeywordTypeInterface
{
    protected $name;
    protected $title;

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function makeOptionTag(): string
    {
        return '<option value="' . $this->name . '">' . $this->title . '</option>';
    }

    public function makeRadioTag($checked = false): string
    {
        return '<label class="radio">
            <input type="radio" name="keyword_type" value="' . $this->name . '"' . ($checked ? ' checked=""' : '') . ' class="seoaic-form-item ' . $this->name . '-radio">
            <span class="name">' . $this->title . '</span>
        </label>';
    }
}
