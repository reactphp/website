var shared = require('./shared');

var cookieDays = 1;

function setCookie(font) {
    var date = new Date();
    date.setTime(date.getTime() + (cookieDays * 24 * 60 * 60 * 1000));

    window.document.cookie = 'font-loaded-' + font + '=true; expires=' + date.toGMTString() + '; path=/';
}

if (!shared.check('source-sans-pro')) {
    require.ensure(['fontfaceobserver/fontfaceobserver'], function() {
        var FontFaceObserver = require('fontfaceobserver/fontfaceobserver');

        var observer = new FontFaceObserver('Source Sans Pro');

        observer
            .load()
            .then(shared.loaded.bind(null, 'source-sans-pro'))
            .then(setCookie.bind(null, 'source-sans-pro'))
            .then(null, function(){})
        ;
    });
}
