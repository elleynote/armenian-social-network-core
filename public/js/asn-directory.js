 (function () {
    'use strict';

    var infiniteObserver = null;

    function getDirectory() {
        return document.querySelector('.asn-directory');
    }

    function getResults(root) {
        return (root || document).querySelector('[data-asn-directory-results]');
    }

    function buildFormUrl(form) {
        var url = new URL(form.action || window.location.href, window.location.origin);
        url.search = '';

        var data = new FormData(form);
        data.forEach(function (value, key) {
            var text = String(value).trim();
            if (text !== '') {
                url.searchParams.set(key, text);
            }
        });

        return url.toString();
    }

    async function fetchDirectory(url) {
        var response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Explore request failed');
        }

        var html = await response.text();
        return new DOMParser().parseFromString(html, 'text/html');
    }

    function stopInfiniteScroll(results) {
        var sentinel = results ? results.querySelector('[data-asn-load-sentinel]') : null;
        var pagination = results ? results.querySelector('[data-asn-pagination-fallback]') : null;

        if (infiniteObserver) {
            infiniteObserver.disconnect();
            infiniteObserver = null;
        }

        if (sentinel) {
            sentinel.hidden = true;
        }

        if (pagination && 'IntersectionObserver' in window) {
            pagination.hidden = true;
        }
    }

    function setupInfiniteScroll() {
        var results = getResults();
        if (!results) {
            return;
        }

        var sentinel = results.querySelector('[data-asn-load-sentinel]');
        var pagination = results.querySelector('[data-asn-pagination-fallback]');
        var nextUrl = results.getAttribute('data-asn-next-url') || '';

        if (infiniteObserver) {
            infiniteObserver.disconnect();
            infiniteObserver = null;
        }

        if (!sentinel || !nextUrl || !('IntersectionObserver' in window)) {
            return;
        }

        if (pagination) {
            pagination.hidden = true;
        }

        sentinel.hidden = false;
        infiniteObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadMore(results);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px 70% 0px',
            threshold: 0.01
        });

        infiniteObserver.observe(sentinel);
    }

    async function loadMore(results) {
        if (!results || results.getAttribute('data-asn-loading-more') === '1') {
            return;
        }

        var nextUrl = results.getAttribute('data-asn-next-url') || '';
        if (!nextUrl) {
            stopInfiniteScroll(results);
            return;
        }

        results.setAttribute('data-asn-loading-more', '1');
        var sentinel = results.querySelector('[data-asn-load-sentinel]');
        if (sentinel) {
            sentinel.classList.add('is-loading');
        }

        try {
            var parsed = await fetchDirectory(nextUrl);
            var replacement = getResults(parsed);
            var currentGrid = results.querySelector(':scope > .asn-directory__grid');
            var nextGrid = replacement ? replacement.querySelector(':scope > .asn-directory__grid') : null;

            if (!replacement || !currentGrid || !nextGrid) {
                throw new Error('Explore response missing member grid');
            }

            Array.prototype.slice.call(nextGrid.children).forEach(function (card) {
                currentGrid.appendChild(card);
            });

            results.setAttribute('data-asn-page', replacement.getAttribute('data-asn-page') || '');
            results.setAttribute('data-asn-pages', replacement.getAttribute('data-asn-pages') || '');
            results.setAttribute('data-asn-next-url', replacement.getAttribute('data-asn-next-url') || '');

            var currentPagination = results.querySelector('[data-asn-pagination-fallback]');
            var nextPagination = replacement.querySelector('[data-asn-pagination-fallback]');
            if (currentPagination && nextPagination) {
                currentPagination.replaceWith(nextPagination);
            }

            var nextSentinel = replacement.querySelector('[data-asn-load-sentinel]');
            if (!results.getAttribute('data-asn-next-url')) {
                stopInfiniteScroll(results);
            } else if (!sentinel && nextSentinel) {
                results.appendChild(nextSentinel);
            }
        } catch (error) {
            var pagination = results.querySelector('[data-asn-pagination-fallback]');
            if (pagination) {
                pagination.hidden = false;
            }
            stopInfiniteScroll(results);
        } finally {
            results.removeAttribute('data-asn-loading-more');
            if (sentinel) {
                sentinel.classList.remove('is-loading');
            }
        }
    }

    async function loadDirectory(url, pushHistory) {
        var current = getDirectory();
        if (!current) {
            window.location.href = url;
            return;
        }

        current.classList.add('asn-directory--loading');
        current.setAttribute('aria-busy', 'true');

        try {
            var parsed = await fetchDirectory(url);
            var replacement = parsed.querySelector('.asn-directory');

            if (!replacement) {
                throw new Error('Explore response missing directory');
            }

            current.replaceWith(replacement);

            if (pushHistory) {
                history.pushState({}, '', url);
            }

            setupInfiniteScroll();
        } catch (error) {
            window.location.href = url;
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.asn-directory__filters');
        if (!form) {
            return;
        }

        event.preventDefault();
        loadDirectory(buildFormUrl(form), true);
    });

    document.addEventListener('click', function (event) {
        var submit = event.target.closest('[data-asn-directory-submit]');
        if (submit) {
            var form = submit.closest('.asn-directory__filters');
            if (form) {
                event.preventDefault();
                loadDirectory(buildFormUrl(form), true);
            }
            return;
        }

        var pagination = event.target.closest('.asn-pagination__link');
        if (pagination) {
            event.preventDefault();
            loadDirectory(pagination.href, true);
        }
    });

    window.addEventListener('popstate', function () {
        loadDirectory(window.location.href, false);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupInfiniteScroll);
    } else {
        setupInfiniteScroll();
    }
}());
