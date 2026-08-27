import { createApp } from 'vue';

/**
 * Vue "islands": pages stay server rendered Blade, and any element carrying
 * data-vue="ComponentName" is hydrated into a standalone Vue app. Props come
 * from a JSON blob in data-props.
 *
 *   <div data-vue="PosApp" data-props="{{ json_encode($props) }}"></div>
 */
export function mountIslands(components, root = document) {
    root.querySelectorAll('[data-vue]:not([data-vue-mounted])').forEach((el) => {
        const name = el.dataset.vue;
        const component = components[name];

        if (!component) {
            console.warn(`[vue] Unknown component "${name}" — is it registered for this page's entry?`);
            return;
        }

        let props = {};

        if (el.dataset.props) {
            try {
                props = JSON.parse(el.dataset.props);
            } catch (error) {
                console.error(`[vue] Invalid data-props JSON on "${name}"`, error);
                return;
            }
        }

        createApp(component, props).mount(el);
        el.dataset.vueMounted = 'true';
    });
}

/**
 * Mount every island once the DOM is ready and hand back a mounter so code that
 * injects markup later can hydrate it too.
 */
export function startIslands(components) {
    const mount = (root = document) => mountIslands(components, root);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => mount());
    } else {
        mount();
    }

    return mount;
}
