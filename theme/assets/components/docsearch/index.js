import {ready, find} from 'domestique';

ready(() => {
    find('[data-docsearch]:not([data-docsearch-initialized])').forEach(element => {
        element.setAttribute('data-docsearch-initialized', true);

        import('./algolia-autocomplete.js').then(module => {
            module.init(element);
        });
    })
});
