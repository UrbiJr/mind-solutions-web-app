document.addEventListener("DOMContentLoaded", () => {

    const input = document.getElementById('user_about_about');
    const label = document.querySelector('.form-label[for="user_about_about"]');

    if (input) {
        input.addEventListener('input', function () {
            const charCount = this.value.length;
            label.textContent = `${charCount}/160`;
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