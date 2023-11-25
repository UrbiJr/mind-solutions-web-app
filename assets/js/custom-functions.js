// Function to get the value of a cookie by its name
function getCookie(cookieName) {
    // Split the document.cookie string into individual cookies
    var cookies = document.cookie.split(";");

    // Iterate through the cookies to find the one with the specified name
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim();

        // Check if this cookie starts with the specified name
        if (cookie.indexOf(cookieName + "=") === 0) {
            // Extract and return the cookie's value
            return cookie.substring(cookieName.length + 1, cookie.length);
        }
    }

    // Return null if the cookie with the specified name was not found
    return null;
}

function updateCookie(cookieName, newValue, expirationDays) {
    // Get the current date
    var currentDate = new Date();

    // Calculate the expiration date
    currentDate.setTime(currentDate.getTime() + (expirationDays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + currentDate.toUTCString();

    // Update the cookie value
    document.cookie = cookieName + "=" + newValue + "; " + expires;
}

function deleteCookie(cookieName) {
    // Set the cookie's expiration date to a past date
    document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
}

function validate(field, regex) {
    if (field && regex) {
        if (regex.test(field.value)) {
            field.className = 'form-control is-valid';
        } else {
            field.className = 'form-control is-invalid';
        }
    }
}

function showToast(title, body, isError = false, isSuccess = false) {
    const toast = document.getElementById("bottomToast");
    toast.querySelector(".toast-header strong").textContent = title;
    toast.querySelector(".toast-body").innerHTML = body;

    toast.querySelector(".toast-body").classList.remove("text-dark");
    toast.querySelector(".toast-body").classList.add("text-light");

    if (isError) {
        toast.classList.remove("bg-success");
        toast.classList.remove("bg-secondary");
        toast.classList.add("bg-danger");
    } else if (isSuccess) {
        toast.classList.remove("bg-danger");
        toast.classList.remove("bg-secondary");
        toast.classList.add("bg-success");
    } else {
        toast.classList.remove("bg-danger");
        toast.classList.remove("bg-success");
        toast.classList.add("bg-secondary");
    }

    const bootstrapToast = new bootstrap.Toast(toast);
    bootstrapToast.show();
}

function toastWithTimeout(titleContent, bodyContent, background, timeout) {

    if (timeout === undefined) {
        timeout = 2000;
    }

    // Get references to the toast elements
    const bottomToast = document.getElementById('bottomToast');
    const bottomToastTitle = bottomToast.querySelector('[name="title"]');
    const bodyElement = bottomToast.querySelector('.toast-body');

    bottomToast.querySelector(".toast-body").classList.add("text-dark");
    bottomToast.querySelector(".toast-body").classList.remove("text-light");
    bottomToast.classList = "toast hide";

    // Set the title, body, and time
    bottomToastTitle.textContent = titleContent; // Replace with your title
    bodyElement.textContent = bodyContent; // Replace with your message

    if (background !== undefined) {
        bottomToast.classList.add(background);
    }

    // Show the toast
    const bsBottomToast = new bootstrap.Toast(bottomToast, {
        delay: timeout,
    });
    bsBottomToast.show();
}

function hideBottomToast() {
    // Get references to the toast elements
    const bottomToast = document.getElementById('bottomToast');
    // Show the toast
    const bsBottomToast = new bootstrap.Toast(bottomToast);
    bsBottomToast.hide();
}

function formatAmountArrayAsSymbol(amountArray) {
    switch (amountArray.currency.toUpperCase()) {
        case 'EUR':
            return `€${amountArray.amount.toFixed(2)}`;
        case 'GBP':
            return `£${amountArray.amount.toFixed(2)}`;
        case 'USD':
            return `$${amountArray.amount.toFixed(2)}`;
        case 'CAD':
            return `C$${amountArray.amount.toFixed(2)}`;
        case 'CHF':
            return `₣${amountArray.amount.toFixed(2)}`;
        default:
            return `${amountArray.currency} ${amountArray.amount.toFixed(2)}`;
    }
}

function formatAmountAndCurrencyAsSymbol(amount, stringCurrency) {
    switch (stringCurrency.toUpperCase()) {
        case 'EUR':
            return `€${amount.toFixed(2)}`;
        case 'GBP':
            return `£${amount.toFixed(2)}`;
        case 'USD':
            return `$${amount.toFixed(2)}`;
        case 'CAD':
            return `C$${amount.toFixed(2)}`;
        case 'CHF':
            return `₣${amount.toFixed(2)}`;
        default:
            return `${stringCurrency} ${amount.toFixed(2)}`;
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function haveSameElements(array1, array2) {
    // Sort both arrays.
    array1.sort();
    array2.sort();

    // Iterate over one of the arrays and compare each element to the corresponding element in the other array.
    for (let i = 0; i < array1.length; i++) {
        if (array1[i] !== array2[i]) {
            return false;
        }
    }

    // If all of the elements are equal, return true.
    return true;
}

// Helper function to format the date
function formatDateToDateTimeLocal(dateString) {
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16); // Formats to 'YYYY-MM-DDTHH:MM'
}