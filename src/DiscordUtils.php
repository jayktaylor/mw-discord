<?php

namespace MediaWiki\Extension\Discord;

use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;

class DiscordUtils {
	/**
	 * Checks if criteria is met for this action to be cancelled
	 * @param string $hook
	 * @param int|null $ns
	 * @param User|UserIdentity|null $user
	 * @return bool
	 */
	public static function isDisabled(
		string $hook, int|null $ns, User|UserIdentity|null $user
	): bool {
		global $wgDiscordDisabledHooks, $wgDiscordDisabledNS, $wgDiscordDisabledUsers;

		if ( is_array( $wgDiscordDisabledHooks ) ) {
			if ( in_array( strtolower( $hook ), array_map( 'strtolower', $wgDiscordDisabledHooks ) ) ) {
				// Hook is disabled, return true
				return true;
			}
		} else {
			wfDebugLog( 'discord',
				'The value of $wgDiscordDisabledHooks is not valid and therefore all hooks are enabled.' );
		}
		if ( is_array( $wgDiscordDisabledNS ) ) {
			if ( $ns !== null ) {
				$ns = (int)$ns;
				if ( in_array( $ns, $wgDiscordDisabledNS ) ) {
					// Namespace is disabled, return true
					return true;
				}
			}
		} else {
			wfDebugLog( 'discord',
				'The value of $wgDiscordDisabledNS is not valid and therefore all namespaces are enabled.' );
		}
		if ( is_array( $wgDiscordDisabledUsers ) ) {
			if ( $user !== null ) {
				if ( $user instanceof UserIdentity ) {
					$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $user );
				}

				if ( $user instanceof User ) {
					if ( in_array( $user->getName(), $wgDiscordDisabledUsers ) ) {
						// User shouldn't trigger a message, return true
						return true;
					}
				}
			}
		} else {
			wfDebugLog( 'discord',
				'The value of $wgDiscordDisabledUsers is not valid and therefore all users can trigger messages.' );
		}

		return false;
	}

	/**
	 * Handles sending a webhook to Discord using cURL
	 * @param string $hook
	 * @param string $msg
	 * @return bool
	 */
	public static function handleDiscord( string $hook, string $msg ): bool {
		global $wgDiscordWebhookURL, $wgDiscordEmojis, $wgDiscordUseEmojis, $wgDiscordPrependTimestamp,
			   $wgDiscordUseFileGetContents;

		if ( !$wgDiscordWebhookURL ) {
			// There's nothing in here, so we won't do anything
			return false;
		}

		$urls = [];

		if ( is_array( $wgDiscordWebhookURL ) ) {
			$urls = array_merge( $urls, $wgDiscordWebhookURL );
		} elseif ( is_string( $wgDiscordWebhookURL ) ) {
			$urls[] = $wgDiscordWebhookURL;
		} else {
			wfDebugLog( 'discord',
				'The value of $wgDiscordWebhookURL is not valid and therefore no webhooks could be sent.' );
			return false;
		}

		// Strip whitespace to just one space
		$stripped = preg_replace( '/\s+/', ' ', $msg );

		if ( $wgDiscordPrependTimestamp ) {
			// Add timestamp
			$dateString = gmdate( wfMessage( 'discord-timestampformat' )->inContentLanguage()->text() );
			$stripped = $dateString . ' ' . $stripped;
		}

		if ( $wgDiscordUseEmojis ) {
			// Add emoji
			$emoji = $wgDiscordEmojis[$hook];
			$stripped = $emoji . ' ' . $stripped;
		}

		DeferredUpdates::addCallableUpdate( function () use ( $stripped, $urls, $wgDiscordUseFileGetContents ) {
			$user_agent = 'mw-discord/1.0 (github.com/jaydenkieran)';
			$json_data = [
				'content' => "$stripped",
				'allowed_mentions' => [
					'parse' => []
				]
			];
			$json = json_encode( $json_data );

			if ( $wgDiscordUseFileGetContents ) {
				// They want to use file_get_contents
				foreach ( $urls as $value ) {
					$contextOpts = [
						'http' => [
							'header' => 'Content-Type: application/x-www-form-urlencoded',
							'method' => 'POST',
							'user_agent' => $user_agent,
							'content' => $json,
							'ignore_errors' => true
						]
					];

					$context = stream_context_create( $contextOpts );
					$result = file_get_contents( $value, false, $context );
				}
			} else {
				// By default, we use cURL
				// Set up cURL multi handlers
				$c_handlers = [];
				$result = [];
				$mh = curl_multi_init();

				foreach ( $urls as $value ) {
					$c_handlers[$value] = curl_init( $value );
					curl_setopt( $c_handlers[$value], CURLOPT_POST, 1 );
					curl_setopt( $c_handlers[$value], CURLOPT_POSTFIELDS, $json );
					curl_setopt( $c_handlers[$value], CURLOPT_FOLLOWLOCATION, 1 );
					curl_setopt( $c_handlers[$value], CURLOPT_HEADER, 0 );
					curl_setopt( $c_handlers[$value], CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt( $c_handlers[$value], CURLOPT_CONNECTTIMEOUT, 10 );
					curl_setopt( $c_handlers[$value], CURLOPT_TIMEOUT, 10 );
					curl_setopt( $c_handlers[$value], CURLOPT_USERAGENT, $user_agent );
					curl_setopt( $c_handlers[$value], CURLOPT_HTTPHEADER, [
						'Content-Type: application/json'
					] );
					curl_multi_add_handle( $mh, $c_handlers[$value] );
				}

				$running = 0;
				do {
					curl_multi_exec( $mh, $running );
				} while ( $running );

				// Remove all handlers and then close the multi handler
				foreach ( $c_handlers as $k => $ch ) {
					$result[$k] = curl_multi_getcontent( $ch );
					wfDebugLog( 'discord', 'Result of cURL was: ' . $result[$k] );
					curl_multi_remove_handle( $mh, $ch );
				}

				curl_multi_close( $mh );
			}
		} );

		return true;
	}

	/**
	 * Creates a formatted markdown link based on text and given URL
	 * @param string $text
	 * @param string $url
	 * @return string
	 */
	public static function createMarkdownLink( string $text, string $url ): string {
		global $wgDiscordSuppressPreviews;

		return "[" . $text . "]" . '(' . ( $wgDiscordSuppressPreviews ? '<' : '' ) .
			self::encodeURL( $url ) . ( $wgDiscordSuppressPreviews ? '>' : '' ) . ')';
	}

	/**
	 * Creates links for a specific MediaWiki User object
	 * @param User|UserIdentity $user
	 * @return string
	 */
	public static function createUserLinks( User|UserIdentity $user ): string {
		global $wgDiscordMaxCharsUsernames;

		if ( $user instanceof UserIdentity ) {
			// If we were passed a UserIdentity object, get the relevant user.
			$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $user );
		}

		if ( $user instanceof User ) {
			$isAnon = $user->isAnon();
			$contribs = Title::newFromText( "Special:Contributions/" . $user );
			$user_abbr = strval( $user );

			if ( $wgDiscordMaxCharsUsernames ) {
				if ( strlen( $user_abbr ) > $wgDiscordMaxCharsUsernames ) {
					$user_abbr = substr( $user_abbr, 0, $wgDiscordMaxCharsUsernames );
					$user_abbr = $user_abbr . '...';
				}
			}

			$userPage = self::createMarkdownLink( $user_abbr, ( $isAnon ? $contribs : $user->getUserPage() )
				->getFullURL( '', false, PROTO_CANONICAL ) );
			$userTalk = self::createMarkdownLink( wfMessage( 'discord-talk' )->inContentLanguage()->text(),
				$user->getTalkPage()->getFullURL( '', false, PROTO_CANONICAL ) );
			$userContribs = self::createMarkdownLink( wfMessage( 'discord-contribs' )->inContentLanguage()->text(),
				$contribs->getFullURL( '', false, PROTO_CANONICAL ) );
			$text = wfMessage( 'discord-userlinks', $userPage, $userTalk, $userContribs )->inContentLanguage()->text();
		} else {
			// If we were given a string, handle this differently.
			$text = wfMessage( 'discord-userlinks', $user, 'n/a', 'n/a' )->inContentLanguage()->text();
		}
		return $text;
	}

	/**
	 * Creates formatted text for a specific Revision object
	 * @param RevisionRecord $revision
	 * @return string
	 */
	public static function createRevisionText( RevisionRecord $revision ): string {
		$diff = self::createMarkdownLink( wfMessage( 'discord-diff' )->inContentLanguage()->text(),
			Title::newFromLinkTarget( $revision->getPageAsLinkTarget() )->getFullURL(
				[ 'diff' => 'prev', 'oldid' => $revision->getId() ], false, PROTO_CANONICAL ) );
		$minor = '';
		$size = '';
		if ( $revision->isMinor() ) {
			$minor .= wfMessage( 'discord-minor' )->inContentLanguage()->text();
		}
		$parentId = $revision->getParentId();
		if ( $parentId ) {
			$parent = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionById( $parentId );
			if ( $parent ) {
				$size .= wfMessage( 'discord-size', sprintf( "%+d", $revision->getSize() - $parent->getSize() ) )
					->inContentLanguage()->text();
			}
		}
		if ( $size == '' ) {
			$size .= wfMessage( 'discord-size', sprintf( "%d", $revision->getSize() ) )->inContentLanguage()->text();
		}
		return wfMessage( 'discord-revisionlinks', $diff, $minor, $size )->inContentLanguage()->text();
	}

	/**
	 * Strip bad characters from a URL
	 * @param string $url
	 * @return string
	 */
	public static function encodeURL( string $url ): string {
		$url = str_replace( " ", "%20", $url );
		$url = str_replace( "(", "%28", $url );
		return str_replace( ")", "%29", $url );
	}

	/**
	 * Formats bytes to a string representing B, KB, MB, GB, TB
	 * @param int $bytes
	 * @param ?int $precision
	 * @return string
	 */
	public static function formatBytes( int $bytes, ?int $precision = 2 ): string {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );

		$bytes /= ( 1 << ( 10 * $pow ) );

		return round( $bytes, $precision ) . ' ' . $units[$pow];
	}

	/**
	 * Truncate text to maximum allowed characters
	 * @param string $text
	 * @return string
	 */
	public static function truncateText( string $text ): string {
		global $wgDiscordMaxChars;
		if ( $wgDiscordMaxChars ) {
			if ( strlen( $text ) > $wgDiscordMaxChars ) {
				$text = substr( $text, 0, $wgDiscordMaxChars );
				$text = $text . '...';
			}
		}
		return $text;
	}

	/**
	 * Sanitise text input to remove the potential for abuse of Discord's role pings.
	 * @param string $text
	 * @return string
	 */
	public static function sanitiseText( string $text ): string {
		return preg_replace( '/([`@])/', '', $text );
	}
}
