function getRecaptchaToken(sitekey, pageUrl, version, pageAction, provider, apiKey, successCallback, errorCallback) {


    makeApiRequest();

    function makeApiRequest() {
        $.ajax({
            url: `${API_BASE_URL}captcha/${provider}/harvest`,
            type: 'GET',
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