// Note, that critical javascript must not require any polyfills, eg. can't
// use dynamic imports as webpack uses promises for that!
import polyfill from '@dotsunited/load-css-polyfill';
polyfill();

require('./fonts/fonts.css');
require('./styles/styles-critical.css');
require('./components/off-canvas-menu/index-critical');
require('./components/component-info/index-critical');
require('./components/version-selector/index-critical');
