import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { onBeforeUnmount, onMounted } from 'vue';

let lenis: Lenis | null = null;

const scrollOffset = (target: HTMLElement) => -(parseFloat(getComputedStyle(target).scrollMarginTop) || 0);

/** Scrolls to an element, smoothly through Lenis when it is running. Honours `scroll-margin-top`. */
export const scrollToElement = (target: HTMLElement | null) => {
    if (!target) return;

    if (lenis) {
        lenis.scrollTo(target, { offset: scrollOffset(target) });
    } else {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Lenis' built-in `anchors` option swallows the skip link's focus move, so same-page anchors are handled here instead.
const onAnchorClick = (event: MouseEvent) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const anchor = (event.target as HTMLElement | null)?.closest<HTMLAnchorElement>('a[href*="#"]');
    if (!anchor || anchor.classList.contains('skip-link') || anchor.target === '_blank') return;

    const url = new URL(anchor.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || !url.hash) return;

    const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
    if (!target) return;

    event.preventDefault();
    scrollToElement(target);
    history.pushState(history.state, '', url.hash);
};

/** Enables Lenis smooth scrolling while the calling component is mounted (skipped for reduced motion). */
export function useSmoothScroll() {
    let owner = false;

    onMounted(() => {
        if (lenis || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        owner = true;
        lenis = new Lenis({
            autoRaf: true,
            lerp: 0.1,
            wheelMultiplier: 0.9,
            autoToggle: true,
            allowNestedScroll: true,
        });
        document.addEventListener('click', onAnchorClick);
    });

    onBeforeUnmount(() => {
        if (!owner) return;

        document.removeEventListener('click', onAnchorClick);
        lenis?.destroy();
        lenis = null;
    });
}
