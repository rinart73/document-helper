<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper;

use Closure;

interface AssetInterface
{
    /**
     * @return string[]
     */
    public function getDependencies(): array;

    public function getPreparation(): ?Closure;

    public function render(): string;
}
