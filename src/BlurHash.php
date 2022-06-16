<?php
/**
 * BlurHash plugin for Craft CMS 4.x
 *
 * Render a BlurHash from a given image.
 *
 * @link      https://dodeca.studio
 * @copyright Copyright (c) 2021 Dodeca Studio
 */

namespace dodecastudio\blurhash;

use dodecastudio\blurhash\models\Settings;
use dodecastudio\blurhash\gql\directives\AverageColor;
use dodecastudio\blurhash\gql\directives\BlurHashToUri;
use dodecastudio\blurhash\gql\directives\AssetToBlurHash;
use dodecastudio\blurhash\gql\directives\MemoryInfo;
use dodecastudio\blurhash\services\BlurHashService;
use dodecastudio\blurhash\twigextensions\BlurHashTwigExtension;

use Craft;
use craft\base\Plugin;
use craft\services\Gql;
use craft\services\Plugins;
use craft\events\RegisterGqlDirectivesEvent;
use yii\base\Event;


/**
 * 
 * @author    Dodeca Studio
 * @package   BlurHash
 * @since     2.0.0
 *
 */
class BlurHash extends Plugin
{

    // Static Properties

    /**
     * @var BlurHash
     */
    public static $plugin;

    // Public Methods

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'blurHashServices' => BlurHashService::class,
        ]);
        
        // Add in our Twig extensions
        Craft::$app->view->registerTwigExtension(new BlurHashTwigExtension());

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_DIRECTIVES,
            function(RegisterGqlDirectivesEvent $event) {
                $event->directives[] = AverageColor::class;
                $event->directives[] = BlurHashToUri::class;
                $event->directives[] = AssetToBlurHash::class;
                $event->directives[] = MemoryInfo::class;
            }
        );

        Craft::info(
            Craft::t(
                'blur-hash',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Settings

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

}
