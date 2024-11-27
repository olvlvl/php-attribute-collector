<?php

namespace Acme81\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SampleComplex
{
    public function __construct(
        public SampleComplexValue $value,
    ) {
    }
}
