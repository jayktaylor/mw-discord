# Discord (mw-discord)
MediaWiki extension for sending notifications to a Discord webhook from MediaWiki. When a certain event occurs on your MediaWiki wiki, including new edits, they can be sent as a message to a channel on a Discord server using a webhook.

Multiple webhook URLs are supported and messages will be sent to all of them using cURL, so your web server is required to have cURL installed (for most Linux distros, installing using `sudo apt install curl` should work).

**Live demo**: https://runescape.wiki (https://discord.gg/runescapewiki)

## Requirements
- **Discord webhook URL**: This can be obtained by editing a channel on a server with the correct permissions.
- **MediaWiki 1.31+**
- **cURL**

## Configuration
- `$wgDiscordWebhookURL` - A string **or** array containing webhook URLs

### Optional
- `$wgDiscordNoBots` - Do not send notifications that are triggered by a [bot account](https://www.mediawiki.org/wiki/Manual:Bots) - default: `true`
- `$wgDiscordNoMinor` - Do not send notifications that are for [minor edits](https://meta.wikimedia.org/wiki/Help:Minor_edit) - default: `false`
- `$wgDiscordNoNull` - Do not send notifications for [null edits](https://www.mediawiki.org/wiki/Manual:Purge#Null_edits) - default: `true`
- `$wgDiscordSuppressPreviews` - Force previews for links in Discord messages to be suppressed - default: `true`
- `$wgDiscordDisabledHooks` - Array containing list of hooks to disable sending webhooks for (see [list of hooks used](#Hooks_used)) - default: `[]`

## Hooks used
- `PageContentSaveComplete` - New edits to pages and page creations
- `ArticleDeleteComplete` - Page deletions
- `ArticleUndelete` - Page restorations
- `ArticleRevisionVisibilitySet` - Revision visibility changes
- `ArticleProtectComplete` - Page protections
- `TitleMoveComplete` - Page moves
- `LocalUserCreated` - User registrations
- `BlockIpComplete` - User blocked
- `UnblockUserComplete` - User unblocked
- `UserGroupsChanged` - User rights changed
- `UploadComplete` - File was uploaded
- `FileDeleteComplete` - File revision was deleted
- `FileUndeleteComplete` - File revision was restored

## License
This extension is licensed under the MIT License, [see here](LICENSE) for more information. This project is originally inspired by Szmyk's [mediawiki-discord](https://github.com/Szmyk/mediawiki-discord) project, but has been rewritten completely to be more suitable for my needs.