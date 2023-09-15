let mix = require('laravel-mix');

require('./nova.mix');

mix.setPublicPath('dist')
    .js('resources/js/nested-many.js', 'js')
    .vue({ version: 3 })
    .css('resources/css/nested-many.css', 'css')
    .nova('lupennat/nested-many');
