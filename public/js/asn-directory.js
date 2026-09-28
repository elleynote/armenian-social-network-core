(function () {
    'use strict';

    function getDirectory() {
        return document.querySelector('.asn-directory');
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

    async function loadDirectory(url, pushHistory) {
        var current = getDirectory();
        if (!current) {
            window.location.href = url;
            return;
        }

        current.classList.add('asn-directory--loading');
        current.setAttribute('aria-busy', 'true');

        try {
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
            var parsed = new DOMParser().parseFromString(html, 'text/html');
            var replacement = parsed.querySelector('.asn-directory');

            if (!replacement) {
                throw new Error('Explore response missing directory');
            }

            current.replaceWith(replacement);

            if (pushHistory) {
                history.pushState({}, '', url);
            }
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
}());
