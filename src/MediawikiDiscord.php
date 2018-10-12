<?php

final class MediawikiDiscord
{
	static function getUserText ($user)
	{
			global $wgServer, $wgScriptPath;

			$userUrl = $user->getUserPage()->getFullUrl( '', '', $proto = PROTO_HTTPS );

			$userPageLink = MediawikiDiscordUtils::CreateMarkdownLink ($user, "<" . $userUrl . ">");
			$userTalkLink = MediawikiDiscordUtils::CreateMarkdownLink ("t", "<" . $user->getTalkPage()->getFullURL( '', '', $proto = PROTO_HTTPS ) . ">");
			$userContributionsLink = MediawikiDiscordUtils::CreateMarkdownLink ("c", "<" . Title::newFromText("Special:Contributions/" . $user)->getFullURL( '', '', $proto = PROTO_HTTPS ) . ">");
			$userBlockLink = MediawikiDiscordUtils::CreateMarkdownLink ("b", "<" . Title::newFromText("Special:Block/" . $user)->getFullURL( '', '', $proto = PROTO_HTTPS ) . ">"); // prevent embed - see #5

			return sprintf("%s (%s|%s|%s)", $userPageLink, $userTalkLink, $userContributionsLink, $userBlockLink);
	}

	static function getPageText ($wikiPage, $links = true)
	{
			$title = $wikiPage->getTitle();
			
			$pageUrl = $title->getFullURL( '', '', $proto = PROTO_HTTPS );

			$pageLink = MediawikiDiscordUtils::CreateMarkdownLink ($title->getFullText(), $pageUrl);

			if ($links == true)
			{
					$revisionId = $wikiPage->getRevision()->getID();

					$editLink = MediawikiDiscordUtils::CreateMarkdownLink ('e', "<" . $title->getFullUrl("action=edit", '', $proto = PROTO_HTTPS ) . ">");
					$historyLink = MediawikiDiscordUtils::CreateMarkdownLink ('h', "<" . $title->getFullUrl("action=history", '', $proto = PROTO_HTTPS ) . ">");
					
					// need to use arrays here for the second parameter since mediawiki doesn't allow more than two query string parameters, but you can use arrays to specify more.
					$diffLink = MediawikiDiscordUtils::CreateMarkdownLink (MediawikiDiscord::translate("diff"), "<" . $title->getFullUrl("diff=prev", array("oldid" => $revisionId), $proto = PROTO_HTTPS ) . ">");
					$undoLink = MediawikiDiscordUtils::CreateMarkdownLink (MediawikiDiscord::translate("editundo"), "<" . $title->getFullUrl("action=edit", array("undoafter" => (int)($revisionId - 1), "undo" => $revisionId), $proto = PROTO_HTTPS ) . ">");

					return sprintf("%s (%s|%s) (%s, %s)", $pageLink, $editLink, $historyLink, $diffLink, $undoLink);
			}
			else
			{
					return $pageLink;
			}
	}
	
	static function getTitleText ($title)
	{
		return MediawikiDiscordUtils::CreateMarkdownLink ($title, "<" . $title->getFullURL( '', '', $proto = PROTO_HTTPS ) . ">");
	}
	
	static function getFileText ($file)
	{
		return MediawikiDiscordUtils::CreateMarkdownLink ($file->getName(), "<" . $file->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTPS ) . ">");
	}
	
	static function translate ($key, ...$parameters) 
	{
		global $wgDiscordNotificationsLanguage;
		
		if ($wgDiscordNotificationsLanguage != null) 
		{
			return wfMessage($key, $parameters)->inLanguage($wgDiscordNotificationsLanguage)->plain();
		} 
		else 
		{
			return wfMessage($key, $parameters)->inContentLanguage()->plain();
		}
	}
	
	static function isNotificationExcluded ($hook) 
	{
		global $wgDiscordExcludedNotifications;
	
		return in_array($hook, $wgDiscordExcludedNotifications);
	}
}

final class MediawikiDiscordHooks 
{	
	static function onPageContentSaveComplete ($wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId)
	{
		global $wgDiscordShowNullEdits, $wgDiscordShowBotEdits;

		if (MediawikiDiscord::isNotificationExcluded("onPageContentSaveComplete")) {
			return;
		}
		
		if ($status->value['new'] == true) { // Page was just created, there is no need to trigger second notification
			return;
		}

		if ( !$wgDiscordShowBotEdits && ( $user->isBot() ) ) { // Edit was from a bot
			return;
		}

		if ( !$wgDiscordShowNullEdits && ( !$revision || is_null( $status->getValue()['revision'] ) ) ) { // Edit was a null edit
      return;
    }

		if ($isMinor)  {
			$messageTranslationKey = "onPageContentSaveComplete_MinorEdit";
		} else {
			$messageTranslationKey = "onPageContentSaveComplete";
		}
							
		$message = MediawikiDiscord::translate($messageTranslationKey, MediawikiDiscord::getUserText($user), 
																	   MediawikiDiscord::getPageText($wikiPage));
							
		if (empty($summary) == false) {
			$message .= sprintf(" `%s`", 
						$summary);
		}
		try {
			$message .= sprintf(" (%+d)",
				$revision->getSize() - $revision->getPrevious()->getSize());
		} catch (Exception $e) {
			// this code is broken so let exceptions slide for now
		}
		
	    (new DiscordNotification($message))->Send();		
	}
	
	static function onPageContentInsertComplete ($wikiPage, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision) 
	{
		global $wgDiscordShowBotEdits;

		if (MediawikiDiscord::isNotificationExcluded("onPageContentInsertComplete")) 
		{
			return;
		}

		if ( !$wgDiscordShowBotEdits && ( $user->isBot() ) ) { // Edit was from a bot
			return;
		}
		
		if ($wikiPage->getTitle()->getNamespace() == NS_FILE) // The page is file, there is no need to trigger second notification of file's page creation
		{
			return;
		}

		$message = MediawikiDiscord::translate('onPageContentInsertComplete', MediawikiDiscord::getUserText($user), 
																			  MediawikiDiscord::getPageText($wikiPage, false));
																			  
		if (empty($summary) == false)
		{
			$message .= sprintf(" `%s`", 
						$summary);
		}

		$message .= sprintf(" (%d)",
			$revision->getSize());
																			  
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onTitleMoveComplete ($title, $newTitle, $user, $oldid, $newid, $reason, $revision) 
	{
		global $wgDiscordShowBotEdits;

		if (MediawikiDiscord::isNotificationExcluded("onTitleMoveComplete")) 
		{
			return;
		}

		if ( !$wgDiscordShowBotEdits && ( $user->isBot() ) ) { // Edit was from a bot
			return;
		}
		
		$message = MediawikiDiscord::translate('onTitleMoveComplete', MediawikiDiscord::getUserText($user), 
																	  MediawikiDiscord::getTitleText($title),
																	  MediawikiDiscord::getTitleText($newTitle));
																	  
		if (empty($reason) == false) 
		{
			$message .= sprintf(" `%s`", 
						$reason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onArticleDeleteComplete($wikiPage, $user, $reason)
	{
		global $wgDiscordShowBotEdits;

		if (MediawikiDiscord::isNotificationExcluded("onArticleDeleteComplete")) 
		{
			return;
		}

		if ( !$wgDiscordShowBotEdits && ( $user->isBot() ) ) { // Edit was from a bot
			return;
		}
		
		if ($wikiPage->getTitle()->getNamespace() == NS_FILE) // The page is file, there is no need to trigger second notification of file's page deletion
		{
			return;
		}
		
		$message = MediawikiDiscord::translate('onArticleDeleteComplete', MediawikiDiscord::getUserText($user), 
																		  MediawikiDiscord::getPageText($wikiPage, false));
																		  
		if (empty($reason) == false) 
		{
			$message .= sprintf(" `%s`", 
						$reason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onArticleUndelete($title, $create, $comment)
	{
		global $wgUser;

		if (MediawikiDiscord::isNotificationExcluded("onArticleUndelete")) 
		{
			return;
		}
		
		$message = MediawikiDiscord::translate('onArticleUndelete', MediawikiDiscord::getUserText($wgUser), MediawikiDiscord::getTitleText($title));	
														
		if (empty($comment) == false) 
		{
			$message .= sprintf(": `%s`", 
						$comment);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onArticleProtectComplete ($wikiPage, $user, $protect, $reason) 
	{
		global $wgDiscordShowBotEdits;

		if (MediawikiDiscord::isNotificationExcluded("onArticleProtectComplete")) 
		{
			return;
		}

		if ( !$wgDiscordShowBotEdits && ( $user->isBot() ) ) { // Edit was from a bot
			return;
		}
		
		$message = MediawikiDiscord::translate('onArticleProtectComplete', MediawikiDiscord::getUserText($user), 
																		   MediawikiDiscord::getPageText($wikiPage, false));
																		   
		if (empty($reason) == false) 
		{
			$message .= sprintf(" `%s`", 
						$reason);
		}
		
		
		$notification = new DiscordNotification($message);
		$notification->SetEmbedFields($protect);
	    $notification->Send();
	}	
	
	static function onUploadComplete($image) 
	{ 
		global $wgDiscordShowBotEdits;

		if (MediawikiDiscord::isNotificationExcluded("onUploadComplete")) 
		{
			return;
		}
		
			global $wgUser;

		if ( !$wgDiscordShowBotEdits && ( $wgUser->isBot() ) ) { // Edit was from a bot
			return;
		}
		
		$isNewRevision = count($image->getLocalFile()->getHistory()) > 0;
						
		if ($isNewRevision == true) 
		{
			$message = MediawikiDiscord::translate('onUploadComplete_NewVersion', MediawikiDiscord::getUserText($wgUser),
																				  MediawikiDiscord::getFileText($image->getLocalFile()));
		}	
		else
		{
			$message = MediawikiDiscord::translate('onUploadComplete', MediawikiDiscord::getUserText($wgUser),
																	   MediawikiDiscord::getFileText($image->getLocalFile()));
		}	
		
		$discordNotification = new DiscordNotification($message);
		$discordNotification->Send(); 
	}
	
	static function onFileDeleteComplete($file, $oldimage, $article, $user, $reason)
	{
		global $wgDiscordShowBotEdits;

		if (MediawikiDiscord::isNotificationExcluded("onFileDeleteComplete")) 
		{
			return;
		}

		if ( !$wgDiscordShowBotEdits && ( $user->isBot() ) ) { // Edit was from a bot
			return;
		}
		
		$message = MediawikiDiscord::translate('onFileDeleteComplete', MediawikiDiscord::getUserText($user), 
																	   MediawikiDiscord::getFileText($file));
																	   
		if (empty($reason) == false) 
		{
			$message .= sprintf(" `%s`", 
						$reason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onLocalUserCreated($user, $autocreated) 
	{ 
		if (MediawikiDiscord::isNotificationExcluded("onLocalUserCreated")) 
		{
			return;
		}
		
		$message = MediawikiDiscord::translate('onLocalUserCreated', MediawikiDiscord::getUserText($user));
													 
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onBlockIpComplete($block, $user)
	{
		if (MediawikiDiscord::isNotificationExcluded("onBlockIpComplete")) 
		{
			return;
		}

		if (($expires = strtotime($block->mExpiry))) 
		{
			$expiryMsg = sprintf(" %s", 
						date('d F Y H:i', $expires));
		} 
		else 
		{
			if ($block->mExpiry == "infinity") 
			{
				$expiryMsg = sprintf(" %s", 
							MediawikiDiscord::translate('infiniteblock'));	
			}
			else
			{
				$expiryMsg = sprintf(" (%s %s)", 
							MediawikiDiscord::translate('blocklist-expiry'), 
							$block->mExpiry );
			}			
		}
		
		$message = MediawikiDiscord::translate('onBlockIpComplete', MediawikiDiscord::getUserText($user),
																	MediawikiDiscord::getUserText($block->getTarget()),
																	$expiryMsg
																);

		if (empty($block->mReason) == false) 
		{
			$message .= sprintf(". %s: `%s`",
						MediawikiDiscord::translate('reason'),
						$block->mReason);
		}
		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onUnblockUserComplete($block, $user)
	{
		if (MediawikiDiscord::isNotificationExcluded("onUnblockUserComplete")) 
		{
			return;
		}
		
		$message = MediawikiDiscord::translate('onUnblockUserComplete', MediawikiDiscord::getUserText($user), 
																		MediawikiDiscord::getUserText($block->getTarget()));
																		
	    (new DiscordNotification($message))->Send();	
	}
	
	static function onUserGroupsChanged($user, array $added, array $removed, $performer, $reason)
	{
		if (MediawikiDiscord::isNotificationExcluded("onUserGroupsChanged")) 
		{
			return;
		}
		
		$message = MediawikiDiscord::translate('onUserGroupsChanged', MediawikiDiscord::getUserText($performer), MediawikiDiscord::getUserText($user));
		
		if (count($added) > 0) 
		{
			$message .= sprintf(" %s: `%s`.", 
						MediawikiDiscord::translate('added'), 
						join(', ', $added));
		}		
		
		if (count($removed) > 0) 
		{
			$message .= sprintf(" %s: `%s`.", 
						MediawikiDiscord::translate('removed'), 
						join(', ', $removed));
		}

		if ($reason) {
			$message .= sprintf(" %s: `%s`.", 
						MediawikiDiscord::translate('reason'), 
						$reason);
		}
	
	    (new DiscordNotification($message))->Send();	
	}
}

final class DiscordNotification
{
	private $message;
	private $embedImageUrl;
	private $embedFields;
	
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
	
	public function SetEmbedFields ($embedFields) 
	{
		$this->embedFields = $embedFields;
	}
	
	public function Send ()
	{
		global $wgDiscordWebhookUrl;
		global $wgSitename;

		if ( empty($wgDiscordWebhookUrl) ) {
			return;
		}
		
		$userName = $wgSitename;
						
		if (strlen($userName) >= 32) //32 characters is a limit of Discord usernames
		{
			$userName = substr($userName, 0, -(strlen($userName) - 32)); //if the wiki's name is too long, just remove last characters
		}
		
		$json = new stdClass();
		$json->content = $this->message;	
		$json->username = $userName;
		
		if ($this->embedImageUrl != null)
		{
			$json->embeds[0]->image->url = $this->embedImageUrl;
		}
		
		if ($this->embedFields != null)
		{
			foreach ($this->embedFields as $field => $value)
			{
				$json->embeds[0]->fields[] = 
				[ 
					"name" => ucfirst(MediawikiDiscord::translate("restriction-" . $field)), 
					"value" => MediawikiDiscord::translate("group-" . (empty($value) ? "user" : $value)),
					"inline" => "true" 
				];
			}			
		}
		
		$data = array
		(
			'http' => array
			(				
				'method'  => 'POST',
				'header'  => "Content-Type: application/x-www-form-urlencoded\n",
				'content' => json_encode($json)
			)
		);

		file_get_contents($wgDiscordWebhookUrl, false, stream_context_create($data));	
	}
}

?>
