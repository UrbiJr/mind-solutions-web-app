document.addEventListener("DOMContentLoaded", () => {

    if ($("#eventsTable").length) {
        var dataTable = $('#eventsTable').DataTable({
            "dom": '<"row align-items-center"<"col-md-6" l><"col-md-6" f>><"table-responsive border-bottom my-3" rt><"row align-items-center" <"col-md-6" i><"col-md-6" p>><"clear">',
            "columnDefs": [
                {
                    "targets": [1], // The index of the column you want to customize (0 for the first column)
                    "type": "num-fmt", // Use the original data in the first column for sorting
                },
                {
                    "targets": [2], // The index of the column you want to customize (0 for the first column)
                    "type": "date", // Use the original data in the first column for sorting
                },
            ],
        });

        // Trigger the initial draw
        dataTable.draw();
    }

    if ($('#eventOverview').length) {

        // get eventId
        const searchParams = new URLSearchParams(window.location.search);
        const eventId = searchParams.get('eventId');

        if (eventId === undefined || eventId == "") {
            return false;
        }

        document.getElementById("overlay").style.display = "";

        getEventOverviewData(eventId, function (response) {
            // Function to update the HTML content with response data
            // Update event name, venue, city, and country
            document.getElementById("eventName").textContent = response.eventData.name;
            document.getElementById("venueDetails").textContent = response.eventData._embedded.venue.name + ', ' + response.eventData._embedded.venue.city + ', ' + response.eventData._embedded.venue._embedded.country.name;

            // Update floor price rating
            var floorPriceRating = response.floorPriceRating;
            var ratingHtml = '';
            var decimalRating;
            var integerRating = Math.floor(floorPriceRating);
            if (integerRating >= 5) {
                integerRating = 5;
                decimalRating = 0;
            } else {
                decimalRating = response.floorPriceRating - Math.floor(floorPriceRating);
            }

            for (var i = 0; i < integerRating; i++) {
                ratingHtml += `
                    <svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" fill="orange">
                        <path d="M2.047 14.668a.994.994 0 0 0 .465.607l1.91 1.104v2.199a1 1 0 0 0 1 1h2.199l1.104 1.91a1.01 1.01 0 0 0 .866.5c.174 0 .347-.046.501-.135L12 20.75l1.91 1.104a1.001 1.001 0 0 0 1.366-.365l1.103-1.91h2.199a1 1 0 0 0 1-1V16.38l1.91-1.104a1 1 0 0 0 .365-1.367L20.75 12l1.104-1.908a1 1 0 0 0-.365-1.366l-1.91-1.104v-2.2a1 1 0 0 0-1-1H16.38l-1.103-1.909a1.008 1.008 0 0 0-.607-.466.993.993 0 0 0-.759.1L12 3.25l-1.909-1.104a1 1 0 0 0-1.366.365l-1.104 1.91H5.422a1 1 0 0 0-1 1V7.62l-1.91 1.104a1.003 1.003 0 0 0-.365 1.368L3.251 12l-1.104 1.908a1.009 1.009 0 0 0-.1.76zM12 13c-3.48 0-4-1.879-4-3 0-1.287 1.029-2.583 3-2.915V6.012h2v1.109c1.734.41 2.4 1.853 2.4 2.879h-1l-1 .018C13.386 9.638 13.185 9 12 9c-1.299 0-2 .515-2 1 0 .374 0 1 2 1 3.48 0 4 1.879 4 3 0 1.287-1.029 2.583-3 2.915V18h-2v-1.08c-2.339-.367-3-2.003-3-2.92h2c.011.143.159 1 2 1 1.38 0 2-.585 2-1 0-.325 0-1-2-1z" />
                    </svg>`;
            }

            if (decimalRating >= 0.5) {
                ratingHtml += `
                <svg height="24" viewBox="0 0 12 24" width="12" xmlns="http://www.w3.org/2000/svg" fill="orange">
                    <path d="M2.047 14.668a.994.994 0 0 0 .465.607l1.91 1.104v2.199a1 1 0 0 0 1 1h2.199l1.104 1.91a1.01 1.01 0 0 0 .866.5c.174 0 .347-.046.501-.135L12 20.75l1.91 1.104a1.001 1.001 0 0 0 1.366-.365l1.103-1.91h2.199a1 1 0 0 0 1-1V16.38l1.91-1.104a1 1 0 0 0 .365-1.367L20.75 12l1.104-1.908a1 1 0 0 0-.365-1.366l-1.91-1.104v-2.2a1 1 0 0 0-1-1H16.38l-1.103-1.909a1.008 1.008 0 0 0-.607-.466.993.993 0 0 0-.759.1L12 3.25l-1.909-1.104a1 1 0 0 0-1.366.365l-1.104 1.91H5.422a1 1 0 0 0-1 1V7.62l-1.91 1.104a1.003 1.003 0 0 0-.365 1.368L3.251 12l-1.104 1.908a1.009 1.009 0 0 0-.1.76zM12 13c-3.48 0-4-1.879-4-3 0-1.287 1.029-2.583 3-2.915V6.012h2v1.109c1.734.41 2.4 1.853 2.4 2.879h-1l-1 .018C13.386 9.638 13.185 9 12 9c-1.299 0-2 .515-2 1 0 .374 0 1 2 1 3.48 0 4 1.879 4 3 0 1.287-1.029 2.583-3 2.915V18h-2v-1.08c-2.339-.367-3-2.003-3-2.92h2c.011.143.159 1 2 1 1.38 0 2-.585 2-1 0-.325 0-1-2-1z" />
                </svg>`;
            }

            document.getElementById("floorPriceRating").innerHTML = ratingHtml;
            document.getElementById("eventImage").setAttribute("src", `/images/events/${response.eventData._embedded.genre.name}.png`);

            // Update event date and on-sale date
            document.getElementById("eventDate").textContent = "Event Date: " + response.startDate;
            document.getElementById("onSaleDate").textContent = "Sale Started: " + response.onSaleDate;
            if (response.eventData.min_ticket_price !== null) {
                document.getElementById("minPrice").textContent = "Floor Price: " + response.eventData.min_ticket_price.display;
            } else {
                document.getElementById("minPrice").textContent = "Floor Price: N/A";
            }


            document.getElementById("addToInventory").setAttribute("href", `/?model=inventory&action=addEvent&eventId=${response.eventData.id}`);

            // Update floor price for all sections
            var sectionsData = response.sectionsData;
            var sectionSelect = document.getElementById("viagogoEventSectionSelect");
            var sectionFloorPrice = document.getElementById("viagogoEventSectionFloorPrice");

            if (sectionSelect !== null && sectionSelect.length) {
                // Clear existing options
                sectionSelect.innerHTML = '<option selected="" disabled="">Section Name</option>';

                // Populate section options and set event listeners
                sectionsData.forEach(function (section) {
                    var option = document.createElement("option");
                    option.value = JSON.stringify({ "name": section.Section, "price": section.Price });
                    option.text = "Section: " + section.Section;
                    sectionSelect.appendChild(option);
                });

                sectionSelect.addEventListener("change", function () {
                    var selectedOption = JSON.parse(sectionSelect.value);
                    sectionFloorPrice.textContent = "Floor Price: " + selectedOption.price;
                });
            }

            document.getElementById('viewOnViagogo').setAttribute('href', `https://www.viagogo.com/ww/E-${response.eventData.id}`)
            document.getElementById('bannerBtn').style.display = "";
            document.getElementById('bannerTitle').textContent = response.eventData.name + " Tickets";
            document.getElementById('bannerSubtitle').textContent = "Analyze what's about to sell out, ticket prices, and more. All at a glance.";

            document.getElementById("overlay").style.display = "none";
            $('#eventOverview').fadeIn();
        }, function () { });
    }

    const searchInput = document.getElementById("searchInput");

    // Listen for Enter key press on the search input
    // Listen for input changes (when the user clears the input)
    searchInput.addEventListener("input", () => {
        const query = searchInput.value;
        if (query.trim() === "") {
            // The input is empty
            document.getElementById("searchResultsDropdown").innerHTML = "";
            document.getElementById("searchResultsDropdown").style.display = "none";
        } else {
            // Input is not empty, display results
            displaySearchResults(query);
        }
    });
});

// Function to display search results
function displaySearchResults(query) {
    getFilteredEvents(null, null, query, function (response) {

        const resultsContainer = document.getElementById("searchResultsDropdown");
        resultsContainer.innerHTML = ""; // Clear previous results

        if (response.events.length === 0) {
            const resultItem = document.createElement("a");
            resultItem.classList.add("dropdown-item");
            resultItem.textContent = "No results found";
            resultsContainer.appendChild(resultItem);
            resultsContainer.style.display = "flex";
            return;
        }

        for (var i = 0; i < 5; i++) {
            var event = response.events[i];
            if (event !== undefined) {
                const resultItem = document.createElement("a");
                resultItem.classList.add("dropdown-item");
                const eventLink = document.createElement("a");
                eventLink.href = `/?model=events&action=eventOverview&eventId=${event.id}`;
                eventLink.textContent = `${event.name} - ${event._embedded.venue.city}`;
                resultItem.appendChild(eventLink);
                resultsContainer.appendChild(resultItem);
            }
        }

        // Add "Display All Results" button
        const displayAllButton = document.createElement("a");
        displayAllButton.classList.add("dropdown-item");
        const link = document.createElement("a");
        link.href = `/?model=events&q=${query}`;
        link.textContent = `Display all results`;
        displayAllButton.appendChild(link);
        resultsContainer.appendChild(displayAllButton);
        resultsContainer.style.display = "flex";
    });


}

function onSubmitFilterViagogoEvents(form) {
    // Show the preloader
    $('#loadingPreloader').show();

    // Disable the submit button to prevent multiple submissions
    $('button[type="submit"]').prop('disabled', true);

    // Serialize the form data into a JSON object
    var query, country, genre;
    $(form).serializeArray().forEach(function (field) {
        switch (field.name) {
            case "query":
                query = field.value;
                break;

            case "country":
                country = field.value;
                break;

            case "genre":
                genre = field.value;
                break;

            default:
                break;
        }
    });

    getFilteredEvents(genre, country, query, function (response) {
        var events = response.events;

        if (events !== undefined) {
            // Get a reference to the DataTable
            var dataTable = $('#eventsTable').DataTable();

            // Clear existing rows and add new rows
            dataTable.clear().draw();
            for (var i = 0; i < events.length; i++) {
                var event = events[i];
                var startDate = new Date(event.start_date);
                var formattedStartDate = startDate.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                });

                var rowData = [
                    '<a href="/?model=events&action=eventOverview&eventId=' + event.id + '">' + event.name + '</a>',
                    event.min_ticket_price ? event.min_ticket_price.display : 'N/A',
                    formattedStartDate,
                    event._embedded.genre.name,
                    event._embedded.venue._embedded.country.code,
                    event._embedded.venue.city,
                    event._embedded.venue.name
                ];

                dataTable.row.add(rowData);
            }

            // Redraw the table with the new data
            dataTable.draw();
        }

        // Hide the preloader
        $('#loadingPreloader').hide();

        // Enable the submit button
        $('button[type="submit"]').prop('disabled', false);
    });

    // Prevent the default form submission
    return false;
}

function getFilteredEvents(genreId, countryCode, query, callback) {

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
                // Handle the error here
            });
    }

    function makeApiRequest(jwtToken) {
        $.ajax({
            url: 'https://api.mindsolutions.app/', // Replace with the URL of your PHP script
            type: 'POST',
            headers: {
                'Authorization': `Bearer ${jwtToken}`, // Use the retrieved JWT token
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                action: "filter_events",
                genre: genreId,
                country: countryCode,
                query: query,
            }),
            success: function (response) {
                // Handle the response here
                if (response) {
                    callback(response);
                } else {
                    console.error('Failed to retrieve sections prices');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX request failed:', error);
            }
        });
    }
}

function getEventOverviewData(eventId, successCallback, errorCallback) {

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
            url: 'https://api.mindsolutions.app/', // Replace with the URL of your PHP script
            type: 'POST',
            headers: {
                'Authorization': `Bearer ${jwtToken}`, // Use the retrieved JWT token
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                action: "event_overview",
                eventId: eventId,
            }),
            success: function (response) {
                // Handle the response here
                if (response) {
                    successCallback(response);
                } else {
                    errorCallback(response.message);
                }
            },
            error: function (xhr, status, error) {
                errorCallback(error);
            }
        });
    }
}
