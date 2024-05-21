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
      $this->assertFalse($isIndexable);
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

  public function testThatAExerptIsShortened() { 
     
    // Given
    $excerpt = str_repeat("excerpt ", 100);
    $postContent = str_repeat("excerpt content ", 1000) . "<!-- more -->" . str_repeat("excerpt content ", 10000); 
    $post = self::factory()->post->create_and_get([
      "post_title" => "Test Post",
      "post_content" => $postContent,
      "post_excerpt" => $excerpt
    ]);

    // When
    $truncatedExcerpt = $this->invokeMethod(
      $this->targetTestClass,
      'getTheExcerpt',
      [$post]
    );

    // Then
    $this->assertnotEquals($truncatedExcerpt, $excerpt, "Truncated excerpt is equal to excerpt.");
  }

  public function testThatAExerptIsShortenedWhenNoExcerptDefined() { 
     
    // Given
    $excerpt = str_repeat("excerpt ", 100);
    $postContent = str_repeat("excerpt content ", 1000) . "<!-- more -->" . str_repeat("excerpt content ", 10000); 
    $post = self::factory()->post->create_and_get([
      "post_title" => "Test Post",
      "post_content" => $postContent
    ]);

    // When
    $truncatedExcerpt = $this->invokeMethod(
      $this->targetTestClass,
      'getTheExcerpt',
      [$post]
    );

    // Then
    $this->assertnotEquals($truncatedExcerpt, $excerpt, "Truncated excerpt is equal to excerpt.");
  }

  public function testThatABlockPostIsGeneratingAExcerpt() { 
     
    // Given
    $post = self::factory()->post->create_and_get([
      "post_title" => "Gutenberg Test Post",
      "post_content" => '<!--wp:columns--><divclass="wp-block-columns"><!--wp:column--><divclass="wp-block-column"><!--wp:paragraph--><p>Column1</p><!--/wp:paragraph--></div><!--/wp:column--><!--wp:column--><divclass="wp-block-column"><!--wp:paragraph--><p>Column2</p><!--/wp:paragraph--></div><!--/wp:column--><!--wp:column--><divclass="wp-block-column"><!--wp:paragraph--><p>Column3</p><!--/wp:paragraph--></div><!--/wp:column--></div><!--/wp:columns-->'
    ]);

    // When
    $truncatedExcerpt = $this->invokeMethod(
      $this->targetTestClass,
      'getTheExcerpt',
      [$post]
    );

    // Then
    $this->assertNotEmpty($truncatedExcerpt);
  }

  public function testThatMalformedUTF8ContentIsFixed() { 
     
    // Given
    $post = [
      "post_title" => "Test Post",
      "post_content" => "This is a test post with a malformed UTF8 character: \x80"
    ];

    // When
    $fixedContent = $this->invokeMethod(
      $this->targetTestClass,
      'utf8ize',
      [$post]
    );

    $this->assertEquals($fixedContent["post_content"], "This is a test post with a malformed UTF8 character: ");
  }    

}
