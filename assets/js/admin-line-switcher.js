function build_language_links(data, $, container) {
    "use strict";

    const getNewQueryString = function(sourceUrl, newQueryArgs) {
        const url = new URL(sourceUrl),
            search = url.searchParams;

        for (let key in newQueryArgs) {
            if (Object.prototype.hasOwnProperty.call(newQueryArgs, key)) {
                let value = newQueryArgs[key];
                if(value === null){
                    search.delete(key);
                }else{
                    search.set(key, newQueryArgs[key]);
                }
            }
        }

        return search.toString();
    };

    let urlData;
    if (data.hasOwnProperty('languageLinks')) {
        let languages_container = $('<ul></ul>');
        languages_container.prependTo(container);

        for (let i = 0; i < data.languageLinks.length; i++) {
            let item = data.languageLinks[i];
            let is_current = item.current || false;
            let language_code = item.code;
            let language_count = item.count;
            let language_name = item.name;
            let statuses = item.statuses;
            let type = item.type;

            let language_item = $('<li></li>');
            language_item.addClass('language_' + language_code);
            if (i > 0) {
                language_item.append('&nbsp;|&nbsp;');
            }

            let language_summary = $('<span></span>');
            language_summary.addClass('count');
            language_summary.addClass(language_code);
            language_summary.text(' (' + ( language_count < 0 ? "0" : language_count ) + ')');

            let current;
            if (is_current) {
                current = $('<strong></strong>');
            } else if (language_count >= 0) {
                current = $('<a></a>');
                urlData = {
                    trl_source: null,
                    post_type: type,
                    lang:      language_code
                };

                if (statuses && statuses.length) {
                    urlData.post_status = statuses.join(',');
                }

                current.attr('href', '?' + getNewQueryString(location.href, urlData));
            } else {
                current = $('<span></span>');
            }

            current.append(language_name);
            current.appendTo(language_item);
            current.append(language_summary);

            language_item.appendTo(languages_container);
        }

    }
}

jQuery(function ($) {
    "use strict";

    let data = TRLTermLineSwitcher;

    let subsubsub = $('.tablenav.top');
    let container = subsubsub.next('.trl_subsubsub');

    if (container.length === 0) {
        container = $('<div></div>');
        container.addClass('trl_subsubsub');
        container.addClass('subsubsub');
        container.addClass('clear');

        subsubsub.after(container);
    }

    build_language_links(data, $, container);
});