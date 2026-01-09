<?php

namespace MediaWiki\Extension\Discord;

use Exception;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Http\HttpRequestFactory;
use Wikimedia\Http\MultiHttpClient;

class DiscordMessageSender {
	public MultiHttpClient $multiHttpClient;

	public function __construct( HttpRequestFactory $httpRequestFactory ) {
		$this->multiHttpClient = $httpRequestFactory->createMultiClient( [
			'connTimeout' => 10,
			'reqTimeout' => 10
		] );
	}

	/**
	 * Sends a message to Discord, based on the extension's configuration.
	 * @param string $hookName
	 * @param string $msg
	 * @return void
	 */
	public function sendToDiscord( string $hookName, string $msg ) {
		global $wgDiscordWebhookURL, $wgDiscordEmojis, $wgDiscordUseEmojis, $wgDiscordPrependTimestamp;

		if ( !$wgDiscordWebhookURL ) {
			return;
		}

		$urls = [];

		if ( is_array( $wgDiscordWebhookURL ) ) {
			$urls = array_merge( $urls, $wgDiscordWebhookURL );
		} elseif ( is_string( $wgDiscordWebhookURL ) ) {
			$urls[] = $wgDiscordWebhookURL;
		} else {
			wfDebugLog( 'discord',
				'The value of $wgDiscordWebhookURL is not valid and therefore no webhooks could be sent.' );
			return;
		}

		// Strip whitespace to just one space
		$stripped = preg_replace( '/\s+/', ' ', $msg );

		if ( $wgDiscordPrependTimestamp ) {
			$dateString = gmdate( wfMessage( 'discord-timestampformat' )->inContentLanguage()->text() );
			$stripped = $dateString . ' ' . $stripped;
		}

		if ( $wgDiscordUseEmojis ) {
			$emoji = $wgDiscordEmojis[$hookName];
			$stripped = $emoji . ' ' . $stripped;
		}

		$reqs = [];
		foreach ( $urls as $url ) {
			$reqs[] = $this->buildRequest( $url, $stripped );
		}

		DeferredUpdates::addCallableUpdate( function () use ( $reqs ) {
			try {
				$this->multiHttpClient->runMulti( $reqs );
			} catch ( Exception ) {
				// TODO: better logging
			}
		} );
	}

	/**
	 * Builds a request for use with MultiHttpClient
	 * @param string $url
	 * @param string $msg
	 * @return array
	 */
	private function buildRequest( string $url, string $msg ): array {
		return [
			'method' => 'POST',
			'url' => $url,
			'headers' => [
				'Content-Type' => 'application/json',
				'User-Agent' => 'mw-discord/1.2 (github.com/jayktaylor)'
			],
			'body' => json_encode( [
				'content' => $msg,
				'allowed_mentions' => [
					'parse' => []
				]
			] )
		];
	}
}
