<?php
/**
 * Hooks for Discord extension
 *
 * @file
 * @ingroup Extensions
 */
class DiscordHooks {
	public static function onPageContentSaveComplete( &$wikiPage, &$user, $content, $summary, $isMinor, $revision ) {
		DiscordUtils::handleDiscord('memes');
		return true;
	}
}
