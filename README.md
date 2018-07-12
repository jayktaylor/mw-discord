# mediawiki-discord
<img src="https://discordapp.com/assets/fc0b01fe10a0b8c602fb0106d8189d9b.png" align="right" height=100>
<img src="https://takahashi-it.com/wp-content/uploads/2017/01/MediaWiki_logo_1-800x538.jpg" align="right" height=100>
<br>

> A simple extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) which sends notifications of wiki activity to [Discord](https://discordapp.com/). Early stage of development.

This project was developed primarily for the purpose of assisting the SoulFire team with the development of [Gothic II: The Chronicles of Myrtana](https://kronikimyrtany.pl/en).

## Features / Roadmap 

- :heavy_check_mark: simple notification (`content` key) to Discord channel with [webhook](https://discordapp.com/developers/docs/resources/webhook)
- :heavy_check_mark: hooks
  - :heavy_check_mark: [PageContentSaveComplete](https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete)
  - :heavy_check_mark: [PageContentInsertComplete](https://www.mediawiki.org/wiki/Manual:Hooks/PageContentInsertComplete)
  - :heavy_check_mark: [TitleMoveComplete](https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete)
  - :heavy_check_mark: [ArticleDeleteComplete](https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete)
  - :heavy_check_mark: [ArticleProtectComplete](https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete)
  - :heavy_check_mark: [ArticleUndelete](https://www.mediawiki.org/wiki/Manual:Hooks/ArticleUndelete)
  - :heavy_check_mark: [UploadComplete](https://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete)
  - :heavy_check_mark: [FileDeleteComplete](https://www.mediawiki.org/wiki/Manual:Hooks/FileDeleteComplete)
  - :heavy_check_mark: [LocalUserCreated](https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated)
  - :heavy_check_mark: [BlockIpComplete](https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete)
  - :heavy_check_mark: [UnblockUserComplete](https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete)
  - :heavy_check_mark: [UserRights](https://www.mediawiki.org/wiki/Manual:Hooks/UserRights)
- :heavy_check_mark: clickable URLs in notifications
- :heavy_check_mark: `UploadComplete` - sending pictures as embeds
- :heavy_check_mark: i18n
  - :heavy_check_mark: English
  - :heavy_check_mark: Polish
  - :heavy_check_mark: Russian
- :x: `ArticleProtectComplete` - showing new protection settings of page, eg. in embed variables
- :x: configuration
  - :x: setting language of notifications
  - :x: excluding specific notifications
  - :x: excluding specific pages
  - :x: excluding specific namespaces
  - :x: excluding specific user roles
