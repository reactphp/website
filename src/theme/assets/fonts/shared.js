module.exports = {
    check: function(font) {
        return ('; ' + document.cookie).split('; font-loaded-' + font + '=').length === 2;
    },
    loaded: function(font) {
        document.documentElement.className += ' font-loaded-' + font;
    }
};
