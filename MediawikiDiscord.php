<?php

final class MediawikiDiscordHooks
{
	static function onPageContentSaveComplete ($wikiPage, $user)
	{
		$message = "User `" . $user . "` saved changes on page `" . $wikiPage->getTitle()->getFullText() . "`";

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
