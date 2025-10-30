<?php

// Datei: Ndrstmr/DpT3Toc/Service/TcaContainerCheckService.php
declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

/**
 * A testable wrapper service to check container configuration
 * stored in $GLOBALS['TCA'].
 * Implements the interface for clean DI.
 */
final readonly class TcaContainerCheckService implements TcaContainerCheckServiceInterface
{
    /**
     * Check if a given CType is configured as a container in TCA.
     *
     * @param string $ctype The CType to check
     */
    public function isContainer(string $ctype): bool
    {
        // This is the *only* place in our code
        // that accesses $GLOBALS['TCA'] directly.

        // We ignore this line for PHPStan (Max Level).
        // The tool correctly flags access to superglobals and array access
        // on 'mixed'. However, isolating exactly this access is the
        // entire purpose of this wrapper class.

        /** @phpstan-ignore-next-line */
        return isset($GLOBALS['TCA']['tt_content']['containerConfiguration'][$ctype]);
    }
}
