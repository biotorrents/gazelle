/**
 * https://laravel-mix.com/docs/main/installation
 */

let mix = require("laravel-mix");

// css
mix.combine("resources/scss", "public/css/app.css");
mix.sass("public/css/app.css", "public/css/app.css");
mix.minify("public/css/app.css");

// js
mix.combine("resources/js", "public/js/app.js");
mix.minify("public/js/app.js");
