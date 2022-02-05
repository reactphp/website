import {ready, find} from 'domestique';

// Workaround for https://github.com/algolia/algoliasearch-client-javascript/issues/691
window.process = {
    env: { DEBUG: undefined }
};

ready(() => {
    find('[data-docsearch]:not([data-docsearch-initialized])').forEach(element => {
        element.setAttribute('data-docsearch-initialized', true);

        import('./algolia-autocomplete.js').then(module => {
            module.init(element);
        });
    })
});
