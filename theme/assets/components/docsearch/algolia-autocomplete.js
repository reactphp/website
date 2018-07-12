import docsearch from 'docsearch.js';

import './algolia-autocomplete.css';

let idCounter = 0;

export function init(input) {
    let id = input.getAttribute('id');

    if (!id) {
        id = 'docsearch-' + idCounter++;
        input.setAttribute('id', id);
    }

    docsearch({
        apiKey: '4c440463ddff54a35b4d7dc24afb010b',
        indexName: 'reactphp',
        inputSelector: '#' + id,
        debug: 'true' === input.getAttribute('data-docsearch-debug'),
        algoliaOptions: {
            hitsPerPage: 5
        }
    });
}
