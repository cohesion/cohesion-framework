requirejs.config({
    baseUrl: '/assets/js',
    paths: {
        modules: 'modules',
        vendor: '../vendor',
        jquery: '../vendor/jquery/dist/jquery.min',
        bootstrap: '../vendor/bootstrap/dist/js/bootstrap.min',
        stache: '../vendor/requirejs-mustache/stache',
        text: '../vendor/requirejs-text/text',
        mustache: '../vendor/mustache/mustache',
        templates: '../templates'
    },
    waitSeconds: 60
});

// load common libraries
require(['jquery', 'bootstrap', 'stache'], function($) {
});
