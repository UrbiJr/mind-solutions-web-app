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

        if ($('#releaseBulkActions').length) {
            $('#releaseBulkActions .delete').on('click', function () {
                const selections = $('table[data-multiple-select-row="true"]').bootstrapTable('getSelections');
                if (selections.length <= 0) {
                    toastWithTimeout("No Releases Selected", "You must select one or more rows");
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
    toastWithTimeout("Deleting", "Deleting release...", "bg-secondary");

    // Send an AJAX request to delete the item
    $.ajax({
        type: 'DELETE',
        url: `/api/admin/releases/${releaseId}`,
        success: function (response) {
            if (response.success === true) {
                $('#releasesTable').bootstrapTable('refresh');
            } else {
                toastWithTimeout("Error", 'Error deleting release: ' + response.message, "bg-danger", 5000);
            }
        },
        error: function (error) {
            toastWithTimeout("Error", 'Error deleting release: ' + error, "bg-danger", 5000);
        }
    });
}

function duplicateRelease(releaseId) {
    // Disable submit button to prevent multiple submissions
    $("#addRelease").prop("disabled", true);
    toastWithTimeout("Copying", "Copying release...", "bg-secondary");

    // Perform Ajax POST request
    $.ajax({
        type: "POST",
        url: `/api/admin/releases/copy/${releaseId}`,
        success: function (response) {
            // Handle the response from the server
            if (response.success === true) {
                // Refresh the table content
                $('#releasesTable').bootstrapTable('refresh');
            } else {
                toastWithTimeout("Error", "Copy failed: " + response.message, "bg-danger", 5000);
            }
        },
        error: function (response) {
            toastWithTimeout("Error", "Copy failed due to request error", "bg-danger", 5000);
        }
    });
}