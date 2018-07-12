<?php

final class MediawikiDiscordUtils
{
	public static function CreateMarkdownLink ($text, $url) 
	{
		return "[" . $text . "]" . "(" . self::EncodeUrl($url) . ")";
	} 
	
	public static function EncodeUrl($url)
	{
		$url = str_replace(" ", "%20", $url);
		$url = str_replace("(", "%28", $url);
		$url = str_replace(")", "%29", $url);
		
		return $url;
	}
	
	public static function RemoveMultipleSlashes ($url)
	{
		return preg_replace('/([^:])(\/{2,})/', '$1/', $url);
	}
}

?>
