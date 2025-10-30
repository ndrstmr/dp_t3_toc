<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

/**
 * Interface for a testable wrapper service to check container configuration in TCA.
 */
interface TcaContainerCheckServiceInterface
{
    /**
     * Check if a given CType is configured as a container in TCA.
     *
     * @param string $ctype The CType to check
     */
    public function isContainer(string $ctype): bool;
}
