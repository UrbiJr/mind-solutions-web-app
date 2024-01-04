document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("confirmDeleteButton").addEventListener("click", function () {
        const backupId = this.getAttribute("data-item-id");
        deleteBackup(backupId);
    });

    $('#backupsTable').on('click', 'button[name="delete-backup"]', function () {
        var backupId = $(this).attr('data-item-id');
        document.getElementById("confirmDeleteButton").setAttribute("data-item-id", backupId);
        $('#confirmDeleteModal').modal('show');
    });

    $('#confirmRestoreInput').on('input', function () {
        const inputText = $(this).val();
        if (inputText.toLowerCase() === 'restore') {
            $('#confirmRestoreBtn').prop('disabled', false);
        } else {
            $('#confirmRestoreBtn').prop('disabled', true);
        }
    });

    $('#confirmRestoreBtn').click(function () {
        // Handle the confirmed action here
        $('#confirmRestoreModal').modal('hide');
        $('#loadingPreloader').show();
        var formData = new FormData($('#restoreForm')[0]); // Construct FormData object (for file uploads)
        $.ajax({
            type: "POST",
            url: $('#restoreForm').attr('action'),
            data: formData,
            contentType: false, // Set content type to false for file uploads
            processData: false, // Disable data processing
            success: function (response) {
                // Hide loading spinner
                $('#loadingPreloader').hide();

                // Handle the response from the server
                if (response.success === true) {
                    showToast("Success", "Inventory successfully restored", false, true);
                } else if (response.message) {
                    showToast("Error", response.message, true);
                } else {
                    showToast("Error", "Inventory import error", true);
                }
            },
            error: function (response) {
                // Hide loading spinner
                $('#loadingPreloader').hide();
                showToast("Error", "An error occurred while processing the request, please try again", true);
            }
        });
    });
});

function deleteBackup(backupId) {
    toastWithTimeout("Deleting", "Deleting backup...");

    // Send an AJAX request to delete the item
    $.ajax({
        type: 'DELETE',
        url: `/api/user/backups/${backupId}`,
        success: function (response) {
            if (response.success === true) {
                $('#backupsTable').bootstrapTable('refresh');
            } else {
                toastWithTimeout("Error", 'Error deleting backup: ' + response.message, "bg-danger", 5000);
            }
        },
        error: function (error) {
            toastWithTimeout("Error", 'Error deleting backup: ' + error, "bg-danger", 5000);
        }
    });
}
