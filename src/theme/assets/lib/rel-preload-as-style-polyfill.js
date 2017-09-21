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
        var links = doc.querySelectorAll('link[rel="preload"][as="style"]');

        for (var i = 0; i < links.length; i++) {
            var link = links[i];

            link.rel = '';

            var newLink = doc.createElement('link');

            function final() {
                newLink.media = link.getAttribute('media') || 'all';
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

    if (doc.readyState !== "complete") {
        var run = w.setInterval(poly, 300);

        w.addEventListener('load', function() {
            poly();
            w.clearInterval(run);
        });
    }
}

if (!supports()) {
    polyfill();
}
