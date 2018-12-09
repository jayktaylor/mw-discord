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

		if ( $wikiPage->getTitle()->inNamespace( NS_FILE ) ) {
			// Don't continue, it's a file which onUploadComplete will handle instead
			return true;
		}

		$msg .= DiscordUtils::createUserLinks( $user ) . ' edited ';
		$msg .= DiscordUtils::createMarkdownLink( $wikiPage->getTitle(), $wikiPage->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) );
		$msg .= ' ' . DiscordUtils::createRevisionText( $revision ) . ( $summary ? (' `' . $summary . '` ' ) : '' );
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
		$msg .= DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) );
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
		$msg .= DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) );
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
		$msg .= DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) );
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page is protected (or unprotected)
	 */
	public static function onArticleProtectComplete( &$article, &$user, $protect, $reason ) {
		global $wgDiscordNoBots;

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg .= DiscordUtils::createUserLinks( $user ) . ' changed protection of ';
		$msg .= DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) );
		$msg .= ( $reason ? (' `' . $reason . '` ' ) : ' ' ) . "(" . (implode(", ", $protect)) . ")";
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a page is moved
	 */
	public static function onTitleMoveComplete( Title &$title, Title &$newTitle, User $user, $oldid, $newid, $reason, Revision $revision ) {
		global $wgDiscordNoBots;

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg .= DiscordUtils::createUserLinks( $user ) . ' moved ';
		$msg .= DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ) . ' to ';
		$msg .= DiscordUtils::createMarkdownLink( $newTitle, $newTitle->getFullUrl( '', '', $proto = PROTO_HTTP ) );
		$msg .= ( $reason ? (' `' . $reason . '` ' ) : ' ' ) . DiscordUtils::createRevisionText( $revision );
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user is created
	 */
	public static function onLocalUserCreated( $user, $autocreated ) {
		$msg .= DiscordUtils::createUserLinks( $user ) . ' registered';
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user is blocked
	 */
	public static function onBlockIpComplete( Block $block, User $user ) {
		$expiry = $block->getExpiry();
		if ($expires = strtotime($expiry)) {
			$expiryMsg = sprintf('%s', date('d F Y H:i', $expires));
		} else {
			$expiryMsg = $expiry;
		}

		$msg .= DiscordUtils::createUserLinks( $user ) . ' blocked ';
		$msg .= DiscordUtils::createUserLinks( $block->getTarget() );
		$msg .= ( $block->mReason ? (' `' . $block->mReason . '` ' ) : ' ' ) . "($expiryMsg)";
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user is unblocked
	 */
	public static function onUnblockUserComplete( Block $block, User $user ) {
		$msg .= DiscordUtils::createUserLinks( $user ) . ' unblocked ';
		$msg .= DiscordUtils::createUserLinks( $block->getTarget() );
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a user's rights are changed
	 */
	public static function onUserGroupsChanged( User $user, array $added, array $removed, $performer, $reason ) {
		if ($performer === false) {
			// Rights were changed by autopromotion, do nothing
			return true;
		}

		$msg .= DiscordUtils::createUserLinks( $performer ) . ' changed rights of ';
		$msg .= DiscordUtils::createUserLinks( $user );
		$msg .= ( $reason ? (' `' . $reason . '` ' ) : ' ' );
		$msg .= ( ( count($added) > 0 ) ? ('(added: ' . join(', ', $added) . ') ') : ' ');
		$msg .= ( ( count($removed) > 0 ) ? ('(removed: ' . join(', ', $removed) . ')') : '');
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a file upload is complete
	 */
	public static function onUploadComplete( &$image ) {
		$lf = $image->getLocalFile();
		$user = $lf->getUser( $type = 'object' ); // only supported in MW 1.31+
		$comment = $lf->getDescription();
		$isNewRevision = count($lf->getHistory()) > 0;
		$msg .= DiscordUtils::createUserLinks( $user ) . ' uploaded ' . ( $isNewRevision ? 'new version of ' : '' );
		$msg .= DiscordUtils::createMarkdownLink( $lf->getName(), $lf->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) );
		$msg .= ( $comment ? (' `' . $comment . '` ' ) : ' ' );
		$msg .= '(' . DiscordUtils::formatBytes($lf->getSize()) . ', ' . $lf->getWidth() . 'x' . $lf->getHeight() . ', ' . $lf->getMimeType() . ')';
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a file is deleted
	 */
	public static function onFileDeleteComplete( $file, $oldimage, $article, $user, $reason ) {
		if ( $article ) {
			// Entire page was deleted, onArticleDeleteComplete will handle this
			return true;
		}

		$msg .= DiscordUtils::createUserLinks( $user ) . ' deleted a version of file ';
		$msg .= DiscordUtils::createMarkdownLink( $file->getName(), $file->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) );
		$msg .= ( $reason ? (' `' . $reason . '` ' ) : ' ' );
		DiscordUtils::handleDiscord($msg);
		return true;
	}

	/**
	 * Called when a file is deleted
	 */
	public static function onFileUndeleteComplete( $title, $fileVersions, $user, $reason ) {
		$msg .= DiscordUtils::createUserLinks( $user ) . ' restored some versions of file ';
		$msg .= DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) );
		$msg .= ( $reason ? (' `' . $reason . '` ' ) : ' ' );
		DiscordUtils::handleDiscord($msg);
		return true;
	}
}
