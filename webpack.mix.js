// webpack.mix.js

let mix = require('laravel-mix');
require("laravel-mix-compress");

mix.sass("resources/scss/homepage.scss","public/css/")
    .minify("resources/js/homepage.js","public/js/homepage.js");

mix.compress({
    productionOnly: true,
});
