<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Service;

use Ndrstmr\DpT3Toc\Service\TcaContainerCheckService;
use PHPUnit\Framework\TestCase;

final class TcaContainerCheckServiceTest extends TestCase
{
    private TcaContainerCheckService $service;

    protected function setUp(): void
    {
        $this->service = new TcaContainerCheckService();
    }

    protected function tearDown(): void
    {
        // Clean up global state after each test
        if (
            isset($GLOBALS['TCA'])
            && is_array($GLOBALS['TCA'])
            && isset($GLOBALS['TCA']['tt_content'])
            && is_array($GLOBALS['TCA']['tt_content'])
            && isset($GLOBALS['TCA']['tt_content']['containerConfiguration'])
        ) {
            unset($GLOBALS['TCA']['tt_content']['containerConfiguration']);
        }
    }

    /**
     * Set container configuration in TCA (type-safe for PHPStan max level).
     *
     * @param array<string, mixed> $config
     */
    private function setContainerConfiguration(array $config): void
    {
        if (!isset($GLOBALS['TCA'])) {
            $GLOBALS['TCA'] = [];
        }

        if (!is_array($GLOBALS['TCA'])) {
            $GLOBALS['TCA'] = [];
        }

        if (!isset($GLOBALS['TCA']['tt_content'])) {
            $GLOBALS['TCA']['tt_content'] = [];
        }

        if (!is_array($GLOBALS['TCA']['tt_content'])) {
            $GLOBALS['TCA']['tt_content'] = [];
        }

        $GLOBALS['TCA']['tt_content']['containerConfiguration'] = $config;
    }

    public function testIsContainerReturnsTrueWhenConfigured(): void
    {
        // Setup: Configure a container in TCA
        $this->setContainerConfiguration([
            'container_2col' => [
                'label' => '2 Column Container',
            ],
        ]);

        $result = $this->service->isContainer('container_2col');

        static::assertTrue($result);
    }

    public function testIsContainerReturnsFalseWhenNotConfigured(): void
    {
        // Setup: Ensure clean TCA state (tearDown handles cleanup)

        $result = $this->service->isContainer('container_2col');

        static::assertFalse($result);
    }

    public function testIsContainerReturnsFalseForStandardCType(): void
    {
        // Setup: Configure some containers but not 'text'
        $this->setContainerConfiguration([
            'container_2col' => [],
        ]);

        $result = $this->service->isContainer('text');

        static::assertFalse($result);
    }

    public function testIsContainerReturnsFalseWhenTcaNotSet(): void
    {
        // Setup: Completely unset TCA
        unset($GLOBALS['TCA']);

        $result = $this->service->isContainer('container_2col');

        static::assertFalse($result);
    }

    public function testIsContainerWithMultipleContainers(): void
    {
        // Setup: Configure multiple containers
        $this->setContainerConfiguration([
            'container_2col' => [],
            'container_3col' => [],
            'container_grid' => [],
        ]);

        static::assertTrue($this->service->isContainer('container_2col'));
        static::assertTrue($this->service->isContainer('container_3col'));
        static::assertTrue($this->service->isContainer('container_grid'));
        static::assertFalse($this->service->isContainer('text'));
        static::assertFalse($this->service->isContainer('nonexistent'));
    }

    public function testIsContainerWithEmptyString(): void
    {
        $this->setContainerConfiguration([
            '' => [],
        ]);

        $result = $this->service->isContainer('');

        static::assertTrue($result);
    }

    public function testIsContainerIsCaseSensitive(): void
    {
        $this->setContainerConfiguration([
            'container_2col' => [],
        ]);

        // Exact match should return true
        static::assertTrue($this->service->isContainer('container_2col'));

        // Different case should return false
        static::assertFalse($this->service->isContainer('Container_2col'));
        static::assertFalse($this->service->isContainer('CONTAINER_2COL'));
    }
}
