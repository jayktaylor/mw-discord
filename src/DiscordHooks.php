<?php

namespace MediaWiki\Extension\Discord;

use ForeignTitle;
use ImagePage;
use LocalFile;
use ManualLogEntry;
use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\AfterImportPageHook;
use MediaWiki\Hook\ArticleMergeCompleteHook;
use MediaWiki\Hook\ArticleRevisionVisibilitySetHook;
use MediaWiki\Hook\BlockIpCompleteHook;
use MediaWiki\Hook\FileDeleteCompleteHook;
use MediaWiki\Hook\FileUndeleteCompleteHook;
use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Hook\UnblockUserCompleteHook;
use MediaWiki\Hook\UploadCompleteHook;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\Hook\ArticleProtectCompleteHook;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\Hook\PageUndeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Parser\Parser;
use MediaWiki\Permissions\Authority;
use MediaWiki\RenameUser\Hook\RenameUserCompleteHook;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;
use MediaWiki\User\Hook\UserGroupsChangedHook;
use MediaWiki\User\User;
use MediaWiki\User\UserGroupMembership;
use MediaWiki\User\UserIdentity;
use UploadBase;
use WikiFilePage;
use WikiPage;

/**
 * Hooks for the Discord extension
 *
 * @file
 * @ingroup Extensions
 */
class DiscordHooks implements
	PageSaveCompleteHook,
	PageDeleteCompleteHook,
	ArticleRevisionVisibilitySetHook,
	ArticleProtectCompleteHook,
	PageMoveCompleteHook,
	LocalUserCreatedHook,
	BlockIpCompleteHook,
	UnblockUserCompleteHook,
	UserGroupsChangedHook,
	UploadCompleteHook,
	FileDeleteCompleteHook,
	FileUndeleteCompleteHook,
	AfterImportPageHook,
	ArticleMergeCompleteHook,
	RenameUserCompleteHook,
	PageUndeleteCompleteHook
{
	/**
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $userIdentity
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 * @return bool
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$userIdentity,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
	): bool {
		global $wgDiscordNoBots, $wgDiscordNoMinor, $wgDiscordNoNull;
		$hookName = 'PageSaveComplete';
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $userIdentity );

		if ( DiscordUtils::isDisabled( $hookName, $wikiPage->getTitle()->getNamespace(), $user ) ) {
			return true;
		}

		$isNew = $editResult->isNew();

		if (
			( $wgDiscordNoBots && $user->isBot() ) ||
			( $wgDiscordNoMinor && $revisionRecord->isMinor() ) ||
			( $wgDiscordNoNull && $editResult->isNullEdit() ) ||
			( $isNew && $wikiPage->getNamespace() === NS_FILE )
		) {
			return true;
		}

		$msgKey = 'discord-edit';
		if ( $isNew ) {
			$msgKey = 'discord-create';
		}

		$msg = wfMessage( $msgKey, DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $wikiPage->getTitle(),
				$wikiPage->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createRevisionText( $revisionRecord ),
			( $summary ? ( '`' . DiscordUtils::sanitiseText(
				DiscordUtils::truncateText( $summary ) ) . '`' ) : '' ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	public function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	): bool {
		global $wgDiscordNoBots;
		$hookName = 'PageDeleteComplete';

		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $deleter->getUser() );
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $page );

		if ( DiscordUtils::isDisabled( $hookName, $page->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-pagedeletecomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $page->getTitle(),
				$page->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ( '`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			$archivedRevisionCount )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	public function onPageUndeleteComplete(
		ProperPageIdentity $page,
		Authority $restorer,
		string $reason,
		RevisionRecord $restoredRev,
		ManualLogEntry $logEntry,
		int $restoredRevisionCount,
		bool $created,
		array $restoredPageIds
	): void {
		global $wgDiscordNoBots;
		$hookName = 'PageUndeleteComplete';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, $page->getNamespace(), $user ) ) {
			return;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return;
		}

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromPageIdentity( $page );

		$msg = wfMessage( 'discord-articleundelete', DiscordUtils::createUserLinks( $user ),
			( $created ? '' : wfMessage( 'discord-undeleterev' )->inContentLanguage()->text() ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ( '`' . DiscordUtils::sanitiseText(
				DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
	}

	/**
	 * @param Title $title
	 * @param int[] $ids
	 * @param array $visibilityChangeMap
	 * @return bool
	 */
	public function onArticleRevisionVisibilitySet( $title, $ids, $visibilityChangeMap ): bool {
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
			count( $visibilityChangeMap ),
			DiscordUtils::createMarkdownLink( $title,
				$title->getFullURL( '', false, PROTO_CANONICAL ) ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param array $protect
	 * @param string $reason
	 * @return bool
	 */
	public function onArticleProtectComplete( $wikiPage, $user, $protect, $reason ): bool {
		global $wgDiscordNoBots;
		$hookName = 'ArticleProtectComplete';

		if ( DiscordUtils::isDisabled( $hookName, $wikiPage->getTitle()->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-articleprotect', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $wikiPage->getTitle(),
				$wikiPage->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ( '`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			implode( ", ", $protect ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param LinkTarget $old
	 * @param LinkTarget $new
	 * @param UserIdentity $userIdentity
	 * @param int $pageid
	 * @param int $redirid
	 * @param string $reason
	 * @param RevisionRecord $revision
	 * @return bool
	 */
	public function onPageMoveComplete(
		$old,
		$new,
		$userIdentity,
		$pageid,
		$redirid,
		$reason,
		$revision
	): bool {
		global $wgDiscordNoBots;
		$hookName = 'PageMoveComplete';
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $userIdentity );

		if ( DiscordUtils::isDisabled( $hookName, $old->getNamespace(), $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$msg = wfMessage( 'discord-titlemove', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $old,
				Title::castFromLinkTarget( $old )->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createMarkdownLink( $new,
				Title::castFromLinkTarget( $new )->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ( '`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			DiscordUtils::createRevisionText( $revision ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param User $user
	 * @param bool $autocreated
	 * @return bool
	 */
	public function onLocalUserCreated( $user, $autocreated ): bool {
		$hookName = 'LocalUserCreated';

		if ( DiscordUtils::isDisabled( $hookName, null, $user ) ) {
			return true;
		}

		$msg = wfMessage( 'discord-localusercreated',
			DiscordUtils::createUserLinks( $user ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param DatabaseBlock $block
	 * @param User $user
	 * @param ?DatabaseBlock $priorBlock
	 * @return bool
	 */
	public function onBlockIpComplete( $block, $user, $priorBlock ): bool {
		$hookName = 'BlockIpComplete';

		if ( DiscordUtils::isDisabled( $hookName, null, $user ) ) {
			return true;
		}

		$expiry = $block->getExpiry();
		$expiryAsUnix = strtotime( $expiry );
		if ( $expiryAsUnix ) {
			$expiryMsg = sprintf( '%s',
				date( wfMessage( 'discord-blocktimeformat' )->inContentLanguage()->text(), $expiryAsUnix ) );
		} else {
			$expiryMsg = $expiry;
		}

		$target = $block->getTargetUserIdentity();
		if ( $target === null ) {
			$target = $block->getTargetName();
		}

		// Partial blocks

		$msg = wfMessage( 'discord-blockipcomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createUserLinks( $target ),
			( $block->getReasonComment()->text ? ( '`' . DiscordUtils::sanitiseText(
				DiscordUtils::truncateText( $block->getReasonComment()->text ) ) . '`' ) : '' ),
			$expiryMsg )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param DatabaseBlock $block
	 * @param User $user
	 * @return bool
	 */
	public function onUnblockUserComplete( $block, $user ): bool {
		$hookName = 'UnblockUserComplete';

		if ( DiscordUtils::isDisabled( $hookName, null, $user ) ) {
			return true;
		}

		$target = $block->getTargetUserIdentity();
		if ( $target === null ) {
			$target = $block->getTargetName();
		}

		$msg = wfMessage( 'discord-unblockusercomplete', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createUserLinks( $target ) )->inContentLanguage()->text();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param User|UserIdentity $user
	 * @param string[] $added
	 * @param string[] $removed
	 * @param User|false $performer
	 * @param string|false $reason
	 * @param UserGroupMembership[] $oldUGMs
	 * @param UserGroupMembership[] $newUGMs
	 * @return bool
	 */
	public function onUserGroupsChanged( $user, $added, $removed, $performer, $reason, $oldUGMs, $newUGMs ): bool {
		$hookName = 'UserGroupsChanged';

		if ( DiscordUtils::isDisabled( $hookName, null, $performer ) ) {
			return true;
		}

		if ( $performer === false ) {
			// Rights were changed by autopromotion, do nothing
			return true;
		}

		$msg = wfMessage( 'discord-usergroupschanged', DiscordUtils::createUserLinks( $performer ),
			DiscordUtils::createUserLinks( $user ),
			( $reason ? ( '`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ),
			( ( count( $added ) > 0 ) ? ( '+ ' . implode( ', ', $added ) ) : '' ),
			( ( count( $removed ) > 0 ) ? ( '- ' . implode( ', ', $removed ) ) : '' ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param UploadBase $uploadBase
	 * @return bool
	 */
	public function onUploadComplete( $uploadBase ): bool {
		global $wgDiscordNoBots;
		$hookName = 'UploadComplete';

		$lf = $uploadBase->getLocalFile();
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $lf->getUploader() );

		if ( DiscordUtils::isDisabled( $hookName, NS_FILE, $user ) ) {
			return true;
		}

		if ( $wgDiscordNoBots && $user->isBot() ) {
			// Don't continue, this is a bot change
			return true;
		}

		$comment = $lf->getDescription();
		$isNewRevision = count( $lf->getHistory() ) > 0;

		$msg = wfMessage( 'discord-uploadcomplete', DiscordUtils::createUserLinks( $user ),
			( $isNewRevision ? wfMessage( 'discord-uploadnewver' )->inContentLanguage()->text() : '' ),
			DiscordUtils::createMarkdownLink( $lf->getName(),
				$lf->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $comment ? ( '`' . DiscordUtils::sanitiseText( DiscordUtils::truncateText( $comment ) ) . '`' ) : '' ),
			DiscordUtils::formatBytes( $lf->getSize() ),
			$lf->getWidth(),
			$lf->getHeight(),
			$lf->getMimeType() )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param LocalFile $file
	 * @param string|null $oldimage
	 * @param WikiFilePage|null $article
	 * @param User $user
	 * @param string $reason
	 * @return bool
	 */
	public function onFileDeleteComplete( $file, $oldimage, $article, $user, $reason ): bool {
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
			DiscordUtils::createMarkdownLink( $file->getName(),
				$file->getTitle()->getFullURL( '', false, PROTO_CANONICAL ) ),
			( $reason ? ( '`' . DiscordUtils::sanitiseText(
				DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param Title $title
	 * @param int[] $fileVersions
	 * @param User $user
	 * @param string $reason
	 * @return bool
	 */
	public function onFileUndeleteComplete( $title, $fileVersions, $user, $reason ): bool {
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
			( $reason ? ( '`' . DiscordUtils::sanitiseText(
				DiscordUtils::truncateText( $reason ) ) . '`' ) : '' ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param Title $title
	 * @param ForeignTitle $foreignTitle
	 * @param int $revCount
	 * @param int $sRevCount
	 * @param array $pageInfo
	 * @return bool
	 */
	public function onAfterImportPage( $title, $foreignTitle, $revCount, $sRevCount, $pageInfo ): bool {
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
			$revCount, $sRevCount )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param Title $targetTitle
	 * @param Title $destTitle
	 * @return bool
	 */
	public function onArticleMergeComplete( $targetTitle, $destTitle ): bool {
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
			DiscordUtils::createMarkdownLink( $destTitle,
				$destTitle->getFullURL( '', false, PROTO_CANONICAL ) ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param null $output
	 * @param Title $title
	 * @param int $rev_id
	 * @param mixed $content
	 * @return bool
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_45/includes/ApprovedRevs.php#L602
	 */
	public function onApprovedRevsRevisionApproved( $output, $title, $rev_id, $content ): bool {
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
			$revAuthor )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param null $output
	 * @param Title $title
	 * @param mixed $content
	 * @return bool
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_45/includes/ApprovedRevs.php#L712
	 */
	public function onApprovedRevsRevisionUnapproved( $output, $title, $content ): bool {
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
			DiscordUtils::createMarkdownLink( $title,
				$title->getFullURL( '', false, PROTO_CANONICAL ) ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param Parser $parser
	 * @param Title $title
	 * @param string $timestamp
	 * @param string $sha1
	 * @return bool
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_45/includes/ApprovedRevs.php#L827
	 */
	public function onApprovedRevsFileRevisionApproved( $parser, $title, $timestamp, $sha1 ): bool {
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
		// getFullURL doesn't work quite the same on File classes
		$displayedFileUrl = $displayedFile->getCanonicalUrl();
		$uploader = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity(
			$displayedFile->getUploader() );

		$msg = wfMessage( 'discord-approvedrevsfilerevisionapproved', DiscordUtils::createUserLinks( $user ),
			DiscordUtils::createMarkdownLink( $title, $title->getFullURL( '', false, PROTO_CANONICAL ) ),
			DiscordUtils::createMarkdownLink( 'direct', $displayedFileUrl ),
			DiscordUtils::createUserLinks( $uploader ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param Parser $parser
	 * @param Title $title
	 * @return bool
	 * @see https://github.com/wikimedia/mediawiki-extensions-ApprovedRevs/blob/REL1_45/includes/ApprovedRevs.php#L865
	 */
	public function onApprovedRevsFileRevisionUnapproved( $parser, $title ): bool {
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
			DiscordUtils::createMarkdownLink( $title,
				$title->getFullURL( '', false, PROTO_CANONICAL ) ) )->inContentLanguage()->plain();
		DiscordUtils::handleDiscord( $hookName, $msg );
		return true;
	}

	/**
	 * @param int $uid
	 * @param string $old
	 * @param string $new
	 * @return void
	 */
	public function onRenameUserComplete( $uid, $old, $new ): void {
		$hookName = 'RenameUserComplete';

		$user = RequestContext::getMain()->getUser();

		if ( DiscordUtils::isDisabled( $hookName, null, null ) ) {
			return;
		}

		$renamedUserAsTitle = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $new )->getUserPage();

		$msg = wfMessage( 'discord-renameusercomplete', DiscordUtils::createUserLinks( $user ),
			"*$old*",
			DiscordUtils::createMarkdownLink( $new,
				$renamedUserAsTitle->getFullURL( '', false, PROTO_CANONICAL ) ) )->inContentLanguage()->plain();
			DiscordUtils::handleDiscord( $hookName, $msg );
	}
}
