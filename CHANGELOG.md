# BlurHash Changelog

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
