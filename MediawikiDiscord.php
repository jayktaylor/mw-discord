<?php

final class MediawikiDiscordHooks 
{
	static function onPageContentSaveComplete ($wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId)
	{		
		if ($status->value['new'] == true) //page is just created, there is no need to trigger second notification
		{
			return;
		}
			
		$message = "User `" . $user . "` saved changes on page `" . $wikiPage->getTitle()->getFullText() . "`";		
		
		DiscordNotifications::Send($message);
	}
	
	static function onPageContentInsertComplete ($wikiPage, $user) 
	{
		$message = "User `" . $user . "` created new page `" . $wikiPage->getTitle()->getFullText() . "`";		
		
		DiscordNotifications::Send($message);
	}
	
	static function onTitleMoveComplete ($title, $newTitle, $user, $oldid, $newid, $reason, $revision) 
	{
		$message = "User `" . $user . "` moved page `" . $title . "` to `" . $newTitle . "`";		
		
		if (empty($reason) == false) 
		{
			$message .= " (reason: `" .  $reason . "`)";
		}
		
		DiscordNotifications::Send($message);
	}
	
	static function onArticleDeleteComplete($wikiPage, $user, $reason)
	{
		$message = "User `" . $user . "` deleted page `" . $wikiPage->getTitle()->getFullText() . "`";		
		
		if (empty($reason) == false) 
		{
			$message .= " (reason: `" .  $reason . "`)";
		}
		
		DiscordNotifications::Send($message);
	}
}

final class DiscordNotifications
{
	public static function Send ($message)
	{
		global $wgDiscordWebhookUrl;

		$content = '{ "content": "' . $message . '" }';
				
		$data = array
		(
			'http' => array
			(				
				'method'  => 'POST',
				'content' => $content
			)
		);

		file_get_contents($wgDiscordWebhookUrl, false, stream_context_create($data));	
	}
}

?>
