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

		if ( DiscordUtils::isDisabled( 'PageContentSaveComplete', $wikiPage->getTitle()->getNamespace(), $user ) ) {
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

		if ( $wikiPage->getTitle()->inNamespace( NS_FILE ) && is_null( $revision->getPrevious() ) ) {
			// Don't continue, it's a new file which onUploadComplete will handle instead
			return true;
		}

		$msgKey = 'discord-edit';

		$isNew = $status->value['new'];
		if ($isNew == 1) { // is a new page
			$msgKey = 'discord-create';
		}

		$msg = wfMessage( $msgKey, DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $wikiPage->getTitle(), $wikiPage->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			DiscordUtils::createRevisionText( $revision ),
			( $summary ? ('`' . DiscordUtils::truncateText( $summary ) . '`' ) : '' ) )->plain();
		DiscordUtils::handleDiscord(':pencil2:', $msg);
		return true;
	}

	/**
	 * Called when a page is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 */
	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, $content, LogEntry $logEntry, $archivedRevisionCount ) {
		global $wgDiscordNoBots;

		if ( DiscordUtils::isDisabled( 'ArticleDeleteComplete', $article->getTitle()->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articledelete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ),
			$archivedRevisionCount)->plain();
		DiscordUtils::handleDiscord(':wastebasket:', $msg);
		return true;
	}

	/**
	 * Called when a page's revisions are restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleUndelete
	 */
	public static function onArticleUndelete( Title $title, $create, $comment, $oldPageId, $restoredPages ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'ArticleUndelete', $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articleundelete', DiscordUtils::createUserLinks( $user ),
			($create ? '' : wfMessage( 'discord-undeleterev' )->text() ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $comment ? ('`' . DiscordUtils::truncateText( $comment ) . '`' ) : '' ))->plain();
		DiscordUtils::handleDiscord(':wastebasket:', $msg);
		return true;
	}

	/**
	 * Called after committing revision visibility changes to the database
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleRevisionVisibilitySet
	 */
	public static function onArticleRevisionVisibilitySet( &$title, $ids, $visibilityChangeMap ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'ArticleRevisionVisibilitySet', $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-revvisibility', DiscordUtils::createUserLinks( $user ),
			count($visibilityChangeMap),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ) )->plain();
		DiscordUtils::handleDiscord(':spy:', $msg);
		return true;
	}

	/**
	 * Called when a page is protected (or unprotected)
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	public static function onArticleProtectComplete( &$article, &$user, $protect, $reason ) {
		global $wgDiscordNoBots;

		if ( DiscordUtils::isDisabled( 'ArticleProtectComplete', $article->getTitle()->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articleprotect', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ),
			implode(", ", $protect) )->plain();
		DiscordUtils::handleDiscord(':lock:', $msg);
		return true;
	}

	/**
	 * Called when a page is moved
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 */
	public static function onTitleMoveComplete( Title &$title, Title &$newTitle, User $user, $oldid, $newid, $reason, Revision $revision ) {
		global $wgDiscordNoBots;

		if ( DiscordUtils::isDisabled( 'TitleMoveComplete', $title->getNamespace(), $user ) ) {
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
			DiscordUtils::createRevisionText( $revision ) )->plain();
		DiscordUtils::handleDiscord(':truck:', $msg);
		return true;
	}

	/**
	 * Called when a user is created
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 */
	public static function onLocalUserCreated( $user, $autocreated ) {
		if ( DiscordUtils::isDisabled( 'LocalUserCreated', NULL, $user ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-localusercreated', DiscordUtils::createUserLinks( $user ) )->plain();
		DiscordUtils::handleDiscord(':wave:', $msg);
		return true;
	}

	/**
	 * Called when a user is blocked
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
	 */
	public static function onBlockIpComplete( Block $block, User $user ) {
		if ( DiscordUtils::isDisabled( 'BlockIpComplete', NULL, $user ) ) {
			return true;
		}

		$expiry = $block->getExpiry();
		if ($expires = strtotime($expiry)) {
			$expiryMsg = sprintf('%s', date( wfMessage( 'discord-blocktimeformat' )->text(), $expires));
		} else {
			$expiryMsg = $expiry;
		}

		$msg = wfMessage( 'discord-blockipcomplete', DiscordUtils::createUserLinks( $user ), DiscordUtils::createUserLinks( $block->getTarget() ),
			( $block->getReasonComment()->text ? ('`' . DiscordUtils::truncateText( $block->getReasonComment()->text ) . '`' ) : '' ),
			$expiryMsg )->plain();
		DiscordUtils::handleDiscord(':no_entry_sign:', $msg);
		return true;
	}

	/**
	 * Called when a user is unblocked
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
	 */
	public static function onUnblockUserComplete( Block $block, User $user ) {
		if ( DiscordUtils::isDisabled( 'UnblockUserComplete', NULL, $user ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-unblockusercomplete', DiscordUtils::createUserLinks( $user ), DiscordUtils::createUserLinks( $block->getTarget() ) )->text();
		DiscordUtils::handleDiscord(':no_entry_sign:', $msg);
		return true;
	}

	/**
	 * Called when a user's rights are changed
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 */
	public static function onUserGroupsChanged( User $user, array $added, array $removed, $performer, $reason ) {
		if ( DiscordUtils::isDisabled( 'UserGroupsChanged', NULL, $performer ) ) {
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
			( ( count($removed) > 0 ) ? ( '- ' . join(', ', $removed) ) : '' ) )->plain();
		DiscordUtils::handleDiscord(':people_holding_hands:', $msg);
		return true;
	}

	/**
	 * Called when a file upload is complete
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete
	 */
	public static function onUploadComplete( &$image ) {
		global $wgDiscordNoBots;

		$lf = $image->getLocalFile();
		$user = $lf->getUser( $type = 'object' ); // only supported in MW 1.31+

		if ( DiscordUtils::isDisabled( 'UploadComplete', NS_FILE, $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$comment = $lf->getDescription();
		$isNewRevision = count($lf->getHistory()) > 0;

		$msg = wfMessage( 'discord-uploadcomplete', DiscordUtils::createUserLinks( $user ),
			( $isNewRevision ? wfMessage( 'discord-uploadnewver' )->text() : '' ),
			DiscordUtils::createMarkdownLink( $lf->getName(), $lf->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ), 
			( $comment ? ('`' . DiscordUtils::truncateText( $comment ) . '`' ) : '' ),
			DiscordUtils::formatBytes($lf->getSize()),
			$lf->getWidth(),
			$lf->getHeight(),
			$lf->getMimeType() )->plain();
		DiscordUtils::handleDiscord(':inbox_tray:', $msg);
		return true;
	}

	/**
	 * Called when a file is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileDeleteComplete
	 */
	public static function onFileDeleteComplete( $file, $oldimage, $article, $user, $reason ) {
		global $wgDiscordNoBots;

		if ( DiscordUtils::isDisabled( 'FileDeleteComplete', NS_FILE, $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		if ( $article ) {
			// Entire page was deleted, onArticleDeleteComplete will handle this
			return true;
		}

		$msg = wfMessage( 'discord-filedeletecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $file->getName(), $file->getTitle()->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ) )->plain();
		DiscordUtils::handleDiscord(':wastebasket:', $msg);
		return true;
	}

	/**
	 * Called when a file is restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUndeleteComplete
	 */
	public static function onFileUndeleteComplete( $title, $fileVersions, $user, $reason ) {
		global $wgDiscordNoBots;

		if ( DiscordUtils::isDisabled( 'FileUndeleteComplete', NS_FILE, $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-fileundeletecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			( $reason ? ('`' . DiscordUtils::truncateText( $reason ) . '`' ) : '' ) )->plain();
		DiscordUtils::handleDiscord(':wastebasket:', $msg);
		return true;
	}

	/**
	 * Called when a page is imported
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/AfterImportPage
	 */
	public static function onAfterImportPage( $title, $origTitle, $revCount, $sRevCount, $pageInfo ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'AfterImportPage', $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-afterimportpage', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			$revCount, $sRevCount)->plain();
		DiscordUtils::handleDiscord(':books:', $msg);
		return true;
	}

	public static function onArticleMergeComplete( $targetTitle, $destTitle ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'ArticleMergeComplete', $destTitle->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-articlemergecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $targetTitle, $targetTitle->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			DiscordUtils::createMarkdownLink( $destTitle, $destTitle->getFullUrl( '', '', $proto = PROTO_HTTP ) ))->plain();
		DiscordUtils::handleDiscord(':card_box:', $msg);
		return true;
	}

	/**
	 * Called when a revision is approved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function externalonApprovedRevsRevisionApproved ( $output, $title, $rev_id, $content ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'ApprovedRevsRevisionApproved', $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		// Get the revision being approved here
		$rev = Revision::newFromTitle( $title, $rev_id );
		$revLink = $title->getFullURL( array( 'oldid' => $rev_id ), '', $proto = PROTO_HTTP );
		$revAuthor = $rev->getUser( Revision::RAW );

		if ($revAuthor === 0) {
			$revAuthor = DiscordUtils::createUserLinks( User::newFromName($rev->getUserText(), false) );
		} else if ($revAuthor) {
			$revAuthor = DiscordUtils::createUserLinks( User::newFromId($revAuthor) );
		}

		$msg = wfMessage( 'discord-approvedrevsrevisionapproved', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ),
			DiscordUtils::createMarkdownLink( $rev_id, $revLink ),
			$revAuthor)->plain();
		DiscordUtils::handleDiscord(':white_check_mark:', $msg);
		return true;
	}

	/**
	 * Called when a revision is unapproved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function externalonApprovedRevsRevisionUnapproved ( $output, $title, $content ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'ApprovedRevsRevisionUnapproved', $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-approvedrevsrevisionunapproved', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ))->plain();
		DiscordUtils::handleDiscord(':white_check_mark:', $msg);
		return true;
	}

	/**
	 * Called when a file is approved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function externalonApprovedRevsFileRevisionApproved ( $parser, $title, $timestamp, $sha1 ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'ApprovedRevsFileRevisionApproved', $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$imagepage = ImagePage::newFromID( $title->getArticleID() );
		$displayedFile = $imagepage->getDisplayedFile();
		$displayedFileUrl = $displayedFile->getCanonicalUrl(); // getFullURL doesn't work quite the same on File classes
		$uploader = $displayedFile->getUser();

		if (is_string($uploader)) {
			$uploader = User::newFromName($uploader, false);
		} else {
			$uploader = User::newFromId($uploader);
		}

		$msg = wfMessage( 'discord-approvedrevsfilerevisionapproved', DiscordUtils::createUserLinks( $user ),
		    DiscordUtils::createMarkdownLink( $title, $title->getFullURL('', '', $proto = PROTO_HTTP) ),
			DiscordUtils::createMarkdownLink( 'direct', $displayedFileUrl ),
			DiscordUtils::createUserLinks( $uploader ) )->plain();
		DiscordUtils::handleDiscord(':white_check_mark:', $msg);
		return true;
	}

	/**
	 * Called when a file is unapproved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function externalonApprovedRevsFileRevisionUnapproved ( $parser, $title ) {
		global $wgDiscordNoBots;

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( 'ApprovedRevsFileRevisionUnapproved', $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-approvedrevsfilerevisionunapproved', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullUrl( '', '', $proto = PROTO_HTTP ) ))->plain();
		DiscordUtils::handleDiscord(':white_check_mark:', $msg);
		return true;
	}
}
