function sendDefaultParamsForNewTermRequest(pageUrlParams) {

    const formData = new FormData();
    formData.append('term', pageUrlParams.get('trl_source'));
    formData.append('_wpnonce', TRL._wpnonce);

    let url = TRL.restEndpoint + '/term/get';
    url += '?' + (new URLSearchParams(formData).toString());

    fetch(url, {
        method: 'GET', body: null,
    })
        .then((response) => {
            response.json().then(function (response) {
                document.querySelector('#tag-name').value = response.name;
                document.querySelector('#tag-slug').value = response.slug + '-' + pageUrlParams.get('lang');
                document.querySelector('#tag-description').value = response.description;
            });
        })
        .catch((error) => {
            alert(error);
        });
}

document.addEventListener('DOMContentLoaded', function () {
    let pageUrlParams = new URL(document.location.href).searchParams;
    if (pageUrlParams.get('trl_source')) {
        sendDefaultParamsForNewTermRequest(pageUrlParams);
    }
});

