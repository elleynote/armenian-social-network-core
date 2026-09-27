(function () {
    'use strict';

    function launcherAvailable() {
        return !!(
            window.jqcc &&
            window.jqcc.cometchat &&
            typeof window.jqcc.cometchat.launch === 'function'
        );
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-asn-message-user]');
        if (!button) {
            return;
        }

        var userId = parseInt(button.getAttribute('data-asn-message-user'), 10);
        if (!Number.isInteger(userId) || userId <= 0 || !launcherAvailable()) {
            button.disabled = true;
            return;
        }

        try {
            window.jqcc.cometchat.launch({ uid: userId });
        } catch (error) {
            button.disabled = true;
        }
    });
}());
