const API_BASE_URL = 'https://api.mindsolutions.app/';
//const API_BASE_URL = 'https://127.0.0.1:8001/';
let refreshingToken = false;
let originalOptionsForRetry; // Store the original options for retry

$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
    if (options.url.endsWith('/authenticate')) {
        return;
    }
    if (!options.url.startsWith(API_BASE_URL)) {
        return;
    }
    if (refreshingToken) {
        return;
    }

    console.log('ajaxPrefilter', options.url);

    beforeSend = function (xhr) {
        const refreshedToken = getWithExpiry('token');
        xhr.setRequestHeader('Authorization', 'Bearer ' + refreshedToken);
    };

    originalOptionsForRetry = options; // Store the original options for retry
    options.beforeSend = beforeSend; // Set beforeSend for the initial request
    checkTokenExp();
});

function checkTokenExp() {
    var token = getWithExpiry('token');

    if (!token) {
        refreshingToken = true;
        // API TOKEN EXPIRED
        $.ajax({
            type: 'POST',
            url: API_BASE_URL + 'authenticate',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                user: user,
            }),
            success: function (data) {
                if (data.token && data.expiresIn) {
                    setWithExpiry('token', data.token, data.expiresIn); // 1 hour

                    // Retry the original request with the new token
                    originalOptionsForRetry.headers = {
                        'Authorization': 'Bearer ' + data.token,
                    };

                    $.ajax(originalOptionsForRetry)
                        .done(function (response) {
                            console.log('Request successful after token refresh:', response);
                        })
                        .fail(function (jqXHR, textStatus, errorThrown) {
                            if (jqXHR.status === 401) {
                                console.error('Token expired');
                                sessionStorage.removeItem('token');
                            } else {
                                console.error('Error in the original request after token refresh:', errorThrown);
                            }
                        });
                }
                // TODO: log error here, don't do validToken = true
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error refreshing token:', errorThrown);
            },
        });
    }
    // API TOKEN NOT EXPIRED - No need to do anything, the initial request will be made with the current token
}

function getWithExpiry(key) {
    const itemStr = sessionStorage.getItem(key);
    // If the item doesn't exist, or if there's an issue parsing it, return null
    if (!itemStr) {
        return null;
    }

    try {
        const item = JSON.parse(itemStr);
        // Ensure that the item has both a value and an expiry property
        if (!item || !item.value || !item.expiry) {
            sessionStorage.removeItem(key);
            return null;
        }

        const now = new Date();

        // Compare the expiry time of the item with the current time
        if (now.getTime() > item.expiry) {
            console.log('Token expired');
            // If the item is expired, delete it from storage and return null
            sessionStorage.removeItem(key);
            return null;
        }

        // Return the value if it's still valid
        return item.value;
    } catch (error) {
        console.error('Error parsing item from local storage:', error);
        sessionStorage.removeItem(key);
        return null;
    }
}

function setWithExpiry(key, value, ttl) {
    const now = new Date()

    // `item` is an object which contains the original value
    // as well as the time when it's supposed to expire
    const item = {
        value: value,
        expiry: now.getTime() + ttl,
    }
    sessionStorage.setItem(key, JSON.stringify(item));
}

function setViagogoUser(username, password, cookie1, cookie2) {
    return new Promise(function (resolve, reject) {
        $.ajax({
            type: "POST",
            url: '/api/viagogo/user',
            data: {
                username: username,
                password: password,
                cookies: JSON.stringify([
                    cookie1,
                    cookie2
                ])
            },
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response);
                } else {
                    reject(response.message);
                }
            },
            error: function (response) {
                reject(response.responseText);
            }
        });
    });
}

function getViagogoUser() {
    return new Promise(function (resolve, reject) {
        $.ajax({
            type: "GET",
            url: '/api/viagogo/user',
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response);
                } else {
                    reject(response.message);
                }
            },
            error: function (response) {
                reject(response.responseText);
            }
        });
    });
}

function deleteViagogoUser() {
    return new Promise(function (resolve, reject) {
        $.ajax({
            type: "DELETE",
            url: '/api/viagogo/user',
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response);
                } else {
                    reject(response.message);
                }
            },
            error: function (response) {
                reject(response.responseText);
            }
        });
    });
}