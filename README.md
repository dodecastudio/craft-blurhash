# BlurHash plugin for Craft CMS

[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/dodecastudio/craft-blurhash)

<img src="src/icon.svg" width="128" height="128" />

**Render a BlurHash from a given asset in Craft CMS.**

A [BlurHash](https://blurha.sh/) is a compact representation of a placeholder for an image. A blurred version of an image, useful for displaying whilst the full resolution image is loading.

This plugin uses kornrunner's PHP implementation of BlurHash, [php-blurhash](https://github.com/kornrunner/php-blurhash).

## Requirements

- Craft CMS 3.X or 4.x
- PHP 7.4+
- GD / ImageMagick (_as required by Craft CMS_)

## Installation

Install the plugin as follows:

1.  Open your terminal and go to your Craft project:

        cd /path/to/project

2.  Then tell Composer to load the plugin:

        composer require dodecastudio/craft-blurhash

3.  In the Control Panel, go to Settings → Plugins and click the “Install” button for BlurHash.

## BlurHash Overview

The BlurHash plugin will generate a BlurHash image from a craft asset. Here's an example of what you can expect:

### Input:

<img src="resources/img/example-01-photo.jpg" width="512" />

### Result:

<img src="resources/img/example-01-blurhash.jpg" width="512" />

## Using BlurHash

### Common use

To use the plugin, first grab an asset from Craft, perhaps something like this:

```twig
{% set testAsset = craft.assets().id(101).one() %}
```

You can then pass your asset to the plugin's `blurhash` function, which will return a blurhash image as a data-url, perfect for using in an `img` element like so:

```twig
<img src="{{ blurhash(testAsset) }}" width="{{testAsset.width}}" height="{{testAsset.height}}" style="aspect-ratio: {{testAsset.width}} / {{testAsset.height}};" />
```

Note: Due to the compact dimensions of the blurhash image, its aspect ratio may differ fractionally from the source material. Ensure any image markup correctly renders the aspect ratio based on the source markup if you are trying to prevent [CLS](https://web.dev/cls/). One way to achieve this is to use the [CSS `aspect-ratio` property](https://developer.mozilla.org/en-US/docs/Web/CSS/aspect-ratio), as used in the example above.

### Other features

#### Generating a blurhash string

If you just want to generate just a blurhash string from a given asset, you can use the `blurhash` filter like this:

```twig
{{ testAsset|blurhash }}
```

Which will return something like this:

```twig
f+IrKQogj]j[ayj[_4ofj[j[ayj[%NazayfQj[j[t6ayWBayofj[Rjj[j@fQj[ay
```

You might want to store this in some way, for use later.

#### Generating a blurhash image from a blurhash string

If you already have a blurhash string and want to generate an image from it, you can use the `blurhashToUri` function like this:

```twig
<img src="{{ blurhashToUri('f+IrKQogj]j[ayj[_4ofj[j[ayj[%NazayfQj[j[t6ayWBayofj[Rjj[j@fQj[ay') }}" width="256" height="256" alt="Blurhash image" />
```

Note that blurhash strings do not have any data encoded about the size or scale of the image. You may need additional information in order to display the correct aspect ratio.

#### Returning average color for an image

BlurHash strings contain the average color for the image. You can decode this value from a BlurHash string and return it as a [Craft ColorData object](https://docs.craftcms.com/api/v3/craft-fields-data-colordata.html#public-properties).

```twig
{{ averageColor('f+IrKQogj]j[ayj[_4ofj[j[ayj[%NazayfQj[j[t6ayWBayofj[Rjj[j@fQj[ay') }}
{{ averageColor('f+IrKQogj]j[ayj[_4ofj[j[ayj[%NazayfQj[j[t6ayWBayofj[Rjj[j@fQj[ay').getRgb() }}
```

...or directly from an asset:

```twig
{{ testAsset|averageColor }}
{{ averageColor(testAsset) }}
{{ averageColor(testAsset).getRgb() }}
```

#### Returning memory info

Images need to be loaded in to memory in order to be read, and a blurhash generated. This helper function will tell you approximately how much memory (in bytes) will be needed for a given image.

```twig
{{ testAsset|memoryInfo }}
```

### GraphQL Support

As of v1.1.0 it's possible to use the plugin via Graph QL. The same functionality that's available with the Twig functions, is now available through Graph QL directives.

#### Returning a blurhash data URI from an asset field

An asset field can be returned as a data uri to a blurhash image using the `@assetToBlurHash` directive. It can also be used to return a blurhash string by setting the `asUri` argument to `false`.

```graphql
{
  entries(section: "news") {
    title
    ... on news_article_Entry {
      asset {
        blurhashString: url @assetToBlurHash(asUri: false)
        blurhashUri: url @assetToBlurHash
      }
    }
  }
}
```

This will return some JSON which looks a bit something like this:

```json
{
  "data": {
    "entries": [
      {
        "title": "An important news item",
        "asset": [
          {
            "blurhashString": "f+IrKQogj]j[ayj[_4ofj[j[ayj[%NazayfQj[j[t6ayWBayofj[Rjj[j@fQj[ay",
            "blurhashUri": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAAB..."
          }
        ]
      }
    ]
  }
}
```

#### Rendering a blurhash image from a plaintext blurhash string

If you have a blurhash string saved in a Craft, you can render it to a data URI using the `@blurhashToUri` directive. In this example, there is a plaintext field called `blurhashStringField` stored in our news article entry.

```graphql
{
  entries(section: "news") {
    title
    blurhashStringField
    blurhashUri: blurhashStringField @blurhashToUri
  }
}
```

And that will then give us some JSON that looks a bit like this:

```json
{
  "data": {
    "entries": [
      {
        "title": "An important news item",
        "blurhashStringField": "f+IrKQogj]j[ayj[_4ofj[j[ayj[%NazayfQj[j[t6ayWBayofj[Rjj[j@fQj[ay",
        "blurhashUri": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAAB..."
      }
    ]
  }
}
```

#### Getting an average color for an image

If you want to return the average color for an image saved in Craft, you can use the `@averageColor` directive. This returns a hex color value as a string.

```graphql
{
  entries(section: "news") {
    title
    ... on news_article_Entry {
      blurhashStringField @averageColor
      asset {
        averageColor: url @averageColor
      }
    }
  }
}
```

Which will return you some JSON like this:

```
{
  "data": {
    "entries": [
      {
        "title": "An important news item",
        "blurhashStringField": "#a3a696",
        "asset": [
          {
            "averageColor": "#817c82"
          }
        ]
      }
    ]
  }
}
```

## Settings

Default settings can be overridden. Please see the `blurhash-config.php` file for details.

## Licence 🌳

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/dodecastudio/craft-blurhash/).  
And why not? By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

### Thanks! 🙌

Shout-out to [@olsp](https://github.com/olsp) for [buying 100 trees](https://ecologi.com/treeware?tileId=623c81be1644e1fb9a93ad35) and [@mokopan](https://github.com/mokopan) for [buying 25 trees](https://ecologi.com/treeware?tileId=625e603f776930bd0b4f973a).

If you've purchased trees through Ecologi, as part of the Treeware license, please let us know for a shout-out.
