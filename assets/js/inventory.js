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
        // CODE FOR EDIT LISTING MODAL (INVENTORY ITEM VIEW)

        $('#openEditListingModal').on('click', function () {
            $('#loadingPreloader').show();

            const eventId = $(this).attr('data-event-id');
            const quantity = $(this).attr('data-quantity');
            const select = $('#editListingSplitType');
            const sectionSelect = document.getElementById("editListingSectionSelect");
            const callback = () => {
                // Manually dispatch change event in order to disable/enable quantity/seats/row fields accordingly to current item values
                var event = new Event('change');
                sectionSelect.dispatchEvent(event);
                // populate split types
                fetchSplitTypes(quantity, window.viagogoUser.wsu2Cookie)
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
            };
            // filter selection list by removing default option (empty value)
            const sectionSelectOptions = Array.from(sectionSelect)
                .filter(option => option.value != "");

            if (sectionSelectOptions.length <= 0) {
                // Sections select is empty, populate it
                getEventOverviewData(eventId, function (result) {
                    if (result.eventData !== null && result.eventData.id && Object.keys(result.sections.sections) > 0) {
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
                        sectionSelect.value = selectedSection;
                        callback();
                    } else {
                        $('#loadingPreloader').hide();
                        sectionSelect.style.display = 'none';
                        $('#editListingModal .customSection').removeClass('hidden');
                        callback();
                    }
                }, function (error) {
                    $('#loadingPreloader').hide();
                    // Handle any errors that occurred during the data fetching process
                    console.error('Error fetching event data:', error);
                    showToast("Error", 'Error fetching event data: ' + error, true);
                }, true);
            } else {
                callback();
            }
        })
    }

    if ($('#openEditItemModal').length) {
        // CODE FOR EDIT ITEM MODAL (INVENTORY ITEM VIEW)

        $('#openEditItemModal').on('click', function () {
            $('#loadingPreloader').show();
            const eventId = $(this).attr('data-event-id');
            const sectionSelect = document.getElementById("editItemSectionSelect");
            const callback = () => {
                // Manually dispatch change event in order to disable/enable quantity/seats/row fields accordingly to current item values
                var event = new Event('change');
                sectionSelect.dispatchEvent(event);
                $('#loadingPreloader').hide();
                $('#editItemModal').modal("show");
            }

            // filter selection list by removing default option (empty value)
            const sectionSelectOptions = Array.from(sectionSelect)
                .filter(option => option.value != "");

            if (sectionSelectOptions.length <= 0) {
                // Sections select is empty, populate it
                getEventOverviewData(eventId, function (result) {
                    if (result.eventData !== null && result.eventData.id && Object.keys(result.sections.sections) > 0) {
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
                        sectionSelect.value = selectedSection;
                        callback();
                    } else {
                        $('#loadingPreloader').hide();
                        sectionSelect.style.display = 'none';
                        $('#editItemModal .customSection').removeClass('hidden');
                        callback();
                    }
                }, function (error) {
                    $('#loadingPreloader').hide();
                    // Handle any errors that occurred during the data fetching process
                    console.error('Error fetching event data:', error);
                    showToast("Error", 'Error fetching event data: ' + error, true);
                }, true);
            } else {
                callback();
            }
        })
    }

    if ($("#markListedForm").length) {
        // CODE FOR MARKASLISTEDTYPE FORM (INVENTORY ITEM VIEW)

        const select = $("#markListedSplitType"); // Get the existing select element by its id
        const selectContainer = $("#splitTypeContainer");
        const restrictionsContainer = $('#markListedForm .restrictionsContainer');
        const ticketDetailsContainer = $('#markListedForm .ticketDetailsContainer');
        const markListedBtn = $("#markListedBtn");

        $('#markListedForm select[name="platform"]').on('change', function () {
            if (this.value == "Viagogo") {
                // user selected viagogo platform, show additional form inputs
                const quantity = $('input[name="quantity"]').val();
                fetchSplitTypes(quantity, window.viagogoUser.wsu2Cookie)
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
        // CODE FOR ADD TO INVENTORY FORM / UPDATE ITEM FORM (INVENTORYOVERVIEW VIEW)

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
                    type: "PUT",
                    url: '/api/user/inventory/' + itemId,
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

                // manually add fetched sections, we will persist them on db to avoid fetching everytime 
                const sectionList = [];
                $('.sectionSelect').children().each(function (index, el) {
                    if (el.value !== "") {
                        sectionList.push(el.value);
                    }
                });
                formData.push({ name: 'sectionList', value: sectionList });

                // add new item
                // Perform Ajax POST request
                $.ajax({
                    type: "POST",
                    url: '/api/user/inventory/add',
                    data: formData,
                    success: function (response) {
                        // Hide loading spinner
                        $("#addingToInventorySpinner").hide();

                        // Handle the response from the server
                        if (response.success === true) {
                            // Refresh the table content
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
                var sectionSelect = document.getElementById("sectionSelect");
                lookupEvent(eventName, eventDate, country, ticketGenre).
                    then(function (result) {
                        if (result.id !== null && result.id !== "") {
                            sectionSelect.style.display = "block";
                            document.getElementById("customSection").style.display = "none";

                            document.getElementById("city").disabled = true;
                            document.getElementById("location").disabled = true;
                            document.getElementById("eventId").value = result.id;
                            document.getElementById("city").value = result.city;
                            document.getElementById("location").value = result.location;
                            document.getElementById("categoryId").value = result.categoryId;

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
                            sectionSelect.style.display = "none";
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
        // CODE FOR INVENTORY OVERVIEW VIEW
        $('#inventoryBulkActions .edit').on('click', function () {
            const selections = $('table[data-multiple-select-row="true"]').bootstrapTable('getSelections');
            if (selections.length <= 0) {
                toastWithTimeout("No Items Selected", "You must select one or more rows");
            } else {
                $bulkUpdateModal.modal("show");
            }
        });

        $('#inventoryBulkActions .delete').on('click', function () {
            const selections = $('table[data-multiple-select-row="true"]').bootstrapTable('getSelections');
            if (selections.length <= 0) {
                toastWithTimeout("No Items Selected", "You must select one or more rows");
            } else {
                $('#confirmBulkDeleteModal .modal-body').text(`Are you sure you want to delete ${selections.length} items?`);
                $('#confirmBulkDeleteModal').modal('show');
            }
        });

        $('#bulkDeleteBtn').on('click', function () {
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

            $.ajax({
                type: "DELETE",
                url: '/api/user/inventory',
                data: {
                    ids: ids,
                },
                success: function (response) {
                    // Handle the response from the server
                    if (response.success === true) {
                        showToast("Success", "Successfully deleted " + response.count + " items", false, true);
                        // Refresh the table content
                        $("#inventoryTable").bootstrapTable('refresh');
                    } else {
                        showToast("Error", "Delete failed, please try again", true);
                    }
                },
                error: function (response) {
                    showToast("Error", "An error occurred while processing the request, please try again", true);
                }
            });
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
            const currency = document.body.getAttribute('currency');
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
                type: "PUT",
                url: '/api/user/inventory',
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
        // CODE FOR INVENTORYOVERVIEW VIEW

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
        // CODE FOR ANY VIEW

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


/********************************************* START HELPER & AJAX FUNCTIONS  *********************************************/

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
            type: "GET",
            url: `/api/user/inventory/${itemId}`,
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response.item);
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

    function makeApiRequest(resolve, reject) {
        $.ajax({
            url: `${API_BASE_URL}viagogo/events/lookup`,
            type: 'GET',
            data: {
                eventName: eventName,
                eventDate: eventDate,
                country: country,
                ticketGenre: ticketGenre,
            },
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response.event);
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

        makeApiRequest(resolve, reject); // Pass resolve and reject as arguments

    });
}

function getUserListings(viagogoSessionId, callback) {

    makeApiRequest(token);

    function makeApiRequest() {
        $.ajax({
            url: `${API_BASE_URL}service-integration/viagogo/listings`,
            type: 'GET',
            data: {
                sessionCookie: viagogoSessionId,
            },
            success: function (response) {
                // Handle the response here
                if (response) {
                    callback(response.listings);
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

    makeApiRequest();

    function makeApiRequest() {
        $.ajax({
            url: `${API_BASE_URL}service-integration/viagogo/sales`,
            type: 'GET',
            data: {
                sessionCookie: viagogoSessionId,
            },
            success: function (response) {
                // Handle the response here
                if (response) {
                    callback(response.sales);
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
    const viagogoSessionId = window.viagogoUser.wsu2Cookie;

    if (!viagogoSessionId || viagogoSessionId === "") {
        enableSyncButton();
        showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
    } else {
        $('#preloader').show();

        getUserListings(viagogoSessionId, (response) => {
            if (response.success) {
                if (response.cookie) {
                    // Store Viagogo user on the backend and update status to connected
                    setViagogoUser(window.viagogoUser.username, window.viagogoUser.password, response.cookie, window.viagogoUser.rvtCookie).then(function (response) {
                    }).catch(function (error) {
                    });
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
                            setViagogoUser(window.viagogoUser.username, window.viagogoUser.password, response.cookie, window.viagogoUser.rvtCookie).then(function (response) {
                            }).catch(function (error) {
                            });
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
                            url: "/api/viagogo/sync",
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
                        if (response.message === "session expired") {
                            deleteViagogoUser()
                                .then(() => { })
                                .catch(() => { });
                            showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
                        } else {
                            showToast("Error", response.message, true);
                        }
                    }
                });
            } else {
                $('#preloader').hide();
                enableSyncButton();
                if (response.message === "session expired") {
                    deleteViagogoUser()
                        .then(() => { })
                        .catch(() => { });
                    showToast("Error", '<p>Viagogo session expired, you may want to login again.</p><br><a class="btn btn-soft-light" href="/?model=viagogo&action=login">Login</a>', true);
                } else {
                    showToast("Error", response.message, true);
                }
            }
        });
    }

    return false;
}

function deleteInventoryItem(itemId) {
    toastWithTimeout("Deleting", "Deleting item from inventory...");

    // Send an AJAX request to delete the item
    $.ajax({
        type: 'DELETE',
        url: `/api/user/inventory/${itemId}`,
        success: function (response) {
            if (response.success === true) {
                $("#inventoryTable").bootstrapTable('refresh');
            } else {
                toastWithTimeout("Error", 'Error deleting item: ' + response.message, "bg-danger", 5000);
            }
        },
        error: function (error) {
            toastWithTimeout("Error", 'Error deleting item: ' + error, "bg-danger", 5000);
        }
    });
}

function duplicateInventoryItem(itemId) {
    // Disable submit button to prevent multiple submissions
    $("#addToInventory").prop("disabled", true);
    toastWithTimeout("Copying", "Copying item to inventory...");

    // Perform Ajax POST request
    $.ajax({
        type: "POST",
        url: `/api/user/inventory/copy/${itemId}`,
        success: function (response) {
            // Handle the response from the server
            if (response.success === true) {
                // Refresh the table content
                $("#inventoryTable").bootstrapTable('refresh');
            } else {
                toastWithTimeout("Error", "Copy failed: " + response.message, "bg-danger", 5000);
            }
        },
        error: function (response) {
            toastWithTimeout("Error", "Copy failed due to request error", "bg-danger", 5000);
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

        if (fieldName === "itemId" || fieldName === "id") {
            itemId = fieldValue;
        }

        if (fieldName === "status") {
            itemStatus = fieldValue;
        }

        if (fieldName === "customPlatform") {
            attributesMap['platform'] = fieldValue;
            platform = fieldValue;
        }

        if (fieldName === "customSection" && fieldValue != "") {
            attributesMap['section'] = fieldValue;
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
                currency: document.body.getAttribute('currency')
            };
            attributesMap['totalPayout'] = {
                amount: 0,
                currency: document.body.getAttribute('currency')
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
                itemData.individualTicketCost = {
                    "currency": itemData.individualTicketCostCurrency,
                    "amount": itemData.individualTicketCostAmount,
                };
                try {
                    // check if item is missing any required field
                    validateListingData(itemData);
                    const checkedRestrictions = getCheckedRestrictions(form);
                    const checkedDetails = getCheckedTicketDetails(form);
                    if (itemData.restrictions === undefined) {
                        itemData.restrictions = [];
                    }
                    if (itemData.ticketDetails === undefined) {
                        itemData.ticketDetails = [];
                    }
                    sameRestrictions = haveSameElements(checkedRestrictions, itemData.restrictions);
                    sameTicketDetails = haveSameElements(checkedDetails, itemData.ticketDetails);

                    if (sameRestrictions && sameTicketDetails && itemData.listingId && itemData.listingId.length > 0) {
                        // listing was already created, first of all edit details
                        $('#loadingPreloader .title').text('Listing activation in progress (1/2)');
                        $('#loadingPreloader .subtitle').text('Editing listing details...');
                        const yourPricePerTicket = {
                            "currency": itemData.yourPricePerTicketCurrency,
                            "amount": itemData.yourPricePerTicketAmount,
                        };
                        editListingDetails(
                            itemData.listingId,
                            itemData.viagogoEventId,
                            itemData.ticketType,
                            itemData.quantityRemain,
                            splitType,
                            itemData.section,
                            itemData.row,
                            itemData.seatFrom,
                            itemData.seatTo,
                            itemData.individualTicketCost,
                            yourPricePerTicket,
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
                                            deleteViagogoUser()
                                                .then(() => { })
                                                .catch(() => { });
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
                                    deleteViagogoUser()
                                        .then(() => { })
                                        .catch(() => { });
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
                            itemData.viagogoEventId,
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
                                    deleteViagogoUser()
                                        .then(() => { })
                                        .catch(() => { });
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
                toastWithTimeout("Error", 'Error fetching item data: ' + error, "bg-danger", 5000);
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
                                    deleteViagogoUser()
                                        .then(() => { })
                                        .catch(() => { });
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
                toastWithTimeout("Error", 'Error fetching item data: ' + error, "bg-danger", 5000);
            });
    } else if (platform == "Viagogo") {
        // Update viagogo listing
        // fetch latest item data
        fetchItemData(itemId).
            then(function (itemData) {
                itemData.individualTicketCost = {
                    "currency": itemData.individualTicketCostCurrency,
                    "amount": itemData.individualTicketCostAmount,
                };
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
                        itemData.viagogoEventId,
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
                                deleteViagogoUser()
                                    .then(() => { })
                                    .catch(() => { });
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
                toastWithTimeout("Error", 'Error fetching item data: ' + error, "bg-danger", 5000);
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
        type: "PUT",
        url: '/api/user/inventory',
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
                toastWithTimeout("Item updated", "Successfully saved changes");
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

function fetchSplitTypes(quantity, wsu2Cookie) {

    function makeApiRequest(resolve, reject) {
        $.ajax({
            url: `${API_BASE_URL}service-integration/viagogo/split-types`,
            type: 'GET',
            data: {
                quantity: quantity,
                sessionCookie: wsu2Cookie
            },
            success: function (response) {
                // Handle the response from the server
                if (response.success === true) {
                    resolve(response.result);
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

        makeApiRequest(resolve, reject); // Pass resolve and reject as arguments

    });
}

/********************************************* END HELPER & AJAX FUNCTIONS  *********************************************/