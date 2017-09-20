/**
 * Note, that the polyfill does **not** load CSS files asynchronously.
 */
export function supports(win) {
    try {
        return (win || window).document.createElement('link').relList.supports('preload');
    } catch (e) {
        return false;
    }
}

export function polyfill(win) {
    var w = win || window;
    var doc = w.document;

    function poly() {
        var links = w.document.querySelectorAll('link[rel="preload"][as="style"]');

        for (var i = 0; i < links.length; i++) {
            var link = links[i];

            link.rel = '';

            var media = link.getAttribute('media') || 'all';
            var newLink = doc.createElement('link');

            function final() {
                newLink.media = media;
                newLink.removeEventListener('load', final);
            }

            newLink.rel = 'stylesheet';
            newLink.href = link.href;
            newLink.media = 'only x';

            newLink.addEventListener('load', final);

            link.parentNode.insertBefore(newLink, link.nextSibling || link);
        }
    }

    poly();

    var run = w.setInterval(poly, 300);

    w.addEventListener('load', function() {
        poly();
        w.clearInterval(run);
    });
}

if (!supports()) {
    polyfill();
}
