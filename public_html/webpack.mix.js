const mix = require("laravel-mix");
// const SVGSpritemapPlugin = require('svg-spritemap-webpack-plugin');

mix.js("resources/js/login.js", "public/assets/js")
    .js("resources/js/backend/app.js", "public/assets/js")
    .js("resources/js/frontend/main.js", "public/assets/js")
    /* .webpackConfig({
        plugins: [
            new SVGSpritemapPlugin(['resources/js/src/assets/images/*.svg'],{
                output: {
                    filename: "assets/images/sprite.svg",
                },
                sprite: {
                    prefix: "icon-"
                }
            }),
        ],
    }) */
    .vue();
