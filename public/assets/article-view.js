const article = document.querySelector('[data-article-id]');

if (article) {
    let visibleTime = 0;
    let lastTick = performance.now();
    let scrolled = window.scrollY > 100;

    window.addEventListener('scroll', () => {
        scrolled ||= window.scrollY > 100;
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        lastTick = performance.now();
    });

    const timer = setInterval(async () => {
        const now = performance.now();
        if (!document.hidden) {
            visibleTime += Math.min(now - lastTick, 1500);
        }
        lastTick = now;
        if (visibleTime < 30000 || !scrolled || document.hidden) {
            return;
        }
        clearInterval(timer);
        try {
            const response = await fetch('/article/view?id=' + article.dataset.articleId, {
                method: 'POST',
                body: new URLSearchParams({ csrf: article.dataset.csrf }),
            });
            if (response.ok) {
                const result = await response.json();
                document.getElementById('article-views').textContent = result.views;
            }
        } catch {
        }
    }, 1000);
}
