const win = typeof window !== 'undefined' ? window : this;
const doc = win.document;
const docEl = doc.documentElement;

const query = (el) => [].slice.call(doc.querySelectorAll(el));

function trigger(element, eventName, detail) {
    let event;

    try {
        event = new CustomEvent(eventName, {
            detail: detail,
            bubbles: true,
            cancelable: true
        });
    } catch (e) {
        event = doc.createEvent('CustomEvent');
        event.initCustomEvent(eventName, true, true, detail);
    }

    element.dispatchEvent(event);
}

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
    if (
        !('addEventListener' in win) ||
        !('querySelector' in doc) ||
        !docEl.classList
    ) {
        return {
            destroy: function() {}
        }
    }

    const settings = options || {};
    const namespace = settings.namespace || 'toggle';

    // ---

    const instances = {};
    let active;

    function open(control, id, target) {
        query(`[aria-controls="${id}"]`)
            .forEach((c) => c.setAttribute('aria-expanded', 'true'));

        target.classList.add(`${namespace}--ready`);

        target.setAttribute('aria-hidden', 'false');
        target.setAttribute('tabindex', '-1');

        // Delay focus to avoid page scroll jumps
        setTimeout(function() {
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

        doc.addEventListener('keyup', instance.keyup, instance.evtOptions);

        target.addEventListener('mouseenter', instance.activate, instance.evtOptions);
        target.addEventListener('mouseleave', instance.deactivate, instance.evtOptions);
        target.addEventListener('touchstart', instance.activate, instance.evtOptions);
        target.addEventListener('touchend', instance.deactivate, instance.evtOptions);

        trigger(
            target,
            `${namespace}:open`,
            {control: control}
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

        target.removeEventListener('mouseenter', activate, evtOptions);
        target.removeEventListener('mouseleave', deactivate, evtOptions);
        target.removeEventListener('touchstart', activate, evtOptions);
        target.removeEventListener('touchend', deactivate, evtOptions);

        doc.removeEventListener('keyup', keyup, evtOptions);

        if (active === id) {
            active = null;
        }

        target.setAttribute('aria-hidden', 'true');
        target.removeAttribute('tabindex');
        target.blur();

        query(`[aria-controls="${id}"]`)
            .forEach((c) => c.setAttribute('aria-expanded', 'false'));

        if (returnFocus && isElementInViewport(control)) {
            control.focus();
        }

        trigger(
            target,
            `${namespace}:close`,
            {control: control}
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

    doc.addEventListener('click', onDocClick);

    const onWindowScroll = function() {
        if (!active && !!settings.closeOnScroll) {
            closeAll();
        }
    };

    win.addEventListener('scroll', onWindowScroll);

    return {
        destroy: function() {
            doc.removeEventListener('click', onDocClick);
            win.removeEventListener('scroll', onWindowScroll);

            closeAll();
        }
    };
};
