(function () {
    'use strict';

    var root = document.querySelector('[data-asn-registration-v2]');
    if (!root) {
        return;
    }

    function hideMessagingWidgets() {
        var selectors = [
            '.bp-messages-wrap',
            '.better-messages-mini-list',
            '.better-messages-mini-chat',
            '.bm-mini-widgets',
            '.bm-mini-widget',
            '.bm-floating-widgets'
        ];

        document.querySelectorAll(selectors.join(',')).forEach(function (element) {
            if (!root.contains(element)) {
                element.style.display = 'none';
            }
        });
    }

    hideMessagingWidgets();
    new MutationObserver(hideMessagingWidgets).observe(document.body, {
        childList: true,
        subtree: true
    });

    var complete = root.querySelector('[data-asn-complete-redirect]');
    if (complete) {
        var destination = complete.getAttribute('data-asn-complete-redirect');
        if (destination) {
            window.setTimeout(function () {
                window.location.assign(destination);
            }, 1600);
        }
    }

    function value(name) {
        var field = root.querySelector('[name="' + name + '"]');
        return field ? String(field.value || '').trim() : '';
    }

    function updatePreview() {
        var display = value('display_name');
        var first = value('first_name');
        var last = value('last_name');
        var age = value('age');
        var job = value('job_title');
        var country = value('country');
        var spoken = value('spoken_proficiency');

        var name = display || [first, last].filter(Boolean).join(' ');
        if (age) {
            name += (name ? ', ' : '') + age;
        }

        var nameNode = root.querySelector('[data-asn-preview-name]');
        if (nameNode && name) {
            nameNode.textContent = name;
        }

        var jobNode = root.querySelector('[data-asn-preview-job]');
        if (jobNode && job) {
            jobNode.textContent = job;
        }

        var countryNode = root.querySelector('[data-asn-preview-country]');
        if (countryNode && country) {
            countryNode.textContent = country;
        }

        var speakerNode = root.querySelector('[data-asn-preview-speaker]');
        if (speakerNode && spoken) {
            var parts = spoken.split('-');
            var proficiency = String(parts.length > 1 ? parts[1] : parts[0]).trim();
            speakerNode.textContent = proficiency ? proficiency + ' Speaker' : '';
        }
    }

    root.querySelectorAll('input, select, textarea').forEach(function (field) {
        field.addEventListener('input', updatePreview);
        field.addEventListener('change', updatePreview);
    });

    root.querySelectorAll('.asn-registration-v2__photo-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || !file.type.match(/^image\//)) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                var card = input.closest('.asn-registration-v2__photo-card');
                if (!card) {
                    return;
                }

                var preview = card.querySelector('.asn-registration-v2__photo-preview');
                var plus = card.querySelector('.asn-registration-v2__photo-plus');

                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'asn-registration-v2__photo-preview';
                    preview.alt = '';
                    card.insertBefore(preview, card.querySelector('.asn-registration-v2__photo-add'));
                }

                preview.src = event.target.result;
                if (plus) {
                    plus.remove();
                }
            };
            reader.readAsDataURL(file);
        });
    });
}());
