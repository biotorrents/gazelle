<?php

declare(strict_types=1);


/**
 * AppTest
 */

final class AppTest extends PHPUnit\Framework\TestCase
{
    /**
     * @covers App::go()
     */
    public function testGo(): void
    {
        $app = App::go();

        $this->assertSame(
            App::go(),
            $app
        );
    }
}
