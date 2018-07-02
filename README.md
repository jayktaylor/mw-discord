# mediawiki-discord

> A simple extension for MediaWiki which sends notifications of wiki activity to Discord. Early stage of development.

This project was developed primarily for the purpose of assisting the SoulFire team with the development of [Gothic II: The Chronicles of Myrtana](https://kronikimyrtany.pl/en).

## Features / Roadmap 

- :heavy_check_mark: simple notification (`content` key) to Discord channel with Webhook 
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
- :x: `ArticleProtectComplete` - showing new protection settings of page, eg. in embed variables
- :x: `UploadComplete` - sending pictures as embeds
- :x: i18n
