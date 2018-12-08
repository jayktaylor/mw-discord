# Discord (mw-discord)
MediaWiki extension for sending notifications to a Discord webhook from MediaWiki.

## Requirements
- **Discord webhook URL**: This can be obtained by editing a channel on a server with the correct permissions.
- **MediaWiki 1.31+**

## Configuration
- `$wgDiscordWebhookURL` - A string or array containing webhook URLs

### Optional

- `$wgDiscordNoBots` - Do not send notifications that are triggered by a bot account - default: `true`
- `$wgDiscordNoMinor` - Do not send notifications that are for minor edits - default: `false`
- `$wgDiscordNoNull` - Do not send notifications for null edits - default: `true`

## Hooks used
- `PageContentSaveComplete` - New edits to pages and page creations
- `ArticleDeleteComplete` - Page deletions