<?php

declare(strict_types=1);


/**
 * AppTest
 */

final class AppTest extends PHPUnit\Framework\TestCase
{
    /**
     * @covers \Gazelle\App::go()
     */
    public function testGo(): void
    {
        $app = \Gazelle\App::go();

        $this->assertSame(
            \Gazelle\App::go(),
            $app
        );
    }
}
