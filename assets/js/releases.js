document.addEventListener("DOMContentLoaded", () => {
    if ($("#releasesTable").length) {
        document.getElementById("confirmDeleteButton").addEventListener("click", function () {
            const releaseId = this.getAttribute("data-item-id");
            deleteRelease(releaseId);
        });

        $('#releasesTable').on('click', 'button[name="delete-release"]', function () {
            var releaseId = $(this).attr('data-item-id');
            document.getElementById("confirmDeleteButton").setAttribute("data-item-id", releaseId);
            $('#confirmDeleteModal').modal('show');
        });

        $('#releasesTable').on('click', 'button[name="copy-release"]', function () {
            var releaseId = $(this).attr('data-item-id');
            duplicateRelease(releaseId);
        });

        $('#releasesTable').on('click', 'button[name="edit-release"]', function () {
            // Get the item ID from the clicked button's data attribute
            var releaseId = $(this).attr('data-item-id');
            editRelease(releaseId);
        });

        $('#editReleaseBtn').on('click', function () {
            const releaseId = $('#editReleaseBtn').attr("data-item-id");
            $("#editReleaseModal .loading-container").show();
            $("#editReleaseModal .status").text("Updating Release...");
            // Perform Ajax POST request
            $.ajax({
                type: "POST",
                url: '/?model=releases&action=update&id=' + releaseId,
                data: $('#editReleaseForm').serialize(),
                success: function (response) {
                    // Hide loading spinner
                    $("#editReleaseModal .spinner").hide();

                    // Handle the response from the server
                    if (response.success === true) {
                        $("#editReleaseModal .status").text("Successfully updated item.").attr('class', 'text-center purple-text text-success');
                        // Refresh the table content
                        $('#releasesTable').bootstrapTable('refresh');
                    } else {
                        $("#editReleaseBtn").prop("disabled", false);
                        $("#editReleaseModal .status").text("Error: " + response.message).attr('class', 'text-center purple-text text-danger');
                    }
                },
                error: function (response) {
                    // Hide loading spinner
                    $("#editReleaseBtn").prop("disabled", false);
                    $("#editReleaseModal .spinner").hide();
                    $("#editReleaseModal .status").text("An error occurred while processing the request.").attr('class', 'text-center purple-text text-danger');
                }
            });
        });

        $('#editReleaseModal').on("hidden.bs.modal", function () {
            $("#editReleaseForm")[0].reset();
            $("#editReleaseModal .loading-container").hide();
        });

        if ($('#releaseBulkActions').length) {
            $('#releaseBulkActions .delete').on('click', function () {
                const selections = $('table[data-multiple-select-row="true"]').bootstrapTable('getSelections');
                if (selections.length <= 0) {
                    showBottomToast("No Releases Selected", "You must select one or more rows");
                } else {
                    selections.forEach(selection => {
                        var $htmlEventData = $(selection.releaseData);
                        // Get the value of the data-item-id attribute using the data method
                        var releaseId = $htmlEventData.data('item-id');
                        deleteRelease(releaseId);
                    });
                }
            });
        }
    }
});

function deleteRelease(releaseId) {
    showBottomToast("Deleting", "Deleting release...", "bg-secondary");

    // Send an AJAX request to delete the item
    $.ajax({
        type: 'POST',
        url: '/?model=releases&action=delete',
        data: {
            id: releaseId
        },
        success: function (response) {
            if (response.success === true) {
                $('#releasesTable').bootstrapTable('refresh');
            } else {
                showBottomToast("Error", 'Error deleting release: ' + response.message, "bg-danger", 5000);
            }
        },
        error: function (error) {
            showBottomToast("Error", 'Error deleting release: ' + error, "bg-danger", 5000);
        }
    });
}

function duplicateRelease(releaseId) {
    // Disable submit button to prevent multiple submissions
    $("#addRelease").prop("disabled", true);
    showBottomToast("Copying", "Copying release...", "bg-secondary");

    // Perform Ajax POST request
    $.ajax({
        type: "POST",
        url: '/?model=releases&action=duplicate',
        data: {
            id: releaseId
        },
        success: function (response) {
            // Handle the response from the server
            if (response.success === true) {
                // Refresh the table content
                $('#releasesTable').bootstrapTable('refresh');
            } else {
                showBottomToast("Error", "Copy failed: " + response.message, "bg-danger", 5000);
            }
        },
        error: function (response) {
            showBottomToast("Error", "Copy failed due to request error", "bg-danger", 5000);
        }
    });
}

function editRelease(releaseId) {
    // Use releaseId to fetch release (replace this with your data fetching logic)
    document.getElementById("loadingPreloader").style.display = "";
    fetchReleaseData(releaseId).
        then(function (releaseData) {
            const editReleaseBtn = document.getElementById('editReleaseBtn');
            document.getElementById("loadingPreloader").style.display = "none";
            document.querySelector('#editReleaseModal input[name="description"]').setAttribute('value', releaseData.description);
            document.querySelector('#editReleaseModal input[name="city"]').setAttribute('value', releaseData.city);
            document.querySelector('#editReleaseModal input[name="location"]').setAttribute('value', releaseData.location);
            document.querySelector('#editReleaseModal input[name="eventDate"]').setAttribute('value', releaseData.eventDate);
            document.querySelector('#editReleaseModal input[name="releaseDate"').setAttribute('value', releaseData.releaseDate);
            document.querySelector('#editReleaseModal input[name="retailer"').setAttribute('value', releaseData.retailer);
            document.querySelector('#editReleaseModal input[name="earlyLink"]').setAttribute('value', releaseData.earlyLink);
            document.querySelector('#editReleaseModal textarea[name="comments"]').value = releaseData.comments;
            var countrySelect = document.querySelector('#editReleaseModal select[name="country"]');
            var countryOptions = countrySelect.options;
            for (var i = 0; i < countryOptions.length; i++) {
                if (countryOptions[i].value === releaseData.country) {
                    countryOptions[i].selected = true;
                    break; // Exit the loop once the correct option is selected
                }
            }

            // Show the modal
            editReleaseBtn.setAttribute("data-item-id", releaseId);
            $('#editReleaseModal').modal('show');
        })
        .catch(function (error) {
            document.getElementById("loadingPreloader").style.display = "none";
            // Handle any errors that occurred during the data fetching process
            console.error('Error fetching release:', error);
            showBottomToast("Error", 'Error fetching release: ' + error, "bg-danger", 5000);
        });
}


function fetchReleaseData(itemId) {
    return new Promise(function (resolve, reject) {
        // Perform Ajax POST request
        $.ajax({
            type: "POST",
            url: '/?model=releases&action=getRelease',
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