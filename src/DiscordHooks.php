<?php
/**
 * Hooks for the Discord extension
 *
 * @file
 * @ingroup Extensions
 */
class DiscordHooks {
	/**
	 * Called when a page is created or edited
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 */
	public static function onPageContentSaveComplete( &$wikiPage, &$user, $content, $summary, $isMinor, $isWatch, $section, &$flags, $revision, &$status, $baseRevId, $undidRevId ) {
		global $wgDiscordNoBots, $wgDiscordNoMinor, $wgDiscordNoNull;

		if ( DiscordUtils::isDisabled( 'PageContentSaveComplete', $wikiPage->getTitle()->getNamespace() ) ) {
			return true;
		}

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

		if ( $wikiPage->getTitle()->inNamespace( NS_FILE ) ) {
			// Don't continue, it's a file which onUploadComplete will handle instead
			return true;
		}

		$msg = wfMessage( 'discord-edit', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $wikiPage->getTitle(), $wikiPage->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			DiscordUtils::createRevisionText( $revision ),
			( $summary ? ('`' . DiscordUtils::truncateText( $summary ) . '`' ) : '' ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 */
	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, $content, LogEntry $logEntry, $archivedRevisionCount ) {
		global $wgDiscordNoBots, $wgDiscordNoMinor, $wgDiscordNoNull;

		if ( DiscordUtils::isDisabled( 'ArticleDeleteComplete', $article->getTitle()->getNamespace() ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articledelete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ),
			$archivedRevisionCount)->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page's revisions are restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleUndelete
	 */
	public static function onArticleUndelete( Title $title, $create, $comment, $oldPageId, $restoredPages ) {
		global $wgUser;

		if ( DiscordUtils::isDisabled( 'ArticleUndelete', $title->getNamespace() ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-articleundelete', DiscordUtils::createUserLinks( $wgUser ),
			($create ? '' : wfMessage( 'discord-undeleterev' )->text() ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $comment ? ('`' . DiscordUtils::truncateText( $comment ) . '`' ) : '' ))->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called after committing revision visibility changes to the database
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleRevisionVisibilitySet
	 */
	public static function onArticleRevisionVisibilitySet( &$title, $ids, $visibilityChangeMap ) {
		global $wgUser;

		if ( DiscordUtils::isDisabled( 'ArticleRevisionVisibilitySet', $title->getNamespace() ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-revvisibility', DiscordUtils::createUserLinks( $wgUser ),
			count($visibilityChangeMap),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page is protected (or unprotected)
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	public static function onArticleProtectComplete( &$article, &$user, $protect, $reason ) {
		global $wgDiscordNoBots;

		if ( DiscordUtils::isDisabled( 'ArticleProtectComplete', $article->getTitle()->getNamespace() ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articleprotect', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ),
			implode(", ", $protect) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page is moved
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 */
	public static function onTitleMoveComplete( Title &$title, Title &$newTitle, User $user, $oldid, $newid, $reason, Revision $revision ) {
		global $wgDiscordNoBots;

		if ( DiscordUtils::isDisabled( 'TitleMoveComplete', $title->getNamespace() ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-titlemove', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			DiscordUtils::createMarkdownLink( $newTitle, $newTitle->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ),
			DiscordUtils::createRevisionText( $revision ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user is created
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 */
	public static function onLocalUserCreated( $user, $autocreated ) {
		if ( DiscordUtils::isDisabled( 'LocalUserCreated', NULL ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-localusercreated', DiscordUtils::createUserLinks( $user ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user is blocked
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
	 */
	public static function onBlockIpComplete( Block $block, User $user ) {
		if ( DiscordUtils::isDisabled( 'BlockIpComplete', NULL ) ) {
			return true;
		}

		$expiry = $block->getExpiry();
		if ($expires = strtotime($expiry)) {
			$expiryMsg = sprintf('%s', date( wfMessage( 'discord-blocktimeformat' )->text(), $expires));
		} else {
			$expiryMsg = $expiry;
		}

		$msg = wfMessage( 'discord-blockipcomplete', DiscordUtils::createUserLinks( $user ), DiscordUtils::createUserLinks( $block->getTarget() ),
			( $block->mReason ? ('`' . DiscordUtils::truncateText( $block->mReason ) . '`' ) : '' ),
			$expiryMsg )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user is unblocked
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
	 */
	public static function onUnblockUserComplete( Block $block, User $user ) {
		if ( DiscordUtils::isDisabled( 'UnblockUserComplete', NULL ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-unblockusercomplete', DiscordUtils::createUserLinks( $user ), DiscordUtils::createUserLinks( $block->getTarget() ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user's rights are changed
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 */
	public static function onUserGroupsChanged( User $user, array $added, array $removed, $performer, $reason ) {
		if ( DiscordUtils::isDisabled( 'UserGroupsChanged', NULL ) ) {
			return true;
		}

		if ($performer === false) {
			// Rights were changed by autopromotion, do nothing
			return true;
		}

		$msg = wfMessage( 'discord-usergroupschanged', DiscordUtils::createUserLinks( $performer ),
			DiscordUtils::createUserLinks( $user ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ),
			( ( count($added) > 0 ) ? ( '+ ' . join(', ', $added) ) : ''),
			( ( count($removed) > 0 ) ? ( '- ' . join(', ', $removed) ) : '' ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a file upload is complete
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete
	 */
	public static function onUploadComplete( &$image ) {
		if ( DiscordUtils::isDisabled( 'UploadComplete', NS_FILE ) ) {
			return true;
		}

		$lf = $image->getLocalFile();
		$user = $lf->getUser( $type = 'object' ); // only supported in MW 1.31+
		$comment = $lf->getDescription();
		$isNewRevision = count($lf->getHistory()) > 0;

		$msg = wfMessage( 'discord-uploadcomplete', DiscordUtils::createUserLinks( $user ),
			( $isNewRevision ? wfMessage( 'discord-uploadnewver' )->text() : '' ),
			DiscordUtils::createMarkdownLink( $lf->getName(), $lf->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ), 
			( $comment ? ('`' . DiscordUtils::truncateText( $comment ) . '`' ) : '' ),
			DiscordUtils::formatBytes($lf->getSize()),
			$lf->getWidth(),
			$lf->getHeight(),
			$lf->getMimeType() )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a file is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileDeleteComplete
	 */
	public static function onFileDeleteComplete( $file, $oldimage, $article, $user, $reason ) {
		if ( DiscordUtils::isDisabled( 'FileDeleteComplete', NS_FILE ) ) {
			return true;
		}

		if ( $article ) {
			// Entire page was deleted, onArticleDeleteComplete will handle this
			return true;
		}

		$msg = wfMessage( 'discord-filedeletecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $file->getName(), $file->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a file is restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUndeleteComplete
	 */
	public static function onFileUndeleteComplete( $title, $fileVersions, $user, $reason ) {
		if ( DiscordUtils::isDisabled( 'FileUndeleteComplete', NS_FILE ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-fileundeletecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ) )->text();
		DiscordUtils::handleDiscord($msg);
		return true;
	}
}
