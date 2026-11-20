# BlurHash Changelog

## 3.1.0 - 2026-11-20

- Fixed: Cached blurhashes and average colors are now returned _without_ first checking the asset's file exists. On remote volumes (S3, Servd etc) that check was a network request per asset on every page load, and `averageColor` made it twice, so pages showing many images could take longer to render.
- Fixed: `averageColor` could try to read an average color from an invalid blurhash when the asset's file was missing. It now returns `defaultAverageColor`.
- Fixed: Ensure `imagedestroy()` is only used for PHP 7.X, as it's deprecated in PHP 8.
- Fixed: Images that can't be read (corrupt files, unsupported formats or a failed download) no longer cause an error. The error is logged and `false` or `defaultAverageColor` is returned instead.
- Fixed: TIFF images are now converted with Imagick before encoding, as GD can't read them. If Imagick isn't installed, TIFF and HEIC images are skipped and an error is logged.
- Fixed: Issue with the cache key when `dateModified` is not set for an asset, which had returned in 3.0.4. It now falls back to `dateUpdated` while still supporting older versions of PHP.
- Fixed: A bug whereby the `memoryInfo()` Twig function would produce an error, as it was registered as a filter. The `memoryInfo` filter was unaffected.
- Fixed: A bug whereby the `@averageColor` GraphQL directive would produce an error when applied to an empty value. It now returns `null`.
- Fixed: The `@assetToBlurHash` GraphQL directive now returns `null` rather than an empty string when a blurhash can't be generated, and correctly logs any errors.
- Updated: When a blurhash can't be generated for an asset, this is now cached for 1 day, so the file isn't downloaded and checked again on every page load. Replacing the file will retry straight away.
- Updated: Blurhashes and blurhash images are now cached until the asset changes, rather than for 1 month, so they're no longer regenerated from the original image every month.
- Updated: Average colors are no longer cached, as reading them from a blurhash is quicker than a cache lookup.
- Fixed: `memoryInfo` no longer downloads a copy of the original image, which was left behind as a temp file on every call. It now estimates from the asset's dimensions, giving the same results, and now also supports HEIC images.
- Updated: Blurhashes and average colors for images with transparency are now generated against the image's own average color, rather than black, so they're no longer too dark.
- Updated: Blurhashes for image formats that support transparency (PNG, WebP, GIF, AVIF, TIFF and HEIC) will be regenerated once after updating. JPEG and BMP images are unaffected. Caution: pages showing many of these images may be slower the first time they're viewed.

## 3.0.6 - 2026-04-23

- Updated: Made some performance improvements to key functions.
- Fixed: Mimetype check update. Thanks to [@ishetnogferre](https://github.com/dodecastudio/craft-blurhash/pull/28) for contributing.

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
