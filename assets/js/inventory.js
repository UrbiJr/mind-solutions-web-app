document.addEventListener("DOMContentLoaded", () => {

    /* validate sign up fields using regex patterns */

    const addToInventoryButton = document.getElementById('addToInventory');
    const inventoryInputs = document.querySelectorAll('input[name="orderEmail"]');
    const inventoryPatterns = {
        orderEmail: /^([a-z\d\.-]+)@([a-z\d-]+)\.([a-z]{2,8})(\.[a-z]{2,8})?$/,
    };
    const $bulkUpdateModal = $("#bulkUpdateModal");
    const lookupEventButton = document.getElementById("lookupEvent");

    inventoryInputs.forEach((input) => {
        if (input) {
            input.addEventListener('keyup', (e) => {
                validate(e.target, inventoryPatterns[e.target.attributes.id.value]);
                updateAddToInventoryButton();
            });
        }
    });

    if ($('#openEditListingModal').length) {
        $('#openEditListingModal').on('click', function () {
            const eventId = $(this).attr('data-event-id');
            const quantity = $(this).attr('data-quantity');
            const select = $('#editListingSplitType');
            $('#loadingPreloader').show();
            getEventOverviewData(eventId, function (result) {
                if (result.eventData !== null && result.eventData.id) {
                    var sectionSelect = document.getElementById("editListingSectionSelect");
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
                    fetchSplitTypes(quantity)
                        .then(function (response) {
                            select.empty(); // Remove existing options
                            $.each(response.result.AvailableSplitTypes, function (index, option) {
                                select.append($("<option>", {
                                    value: option.Value,
                                    text: option.Text
                                }));
                            });
                            $('#loadingPreloader').hide();
                            $('#editListingModal').modal("show");
                        })
                        .catch(function (error) {
                            $('#loadingPreloader').hide();
                            console.error('Error getting split types:', error);
                            showToast("Error", `Unable to fetch split types`, true);
                        });
                } else {
                    $('#loadingPreloader').hide();
                    console.error('Viagogo event not found:', error);
                    showToast("Event not found", `Could not find an event with id ${eventId}`, true);
                }
            }, function (error) {
                $('#loadingPreloader').hide();
                // Handle any errors that occurred during the data fetching process
                console.error('Error fetching event data:', error);
                showToast("Error", 'Error fetching event data: ' + error, true);
            });
        })
    }

    if ($('#openEditItemModal').length) {
        $('#openEditItemModal').on('click', function () {
            const eventId = $(this).attr('data-event-id');
            $('#loadingPreloader').show();
            getEventOverviewData(eventId, function (result) {
                if (result.eventData !== null && result.eventData.id) {
                    var sectionSelect = document.getElementById("editItemSectionSelect");
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
                    $('#loadingPreloader').hide();
                    $('#editItemModal').modal("show");
                } else {
                    $('#loadingPreloader').hide();
                    console.error('Viagogo event not found:', error);
                    showToast("Event not found", `Could not find an event with id ${eventId}`, true);
                }
            }, function (error) {
                $('#loadingPreloader').hide();
                // Handle any errors that occurred during the data fetching process
                console.error('Error fetching event data:', error);
                showToast("Error", 'Error fetching event data: ' + error, true);
            });
        })
    }

    if ($("#markListedForm").length) {
        const select = $("#markListedSplitType"); // Get the existing select element by its id
        const selectContainer = $("#splitTypeContainer");
        const restrictionsContainer = $('#restrictionsContainer');
        const ticketDetailsContainer = $('#ticketDetailsContainer');
        const markListedBtn = $("#markListedBtn");

        $('#markListedForm select[name="platform"]').on('change', function () {
            if (this.value == "Viagogo") {
                // user selected viagogo platform, show additional form inputs
                const quantity = $('input[name="quantity"]').val();
                fetchSplitTypes(quantity)
                    .then(function (response) {
                        select.empty(); // Remove existing options
                        $.each(response.result.AvailableSplitTypes, function (index, option) {
                            select.append($("<option>", {
                                value: option.Value,
                                text: option.Text
                            }));
                        });
                    })
                    .catch(function (error) {
                        console.error('Error getting split types:', error);
                    });
                markListedBtn.text("Create Listing");
                selectContainer.removeClass("hidden");
                restrictionsContainer.removeClass("hidden");
                ticketDetailsContainer.removeClass("hidden");
            } else {
                markListedBtn.text("Mark Listed");
                selectContainer.addClass("hidden");
                restrictionsContainer.addClass("hidden");
                ticketDetailsContainer.addClass("hidden");
            }
        });

        $('.NoRestrictionOnUse').on('change', function () {
            if (this.checked) {
                $('input[name="restrictions"]').prop('checked', false);
                $('input[name="restrictions"]').prop('disabled', true);
            } else {
                $('input[name="restrictions"]').prop('disabled', false);
            }
        });
    }

    if ($("#form-wizard1").length) {

        // get eventId
        const searchParams = new URLSearchParams(window.location.search);
        const action = searchParams.get('action');
        if (action === "addEvent") {
            /* add event to inventory by eventId */
            const eventId = searchParams.get('eventId');

            if (eventId === undefined || eventId == "") {
                return false;
            }

            document.getElementById("overlay").style.display = "";

            getEventOverviewData(eventId, function (result) {
                if (result.eventData !== null && result.eventData.id) {
                    document.getElementById("city").disabled = true;
                    document.getElementById("location").disabled = true;
                    document.getElementById("eventId").setAttribute('value', result.eventData.id);
                    document.getElementById("city").setAttribute('value', result.eventData._embedded.venue.city);
                    document.getElementById("location").setAttribute('value', result.eventData._embedded.venue.name);
                    document.getElementById("categoryId").setAttribute('value', result.categoryId);
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
        }

        const addToInventoryModal = document.getElementById("addToInventoryModal");

        if (addToInventoryModal) {
            // Add an event listener for the modal's "hidden.bs.modal" event
            addToInventoryModal.addEventListener("hidden.bs.modal", function (event) {
                // This code will run when the modal is closed or hidden
                // You can perform any necessary actions here
                resetForm();
            });
        }

        $("#addToInventory").on('click', () => {
            // Disable submit button to prevent multiple submissions
            $("#addToInventory").prop("disabled", true);
            $("#addingToInventoryContainer").show();
            itemId = addToInventoryButton.getAttribute("data-item-id");
            var form = document.getElementById("form-wizard1");
            var disabledInputs = form.querySelectorAll('input[disabled]');
            disabledInputs.forEach(input => {
                input.removeAttribute('disabled');
            });
            if ($("#staticBackdropLabel").html() == "Edit Inventory Item") {
                $("#addToInventoryStatus").text("Updating Item...");
                //update item
                // Perform Ajax POST request
                $.ajax({
                    type: "POST",
                    url: '/?model=inventory&action=updateItem&id=' + itemId,
                    data: $('#form-wizard1').serialize(),
                    success: function (response) {
                        // Hide loading spinner
                        $("#addingToInventorySpinner").hide();

                        // Handle the response from the server
                        if (response.success === true) {
                            $("#addToInventoryStatus").text("Successfully updated item.").attr('class', 'text-center purple-text text-success');
                            // Refresh the table content
                            $("#inventoryTable").bootstrapTable('refresh');
                        } else {
                            $("#addToInventory").prop("disabled", false);
                            $("#addToInventoryStatus").text("Error: " + response.message).attr('class', 'text-center purple-text text-danger');
                        }
                    },
                    error: function (response) {
                        // Hide loading spinner
                        $("#addToInventory").prop("disabled", false);
                        $("#addingToInventorySpinner").hide();
                        $("#addToInventoryStatus").text("An error occurred while processing the request.").attr('class', 'text-center purple-text text-danger');
                    }
                });
            } else {
                // Serialize the form data, including disabled inputs
                var formData = $('#form-wizard1').serializeArray();

                // Manually collect values from disabled inputs
                $('input:disabled').each(function () {
                    formData.push({ name: $(this).attr('name'), value: $(this).val() });
                });

                // Manually collect values from hidden inputs
                $('input[type="hidden"]').each(function () {
                    formData.push({ name: $(this).attr('name'), value: $(this).val() });
                });

                // add new item
                // Perform Ajax POST request
                $.ajax({
                    type: "POST",
                    url: $('#form-wizard1').attr("action"),
                    data: formData,
                    success: function (response) {
                        // Hide loading spinner
                        $("#addingToInventorySpinner").hide();

                        // Handle the response from the server
                        if (response.success === true) {
                            // Refresh the table content
                            if (action === "addEvent") {
                                window.location.href = "/?model=inventory&action=show";
                            }
                            $("#addToInventoryStatus").text("Item added successfully.").attr('class', 'text-center purple-text text-success');
                            $("#inventoryTable").bootstrapTable('refresh');
                        } else {
                            $("#addToInventoryStatus").text("Error: " + response.message).attr('class', 'text-center purple-text text-danger');
                        }
                    },
                    error: function (response) {
                        // Hide loading spinner
                        $("#addingToInventorySpinner").hide();
                        $("#addToInventoryStatus").text("An error occurred while processing the request.").attr('class', 'text-center purple-text text-danger');
                    }
                });
            }
        });

        var sectionSelect = document.querySelector('select[name="section"]');
        sectionSelect.addEventListener("change", function () {
            if (sectionSelect.value.toLowerCase() === "floor") {
                document.querySelector('input[name="row"]').setAttribute('value', "");
                document.querySelector('input[name="row"]').setAttribute('placeholder', "");
                document.querySelector('input[name="row"]').disabled = true;
                document.querySelector('input[name="seatFrom"]').setAttribute('value', "");
                document.querySelector('input[name="seatFrom"]').setAttribute('placeholder', "");
                document.querySelector('input[name="seatFrom"]').disabled = true;
                document.querySelector('input[name="seatTo"]').setAttribute('value', "");
                document.querySelector('input[name="seatTo"]').setAttribute('placeholder', "");
                document.querySelector('input[name="seatTo"]').disabled = true;
            } else {
                document.querySelector('input[name="row"]').disabled = false;
                document.querySelector('input[name="seatFrom"]').disabled = false;
                document.querySelector('input[name="seatFrom"]').setAttribute('placeholder', "seat from");
                document.querySelector('input[name="seatTo"]').disabled = false;
                document.querySelector('input[name="seatTo"]').setAttribute('placeholder', "seat to");
            }
        });

        // Get references to the input fields and the "Lookup Event" button
        var eventNameInput = document.getElementById("eventName");
        var countrySelect = document.getElementById("country");
        var eventDateInput = document.getElementById("eventDate");
        var ticketGenreSelect = document.getElementById("ticketGenre");

        // Add input event listeners to the input fields
        eventNameInput.addEventListener("input", validateEventFieldset);
        countrySelect.addEventListener("input", validateEventFieldset);
        eventDateInput.addEventListener("input", validateEventFieldset);
        ticketGenreSelect.addEventListener("input", validateEventFieldset);

        validateEventFieldset();

        if (lookupEventButton) {
            lookupEventButton.addEventListener("click", function () {
                var eventName = eventNameInput.value;
                var eventDate = eventDateInput.value;
                var country = countrySelect.value;
                var ticketGenre = ticketGenreSelect.value;
                lookupEvent(eventName, eventDate, country, ticketGenre).
                    then(function (result) {
                        if (result.id !== null && result.id !== "") {
                            document.getElementById("city").disabled = true;
                            document.getElementById("location").disabled = true;
                            document.getElementById("eventId").setAttribute('value', result.id);
                            document.getElementById("city").setAttribute('value', result.city);
                            document.getElementById("location").setAttribute('value', result.location);
                            document.getElementById("categoryId").setAttribute('value', result.categoryId);
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
                            // Parse the date string
                            var parsedDate = new Date(result.date);
                            // Format the parsed date as a string suitable for the input field
                            var formattedDate = parsedDate.toISOString().slice(0, 16); // Truncate to minutes
                            // Set the formatted date as the value for the input field
                            eventDateInput.value = formattedDate;
                            eventNameInput.value = result.name;
                        } else {
                            document.getElementById("sectionSelect").style.display = "none";
                            document.getElementById("customSection").style.display = "block";
                        }
                        nextBtnFunction(1);
                    })
                    .catch(function (error) {
                        // Handle any errors that occurred during the data fetching process
                        console.error('Error fetching event data:', error);
                        document.getElementById("requestErrorMsg").textContent = 'Error fetching event data: ' + error;
                        $("#requestErrorMsg").fadeIn();
                        setTimeout(function () {
                            $("#requestErrorMsg").fadeOut();
                        }, 4000);
                    });
            });
        }

    }

    if ($('#inventoryBulkActions').length) {
        $('#inventoryBulkActions .edit').on('click', function () {
            const selections = $('table[data-multiple-select-row="true"]').bootstrapTable('getSelections');
            if (selections.length <= 0) {
                showBottomToast("No Items Selected", "You must select one or more rows");
            } else {
                $bulkUpdateModal.modal("show");
            }
        });

        $('#bulkUpdateBtn').on('click', function () {
            const selections = $("#inventoryTable").bootstrapTable('getSelections');
            const ids = [];
            // Using forEach to iterate through the JSON array
            selections.forEach(function (item) {
                // Create a jQuery object from the HTML string
                var $htmlEventData = $(item.eventData);
                // Get the value of the data-item-id attribute using the data method
                var itemId = $htmlEventData.data('item-id');
                ids.push(itemId);
            });
            const attributesMap = {};
            const currency = getCookie('currency');
            const formData = $('#bulkUpdateModal form').serializeArray();

            formData.forEach((fieldData) => {
                const fieldName = fieldData.name;
                const fieldValue = fieldData.value;

                if (fieldValue !== null && fieldValue !== "") {
                    attributesMap[fieldName] = fieldValue;
                }
            });

            if (currency !== null && currency != "") {
                if (attributesMap.hasOwnProperty('individualTicketCost')) {
                    attributesMap['individualTicketCost'] = {
                        amount: attributesMap['individualTicketCost'],
                        currency: currency
                    };
                }
            }

            // $("#addToInventoryStatus").text("Updating Item...");
            // Perform Ajax POST request
            $.ajax({
                type: "POST",
                url: '/?model=inventory&action=bulkUpdate',
                data: {
                    ids: ids,
                    attributes: attributesMap,
                },
                success: function (response) {
                    // Handle the response from the server
                    if (response.success === true) {
                        showToast("Success", "Successfully updated " + response.count + " items", false, true);
                        // Refresh the table content
                        $("#inventoryTable").bootstrapTable('refresh');
                    } else {
                        showToast("Error", "Failed updating one or more items", true);
                    }
                },
                error: function (response) {
                    showToast("Error", "An error occurred while processing the request, please try again", true);
                }
            });
        });
    }

    if ($("#inventoryTable").length) {

        $("#inventoryTable").on('load-success.bs.table', function (data, status, xhr) {
            $('#inventoryTable tbody tr').each(function () {
                var $row = $(this);
                var eventData = $row.find('span[data-event-id][data-section][data-category-id]');
                var eventId = eventData.attr('data-event-id');
                var categoryId = eventData.attr('data-category-id');
                var section = eventData.attr('data-section');
                var itemId = eventData.attr('data-item-id');
                $row.attr('data-item-id', itemId);
                $row.attr('data-event-id', eventId);
                $row.attr('data-category-id', categoryId);
                $row.attr('data-section', section);
            });
        });

        $("#inventoryTable").on('click', 'button[name="copy-inventory-item"]', function () {
            var itemId = $(this).attr('data-item-id');
            duplicateInventoryItem(itemId);
        });

        $("#inventoryTable").on('click', 'button[name="edit-inventory-item"]', function () {
            // Get the item ID from the clicked button's data attribute
            var itemId = $(this).attr('data-item-id');
            editInventoryItem(itemId);
        });

        $("#inventoryTable").on('click', 'button[name="delete-inventory-item"]', function () {
            var itemId = $(this).attr('data-item-id');
            document.getElementById("confirmDeleteButton").setAttribute("data-item-id", itemId);
            $('#confirmDeleteModal').modal('show');
        });

        document.getElementById("confirmDeleteButton").addEventListener("click", function () {
            const itemId = this.getAttribute("data-item-id");
            deleteInventoryItem(itemId); // Call the function to send the POST request
        });
    }

    if ($('.syncViagogoListings').length) {
        $('.syncViagogoListings').on('click', function () {
            syncViagogoListings();
        });

        const btns = document.querySelectorAll('.syncViagogoListings');

        btns.forEach((btn) => {
            // Add a class when the user hovers over the element
            btn.addEventListener("mouseover", () => {
                btn.classList.add("animated-rotate-faster");
            });

            // Remove the class when the user moves the cursor away from the element
            btn.addEventListener("mouseout", () => {
                btn.classList.remove("animated-rotate-faster");
            });
        });

    }

});

// Function to validate the form
function validateEventFieldset() {
    const lookupEventButton = document.getElementById("lookupEvent");
    var eventNameInput = document.getElementById("eventName");
    var countrySelect = document.getElementById("country");
    var ticketGenreSelect = document.getElementById("ticketGenre");
    var eventDateInput = document.getElementById("eventDate");

    // Parse the event date input value as a Date object
    var eventDate = new Date(eventDateInput.value);

    // Check if all fields are valid
    var isEventNameValid = eventNameInput.value.trim() !== "";
    var isCountryValid = countrySelect.value !== "Select a country";
    var isEventDateValid = !isNaN(eventDate.getTime());
    var isTicketGenreValid = ticketGenreSelect.value !== "Select a genre";

    if (!isEventNameValid) {
        eventNameInput.className = 'form-control is-invalid';
    } else {
        eventNameInput.className = 'form-control is-valid';
    }
    if (!isEventDateValid) {
        eventDateInput.className = 'form-control is-invalid';
    } else {
        eventDateInput.className = 'form-control is-valid';
    }
    if (!isCountryValid) {
        countrySelect.className = 'form-select is-invalid';
    } else {
        countrySelect.className = 'form-select is-valid';
    }
    if (!isTicketGenreValid) {
        ticketGenreSelect.className = 'form-select is-invalid';
    } else {
        ticketGenreSelect.className = 'form-select is-valid';
    }

    // Enable the "Lookup Event" button if all fields are valid, otherwise disable it
    lookupEventButton.disabled = !(isEventNameValid && isCountryValid && isEventDateValid && isTicketGenreValid);
}

// Function to validate the form
function resetEventFieldset() {
    // Get the current date and time
    var eventNameInput = document.getElementById("eventName");
    var countrySelect = document.getElementById("country");
    var ticketGenreSelect = document.getElementById("ticketGenre");
    var eventDateInput = document.getElementById("eventDate");
    const lookupEventButton = document.getElementById("lookupEvent");
    eventNameInput.className = 'form-control';
    eventDateInput.className = 'form-control';
    countrySelect.className = 'form-select';
    ticketGenreSelect.className = 'form-select';
    lookupEventButton.disabled = true;
}

function fetchItemData(itemId) {
    return new Promise(function (resolve, reject) {
        // Perform Ajax POST request
        $.ajax({
            type: "POST",
            url: '/?model=inventory&action=getItem',
            data: {
                id: itemId
            },
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response);
                } else {
                    reject('Error: ' + response.message); // Reject the Promise with an error message
                }
            },
            error: function (xhr, status, error) {
                reject('AJAX error: ' + error); // Reject the Promise with an AJAX error message
            },
        });
    });
}

function lookupEvent(eventName, eventDate, country, ticketGenre) {

    function makeApiRequest(jwtToken, resolve, reject) {
        $.ajax({
            url: 'https://api.mindsolutions.app/', // Replace with the URL of your PHP script
            type: 'POST',
            headers: {
                'Authorization': `Bearer ${jwtToken}`, // Use the retrieved JWT token
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                action: "lookup_event",
                eventName: eventName,
                eventDate: eventDate,
                country: country,
                ticketGenre: ticketGenre,
            }),
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response);
                } else {
                    reject('Error: ' + response.message); // Reject the Promise with an error message
                }
            },
            error: function (xhr, status, error) {
                reject('AJAX error: ' + error); // Reject the Promise with an AJAX error message
            },
        });
    }

    return new Promise(function (resolve, reject) {
        // Check if the token is already available in localStorage
        var token = getWithExpiry('jwtToken');

        if (token) {
            makeApiRequest(token, resolve, reject); // Pass resolve and reject as arguments
        } else {
            // Token is not available, request it
            requestJwtToken()
                .then((token) => {
                    var parsed = JSON.parse(token);
                    setWithExpiry('jwtToken', parsed.token, parsed.expiresIn * 1000);
                    makeApiRequest(parsed.token, resolve, reject); // Pass resolve and reject as arguments
                })
                .catch((error) => {
                    console.error('Error receiving token:', error);
                    reject('Error receiving token:', error);
                });
        }
    });
}

function getUserListings(viagogoSessionId, callback) {

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
                action: "get_user_listings",
                viagogoSessionId: viagogoSessionId,
            }),
            success: function (response) {
                // Handle the response here
                if (response) {
                    callback(response);
                } else {
                    console.error('Failed to retrieve user listings');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX request failed:', error);
            }
        });
    }
}

function getUserSales(viagogoSessionId, callback) {

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
                action: "get_user_sales",
                viagogoSessionId: viagogoSessionId,
            }),
            success: function (response) {
                // Handle the response here
                if (response) {
                    callback(response);
                } else {
                    console.error('Failed to retrieve user listings');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX request failed:', error);
            }
        });
    }
}

function disableSyncButton() {
    //$('#syncViagogoListings').addClass('animated-rotate-faster');
}

function enableSyncButton() {
    //$('#syncViagogoListings').removeClass('animated-rotate-faster');
}

function syncViagogoListings() {
    disableSyncButton();
    const viagogoSessionId = getCookie('viagogoSessionId');

    if (!viagogoSessionId || viagogoSessionId === "") {
        enableSyncButton();
        showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
    } else {
        $('#preloader').show();

        getUserListings(viagogoSessionId, (response) => {
            if (response.success) {
                if (response.cookie) {
                    updateCookie('viagogoSessionId', response.cookie, 7);
                }
                const listings = response.listings.map(item => ({
                    EventId: item.EventId,
                    Seats: item.Seats,
                    Country: item.Country,
                    PricePerTicket: item.PricePerTicket,
                    PayoutPerTicket: item.PayoutPerTicket,
                    CategoryId: item.CategoryId,
                    EventDescription: item.EventDescription,
                    EventDate: item.EventDate,
                    City: item.City,
                    VenueDescription: item.VenueDescription,
                    Section: item.Section,
                    Rows: item.Rows,
                    TicketType: item.TicketType,
                    GenreId: item.GenreId,
                    Status: item.Status,
                    SaleEndDate: item.SaleEndDate,
                    Quantity: item.Quantity,
                    QuantityRemain: item.QuantityRemain,
                    DateLastModified: item.DateLastModified,
                }));

                getUserSales(viagogoSessionId, (response) => {
                    if (response.success) {
                        if (response.cookie) {
                            updateCookie('viagogoSessionId', response.cookie, 7);
                        }
                        const sales = response.sales.map(item => ({
                            SaleId: item.SaleId,
                            Country: item.Country,
                            EventId: item.EventId,
                            GenreId: item.GenreId,
                            DateLastModified: item.DateLastModified,
                            EventDate: item.EventDate,
                            EventDescription: item.EventDescription,
                            City: item.City,
                            VenueDescription: item.VenueDescription,
                            Section: item.Section,
                            Row: item.Row,
                            Seats: item.Seats,
                            Quantity: item.Quantity,
                            TicketType: item.DeliveryMethodDisplayName,
                            TotalPayout: item.TotalPayout,
                            SaleDate: item.SaleDate,
                        }));

                        $.ajax({
                            type: "POST",
                            data: {
                                listings: listings,
                                sales: sales,
                            },
                            url: "/?model=inventory&action=syncViagogo",
                            success: (response) => {
                                if (response.success) {
                                    $('#preloader').hide();
                                    $("#inventoryTable").bootstrapTable('refresh');
                                    $('#listingsTable').bootstrapTable('refresh');
                                    $('#salesTable').bootstrapTable('refresh');
                                    showToast("Success", "Successfully synced inventory and sales", false, true);
                                    enableSyncButton();
                                } else {
                                    $('#preloader').hide();
                                    enableSyncButton();
                                    showToast("Error", response.message, true);
                                }
                            },
                            error: (error) => {
                                console.error(error);
                                $('#preloader').hide();
                                enableSyncButton();
                                showToast("Error", "Request error, please try again.", true);
                            }
                        });
                    } else {
                        $('#preloader').hide();
                        enableSyncButton();
                        showToast("Error", response.message === "session expired" ? '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>' : response.message, true);
                    }
                });
            } else {
                $('#preloader').hide();
                enableSyncButton();
                showToast("Error", response.message === "session expired" ? '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>' : response.message, true);
            }
        });
    }

    return false;
}

function deleteInventoryItem(itemId) {
    showBottomToast("Deleting", "Deleting item from inventory...");

    // Send an AJAX request to delete the item
    $.ajax({
        type: 'POST',
        url: '/?model=inventory&action=delete',
        data: {
            id: itemId
        },
        success: function (response) {
            if (response.success === true) {
                $("#inventoryTable").bootstrapTable('refresh');
            } else {
                showBottomToast("Error", 'Error deleting item: ' + response.message, "bg-danger", 5000);
            }
        },
        error: function (error) {
            showBottomToast("Error", 'Error deleting item: ' + error, "bg-danger", 5000);
        }
    });
}

function duplicateInventoryItem(itemId) {
    // Disable submit button to prevent multiple submissions
    $("#addToInventory").prop("disabled", true);
    showBottomToast("Copying", "Copying item to inventory...");

    // Perform Ajax POST request
    $.ajax({
        type: "POST",
        url: '/?model=inventory&action=duplicate',
        data: {
            id: itemId
        },
        success: function (response) {
            // Handle the response from the server
            if (response.success === true) {
                // Refresh the table content
                $("#inventoryTable").bootstrapTable('refresh');
            } else {
                showBottomToast("Error", "Copy failed: " + response.message, "bg-danger", 5000);
            }
        },
        error: function (response) {
            showBottomToast("Error", "Copy failed due to request error", "bg-danger", 5000);
        }
    });
}

function updateAddToInventoryButton() {
    const addToInventoryButton = document.getElementById('addToInventory');
    const allValid = [...inventoryInputs].every((input) => input.classList.contains('is-valid'));
    if (allValid) {
        addToInventoryButton.removeAttribute('disabled');
    } else {
        addToInventoryButton.setAttribute('disabled', true);
    }
}

function editInventoryItem(itemId) {
    const addToInventoryButton = document.getElementById('addToInventory');

    // Use itemId to fetch item data (replace this with your data fetching logic)
    document.getElementById("loadingPreloader").style.display = "";
    fetchItemData(itemId).
        then(function (itemData) {
            document.getElementById("loadingPreloader").style.display = "none";
            // Populate modal fields with the fetched item data
            $("#staticBackdropLabel").html("Edit Inventory Item");
            $("#addToInventoryModal .header-title").hide();
            document.querySelector('input[name="eventName"]').setAttribute('value', itemData.eventName);
            document.querySelector('input[name="orderEmail"]').setAttribute('value', itemData.orderEmail);
            document.querySelector('input[name="orderNumber"]').setAttribute('value', itemData.orderNumber);
            document.querySelector('input[name="purchaseDate"]').setAttribute('value', itemData.purchaseDate);
            document.querySelector('input[name="city"]').setAttribute('value', itemData.city);
            document.querySelector('input[name="location"').setAttribute('value', itemData.location);
            document.querySelector('input[name="eventDate"').setAttribute('value', itemData.eventDate);
            document.querySelector('input[name="ticketCost"]').setAttribute('value', itemData.individualTicketCost.amount);

            document.querySelector('input[name="row"]').setAttribute('value', itemData.row);
            document.querySelector('input[name="seatFrom"]').setAttribute('value', itemData.seatFrom);
            document.querySelector('input[name="seatTo"]').setAttribute('value', itemData.seatTo);
            document.querySelector('input[name="quantity"]').setAttribute('value', itemData.quantity);
            document.querySelector('input[name="quantityRemain"]').setAttribute('value', itemData.quantityRemain);
            var countrySelect = document.querySelector('select[name="country"]');
            var countryOptions = countrySelect.options;
            for (var i = 0; i < countryOptions.length; i++) {
                if (countryOptions[i].value === itemData.country) {
                    countryOptions[i].selected = true;
                    break; // Exit the loop once the correct option is selected
                }
            }

            var sectionSelect = document.querySelector('select[name="section"]');
            var option = document.createElement("option");
            // Set the value and text of the option
            option.value = itemData.section;
            option.text = itemData.section;
            // Append the option to the select element
            sectionSelect.appendChild(option);
            option.selected = true;
            // trigger onchange event
            sectionSelect.dispatchEvent(new Event('change'));

            var retailerSelect = document.querySelector('select[name="retailer"]');
            var retailerOptions = retailerSelect.options;
            for (var i = 0; i < retailerOptions.length; i++) {
                if (retailerOptions[i].value === itemData.retailer) {
                    retailerOptions[i].selected = true;
                    break; // Exit the loop once the correct option is selected
                }
            }

            var ticketGenreSelect = document.querySelector('select[name="ticketGenre"]');
            var ticketGenreOptions = ticketGenreSelect.options;
            for (var i = 0; i < ticketGenreOptions.length; i++) {
                if (ticketGenreOptions[i].value === itemData.ticketGenre) {
                    ticketGenreOptions[i].selected = true;
                    break; // Exit the loop once the correct option is selected
                }
            }

            var ticketTypeSelect = document.querySelector('select[name="ticketType"]');
            var ticketTypeOptions = ticketTypeSelect.options;
            for (var i = 0; i < ticketTypeOptions.length; i++) {
                if (ticketTypeOptions[i].value === itemData.ticketType) {
                    ticketTypeOptions[i].selected = true;
                    break; // Exit the loop once the correct option is selected
                }
            }

            // Show the modal
            addToInventoryButton.setAttribute("data-item-id", itemId);
            addToInventoryButton.textContent = "Update";
            validateEventFieldset();
            $('#addToInventoryModal').modal('show');
        })
        .catch(function (error) {
            document.getElementById("loadingPreloader").style.display = "none";
            // Handle any errors that occurred during the data fetching process
            console.error('Error fetching item data:', error);
            showBottomToast("Error", 'Error fetching item data: ' + error, "bg-danger", 5000);
        });
}

function resetForm() {
    const addToInventoryButton = document.getElementById('addToInventory');

    for (var i = 0; i < currentTab; i++) {
        nextBtnFunction(-1);
    }
    if (currentTab > 0) {
        nextBtnFunction(-1);
    }

    document.getElementById("city").disabled = false;
    document.getElementById("location").disabled = false;
    var form = document.getElementById('form-wizard1');

    // Loop through the form elements
    for (var i = 0; i < form.elements.length; i++) {
        var element = form.elements[i];

        // Check if the element is an input field or select element
        if (element.tagName === 'INPUT' || element.tagName === 'SELECT') {
            // Reset the value to its default value or empty string
            element.value = '';
        }
    }
    $("#addToInventory").prop("disabled", false);
    addToInventoryButton.textContent = "Submit";
    $("#addToInventoryStatus").text("Adding To Inventory...");
    $("#staticBackdropLabel").html("Add To Inventory");
    $("#addingToInventoryContainer").hide();
    resetEventFieldset();
    if ($("#addToInventoryModal").length) {
        $("#addToInventoryModal .header-title").show();
    }
}

function showToast(title, body, isError = false, isSuccess = false) {
    const toast = document.getElementById("bottomToast");
    toast.querySelector(".toast-header strong").textContent = title;
    toast.querySelector(".toast-body").innerHTML = body;

    if (body.includes('Viagogo session expired')) {
        deleteCookie('viagogoSessionId');
    }

    if (isError) {
        toast.classList.remove("bg-success");
        toast.classList.remove("bg-primary");
        toast.classList.add("bg-danger");
    } else if (isSuccess) {
        toast.classList.remove("bg-danger");
        toast.classList.remove("bg-primary");
        toast.classList.add("bg-success");
    } else {
        toast.classList.remove("bg-danger");
        toast.classList.remove("bg-success");
        toast.classList.add("bg-primary");
    }

    const bootstrapToast = new bootstrap.Toast(toast);
    bootstrapToast.show();
}

function getCheckedRestrictions(form) {
    // Get all of the checkbox elements in the form.
    const checkboxes = document.querySelectorAll('input[name="restrictions"]', form);

    // Create an array to store the checked values.
    const checkedValues = [];

    // Iterate over the checkbox elements and check if each checkbox is checked.
    for (const checkbox of checkboxes) {
        if (checkbox.checked) {
            // Add the checkbox's value to the array of checked values.
            checkedValues.push(checkbox.value);
        }
    }

    // Return the array of checked values.
    return checkedValues;
}

function getCheckedTicketDetails(form) {
    // Get all of the checkbox elements in the form.
    const checkboxes = document.querySelectorAll('input[name="ticketDetails"]', form);

    // Create an array to store the checked values.
    const checkedValues = [];

    // Iterate over the checkbox elements and check if each checkbox is checked.
    for (const checkbox of checkboxes) {
        if (checkbox.checked) {
            // Add the checkbox's value to the array of checked values.
            checkedValues.push(checkbox.value);
        }
    }

    // Return the array of checked values.
    return checkedValues;
}

function quickEdit(form) {

    $('#markListedModal').modal('hide');
    $('#loadingPreloader').show();

    let itemId;
    const attributesMap = {};
    const formData = $(form).serializeArray();
    let itemStatus = false;
    let platform = false;
    let splitType = '';

    formData.forEach((fieldData) => {
        const fieldName = fieldData.name;
        const fieldValue = fieldData.value;

        if (fieldName === "itemId") {
            itemId = fieldValue;
        }

        if (fieldName === "status") {
            itemStatus = fieldValue;
        }

        if (fieldName === "customPlatform") {
            attributesMap['platform'] = fieldValue;
            platform = fieldValue;
        }

        if (fieldName === "platform") {
            platform = fieldValue;
        }

        if (fieldName === "splitType") {
            splitType = fieldValue;
            return;
        }

        if (fieldValue !== null && fieldValue !== "") {
            attributesMap[fieldName] = fieldValue;
        }
    });

    if (attributesMap.hasOwnProperty('yourPricePerTicketCurrency') && attributesMap.hasOwnProperty('yourPricePerTicket')) {
        attributesMap['yourPricePerTicket'] = {
            amount: attributesMap['yourPricePerTicket'],
            currency: attributesMap['yourPricePerTicketCurrency']
        };
    }
    if (attributesMap.hasOwnProperty('individualTicketCostCurrency') && attributesMap.hasOwnProperty('individualTicketCost')) {
        attributesMap['individualTicketCost'] = {
            amount: attributesMap['individualTicketCost'],
            currency: attributesMap['individualTicketCostCurrency']
        };
    }
    if (attributesMap.hasOwnProperty('totalPayoutCurrency') && attributesMap.hasOwnProperty('totalPayout')) {
        attributesMap['totalPayout'] = {
            amount: attributesMap['totalPayout'],
            currency: attributesMap['totalPayoutCurrency']
        };
    }

    switch (itemStatus) {
        // if status = 'inactive' -> delete sale date + total payout + your price per ticket 
        case 'Inactive':
            attributesMap['yourPricePerTicket'] = {
                amount: 0,
                currency: getCookie('currency')
            };
            attributesMap['totalPayout'] = {
                amount: 0,
                currency: getCookie('currency')
            };
            attributesMap['saleDate'] = null;
            break;

        // if status = 'active' -> delete sale date + total payout
        case 'Active':
            let totalPayouAmount = 0;
            if (platform == 'Viagogo') {
                totalPayouAmount = (parseInt(attributesMap['quantity']) * parseFloat(attributesMap['yourPricePerTicket']['amount'])) * 0.1;
            } else {
                totalPayouAmount = parseInt(attributesMap['quantity']) * parseFloat(attributesMap['yourPricePerTicket']['amount'])
            }
            attributesMap['totalPayout'] = {
                amount: totalPayouAmount,
                currency: attributesMap['yourPricePerTicketCurrency']
            };
            attributesMap['saleDate'] = null;
            break;

        default:
            break;
    }

    if (itemStatus === "Active" && platform === "Viagogo") {
        // fetch latest item data
        fetchItemData(itemId).
            then(function (itemData) {
                itemData.splitType = splitType;
                itemData.yourPricePerTicket = attributesMap['yourPricePerTicket'];
                try {
                    // check if item is missing any required field
                    validateListingData(itemData);
                    const checkedRestrictions = getCheckedRestrictions(form);
                    const checkedDetails = getCheckedTicketDetails(form);
                    sameRestrictions = haveSameElements(checkedRestrictions, itemData.restrictions);
                    sameTicketDetails = haveSameElements(checkedDetails, itemData.ticketDetails);

                    if (sameRestrictions && sameTicketDetails && itemData.listingId && itemData.listingId.length > 0) {
                        // listing was already created, first of all edit details
                        $('#loadingPreloader .title').text('Listing activation in progress (1/2)');
                        $('#loadingPreloader .subtitle').text('Editing listing details...');
                        editListingDetails(
                            itemData.listingId,
                            itemData.eventId,
                            itemData.ticketType,
                            itemData.quantityRemain,
                            splitType,
                            itemData.section,
                            itemData.row,
                            itemData.seatFrom,
                            itemData.seatTo,
                            itemData.individualTicketCost,
                            itemData.yourPricePerTicket,
                        )
                            .then(function (response) {
                                updateItemAttributes(itemId, attributesMap);
                                // then , attempt to activate it again
                                $('#loadingPreloader .title').text('Listing activation in progress (2/2)');
                                $('#loadingPreloader .subtitle').text('Activating listing...');
                                editListing(response.csrfToken, itemData.listingId, "activate")
                                    .then(function (response) {
                                        // Successfully listed
                                        $('#loadingPreloader').hide();
                                        $('#loadingPreloader .title').text('Loading...');
                                        $('#loadingPreloader .subtitle').text('');
                                        updateItemAttributes(itemId, attributesMap);
                                        $('.listing-details').removeClass('hidden');
                                        showToast("Listing activated!", `Successfully listed on Viagogo`, false, true);
                                    })
                                    .catch(function (error) {
                                        $('#loadingPreloader').hide();
                                        $('#loadingPreloader .title').text('Loading...');
                                        $("html, body").animate({ scrollTop: "0" }, 500);
                                        if (error.includes('session expired')) {
                                            deleteCookie('viagogoSessionId');
                                            showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
                                        } else if (error.includes('Invalid listing id')) {
                                            // do not update item status, your price per ticket, platform
                                            attributesMap.delete('status');
                                            attributesMap.delete('yourPricePerTicket');
                                            attributesMap.delete('platform');
                                            // reset listing id since it's invalid
                                            attributesMap['listingId'] = '';
                                            updateItemAttributes(itemId, attributesMap);
                                            showToast("Invalid listing status", "Listing can not be activated: please try again", true);
                                        } else {
                                            showToast("Error activating listing", "Please make sure all listing data is valid", true);
                                        }
                                        console.error("Error activating listing: " + error);
                                    });
                            })
                            .catch(function (error) {
                                $('#loadingPreloader').hide();
                                $('#loadingPreloader .title').text('Loading...');
                                $('#loadingPreloader .subtitle').text('');
                                $("html, body").animate({ scrollTop: "0" }, 500);
                                if (error.includes('session expired')) {
                                    deleteCookie('viagogoSessionId');
                                    showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
                                } else {
                                    showToast("Error activating listing", "Please make sure all listing data is valid", true);
                                }
                                console.error("Error activating listing: " + error);
                            });

                    } else {
                        $('#loadingPreloader .title').text('Listing creation in progress...');
                        $('#loadingPreloader .subtitle').text('This should normally take around 25 seconds. Do not refresh this page.');

                        // create listing on Viagogo
                        createListing(
                            itemData.eventId,
                            itemData.ticketType,
                            itemData.quantityRemain,
                            splitType,
                            itemData.section,
                            itemData.row,
                            itemData.seatFrom,
                            itemData.seatTo,
                            itemData.individualTicketCost,
                            itemData.yourPricePerTicket,
                            checkedRestrictions,
                            checkedDetails,
                        )
                            .then(function (response) {
                                // Successfully listed
                                $('#loadingPreloader').hide();
                                $('#loadingPreloader .title').text('Loading...');
                                $('#loadingPreloader .subtitle').text('');
                                attributesMap['listingId'] = response.listingId;
                                attributesMap['restrictions'] = checkedRestrictions;
                                attributesMap['ticketDetails'] = checkedDetails;
                                updateItemAttributes(itemId, attributesMap);
                                $('.listing-details').removeClass('hidden');
                                showToast("New listing!", `Successfully listed on Viagogo`, false, true);
                            })
                            .catch(function (error) {
                                $('#loadingPreloader').hide();
                                $('#loadingPreloader .title').text('Loading...');
                                $('#loadingPreloader .subtitle').text('');
                                $("html, body").animate({ scrollTop: "0" }, 500);
                                if (error.includes('session expired')) {
                                    deleteCookie('viagogoSessionId');
                                    showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
                                } else {
                                    showToast("Error adding listing", "Please make sure all listing data is valid", true);
                                }
                                console.error("Error creating listing: " + error);
                            });
                    }
                } catch (e) {
                    $('#loadingPreloader').hide();
                    $("html, body").animate({ scrollTop: "0" }, 500);
                    showToast("Listing data missing", `${e}`, true);
                }

            })
            .catch(function (error) {
                $('#loadingPreloader').hide();
                // Handle any errors that occurred during the data fetching process
                console.error('Error fetching item data:', error);
                showBottomToast("Error", 'Error fetching item data: ' + error, "bg-danger", 5000);
            });
    } else if ((itemStatus === "Inactive" && platform === "Viagogo") || (itemStatus === "Soldout" && platform !== "Viagogo")) {
        // deactivate listing if item is being marked not listed OR sold on another platform
        fetchItemData(itemId).
            then(function (itemData) {
                try {
                    if (itemData.listingId && itemData.listingId.length > 0) {
                        // attempt to deactivate listing on viagogo
                        $('#loadingPreloader .title').text('Deactivating your listing on Viagogo...');
                        editListing(null, itemData.listingId, "deactivate")
                            .then(function (response) {
                                $('#loadingPreloader').hide();
                                $('#loadingPreloader .title').text('Loading...');
                                $('#loadingPreloader .subtitle').text('');
                                updateItemAttributes(itemId, attributesMap);
                                showToast("Listing removed!", `Successfully deactivated listing on Viagogo`, false, true);
                            })
                            .catch(function (error) {
                                $('#loadingPreloader').hide();
                                $('#loadingPreloader .title').text('Loading...');
                                $("html, body").animate({ scrollTop: "0" }, 500);
                                if (error.includes('session expired')) {
                                    deleteCookie('viagogoSessionId');
                                    showToast("Error deactivating listing", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
                                } else if (error.includes(`${itemData.listingId} not found`)) {
                                    updateItemAttributes(itemId, attributesMap);
                                    showToast("Listing Not Found", `Listing ${itemData.listingId} was already deactivated or removed`, true);
                                } else {
                                    updateItemAttributes(itemId, attributesMap);
                                    showToast("Viagogo error", "Please deactivate manually from Viagogo", true);
                                }
                                console.error("Error deactivating listing: " + error);
                            });
                    } else {
                        updateItemAttributes(itemId, attributesMap);
                    }
                } catch (e) {
                    $('#loadingPreloader').hide();
                    $("html, body").animate({ scrollTop: "0" }, 500);
                    showToast("Listing data missing", `${e}`, true);
                }

            })
            .catch(function (error) {
                $('#loadingPreloader').hide();
                // Handle any errors that occurred during the data fetching process
                console.error('Error fetching item data:', error);
                showBottomToast("Error", 'Error fetching item data: ' + error, "bg-danger", 5000);
            });
    } else if (platform == "Viagogo") {
        // Update viagogo listing
        // fetch latest item data
        fetchItemData(itemId).
            then(function (itemData) {
                itemData.quantity = attributesMap["quantity"];
                itemData.quantityRemain = attributesMap["quantity"];
                itemData.row = attributesMap["row"];
                itemData.section = attributesMap["section"];
                itemData.seatFrom = attributesMap["seatFrom"];
                itemData.seatTo = attributesMap["seatTo"];
                itemData.ticketType = attributesMap["ticketType"];
                itemData.splitType = splitType;
                itemData.yourPricePerTicket = attributesMap['yourPricePerTicket'];
                try {
                    // check if item is missing any required field
                    validateListingData(itemData);
                    // listing was already created, first of all edit details
                    $('#loadingPreloader .title').text('Listing update in progress...');
                    $('#loadingPreloader .subtitle').text('Do not refresh this page');
                    editListingDetails(
                        itemData.listingId,
                        itemData.eventId,
                        itemData.ticketType,
                        itemData.quantityRemain,
                        splitType,
                        itemData.section,
                        itemData.row,
                        itemData.seatFrom,
                        itemData.seatTo,
                        itemData.individualTicketCost,
                        itemData.yourPricePerTicket,
                    )
                        .then(function (response) {
                            // Successfully updated listing
                            $('#loadingPreloader').hide();
                            $('#loadingPreloader .title').text('Loading...');
                            $('#loadingPreloader .subtitle').text('');
                            updateItemAttributes(itemId, attributesMap);
                            showToast("Listing saved!", `Successfully updated details on Viagogo`, false, true);
                        })
                        .catch(function (error) {
                            $('#loadingPreloader').hide();
                            $('#loadingPreloader .title').text('Loading...');
                            $('#loadingPreloader .subtitle').text('');
                            $("html, body").animate({ scrollTop: "0" }, 500);
                            if (error.includes('session expired')) {
                                deleteCookie('viagogoSessionId');
                                showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
                            } else {
                                showToast("Error updating listing", "Please make sure all listing data is valid", true);
                            }
                            console.error("Error updating listing: " + error);
                        });
                } catch (e) {
                    $('#loadingPreloader').hide();
                    $("html, body").animate({ scrollTop: "0" }, 500);
                    showToast("Listing data missing", `${e}`, true);
                }

            })
            .catch(function (error) {
                $('#loadingPreloader').hide();
                // Handle any errors that occurred during the data fetching process
                console.error('Error fetching item data:', error);
                showBottomToast("Error", 'Error fetching item data: ' + error, "bg-danger", 5000);
            });
    } else {
        updateItemAttributes(itemId, attributesMap);
    }

    // reset quick edit form
    hideInput();

    // Prevent the default form submission
    return false;
}

function updateItemAttributes(itemId, attributesMap) {
    $.ajax({
        type: "POST",
        url: '/?model=inventory&action=bulkUpdate',
        data: {
            ids: [itemId],
            attributes: attributesMap,
        },
        success: function (response) {
            $('#loadingPreloader').hide();

            // Handle the response from the server
            if (response.success === true) {
                $('#loadingPreloader').hide();
                $("html, body").animate({ scrollTop: "0" }, 500);
                showBottomToast("Item updated", "Successfully saved changes");
                if (response.updates && response.updates.hasOwnProperty(itemId)) {
                    const updatedAttributes = response.updates[itemId];
                    let quantity, itemStatus, quantityRemain, yourPricePerTicket, amount, currency, ticketCost = false;

                    // Iterate through the inner array of updated attributes
                    updatedAttributes.forEach(item => {
                        switch (item.path) {
                            case 'individualTicketCost':
                                currency = item.value.currency;
                                amount = item.value.amount;
                                ticketCost = amount;
                                $('.cost-per-ticket').text(formatAmountAndCurrencyAsSymbol(parseFloat(amount), currency));
                                break;

                            case 'quantity':
                                quantity = item.value;
                                $('.quantity').text(item.value);
                                break;

                            case 'quantityRemain':
                                quantityRemain = item.value;
                                $('.quantity-remain').text(item.value);
                                break;

                            case 'retailer':
                                $('.retailer').text(capitalizeFirstLetter(item.value));
                                break;

                            case 'ticketType':
                                $('.ticket-type').text(item.value);
                                break;

                            case 'orderNumber':
                                $('.order-number').text(item.value);
                                break;

                            case 'orderEmail':
                                $('.order-email').text(item.value);
                                break;

                            case 'purchaseDate':
                                var options = { year: 'numeric', month: 'long', day: 'numeric' };
                                var formattedDate = new Date(item.value).toLocaleDateString('en-US', options);
                                $('.purchase-date').text(formattedDate);
                                break;

                            case 'saleDate':
                                var options = { year: 'numeric', month: 'long', day: 'numeric' };
                                var formattedDate = new Date(item.value).toLocaleDateString('en-US', options);
                                $('.sale-date').text(formattedDate);
                                break;

                            case 'platform':
                                $('span.platform').text(item.value);
                                break;

                            case 'section':
                                $('span.section').text(item.value);
                                break;

                            case 'seatFrom':
                                $('span.seat-from').text(item.value);
                                break;

                            case 'seatTo':
                                $('span.seat-to').text(item.value);
                                break;

                            case 'yourPricePerTicket':
                                currency = item.value.currency;
                                amount = item.value.amount;
                                yourPricePerTicket = amount;
                                $('.your-price-per-ticket').text(formatAmountAndCurrencyAsSymbol(parseFloat(amount), currency));
                                break;

                            case 'totalPayout':
                                currency = item.value.currency;
                                amount = item.value.amount;
                                $('.total-payout').text(formatAmountAndCurrencyAsSymbol(parseFloat(amount), currency));
                                break;

                            case 'status':
                                itemStatus = item.value;
                                $('#markAsSoldout').removeClass('hidden');
                                $('#markAsInactive').removeClass('hidden');
                                $('#markAsActive').removeClass('hidden');
                                switch (item.value) {
                                    case 'Soldout':
                                        $('.item-status').addClass('text-success');
                                        $('.item-status').removeClass('text-warning text-primary');
                                        $('.item-status').html("<b>Sold</b>");
                                        $('#markAsSoldout').addClass('hidden');
                                        $('.listed-timeline').removeClass('hidden');
                                        $('.sold-timeline').removeClass('hidden');
                                        $('.listing-details').addClass('hidden');
                                        $('.total-payout-title').text('Generated');
                                        break;

                                    case 'Inactive':
                                        $('.item-status').addClass('text-warning');
                                        $('.item-status').removeClass('text-success text-primary');
                                        $('.item-status').html("<b>Not Listed</b>");
                                        $('#markAsInactive').addClass('hidden');
                                        $('.sold-timeline').addClass('hidden');
                                        $('.listed-timeline').addClass('hidden');
                                        $('.listing-details').addClass('hidden');
                                        $('.total-payout-title').text('Expected');
                                        break;

                                    case 'Active':
                                        $('.item-status').addClass('text-primary');
                                        $('.item-status').removeClass('text-warning text-success');
                                        $('.item-status').html("<b>Listed</b>");
                                        $('#markAsActive').addClass('hidden');
                                        $('.sold-timeline').addClass('hidden');
                                        $('.listed-timeline').removeClass('hidden');
                                        $('.listing-details').removeClass('hidden');
                                        $('.total-payout-title').text('Expected');
                                        break;

                                    default:
                                        break;
                                }
                                break;

                            default:
                                break;
                        }
                    });

                    if (quantity && ticketCost && currency) {
                        $('.total-cost').text(formatAmountAndCurrencyAsSymbol(parseFloat(ticketCost) * parseInt(quantity), currency));
                    }
                }
            } else {
                $('#loadingPreloader').hide();
                showToast("Error", "Failed updating item", true);
            }
        },
        error: function (response) {
            $('#loadingPreloader').hide();
            showToast("Error", "An error occurred while processing the request, please try again", true);
        }
    });
}

function fetchSplitTypes(quantity) {

    function makeApiRequest(jwtToken, resolve, reject) {
        $.ajax({
            url: 'https://api.mindsolutions.app/', // Replace with the URL of your PHP script
            type: 'POST',
            headers: {
                'Authorization': `Bearer ${jwtToken}`, // Use the retrieved JWT token
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                action: "get_split_types",
                quantity: quantity,
            }),
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response);
                } else {
                    reject('Error: ' + response.message); // Reject the Promise with an error message
                }
            },
            error: function (xhr, status, error) {
                reject('AJAX error: ' + error); // Reject the Promise with an AJAX error message
            },
        });
    }

    return new Promise(function (resolve, reject) {
        // Check if the token is already available in localStorage
        var token = getWithExpiry('jwtToken');

        if (token) {
            makeApiRequest(token, resolve, reject); // Pass resolve and reject as arguments
        } else {
            // Token is not available, request it
            requestJwtToken()
                .then((token) => {
                    var parsed = JSON.parse(token);
                    setWithExpiry('jwtToken', parsed.token, parsed.expiresIn * 1000);
                    makeApiRequest(parsed.token, resolve, reject); // Pass resolve and reject as arguments
                })
                .catch((error) => {
                    console.error('Error receiving token:', error);
                    reject('Error receiving token:', error);
                });
        }
    });
}