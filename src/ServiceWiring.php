<?php

namespace MediaWiki\Extension\Discord;

use MediaWiki\MediaWikiServices;

return [
	'Discord.DiscordMessageSender' => static function ( MediaWikiServices $services ): DiscordMessageSender {
		return new DiscordMessageSender(
			$services->getHttpRequestFactory()
		);
	}
];
