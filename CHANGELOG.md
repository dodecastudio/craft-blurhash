# BlurHash Changelog

## 2.0.0-beta.3 - 2022-04-16

- Adds file checking to prevent errors if a file has been removed from disk but not from Craft DB (Thanks to [@mokopan](https://github.com/dodecastudio/craft-blurhash/issues/8) for the suggestion 🙌).
- Adds a helper function, `memoryInfo`. This will return the approximate memory required (in bytes) to load an asset in to memory. Available as a twig filter and in GraphQL.
- Added a sample GraphQL asset query, located in `resources/demo.graphql`.

## 2.0.0-beta.2 - 2022-03-23

- Ensures blurhash images are to the same aspect ratio as the original Craft asset.
- A demo template has been added - copy it to your Craft CMS project to get started. Find it in `resources/demo.twig`.

## 2.0.0-beta.1 - 2022-03-10

- Migration of Craft 3 version, with all existing functionality.
