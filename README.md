# mediawiki-discord

> A simple extension for MediaWiki which sends notifications of wiki activity to Discord. Early stage of development.

This project was developed primarily for the purpose of assisting the SoulFire team with the development of [Gothic II: The Chronicles of Myrtana](https://kronikimyrtany.pl/en).

## Features / Roadmap 

- [x] simple notification (`content` key) to Discord channel with Webhook 
- [ ] hooks
  - [x] [PageContentSaveComplete](https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete)
  - [x] [PageContentInsertComplete](https://www.mediawiki.org/wiki/Manual:Hooks/PageContentInsertComplete)
  - [x] [TitleMoveComplete](https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete)
  - [x] [ArticleDeleteComplete](https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete)
  - [x] [ArticleProtectComplete](https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete)
    - [ ] showing new permissions, eg. in embed variables
  - [x] [ArticleUndelete](https://www.mediawiki.org/wiki/Manual:Hooks/ArticleUndelete)
  - [ ] [UploadComplete](https://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete)
    - [ ] sending pictures as embeds
  - [ ] [FileDeleteComplete](https://www.mediawiki.org/wiki/Manual:Hooks/FileDeleteComplete)
  - [ ] [LocalUserCreated](https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated)
  - [ ] [BlockIpComplete](https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete)
  - [ ] [UnblockUserComplete](https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete)
  - [ ] [UserRights](https://www.mediawiki.org/wiki/Manual:Hooks/UserRights)
