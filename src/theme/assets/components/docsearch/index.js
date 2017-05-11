(function(document) {
    var elements = document.querySelectorAll('[data-docsearch]');

    if (!elements.length) {
        return;
    }

    var idCounter = 0;

    require.ensure([], function() {
        require('./algolia-autocomplete.css');

        var docsearch = require('docsearch.js');

        Array.prototype.forEach.call(elements, function (item) {
            var id = item.getAttribute('id');

            if (!id) {
                id = 'docsearch-' + idCounter++;
                item.setAttribute('id', id);
            }

            docsearch({
                apiKey: '4c440463ddff54a35b4d7dc24afb010b',
                indexName: 'reactphp',
                inputSelector: '#' + id,
                debug: 'true' === item.getAttribute('data-docsearch-debug'),
                algoliaOptions: {
                    hitsPerPage: 5
                }
            });
        });
    });
})(document);
