function getRecaptchaToken(sitekey, pageUrl, version, pageAction, provider, apiKey, successCallback, errorCallback) {

    // Check if the token is already available in localStorage
    var token = getWithExpiry('jwtToken');

    if (token) {
        makeApiRequest(token);
    } else {
        // Token is not available, request it
        requestJwtToken()
            .then((token) => {
                var parsed = JSON.parse(token);
                setWithExpiry('jwtToken', parsed.token, parsed.expiresIn * 1000)
                makeApiRequest(parsed.token);
            })
            .catch((error) => {
                console.error('Error receiving token:', error);
                errorCallback(error);
            });
    }

    function makeApiRequest(jwtToken) {
        $.ajax({
            url: `https://api.mindsolutions.app/captcha/${provider}/harvest`,
            type: 'GET',
            headers: {
                'Authorization': `Bearer ${jwtToken}`, // Use the retrieved JWT token
            },
            data: {
                sitekey: sitekey,
                pageUrl: pageUrl,
                version: version,
                pageAction: pageAction,
                apiKey: apiKey,
            },
            success: function (response) {
                // Handle the response here
                if (response.success) {
                    successCallback(response.result.code);
                } else {
                    errorCallback(response.message)
                }
            },
            error: function (xhr, status, error) {
                errorCallback(error);
            }
        });
    }
}