<?php 

use \AlgoliaIndex\Index as Index;

class IndexTest extends WP_UnitTestCase
{

  private $targetTestClass = null; 

  public function set_up()
  {
    $this->targetTestClass = new Index(false);
  }

  public function invokeMethod(&$object, $methodName, array $parameters = array())
  {
      $reflection = new \ReflectionClass(get_class($object));
      $method = $reflection->getMethod($methodName);
      $method->setAccessible(true);

      return $method->invokeArgs($object, $parameters);
  }

  public function testThatShouldIndexIsEnabledByDefault()
  {
      // Given
      $post = self::factory()->post->create_and_get([
        "post_title" => "Test Post"
      ]);

      // When
      $isIndexable = $this->invokeMethod(
        $this->targetTestClass,
        'shouldIndex',
        [$post]
      );

      // Then
      $this->assertTrue($isIndexable);
  }

  public function testThatShouldIndexIsDisabledByMeta()
  {
      // Given
      $post = self::factory()->post->create_and_get([
        "post_title" => "Test Post",
        "meta_input" => [
          "exclude_from_search" => "1"
        ]
      ]);

      // When
      $isIndexable = $this->invokeMethod(
        $this->targetTestClass,
        'shouldIndex',
        [$post]
      );

      // Then
      $this->assertTrue($isIndexable);
  }

  public function testThatASmallRecordIsentTooLarge() {
    
    // Given
    $post = self::factory()->post->create_and_get([
      "post_title" => "Test Post",
      "post_content" => str_repeat("1", 5000)
    ]);

    // When
    $recordTooLarge = $this->invokeMethod(
      $this->targetTestClass,
      'recordToLarge',
      [$post]
    );

    // Then
    $this->assertFalse($recordTooLarge);
  }

  public function testThatALargeRecordIsToLarge() {
    
    // Given
    $post = self::factory()->post->create_and_get([
      "post_title" => "Test Post",
      "post_content" => str_repeat("1", 20000)
    ]);

    // When
    $recordTooLarge = $this->invokeMethod(
      $this->targetTestClass,
      'recordToLarge',
      [$post]
    );

    // Then
    $this->assertTrue($recordTooLarge);
  }

}
