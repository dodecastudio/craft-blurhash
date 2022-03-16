# BlurHash Changelog

## 1.2.1 - 2022-03-10
- This should fix a previous release error. `main` branch is now for Craft 3 and there is a separate `craft-4` branch, for the Craft 4 release.

## 1.1.1 - 2021-11-30

### Added

- New Twig Function `averageColor` and GraphQL directive `averageColor`, which returns the average color of an image asset or blurhash string.

## 1.1.0 - 2021-11-29

### Added

- Added GraphQL Support (thanks to @stuible on GitHub for the suggestion 🙌).
- Moved all functions to a Service class (thanks to @MoritzLost on Stack Exchange for the suggestion 🙌).

## 1.0.4 - 2021-08-20

### Fixed

- Check image type is either png, jpeg or webp in order to prevent problems with SVG and other filetypes.

## 1.0.3 - 2021-08-05

### Fixed

- Moved image size to cache key.

## 1.0.2 - 2021-08-05

### Added

- Changed size of sampled image to improve the blurred image.

## 1.0.1 - 2021-08-03

### Added

- Added caching to improve load times.
- Changed method of resizing image, resolving SSL errors using `file_get_contents`.

## 1.0.0 - 2021-07-28

### Added

- Initial release
