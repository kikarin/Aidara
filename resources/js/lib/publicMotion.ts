import type { Directive } from 'vue';

let revealObserver: IntersectionObserver | null = null;

const getRevealObserver = () => {
    revealObserver ??= new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver?.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
    );

    return revealObserver;
};

/** Fades an element in when it enters the viewport. Binding value = delay in ms. */
export const vReveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        if (typeof IntersectionObserver === 'undefined') {
            return;
        }

        el.style.setProperty('--reveal-delay', `${binding.value ?? 0}ms`);
        el.classList.add('wp-reveal');
        getRevealObserver().observe(el);
    },
    unmounted(el) {
        revealObserver?.unobserve(el);
    },
};

/** Feeds cursor position to `.wp-tile` so its spotlight follows the pointer. */
export const trackSpotlight = (event: PointerEvent) => {
    const el = event.currentTarget as HTMLElement;
    const rect = el.getBoundingClientRect();
    el.style.setProperty('--spot-x', `${event.clientX - rect.left}px`);
    el.style.setProperty('--spot-y', `${event.clientY - rect.top}px`);
};
