function getViagogoSessionCookie(username, password, recaptchaToken, successCallback, errorCallback) {

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
            url: 'https://api.mindsolutions.app/service-integration/viagogo/login', // Replace with the URL of your PHP script
            type: 'POST',
            headers: {
                'Authorization': `Bearer ${jwtToken}`, // Use the retrieved JWT token
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                username: username,
                password: password,
                recaptchaToken: recaptchaToken,
            }),
            success: function (response) {
                // Handle the response here
                if (response.success) {
                    successCallback(response);
                } else {
                    errorCallback(response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX request failed:', error);
                errorCallback(error);
            }
        });
    }
}

function onSubmitViagogoForm(form) {

    function showError(error) {
        // Hide the preloader
        $('#preloader').hide();

        // Re-enable the submit button
        $('button[type="submit"]').prop('disabled', false);

        // Select the toast element
        var toast = document.getElementById("bottomToast");

        // Initialize a new Bootstrap Toast instance
        var bootstrapToast = new bootstrap.Toast(toast);

        // Set the toast content (title and body)
        var toastTitle = "Error";
        var toastBody = error;
        // Add the "bg-danger" class
        toast.classList.remove("bg-primary");
        toast.classList.add("bg-danger");
        toast.querySelector(".toast-header strong").textContent = toastTitle;
        toast.querySelector(".toast-body").textContent = toastBody;

        // Show the toast
        bootstrapToast.show();
    }

    event.preventDefault();

    // Disable the submit button to prevent multiple submissions
    $('button[type="submit"]').prop('disabled', true);

    // Serialize the form data into a JSON object
    var formData = $(form).serializeArray();

    // Manually include hidden fields in the JSON object
    formData.push({ name: "captchaApiKey", value: $("input[name='captchaApiKey']").val() });
    formData.push({ name: "captchaProvider", value: $("input[name='captchaProvider']").val() });

    var username, password, captchaApiKey, captchaProvider;
    $(form).serializeArray().forEach(function (field) {
        switch (field.name) {
            case "username":
                username = field.value;
                break;

            case "viagogoPassword":
                password = field.value;
                break;

            case "captchaApiKey":
                captchaApiKey = field.value;
                break;

            case "captchaProvider":
                captchaProvider = field.value;
                break;

            default:
                break;
        }
    });

    // Show the preloader
    $('#preloader').show();
    $('#preloader .title').text("Solving captcha, this may take a while...")
    $('#preloader .subtitle').text("Don't refresh this page.")

    // get the recaptcha token first
    getRecaptchaToken("6LckUScUAAAAAN3Poew0YuQiT-o9ARG8sK0euTIM", "https://my.viagogo.com/ww/secure/login", "v2", "", captchaProvider, captchaApiKey, function (recaptchaToken) {

        $('#captchaStep').addClass('done');
        $('#loginStep').addClass('active');
        $('#preloader .title').text("Connecting to your Viagogo account, this may take a while...")

        // and then attempt viagogo login
        getViagogoSessionCookie(username, password, recaptchaToken, function (response) {
            const sessionCookieValue1 = response.sessionCookie1.value;
            const sessionCookieValue2 = response.sessionCookie2.value;

            // Store Viagogo user on the backend and update status to connected
            setViagogoUser(username, password, sessionCookieValue1, sessionCookieValue2).then(function (response) {
                window.location.href = response['redirectUrl'];
            }).catch(function (error) {
                showError(error);
            });
        }, function (error) {
            showError(error);
        });
    }, function (error) {
        showError(error);
    })

    // Prevent the default form submission
    return false;
}