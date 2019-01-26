import { find, dispatch, on, off } from 'domestique';

const win = typeof window !== 'undefined' ? window : this;
const doc = win.document;

function isElementInViewport(element) {
    const rect = element.getBoundingClientRect();

    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

export default function toggle(options = {}) {
    const settings = options || {};
    const namespace = settings.namespace || 'toggle';

    // ---

    const instances = {};
    let active;

    function open(control, id, target) {
        find(`[aria-controls="${id}"]`)
            .forEach(c => c.setAttribute('aria-expanded', 'true'));

        target.classList.add(`${namespace}--ready`);

        target.setAttribute('aria-hidden', 'false');
        target.setAttribute('tabindex', '-1');

        // Delay focus to avoid page scroll jumps
        setTimeout(() => {
            target.focus();
        }, 0);

        target.scrollTop = 0;

        const instance = instances[id] = {
            control: control,
            target: target,
            keyup: function(e) {
                if (e.keyCode !== 27) {
                    return;
                }

                close(id);
            },
            activate: function() {
                active = id;
            },
            deactivate: function() {
                active = null;
            },
            evtOptions: {
                passive: true
            }
        };

        on(doc, 'keyup', instance.keyup, instance.evtOptions);

        on(target, 'mouseenter', instance.activate, instance.evtOptions);
        on(target, 'mouseleave', instance.deactivate, instance.evtOptions);
        on(target, 'touchstart', instance.activate, instance.evtOptions);
        on(target, 'touchend', instance.deactivate, instance.evtOptions);

        dispatch(
            target,
            `${namespace}:open`,
            {
                bubbles: true,
                cancelable: true,
                detail: {
                    control: control
                }
            }
        );
    }

    function close(id, returnFocus = true) {
        if (!id || !instances[id]) {
            return false;
        }

        const {
            control,
            target,
            keyup,
            activate,
            deactivate,
            evtOptions
        } = instances[id];

        delete instances[id];

        off(target, 'mouseenter', activate, evtOptions);
        off(target, 'mouseleave', deactivate, evtOptions);
        off(target, 'touchstart', activate, evtOptions);
        off(target, 'touchend', deactivate, evtOptions);

        off(doc, 'keyup', keyup, evtOptions);

        if (active === id) {
            active = null;
        }

        target.setAttribute('aria-hidden', 'true');
        target.removeAttribute('tabindex');
        target.blur();

        find(`[aria-controls="${id}"]`)
            .forEach(c => c.setAttribute('aria-expanded', 'false'));

        if (returnFocus && isElementInViewport(control)) {
            control.focus();
        }

        dispatch(
            target,
            `${namespace}:close`,
            {
                bubbles: true,
                cancelable: true,
                detail: {
                    control: control
                }
            }
        );
    }

    function closeAll(except, returnFocus = true) {
        for (const id in instances) {
            id !== except && close(id, returnFocus);
        }
    }

    // ---

    const toggleDataAttribute = `data-${namespace}`;

    const onDocClick = function(e) {
        let control = e.target;

        while (!control.hasAttribute(toggleDataAttribute)) {
            if (!control.parentElement || control === doc.body) {
                break;
            }

            control = control.parentElement;
        }

        if (!control.hasAttribute(toggleDataAttribute)) {
            if (!active) {
                closeAll();
            }

            return;
        }

        const targetId = control.getAttribute('aria-controls');

        if (!targetId) {
            // Check if we're inside a panel and close it
            while (control) {
                if (false !== close(control.getAttribute('id'))) {
                    e.preventDefault();
                    break;
                }

                control = control.parentElement;
            }

            return;
        }

        const target = doc.getElementById(targetId);

        if (!target) {
            return;
        }

        e.preventDefault();

        if (target.getAttribute('aria-hidden') === 'false') {
            close(targetId);
        } else {
            if (!settings.allowMultiple) {
                closeAll(targetId, false);
            }

            open(control, targetId, target);
        }
    };

    const removeDocClickListener = on(doc, 'click', onDocClick);

    const onWindowScroll = function() {
        if (!active && !!settings.closeOnScroll) {
            closeAll();
        }
    };

    const removeWinScrollListener = on(win, 'scroll', onWindowScroll);

    return {
        destroy: function() {
            removeDocClickListener();
            removeWinScrollListener();

            closeAll();
        }
    };
};
