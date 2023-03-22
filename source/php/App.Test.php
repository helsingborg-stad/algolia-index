<?php

use \AlgoliaIndex\Helper\Options as Options;

class AppTest extends WP_UnitTestCase
{
    public function testThatisConfiguredReturnsFalseWhenNotConfigured()
    {
        // Given
        $sut = Options::isConfigured();

        // Then
        $this->assertFalse($sut);
    }
}
