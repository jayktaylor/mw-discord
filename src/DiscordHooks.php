<?php

use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;

/**
 * Hooks for the Discord extension
 *
 * @file
 * @ingroup Extensions
 */
class DiscordHooks {
	/**
	 * Called when a page is created or edited
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageSaveComplete
	 */
	public static function onPageSaveComplete( WikiPage $wikiPage, UserIdentity $userIdentity, string $summary, int $flags, RevisionRecord $revision, EditResult $editResult ) {
		global $wgDiscordNoBots, $wgDiscordNoMinor, $wgDiscordNoNull;
		$hookName = 'PageContentSaveComplete';
        $user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $userIdentity );

		if ( DiscordUtils::isDisabled( $hookName, $wikiPage->getTitle()->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot edit
			return true;
		}

		if ( $wgDiscordNoMinor && $revision->isMinor() ) {
			// Don't continue, this is a minor edit
			return true;
		}

		if ( $wgDiscordNoNull && $editResult->isNullEdit() ) {
			// Don't continue, this is a null edit
			return true;
		}

		$isNew = $editResult->isNew();
		if ( $wikiPage->getTitle()->inNamespace( NS_FILE ) && $isNew ) {
			// Don't continue, it's a new file which onUploadComplete will handle instead
			return true;
		}

		$msgKey = 'discord-edit';
		if ( $isNew ) { // is a new page
			$msgKey = 'discord-create';
		}

		$msg = wfMessage( $msgKey, DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $wikiPage->getTitle(), $wikiPage->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createRevisionText( $revision ),
			( $summary ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $summary ) ) . '`' ) : '' ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a page is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageDeleteComplete
	 */
	public static function onPageDeleteComplete( MediaWiki\Page\ProperPageIdentity $page, MediaWiki\Permissions\Authority $deleter, string $reason, int $pageID, MediaWiki\Revision\RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount ) {
		global $wgDiscordNoBots;
		$hookName = 'ArticleDeleteComplete';

		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity($deleter->getUser());
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle($page);

		if ( DiscordUtils::isDisabled( $hookName, $page->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articledelete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $page->getTitle(), $page->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			$archivedRevisionCount)->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a page's revisions are restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleUndelete
	 */
	public static function onArticleUndelete( Title $title, $create, $comment, $oldPageId, $restoredPages ) {
		global $wgDiscordNoBots;
		$hookName = 'ArticleUndelete';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articleundelete', DiscordUtils::createUserLinks( $user ),
			($create ? '' : wfMessage( 'discord-undeleterev' )->text() ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $comment ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $comment ) ) . '`' ) : '' ))->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called after committing revision visibility changes to the database
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleRevisionVisibilitySet
	 */
	public static function onArticleRevisionVisibilitySet( &$title, $ids, $visibilityChangeMap ) {
		global $wgDiscordNoBots;
		$hookName = 'ArticleRevisionVisibilitySet';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-revvisibility', DiscordUtils::createUserLinks( $user ),
			count($visibilityChangeMap),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a page is protected (or unprotected)
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	public static function onArticleProtectComplete( &$article, &$user, $protect, $reason ) {
		global $wgDiscordNoBots;
		$hookName = 'ArticleProtectComplete';

		if ( DiscordUtils::isDisabled( $hookName, $article->getTitle()->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articleprotect', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $article->getTitle(), $article->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			implode(", ", $protect) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a page is moved
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageMoveComplete
	 */
	public static function onPageMoveComplete( LinkTarget $old, LinkTarget $new, UserIdentity $userIdentity, int $pageid, int $redirid, string $reason, RevisionRecord $revision ) {
		global $wgDiscordNoBots;
		$hookName = 'TitleMoveComplete';
        $user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $userIdentity );

		if ( DiscordUtils::isDisabled( $hookName, $old->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-titlemove', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $old, Title::castFromLinkTarget( $old )->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createMarkdownLink( $new, Title::castFromLinkTarget( $new )->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			DiscordUtils::createRevisionText( $revision ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a user is created
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 */
	public static function onLocalUserCreated( $user, $autocreated ) {
		$hookName = 'LocalUserCreated';

		if ( DiscordUtils::isDisabled( $hookName, NULL, $user ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-localusercreated', DiscordUtils::createUserLinks( $user ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a user is blocked
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
	 */
	public static function onBlockIpComplete( Block $block, User $user ) {
		$hookName = 'BlockIpComplete';

		if ( DiscordUtils::isDisabled( $hookName, NULL, $user ) ) {
			return true;
		}

		$expiry = $block->getExpiry();
		if ($expires = strtotime($expiry)) {
			$expiryMsg = sprintf('%s', date( wfMessage( 'discord-blocktimeformat' )->text(), $expires));
		} else {
			$expiryMsg = $expiry;
		}

		$target = $block->getTargetUserIdentity();
		if ( $target === null ) {
			$target = $block->getTargetName();
		}

		$msg = wfMessage( 'discord-blockipcomplete', DiscordUtils::createUserLinks( $user ), DiscordUtils::createUserLinks( $target ),
			( $block->getReasonComment()->text ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $block->getReasonComment()->text ) ) . '`' ) : '' ),
			$expiryMsg )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a user is unblocked
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
	 */
	public static function onUnblockUserComplete( Block $block, User $user ) {
		$hookName = 'UnblockUserComplete';

		if ( DiscordUtils::isDisabled( $hookName, NULL, $user ) ) {
			return true;
		}

		$target = $block->getTargetUserIdentity();
		if ( $target === null ) {
			$target = $block->getTargetName();
		}

		$msg = wfMessage( 'discord-unblockusercomplete', DiscordUtils::createUserLinks( $user ), DiscordUtils::createUserLinks( $target ) )->text();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a user's rights are changed
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 */
	public static function onUserGroupsChanged( User $user, array $added, array $removed, $performer, $reason ) {
		$hookName = 'UserGroupsChanged';

		if ( DiscordUtils::isDisabled( $hookName, NULL, $performer ) ) {
			return true;
		}

		if ($performer === false) {
			// Rights were changed by autopromotion, do nothing
			return true;
		}

		$msg = wfMessage( 'discord-usergroupschanged', DiscordUtils::createUserLinks( $performer ),
			DiscordUtils::createUserLinks( $user ),
			( $reason ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			( ( count($added) > 0 ) ? ( '+ ' . join(', ', $added) ) : ''),
			( ( count($removed) > 0 ) ? ( '- ' . join(', ', $removed) ) : '' ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a file upload is complete
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete
	 */
	public static function onUploadComplete( &$image ) {
		global $wgDiscordNoBots;
		$hookName = 'UploadComplete';

		$lf = $image->getLocalFile();
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $lf->getUploader() );

		if ( DiscordUtils::isDisabled( $hookName, NS_FILE, $user ) ) {
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
			DiscordUtils::createMarkdownLink( $lf->getName(), $lf->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $comment ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $comment ) ) . '`' ) : '' ),
			DiscordUtils::formatBytes($lf->getSize()),
			$lf->getWidth(),
			$lf->getHeight(),
			$lf->getMimeType() )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a file is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileDeleteComplete
	 */
	public static function onFileDeleteComplete( $file, $oldimage, $article, $user, $reason ) {
		global $wgDiscordNoBots;
		$hookName = 'FileDeleteComplete';

		if ( DiscordUtils::isDisabled( $hookName, NS_FILE, $user ) ) {
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
			DiscordUtils::createMarkdownLink( $file->getName(), $file->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a file is restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUndeleteComplete
	 */
	public static function onFileUndeleteComplete( $title, $fileVersions, $user, $reason ) {
		global $wgDiscordNoBots;
		$hookName = 'FileUndeleteComplete';

		if ( DiscordUtils::isDisabled( $hookName, NS_FILE, $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-fileundeletecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ('`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a page is imported
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/AfterImportPage
	 */
	public static function onAfterImportPage( $title, $origTitle, $revCount, $sRevCount, $pageInfo ) {
		global $wgDiscordNoBots;
		$hookName = 'AfterImportPage';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-afterimportpage', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ),
			$revCount, $sRevCount)->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	public static function onArticleMergeComplete( $targetTitle, $destTitle ) {
		global $wgDiscordNoBots;
		$hookName = 'ArticleMergeComplete';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $destTitle->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-articlemergecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $targetTitle, $targetTitle->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createMarkdownLink( $destTitle, $destTitle->getFullURL( '', false, PROTO_CANONICAL ) ))->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a revision is approved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function onApprovedRevsRevisionApproved ( $output, $title, $rev_id, $content ) {
		global $wgDiscordNoBots;
		$hookName = 'ApprovedRevsRevisionApproved';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		// Get the revision being approved here
		$rev = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionByTitle( $title, $rev_id );
		$revLink = $title->getFullURL( '', false, PROTO_CANONICAL );
		$revAuthor = DiscordUtils::createUserLinks( $rev->getUser( RevisionRecord::RAW ) );

		$msg = wfMessage( 'discord-approvedrevsrevisionapproved', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createMarkdownLink( $rev_id, $revLink ),
			$revAuthor)->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a revision is unapproved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function onApprovedRevsRevisionUnapproved ( $output, $title, $content ) {
		global $wgDiscordNoBots;
		$hookName = 'ApprovedRevsRevisionUnapproved';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-approvedrevsrevisionunapproved', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a file is approved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function onApprovedRevsFileRevisionApproved ( $parser, $title, $timestamp, $sha1 ) {
		global $wgDiscordNoBots;
		$hookName = 'ApprovedRevsFileRevisionApproved';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$imagepage = ImagePage::newFromID( $title->getArticleID() );
		$displayedFile = $imagepage->getDisplayedFile();
		$displayedFileUrl = $displayedFile->getCanonicalUrl(); // getFullURL doesn't work quite the same on File classes
		$uploader = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $displayedFile->getUploader() );

		$msg = wfMessage( 'discord-approvedrevsfilerevisionapproved', DiscordUtils::createUserLinks( $user ),
		    DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createMarkdownLink( 'direct', $displayedFileUrl ),
			DiscordUtils::createUserLinks( $uploader ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a file is unapproved (Approved Revs extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_34/includes/ApprovedRevs_body.php
	 */
	public static function onApprovedRevsFileRevisionUnapproved ( $parser, $title ) {
		global $wgDiscordNoBots;
		$hookName = 'ApprovedRevsFileRevisionUnapproved';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $title->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot
			return true;
		}

		$msg = wfMessage( 'discord-approvedrevsfilerevisionunapproved', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ) )->plain();
		DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}

	/**
	 * Called when a user is renamed (Renameuser extension)
	 * @see https://github.com/wikimedia/mediawiki-extensions-Renameuser/blob/REL1_36/includes/RenameuserSQL.php
	 */
	public static function onRenameUserComplete ( $uid, $old, $new ) {
		$hookName = 'RenameUserComplete';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, null, null ) ) {
			return true;
		}

        $renamedUserAsTitle = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $new )->getUserPage();

		$msg = wfMessage( 'discord-renameusercomplete', DiscordUtils::createUserLinks( $user ),
			"*$old*",
			DiscordUtils::createMarkdownLink( $new, $renamedUserAsTitle->getFullURL( '', false, PROTO_CANONICAL ) ) )->plain();
			DiscordUtils::handleDiscord($hookName, $msg);
		return true;
	}
}
