function sendTranslatorMenuMetaboxRequest(pageUrlParams) {

    function getCookie(name) {
        let matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    const formData = new FormData();
    formData.append('termId', document.querySelector('#menu').value);
    formData.append('_wpnonce', TRL._wpnonce);
    const code = getCookie('trl_current_admin_language');
    if (code) {
        formData.append('code', code);
    }

    if (pageUrlParams.get('trl_source')) {
        formData.append('trl_source', pageUrlParams.get('trl_source'));
    }

    let url = TRL.restEndpoint + '/translator/term/metabox/get';
    url += '?' + (new URLSearchParams(formData).toString());

    fetch(url, {
        method: 'GET',
        body: null,
    })
        .then((response) => {
            response.json().then(function (response) {
                document
                    .querySelector('#update-nav-menu h2')
                    .insertAdjacentHTML("afterend", response.metabox);

                document.querySelector('#menu').value = response.menu_id;

                if (response.menu_id === 0 && response.source) {
                    document.querySelector('#menu-name').value = response.source.name + ' (' + code + ')';
                }
            });
        })
        .catch((error) => {
            alert(error);
        });
}

document.addEventListener('DOMContentLoaded', function () {

    function showNotificationText(selector) {
        if (TRL.currentCode !== TRL.defaultCode) {
            document.querySelector(selector).innerHTML =
                '<p>Указывать меню в областях вывода разрешено только для языка по-умолчанию. ' +
                'Переключитесь на язык ' + TRL.defaultCode + '</p>';
        }
    }

    let pageUrlParams = new URL(document.location.href).searchParams;

    if (
        pageUrlParams.get('action') &&
        pageUrlParams.get('action') === 'locations'
    ) {
        showNotificationText('#menu-locations-wrap');
    } else {

        showNotificationText('.menu-settings-group.menu-theme-locations')

        sendTranslatorMenuMetaboxRequest(pageUrlParams);
    }
});

