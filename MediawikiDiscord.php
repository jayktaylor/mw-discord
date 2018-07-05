<?php

final class MediawikiDiscord
{
	static function getUserText ($user) 
	{
		return MediawikiDiscordUtils::CreateMarkdownLink ($user, $user->getUserPage()->getFullUrl());
	}
	
	static function getPageText ($wikiPage) 
	{
		return MediawikiDiscordUtils::CreateMarkdownLink ($wikiPage->getTitle()->getFullText(), $wikiPage->getTitle()->getFullURL());
	}
	
	static function getTitleText ($title)
	{
		return MediawikiDiscordUtils::CreateMarkdownLink ($title, $title->getFullURL());
	}
	
	static function getFileText ($file)
	{
		return MediawikiDiscordUtils::CreateMarkdownLink ($file->getName(), $file->getTitle()->getFullUrl());
	}
}

final class MediawikiDiscordHooks 
{	
	static function onPageContentSaveComplete ($wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId)
	{		
		if ($status->value['new'] == true) //page is just created, there is no need to trigger second notification
		{
			return;
		}
					
		$message = wfMessage('onPageContentSaveComplete', MediawikiDiscord::getUserText($user), 
														  MediawikiDiscord::getPageText($wikiPage))->plain();			
														  
		if (empty($summary) == false)
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('summary')->plain(), 
						$summary);
		}
		
	    (new DiscordNotification($message))->Send();		
	}
	
	static function onPageContentInsertComplete ($wikiPage, $user) 
	{
		if ($wikiPage->getTitle()->getNamespace() == NS_FILE) //the page is file, there is no need to trigger second notification of file's page creation
		{
			return;
		}

		$message = wfMessage('onPageContentInsertComplete', MediawikiDiscord::getUserText($user), 
														    MediawikiDiscord::getPageText($wikiPage))->plain();			
														  
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onTitleMoveComplete ($title, $newTitle, $user, $oldid, $newid, $reason, $revision) 
	{
		$message = wfMessage('onTitleMoveComplete', MediawikiDiscord::getUserText($user), 
												    MediawikiDiscord::getTitleText($title),
													MediawikiDiscord::getTitleText($newTitle))->plain();			
															
		if (empty($reason) == false) 
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('mergehistory-reason')->plain(), 
						$reason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onArticleDeleteComplete($wikiPage, $user, $reason)
	{
		if ($wikiPage->getTitle()->getNamespace() == NS_FILE) //the page is file, there is no need to trigger second notification of file's page deletion
		{
			return;
		}
		
		$message = wfMessage('onArticleDeleteComplete', MediawikiDiscord::getUserText($user), 
														MediawikiDiscord::getPageText($wikiPage))->plain();		
															
		if (empty($reason) == false) 
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('mergehistory-reason')->plain(), 
						$reason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onArticleUndelete($title, $create, $comment)
	{
		$message = wfMessage('onArticleUndelete', MediawikiDiscord::getTitleText($title))->plain();		
														
		if (empty($comment) == false) 
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('import-comment')->plain(), 
						$comment);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onArticleProtectComplete ($wikiPage, $user, $protect, $reason, $moveonly) 
	{
		$message = wfMessage('onArticleProtectComplete', MediawikiDiscord::getUserText($user), 
														 MediawikiDiscord::getPageText($wikiPage))->plain();		
															
		if (empty($reason) == false) 
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('mergehistory-reason')->plain(), 
						$reason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}	
	
	static function onUploadComplete($image) 
	{ 
	    global $wgUser;
		
		$isNewRevision = count($image->getLocalFile()->getHistory()) > 0;
						
		if ($isNewRevision == true) 
		{
			$message = wfMessage('onUploadComplete_NewVersion', MediawikiDiscord::getUserText($wgUser),
													 MediawikiDiscord::getFileText($image->getLocalFile()))->plain();
		}	
		else
		{
			$message = wfMessage('onUploadComplete', MediawikiDiscord::getUserText($wgUser),
													 MediawikiDiscord::getFileText($image->getLocalFile()))->plain();
		}	
		
		$discordNotification = new DiscordNotification($message);
		
		$mimeType = $image->getLocalFile()->getMimeType();
		
		if (($mimeType == "image/jpeg") 
		||  ($mimeType == "image/png")
		||  ($mimeType == "image/gif")
		||  ($mimeType == "image/webp"))
		{				
			$imageUrl = MediawikiDiscordUtils::RemoveMultipleSlashes($image->getLocalFile()->getFullUrl());
			
			$discordNotification->SetEmbedImage($imageUrl);
		}
			
		$discordNotification->Send(); 
	}
	
	static function onFileDeleteComplete($file, $oldimage, $article, $user, $reason)
	{
		$message = wfMessage('onFileDeleteComplete', MediawikiDiscord::getUserText($user), 
												     MediawikiDiscord::getFileText($file))->plain();		
														 
		if (empty($reason) == false) 
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('mergehistory-reason')->plain(), 
						$reason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onLocalUserCreated($user, $autocreated) 
	{ 
		$message = wfMessage('onLocalUserCreated', MediawikiDiscord::getUserText($user))->plain();	
													 
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onBlockIpComplete($block, $user)
	{
		$message = wfMessage('onBlockIpComplete', MediawikiDiscord::getUserText($user), 
												  MediawikiDiscord::getUserText($block->getTarget()))->plain();		
													 
		if (empty($block->mReason) == false) 
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('mergehistory-reason')->plain(), 
						$block->mReason);
		}
			
		if (($expires = strtotime($block->mExpiry))) 
		{
			$message .= sprintf(" (%s `%s`)", 
						wfMessage('blocklist-expiry')->plain(), 
						date('Y-m-d H:i:s', $expires));
		} 
		else 
		{
			if ($block->mExpiry == "infinity") 
			{
				$message .= sprintf(" (`%s`)", 
							wfMessage('infiniteblock')->plain());	
			}
			else
			{
				$message .= sprintf(" (%s `%s`)", 
							wfMessage('blocklist-expiry')->plain(), 
							$block->mExpiry );
			}			
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onUnblockUserComplete($block, $user)
	{
		$message = wfMessage('onUnblockUserComplete', MediawikiDiscord::getUserText($user), 
													  MediawikiDiscord::getUserText($block->getTarget()))->plain();
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onUserRights($user, array $addedGroups, array $removedGroups)
	{
		$message = wfMessage('onUserRights', MediawikiDiscord::getUserText($user))->plain();		
													  
		if (count($addedGroups) > 0) 
		{
			$message .= sprintf(" %s: `%s`", 
						wfMessage('added')->plain(), 
						join(', ', $addedGroups));
		}		
		
		if (count($removedGroups) > 0) 
		{
			$message .= sprintf(" %s: `%s`", 
						wfMessage('removed')->plain(), 
						join(', ', $removedGroups));
		}	
	
	    (new DiscordNotification($message))->Send();	
	}
}

final class DiscordNotification
{
	private $message;
	private $embedImageUrl;
	
	public function __construct($message) 
	{
        $this->message = $message;
    }
	
	public function SetMessage ($message) 
	{
		$this->message = $message;
	}
	
	public function SetEmbedImage ($embedImageUrl)
	{
		$this->embedImageUrl = $embedImageUrl;
	}
	
	public function Send ()
	{
		global $wgDiscordWebhookUrl;
		global $wgSitename;
		
		$userName = $wgSitename;
						
		if (strlen($userName) >= 32) //32 characters is a limit of Discord usernames
		{
			$userName = substr($userName, 0, -(strlen($userName) - 32)); //if the wiki's name is too long, just remove last characters
		}
		
		$json->content = $this->message;	
		$json->username = $userName;
		
		if ($this->embedImageUrl != null)
		{
			$json->embeds[0]->image->url = $this->embedImageUrl;
		}
		
		$data = array
		(
			'http' => array
			(				
				'method'  => 'POST',
				'content' => json_encode($json)
			)
		);

		file_get_contents($wgDiscordWebhookUrl, false, stream_context_create($data));	
	}
}

?>
