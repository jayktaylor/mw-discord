# Discord (mw-discord)
MediaWiki extension for sending notifications to a Discord webhook from MediaWiki. When a certain event occurs on your MediaWiki wiki, including new edits, they can be sent as a message to a channel on a Discord server using a webhook.

Multiple webhook URLs are supported and messages will be sent to all of them.

**Live demo**: https://runescape.wiki (https://discord.gg/runescapewiki)

<p align="center">
  <img src="https://i.imgur.com/tCehglJ.png" alt="Example"/>
</p>

## Requirements
- **Discord webhook URL**: This can be obtained by editing a channel on a server with the correct permissions.
- **MediaWiki**: This extension aims to always support the [latest LTS release](https://www.mediawiki.org/wiki/Version_lifecycle).
  - Use the branch that is equal to, or below your version. For example, if you are using MediaWiki 1.37, use the `REL1_36` branch.
  - We do not guarantee support for versions of MediaWiki that are considered end-of-life.
  - The `master` branch may contain changes that are only applicable to the cutting-edge alpha version of MediaWiki.

### Recommended
- **cURL**: By default, this extension sends requests using cURL. If you don't have cURL, you could try setting `$wgDiscordUseFileGetContents` to `true` instead, but this is not recommended.

## Installation

1. Clone this repository to your MediaWiki installation's `extensions` folder using `git clone https://github.com/jaydenkieran/mw-discord.git -b REL1_35 Discord`
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
These parameters aren't required for the extension to work.

| Variable | Type | Description | Default |
| --- | --- | --- | --- |
| `$wgDiscordNoBots` | bool | Do not send notifications that are triggered by a [bot account](https://www.mediawiki.org/wiki/Manual:Bots) | `true`
| `$wgDiscordNoMinor` | bool | Do not send notifications that are for [minor edits](https://www.mediawiki.org/wiki/Help:Minor_edit) | `false`
| `$wgDiscordNoNull` | bool | Do not send notifications for [null edits](https://www.mediawiki.org/wiki/Manual:Purge#Null_edits) | `true`
| `$wgDiscordSuppressPreviews` | bool | Force previews for links in Discord messages to be suppressed | `true`
| `$wgDiscordMaxChars` | int | Maximum amount of characters for user-generated text (e.g summaries, reasons). Set to `null` to disable truncation | `null`
| `$wgDiscordMaxCharsUsernames` | int | Maximum amount of characters for usernames. Set to `null` to disable truncation | `25`
| `$wgDiscordDisabledHooks` | string array | List of hooks to disable sending webhooks for (see [below](#hooks-used)) | `[]`
| `$wgDiscordDisabledNS` | int array | List of namespace **IDs** to disable sending webhooks for. (see [below](#namespaces)) | `[]`
| `$wgDiscordDisabledUsers` | string array | List of users whose performed actions shouldn't send webhooks | `[]`
| `$wgDiscordPrependTimestamp` | bool | Prepend a timestamp (in UTC) to all sent messages. The format can be changed by editing the MediaWiki message `discord-timestampformat` | `false`
| `$wgDiscordPrependSitename` | bool | Prepend the value of `$wgSitename` to messages. Useful for wiki families. | `false`
| `$wgDiscordUseFileGetContents` | bool | Use `file_get_contents` instead of cURL. Requires `allow_url_fopen` to be set to true in `php.ini`. Not recommended as cURL makes simultaneous calls instead. | `false`
| `$wgDiscordUseEmojis` | bool | Prepend emojis to different types of messages to help distinguish them | `false`
| `$wgDiscordEmojis` | string associative array | Map of hook names and their associated emojis to prepend to messages if `$wgDiscordUseEmojis` is enabled | See [extension.json](/extension.json#L30)

## Hooks used
- `PageSaveComplete` - New edits to pages and page creations
- `PageDeleteComplete` - Page deletions
- `ArticleUndelete` - Page restorations
- `ArticleRevisionVisibilitySet` - Revision visibility changes
- `ArticleProtectComplete` - Page protections
- `PageMoveComplete` - Page moves
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

### [Renameuser](https://www.mediawiki.org/wiki/Extension:Renameuser)
- `RenameUserComplete` - Rename was completed

## Namespaces
As we use Namespace IDs the following resources might be helpful:
- [Built in namespaces' IDs](https://www.mediawiki.org/wiki/Manual:Namespace#Built-in_namespaces)
- [Extension default namespaces](https://www.mediawiki.org/wiki/Extension_default_namespaces)

## Translation
You can submit translations for this extension on [Translatewiki.net](https://translatewiki.net/wiki/Special:Translate/mwgithub-mw-discord).

## License
This extension is available under the MIT license. You can [see here](LICENSE) for more information.

This extension  was inspired by Szmyk's [mediawiki-discord](https://github.com/Szmyk/mediawiki-discord) project.
