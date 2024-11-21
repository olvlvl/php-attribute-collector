<?php

namespace olvlvl\ComposerAttributeCollector;

interface TransientCollectionRenderer
{
    /**
     * @return string The rendered PHP code.
     */
    public static function render(TransientCollection $collector): string;
}
