<?php

namespace SbWereWolf\JsonSerializable;

/** Trait of ability for any class for output all non-static
 * properties of class-exemplar at call json_encode()
 */
trait JsonSerializeTrait
{
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}