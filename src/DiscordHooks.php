<?php
/**
 * Hooks for Discord extension
 *
 * @file
 * @ingroup Extensions
 */
class DiscordHooks {
	/**
	 * Called when a page is created or edited
	 */
	public static function onPageContentSaveComplete( &$wikiPage, &$user, $content, $summary, $isMinor, $isWatch, $section, &$flags, $revision, &$status, $baseRevId, $undidRevId ) {
		global $wgDiscordNoBots, $wgDiscordNoMinor, $wgDiscordNoNull;

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot edit
			return true;
		}

		if ( $wgDiscordNoMinor && $isMinor ) {
			// Don't continue, this is a minor edit
			return true;
		}

		if ( $wgDiscordNoNull && ( !$revision || is_null( $status->getValue()['revision'] ) ) ) {
			// Don't continue, this is a null edit
			return true;
		}

		$msg .= DiscordUtils::createUserLinks( $user ) . ' edited ';
		$msg .= DiscordUtils::createMarkdownLink( $wikiPage->getTitle(), $wikiPage->getTitle()->getFullUrl() );
		$msg .= ( $summary ? (' `' . $summary . '` ' ) : ' ' ) . DiscordUtils::createRevisionText( $revision );
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page is deleted
	 */
	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, $content, LogEntry $logEntry, $archivedRevisionCount ) {
		global $wgDiscordNoBots, $wgDiscordNoMinor, $wgDiscordNoNull;

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg .= DiscordUtils::createUserLinks( $user ) . ' deleted ';
		$msg .= DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullUrl() );
		$msg .= ( $reason ? (' `' . $reason . '` ' ) : ' ' ) . "($archivedRevisionCount revisions deleted)";
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page's revisions are restored
	 */
	public static function onArticleUndelete( Title $title, $create, $comment, $oldPageId, $restoredPages ) {
		global $wgUser;

		$msg .= DiscordUtils::createUserLinks( $wgUser ) . ' restored ' . ($create ? ( '' ) : 'revisions for ' );
		$msg .= DiscordUtils::createMarkdownLink( $title, $title->getFullUrl() );
		$msg .= ( $comment ? (' `' . $comment . '`' ) : '' );
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called after committing revision visibility changes to the database
	 */
	public static function onArticleRevisionVisibilitySet( &$title, $ids, $visibilityChangeMap ) {
		global $wgUser;

		$msg .= DiscordUtils::createUserLinks( $wgUser ) . ' changed visibility of ';
		$msg .= count($visibilityChangeMap) . ' revisions on ';
		$msg .= DiscordUtils::createMarkdownLink( $title, $title->getFullUrl() );
		DiscordUtils::handleDiscord($msg);
		return true;
	}
}
