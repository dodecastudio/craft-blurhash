# BlurHash Changelog

## 3.0.5 - 2025-10-03

- Updated: Added new `defaultAverageColor` config setting, which will be returned if an average color cannot be determined from an asset or blurhash string.
- Fixed: A bug whereby the `@averageColor` GraphQL directive would produce an error when supplied with an unsupported asset. Fixes [#27](https://github.com/dodecastudio/craft-blurhash/,issues/27), thanks to [@plcdnl](https://github.com/plcdnl) and [@jbach](https://github.com/jbach) for raising and contributing.

## 3.0.4 - 2025-03-20

- Updated: Added support for HEIC images. Fixes [#24](https://github.com/dodecastudio/craft-blurhash/issues/24) - thanks to @VincentSmackIt for raising.
- Fixed: Minor change to some syntax introduced in 3.0.2 to better support older versions of PHP.

## 3.0.3 - 2024-12-10

- Updated: Further simplified checking whether a file exists or not, removing superfluous code. Related to [#22](https://github.com/dodecastudio/craft-blurhash/issues/22).

## 3.0.2 - 2024-11-18

- Fixed: Issue with the cache key when `dateModified` is not set for an asset. Thanks for [@jorisnoo](https://github.com/jorisnoo) for finding and fixing this bug.

## 3.0.1 - 2024-10-11

- Added: Config setting `checkFileExists`. If this is set to `false`, then the plugin will not check that the asset file exists before executing. Caution: may lead to unexpected results. Can hopefully assist with [#22](https://github.com/dodecastudio/craft-blurhash/issues/22).

## 3.0.0 - 2024-04-22

- Updated: Added support for Craft 5.

## 2.0.5 - 2023-08-04

- Fixed: Ensure component count is rounded up to prevent zero being returned, fixes #18.

## 2.0.4 - 2022-08-18

- Updated: Added caching to average color functionality.

## 2.0.3 - 2022-06-20

- Updated: Added more filetypes to the supported filetypes list (was being a little overly cautious previously).
- Fixed: Incorrect variable value being returned by Graph QL query, fixes #15.

## 2.0.2 - 2022-06-16

- Updated: Composer.json file to better support Craft 3 and 4 across a single version.

## 2.0.1 - 2022-05-16

- Added settings config. See the `config.php` file for details.

## 2.0.0-beta.3 - 2022-04-16

- Adds file checking to prevent errors if a file has been removed from disk but not from Craft DB (Thanks to [@mokopan](https://github.com/dodecastudio/craft-blurhash/issues/8) for the suggestion 🙌).
- Adds a helper function, `memoryInfo`. This will return the approximate memory required (in bytes) to load an asset in to memory. Available as a twig filter and in GraphQL.
- Added a sample GraphQL asset query, located in `resources/demo.graphql`.

## 2.0.0-beta.2 - 2022-03-23

- Ensures blurhash images are to the same aspect ratio as the original Craft asset.
- A demo template has been added - copy it to your Craft CMS project to get started. Find it in `resources/demo.twig`.

## 2.0.0-beta.1 - 2022-03-10

- Migration of Craft 3 version, with all existing functionality.
