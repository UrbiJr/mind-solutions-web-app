document.addEventListener("DOMContentLoaded", () => {
    const $enterLicenseKeyBtn = $('#activateMembership');
    const $activateMembershipWrapper = $('#activateMembershipWrapper');

    if ($enterLicenseKeyBtn) {
        $enterLicenseKeyBtn.on('click', function () {
            if (!$activateMembershipWrapper.is(":visible")) {
                $enterLicenseKeyBtn.hide();
                $activateMembershipWrapper.slideToggle(700);
            }
        });
    }

    if ($('#inventoryListContainer').length) {
        $('#inventoryListSpinner').removeClass('hidden');
        getInventoryList(0, 15, 'quantityRemain', 'desc', (response) => {
            $('#inventoryListSpinner').addClass('hidden');
            $('#inventoryListContainer').removeClass('hidden');
            makeInventoryList(response.rows);
        },
            (xhr, status, error) => {
                $('#inventoryListSpinner').addClass('hidden');
                $('#inventoryListContainer').removeClass('hidden');
                console.error(error);
            });
    }

    if ($('#inventorySortBySelector').length) {
        document.getElementById('inventorySortBySelector').addEventListener("click", function (event) {
            // Check if the clicked element is an <li> within the <ul>
            if (event.target.tagName === "LI") {
                $('#inventoryListContainer').addClass('hidden');
                $('#inventoryListSpinner').removeClass('hidden');
                var selectedSortBy = event.target.textContent;
                document.getElementById("inventoryListContainer").innerHTML = '';
                switch (selectedSortBy) {
                    case 'Quantity':
                        $('#inventorySortByDropdownMenu').text(`Sort by ${selectedSortBy.toLowerCase()}`)
                        getInventoryList(0, 15, 'quantityRemain', 'desc', (response) => {
                            $('#inventoryListSpinner').addClass('hidden');
                            $('#inventoryListContainer').removeClass('hidden');
                            makeInventoryList(response.rows);
                        },
                            (xhr, status, error) => {
                                $('#inventoryListSpinner').addClass('hidden');
                                $('#inventoryListContainer').removeClass('hidden');
                                console.error(error);
                            });
                        break;

                    case 'Event date':
                        $('#inventorySortByDropdownMenu').text(`Sort by ${selectedSortBy.toLowerCase()}`)
                        getInventoryList(0, 15, 'date', 'desc', (response) => {
                            $('#inventoryListSpinner').addClass('hidden');
                            $('#inventoryListContainer').removeClass('hidden');
                            makeInventoryList(response.rows);
                        },
                            (xhr, status, error) => {
                                $('#inventoryListSpinner').addClass('hidden');
                                $('#inventoryListContainer').removeClass('hidden');
                                console.error(error);
                            });
                        break;

                    case 'ROI':
                        $('#inventorySortByDropdownMenu').text(`Sort by ${selectedSortBy}`)
                        getInventoryList(0, 15, 'roi', 'desc', (response) => {
                            $('#inventoryListSpinner').addClass('hidden');
                            $('#inventoryListContainer').removeClass('hidden');
                            makeInventoryList(response.rows);
                        },
                            (xhr, status, error) => {
                                $('#inventoryListSpinner').addClass('hidden');
                                $('#inventoryListContainer').removeClass('hidden');
                                console.error(error);
                            });
                        break;

                    case 'Listing price':
                        $('#inventorySortByDropdownMenu').text(`Sort by ${selectedSortBy.toLowerCase()}`)
                        getInventoryList(0, 15, 'yourPrice', 'desc', (response) => {
                            $('#inventoryListSpinner').addClass('hidden');
                            $('#inventoryListContainer').removeClass('hidden');
                            makeInventoryList(response.rows);
                        },
                            (xhr, status, error) => {
                                $('#inventoryListSpinner').addClass('hidden');
                                $('#inventoryListContainer').removeClass('hidden');
                                console.error(error);
                            });
                        break;

                    default:
                        break;
                }
            }
        });
    }
});

function makeInventoryList(rows) {
    const inventoryListContainer = document.getElementById("inventoryListContainer");

    // Iterate through the rows and create elements for each item
    rows.forEach(row => {
        // Create a new card element
        const card = document.createElement("div");
        card.className = "d-flex justify-content-between align-items-center flex-wrap mb-2";

        // Create the content of the card
        card.innerHTML = `
    <div>
        <h5>${row.name}</h5>
        <p>${row.date}</p>
    </div>
    <div class="row">
        <span>Cost: <span class="text-danger">${row.totalCost}</span></span>
        <p>Projected Profit: <span class="text-success">${row.projectedProfit}</span></p>
    </div>
    <div>
    <a href="${row.link}" class="btn btn-outline-info btn-sm">
        <span class="btn-inner">
            <svg class="icon-20" xmlns="http://www.w3.org/2000/svg" width="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
        </span>
    </a>
    </div>
`;
        // Append the card to the inventory list container
        inventoryListContainer.appendChild(card);
    });
}

function getInventoryList(offset, limit, sort, order, successCallback, errorCallback) {
    $.ajax({
        url: '/api/user/inventory', // Replace with the URL of your PHP script
        type: 'GET',
        data: {
            format: 'list',
            offset: offset,
            limit: limit,
            sort: sort,
            order: order,
        },
        success: function (response) {
            successCallback(response);
        },
        error: function (xhr, status, error) {
            errorCallback(xhr, status, error);
        }
    });
}