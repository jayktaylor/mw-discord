# Discord (mw-discord)
MediaWiki extension for sending notifications to a Discord webhook from MediaWiki. When a certain event occurs on your MediaWiki wiki, including new edits, they can be sent as a message to a channel on a Discord server using a webhook.

Multiple webhook URLs are supported and messages will be sent to all of them.

**Live demo**: https://runescape.wiki (https://discord.gg/runescapewiki)

<p align="center">
  <img src="https://i.imgur.com/tCehglJ.png" alt="Example"/>
</p>

## Requirements
- **Discord webhook URL**: This can be obtained by editing a channel on a server with the correct permissions.
- **MediaWiki 1.31+**

### Recommended
- **cURL**: By default, this extension sends requests using cURL. If you don't have cURL, you could try setting `$wgDiscordUseFileGetContents` to `true` instead, but this is not recommended.

## Installation

1. Clone this repository to your MediaWiki installation's `extensions` folder using `git clone https://github.com/jaydenkieran/mw-discord.git -b REL1_35 Discord` (or change `REL1_35` to the branch that corresponds with or is the closest version under your MediaWiki version, e.g `REL1_31` will work for 1.32, 1.33, and 1.34)
2. Modify your `LocalSettings.php` file and add:

```php
// Load the extension
wfLoadExtension( 'Discord' );
// Set the webhook URL(s) (string or array)
$wgDiscordWebhookURL = [ '' ];
```

For further configuration variables, see [below](#configuration).

### Getting a webhook URL
To get a webhook URL for use with this extension, open the Discord client and go to a server where you have the `Manage Webhooks` permission. Click the cog icon when hovering over a text channel, switch to the Webhooks tab on the left of the interface, and click 'Create webhook'. The webhook URL can then be copied from that interface.

## Configuration
This extension can be configured using the `LocalSettings.php` file in your MediaWiki installation.

| Variable | Type | Description |
| --- | --- | --- |
| `$wgDiscordWebhookURL` | string/array | Discord webhook URLs

### Optional
| Variable | Type | Description | Default |
| --- | --- | --- | --- |
| `$wgDiscordNoBots` | bool | Do not send notifications that are triggered by a [bot account](https://www.mediawiki.org/wiki/Manual:Bots) | `true`
| `$wgDiscordNoMinor` | bool | Do not send notifications that are for [minor edits](https://meta.wikimedia.org/wiki/Help:Minor_edit) | `false`
| `$wgDiscordNoNull` | bool | Do not send notifications for [null edits](https://www.mediawiki.org/wiki/Manual:Purge#Null_edits) | `true`
| `$wgDiscordSuppressPreviews` | bool | Force previews for links in Discord messages to be suppressed | `true`
| `$wgDiscordMaxChars` | int | Maximum amount of characters for user-generated text (e.g summaries, reasons). Set to `null` to disable truncation | `null`
| `$wgDiscordDisabledHooks` | array | List of hooks to disable sending webhooks for (see [below](#hooks-used)) | `[]`
| `$wgDiscordDisabledNS` | array | List of namespaces to disable sending webhooks for | `[]`
| `$wgDiscordDisabledUsers` | array | List of users whose performed actions shouldn't send webhooks | `[]`
| `$wgDiscordPrependTimestamp` | bool | Prepend a timestamp (in UTC) to all sent messages. The format can be changed by editing the MediaWiki message `discord-timestampformat` | `false`
| `$wgDiscordUseFileGetContents` | bool | Use `file_get_contents` instead of cURL. Requires `allow_url_fopen` to be set to true in `php.ini`. Not recommended as cURL makes simultaneous calls instead. | `false`
| `$wgDiscordUseEmojis` | bool | Prepend emojis to different types of messages to help distinguish them | `false`

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
- `AfterImportPage` - Page was imported
- `ArticleMergeComplete` - Article histories was merged

### [Approved Revs](https://www.mediawiki.org/wiki/Extension:Approved_Revs)
- `ApprovedRevsRevisionApproved` - Revision was approved
- `ApprovedRevsRevisionUnapproved` - Revision was unapproved
- `ApprovedRevsFileRevisionApproved` - File revision was approved
- `ApprovedRevsFileRevisionUnapproved` - File revision was unapproved

## Translation
This extension can be translated through the messages in the `ì18n` folder if you're a developer. As a wiki administrator, you may find it a better option to edit the messages on-site in the MediaWiki namespace.

Any excess whitespace in text that is translated will be stripped (e.g double spaces, etc).

## License
This extension is licensed under the MIT License, [see here](LICENSE) for more information. This project is originally inspired by Szmyk's [mediawiki-discord](https://github.com/Szmyk/mediawiki-discord) project, but has been rewritten completely to be more suitable for my needs.