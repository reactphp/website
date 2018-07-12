import {ready} from "dom-event-helpers";
import {query} from "dom-query-helpers";

ready(() => {
    query('[data-docsearch]:not([data-docsearch-initialized])').forEach(element => {
        element.setAttribute('data-docsearch-initialized', true);

        import('./algolia-autocomplete.js').then(module => {
            module.init(element);
        });
    })
});
