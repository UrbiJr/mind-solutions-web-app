document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById('about');
    const label = document.querySelector('.form-label[for="about"]');

    if (input) {
        input.addEventListener('input', function () {
            const charCount = this.value.length;
            label.textContent = `${charCount}/160`;
        });
    }

    if ($("#confirmRestoreBtn").length) {
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
    }

    if ($("#createListingBtn").length) {
        $('#createListingBtn').click(function () {
            createListing(
                "151420120",
                0,
                3,
                2,
                "B4",
                "F",
                1,
                3,
                {
                    currency: "EUR",
                    amount: "72,49"
                },
                {
                    currency: "EUR",
                    amount: "400,00"
                }
            )
                .then((response) => {
                    console.log(response.message);
                })
                .catch((errorMsg) => {
                    console.error(errorMsg);
                });
        });
    }

});