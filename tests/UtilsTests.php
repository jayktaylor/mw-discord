<?php

require "src/Utils.php";

class UtilsTests extends PHPUnit_Framework_TestCase {
 
    public function testCreateMarkdownLink()
    {
        $link = MediawikiDiscordUtils::CreateMarkdownLink("Link", "https://example.com");
        $this->assertEquals("[Link](https://example.com)", $link);
    }
	
	public function testRemoveMultipleSlashes()
    {
        $url = MediawikiDiscordUtils::RemoveMultipleSlashes("https://example.com/page/page2//page3/page4//page5//");
        $this->assertEquals("https://example.com/page/page2/page3/page4/page5/", $url);
    }
}

?>
