const API_BASE_URL = 'https://mindsolutions.api/';
//const API_BASE_URL = 'https://127.0.0.1:8001/';

// Set up a global AJAX interceptor
$.ajaxSetup({
    beforeSend: function (xhr, settings) {
        // Check if the token is already available in localStorage
        var token = getWithExpiry('jwtToken');

        if (token) {
            // If the token exists, add it to the Authorization header
            xhr.setRequestHeader('Authorization', 'Bearer ' + token);
        } else {
            // Token is not available, fetch it
            requestJwtToken()
                .then((token) => {
                    var parsed = JSON.parse(token);
                    setWithExpiry('jwtToken', parsed.token, parsed.expiresIn * 1000);

                    // Add the fetched token to the Authorization header
                    xhr.setRequestHeader('Authorization', 'Bearer ' + parsed.token);
                })
                .catch((error) => {
                    console.error('Error receiving token:', error);
                });
        }
    },
});

const requestJwtToken = async () => {
    try {
        const response = await fetch(API_BASE_URL + 'authenticate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            mode: 'cors',
            body: JSON.stringify({
                user: user
            }),
        });

        if (response.ok) {
            const token = await response.text();

            return token
        } else {
            console.error('Failed to request token from server');
        }
    } catch (error) {
        console.error('Error requesting token:', error);
    }
}

function getWithExpiry(key) {
    const itemStr = localStorage.getItem(key)
    // if the item doesn't exist, return null
    if (!itemStr) {
        return null
    }
    const item = JSON.parse(itemStr)
    const now = new Date()
    // compare the expiry time of the item with the current time
    if (now.getTime() > item.expiry) {
        // If the item is expired, delete the item from storage
        // and return null
        localStorage.removeItem(key)
        return null
    }
    return item.value
}


function setWithExpiry(key, value, ttl) {
    const now = new Date()

    // `item` is an object which contains the original value
    // as well as the time when it's supposed to expire
    const item = {
        value: value,
        expiry: now.getTime() + ttl,
    }
    localStorage.setItem(key, JSON.stringify(item))
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