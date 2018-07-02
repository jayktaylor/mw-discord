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
		if ($wikiPage->getTitle()->getNamespace() == NS_FILE) //the page is file, there is no need to trigger second notification of file's page creation
		{
			return;
		}

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
		if ($wikiPage->getTitle()->getNamespace() == NS_FILE) //the page is file, there is no need to trigger second notification of file's page deletion
		{
			return;
		}
		
		$message = "User `" . $user . "` deleted page `" . $wikiPage->getTitle()->getFullText() . "`";		
		
		if (empty($reason) == false) 
		{
			$message .= " (reason: `" .  $reason . "`)";
		}
		
		DiscordNotifications::Send($message);
	}
	
	static function onArticleUndelete($title, $create, $comment)
	{
		$message = "Deleted page `" . $title . "` restored";		
		
		if (empty($comment) == false) 
		{
			$message .= " (comment: `" .  $comment . "`)";
		}
		
		DiscordNotifications::Send($message);
	}
	
	static function onArticleProtectComplete ($wikiPage, $user, $protect, $reason, $moveonly) 
	{
		$message = "User `" . $user . "` changed protection of page `" . $wikiPage->getTitle()->getFullText() . "`";				
			
		if (empty($reason) == false) 
		{
			$message .= " (reason: `" .  $reason . "`)";
		}
		
		DiscordNotifications::Send($message);
	}	
	
	static function onUploadComplete($image) 
	{ 
	    global $wgUser;
		
		$isNewRevision = count($image->getLocalFile()->getHistory()) > 0;
		
		$message = "User `" . $wgUser . "` uploaded" . ($isNewRevision ? " new version of " : " " ) . "file `" . $image->getLocalFile()->getName() . "`";
		
		DiscordNotifications::Send($message);
	}
	
	static function onFileDeleteComplete($file, $oldimage, $article, $user, $reason)
	{
		$message = "User `" . $user . "` deleted file `" . $file->getName() . "`";				
			
		if (empty($reason) == false) 
		{
			$message .= " (reason: `" .  $reason . "`)";
		}
		
		DiscordNotifications::Send($message);
	}
	
	static function onLocalUserCreated($user, $autocreated) 
	{ 
		$message = "User `" . $user . "` registered";
		
		DiscordNotifications::Send($message);
	}
	
	static function onBlockIpComplete($block, $user)
	{
		$message = "User `" . $user . "` blocked user `" . $block->getTarget() . "`";				
			
		if (empty($block->mReason) == false) 
		{
			$message .= " with reason: `" .  $block->mReason . "`";
		}
		
		$message .= " (expires: `" . $block->mExpiry ."`)";
		
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
