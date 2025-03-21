const generateRow = (data) => {

    const key = data.key ?? '';
    let url = data.url ?? '';
    const domain = data.domain ?? '';
    const path = data.path ?? '';
    const name = data.name ?? '';
    const title = data.title ?? '';
    const description = data.description ?? '';
    const favicon = data.favicon ?? '';
    const image = data.image ?? '';

    // create a div
    let div = document.createElement('div');
    div.id = 'element-' + key;

    let pathArray = path;
    if (typeof pathArray === 'string') {
        pathArray = JSON.parse(pathArray);
    }

    if (pathArray.length > 0) {
        div.classList.add('sub-row');
    } else {
        div.classList.add('main-row');
    }
    if (image !== '') {
        div.classList.add('has-image');
    }

    // create a link
    let link = document.createElement('a');
    // if url doesn't start with http, add it
    if (!url.startsWith('http')) {
        url = 'https://' + url;
    }
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';

    //create header for the link
    let header = document.createElement('header');
    header.classList.add('row-header');

    // create img for the favicon
    let faviconImg = document.createElement('img');
    faviconImg.classList.add('favicon');
    faviconImg.src = favicon;
    header.appendChild(faviconImg);

    // create div for the title and domain + path
    let titleDiv = document.createElement('div');

    titleDiv.classList.add('title-div');
    let titleEl = document.createElement('h3');
    titleEl.innerHTML = name;
    titleDiv.appendChild(titleEl);

    let urlEl = document.createElement('p');
    // append the domain to the urlEl
    let domainSpan = document.createElement('span');
    domainSpan.innerHTML = domain;
    urlEl.appendChild(domainSpan);
    // for each domain, and path elements, create a span and append it to the urlEl
    pathArray.forEach((el) => {
        let span = document.createElement('span');
        span.innerHTML = el;
        urlEl.appendChild(span);
    });
    titleDiv.appendChild(urlEl);

    header.appendChild(titleDiv);

    // create div for Page title and description
    let descriptionDiv = document.createElement('div');
    descriptionDiv.classList.add('description-div');
    let pageTitle = document.createElement('h4');
    pageTitle.innerHTML = title;
    descriptionDiv.appendChild(pageTitle);
    let pageDescription = document.createElement('p');
    pageDescription.innerHTML = description;
    descriptionDiv.appendChild(pageDescription);

    // append the header, url, and description to the link
    link.appendChild(header);
    link.appendChild(descriptionDiv);

    // create div for the image
    if (image !== '') {
        let imageDiv = document.createElement('div');
        imageDiv.classList.add('image-div');
        let imageEl = document.createElement('img');
        imageEl.src = image;
        imageDiv.appendChild(imageEl);
        link.appendChild(imageDiv);
    }

    // append the link to the div
    div.appendChild(link);

    // append the div to the results div
    document.querySelector('#results .holder').appendChild(div);
}

const heartbeatStatus = () => {
    const url = document.querySelector('#results').getAttribute('data-status');
    const token = document.querySelector('input[name="_token"]').value;
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            updateStatuses(data.scheduled, data.running);
        });
}
let stopped = false;
const stopAction = (e) => {
    e.preventDefault();
    let url = document.querySelector('#stop').getAttribute('data-url');
    url = url.replace('key', document.querySelector('#crawl-key').value);
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            haltCrawler('Crawl stopped');
        });
}

const appendStopAction = () => {
    // remove event listener from button
    document.querySelector('#stop').removeEventListener('click', stopAction);
    // append stop action to button
    document.querySelector('#stop').addEventListener('click', stopAction);
}

let previousAlreadyAdded = [];
let countChecks = 0;

const heartbeatResults = () => {
    let url = document.querySelector('#results').getAttribute('data-results');
    url = url.replace('key', document.querySelector('#crawl-key').value);

    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'done') {
                haltCrawler('Crawl finished');
            } else if (data.status === 'error') {
                haltCrawler(data.message);
            } else if(stopped){
                // do nothing and stop the interval
                clearInterval(statusInterval);
                clearInterval(resultsTimeout);
            } else {
                let alreadyAdded = [];
                // check if the bottom of #results.active is in view
                let scrollBottom = false;
                if (document.querySelector('#results.active').getBoundingClientRect().bottom < window.innerHeight) {
                    scrollBottom = true;
                }

                data.data.forEach((el) => {
                    if (document.querySelector(`#element-${el.key}`) === null) {
                        generateRow(el);
                    } else {
                        alreadyAdded.push(el.key);
                    }
                });

                // smooth scroll to last added element, if user is scrolled to the bottom
                if (scrollBottom) {
                    document.querySelector('#results.active').scrollTo({
                        top: document.querySelector('#results.active').scrollHeight,
                        behavior: 'smooth'
                    });
                }

                if (alreadyAdded.length === previousAlreadyAdded.length) {
                    countChecks++;
                    if (countChecks > 5) {
                        clearInterval(statusInterval);
                        clearTimeout(resultsTimeout);
                        updateStatuses(0, 0);
                    } else {
                        countChecks = 0;
                        setTimeout(heartbeatResults, 5000);
                    }
                } else {
                    previousAlreadyAdded = alreadyAdded;
                    setTimeout(heartbeatResults, 5000);
                }
            }
        });
}

const updateStatuses = (queued = 0, inProgress = 0) => {
    document.querySelector('#queued span').innerHTML = queued;
    document.querySelector('#in-progress span').innerHTML = inProgress;
}

const haltCrawler = (message) => {
    if (!stopped) {
        stopped = true;
        const div = document.createElement('div');
        div.classList.add('crawl-finished');
        div.innerHTML = '<p>' + message + '</p>';
        document.querySelector('#results .holder').appendChild(div);
        document.querySelector('#status').classList.remove('show');
        document.querySelector('#running').classList.remove('active');
        document.querySelector('#crawler').classList.remove('hidden');
        document.querySelector('#stop').removeEventListener('click', stopAction);
        document.querySelector('#results').classList.add('stopped');

        clearInterval(statusInterval);
        clearTimeout(resultsTimeout);
        document.title = originalTitle;

        if (typeof gtag === 'function') {
            gtag('event', 'crawl_finished', {
                'event_category': 'crawl_finished',
                'event_label': message,
                'value': message
            });
        }
    }
}

let statusInterval = null;
let resultsTimeout = null;

const isValidUrl = (url) => {
    const urlPattern = new RegExp('^(https?:\\/\\/)' +
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' +
        '((?!\\d+\\.\\d+\\.\\d+\\.\\d+)([a-zA-Z\\d-]+\\.){1,}[a-zA-Z]{2,}))' +
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' +
        '(\\?[;&a-z\\d%_.~+=-]*)?' +
        '(\\#[-a-z\\d_]*)?$', 'i');
    return urlPattern.test(url);
}

let originalTitle = document.title;

// get the form #crawler
document.querySelector('#crawler').addEventListener('submit', function (e) {
    e.preventDefault();
    // get the input value
    let url = document.querySelector('#url').value;

    // check if is a valid url
    document.querySelector('#url').classList.remove('error');
    if (!isValidUrl(url)) {
        setTimeout(() => {
            document.querySelector('#url').classList.add('error');
        },250);
        return;
    }

    // change page title
    document.title = 'Crawling ' + url + ' | ' + originalTitle;

    // if gtag is available, send the event
    if (typeof gtag === 'function') {
        gtag('event', 'crawl', {
            'event_category': 'crawl',
            'event_label': url,
            'value': url
        });
    }

    // get the csrf token
    let token = document.querySelector('input[name="_token"]').value;
    // get the result div
    let result = document.querySelector('#results');
    // clear the result div
    result.querySelector('.holder').innerHTML = '';
    result.classList.remove('stopped');
    result.classList.add('active');

    document.querySelector('#status').classList.add('show');

    const submitUrl = document.querySelector('#crawler').getAttribute('action');
    stopped = false;

    // send the request
    fetch(submitUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            url: url
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error' || data.status === 'failed') {
                const div = document.createElement('div');
                haltCrawler(data.message);
                return;
            }
            // append the result to the result div
            document.querySelector('#crawl-key').value = data.key;
            generateRow(data);

            // hide the form
            document.querySelector('#crawler').classList.add('hidden');

            // show stop button
            document.querySelector('#running').classList.add('active');

            // append the stop action to the running button
            appendStopAction();

            // set hearthbeatstatus
            statusInterval = setInterval(heartbeatStatus, 5000);
            resultsTimeout = setTimeout(heartbeatResults, 5000);
        })
        .catch((error) => {
            console.error('Error:', error);
            haltCrawler(error);
        });

});

const realUser = () => {
    if (!appended) {
        appended = true;
        // create the script
        const script = document.createElement('script');
        script.src = 'https://www.googletagmanager.com/gtag/js?id=G-PM5HG6NJK5';
        script.async = true;
        // append the script to the head
        document.head.appendChild(script);
        window.dataLayer = window.dataLayer || [];
        window.gtag = function gtag() {
            window.dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'G-PM5HG6NJK5');

        // remove event listeners
        document.removeEventListener('mousemove', realUser);
        document.removeEventListener('touchstart', realUser);
        document.removeEventListener('keydown', realUser);
    }
}


document.querySelector('#status').addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelector('#status').classList.toggle('active');
});
let appended = false;
let scrollTimer = null;
// when the page is scrolled, check if the #running div is in view, if not, then add the class .fixed to #stop button
document.addEventListener('scroll', function () {
    if (document.querySelector('#running').getBoundingClientRect().top < 0) {
        document.querySelector('#stop').classList.add('fixed');
    } else {
        document.querySelector('#stop').classList.remove('fixed');
    }

    clearTimeout(scrollTimer);
    if (!appended) {
        scrollTimer = setTimeout(realUser, 200);
    }
});

// add mouse move, touch start, and key down event listeners
document.addEventListener('mousemove', realUser);
document.addEventListener('touchstart', realUser);
document.addEventListener('keydown', realUser);
