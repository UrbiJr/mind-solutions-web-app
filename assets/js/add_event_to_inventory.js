document.addEventListener("DOMContentLoaded", () => {
    // get eventId
    const pathSegments = window.location.pathname.split('/');
    const eventId = pathSegments[pathSegments.length - 1];

    if (eventId === undefined || eventId == "") {
        return false;
    }

    document.getElementById("overlay").style.display = "";

    getEventOverviewData(eventId, function (result) {
        if (result.eventData !== null && result.eventData.id) {
            document.getElementById("city").disabled = true;
            document.getElementById("location").disabled = true;
            document.getElementById("eventId").value = result.eventData.id;
            document.getElementById("city").value = result.eventData._embedded.venue.city;
            document.getElementById("location").value = result.eventData._embedded.venue.name;
            document.getElementById("categoryId").value = result.categoryId;
            var sectionSelect = document.getElementById("sectionSelect");
            Object.entries(result.sections.sections).forEach((section) => {
                if (sectionSelect.value === section[0]) {
                    // there's already a selected option for this section  
                    return;
                }

                // Create a new option element
                var option = document.createElement("option");

                // Set the value and text of the option
                option.value = section[0];
                option.text = section[0];

                // Append the option to the select element
                sectionSelect.appendChild(option);
            });

            var ticketGenreSelect = document.querySelector('select[name="ticketGenre"]');
            var ticketGenreOptions = ticketGenreSelect.options;
            for (var i = 0; i < ticketGenreOptions.length; i++) {
                if (ticketGenreOptions[i].value === result.eventData._embedded.genre.name) {
                    ticketGenreOptions[i].selected = true;
                    break; // Exit the loop once the correct option is selected
                }
            }

            var countrySelect = document.querySelector('select[name="country"]');
            var countryOptions = countrySelect.options;
            for (var i = 0; i < countryOptions.length; i++) {
                if (countryOptions[i].value === result.eventData._embedded.venue._embedded.country.code) {
                    countryOptions[i].selected = true;
                    break; // Exit the loop once the correct option is selected
                }
            }

            // Parse the date string
            var parsedDate = new Date(result.eventData.start_date);
            // Format the parsed date as a string suitable for the input field
            var formattedDate = parsedDate.toISOString().slice(0, 16); // Truncate to minutes
            // Set the formatted date as the value for the input field
            eventDateInput.value = formattedDate;
            eventNameInput.value = result.eventData.name;
            document.getElementById('bannerSubtitle').textContent = `Add "${result.eventData.name} (${result.eventData._embedded.venue.city})" to your inventory with a couple of clicks.`;
            document.getElementById("overlay").style.display = "none";
            nextBtnFunction(1);
            nextBtnFunction(1);
        } else {
            document.getElementById("overlay").style.display = "none";
        }
    }, function (error) {
        // Handle any errors that occurred during the data fetching process
        console.error('Error fetching event data:', error);
        document.getElementById("overlay").style.display = "none";
        showToast("Error", 'Error fetching event data: ' + error + `<hr><a class="btn btn-light" href="/${currentLocale}/events/${eventId}">Go back</a>`, true);
    });
});