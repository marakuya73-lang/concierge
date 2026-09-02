import './bootstrap.js';
import './styles/app.css';

const jumpToTop = () => {
    if (window.location.hash) {
        return;
    }

    const html = document.documentElement;
    const previous = html.style.scrollBehavior;
    html.style.scrollBehavior = 'auto';
    window.scrollTo(0, 0);
    html.style.scrollBehavior = previous;
};

document.addEventListener('turbo:load', jumpToTop);
document.addEventListener('turbo:render', jumpToTop);
document.addEventListener('turbo:before-cache', () => {
    window.scrollTo(0, 0);
});
