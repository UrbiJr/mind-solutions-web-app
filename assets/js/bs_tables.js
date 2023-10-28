// Define a custom sorter function for columns with currency values
function totalCurrencySorter(a, b, order, column) {
    // Extract numeric values from the currency strings
    const aValue = parseFloat(a.replace(/[^0-9.-]+/g, ''));
    const bValue = parseFloat(b.replace(/[^0-9.-]+/g, ''));

    if (order === 'asc') {
        return aValue - bValue;
    } else {
        return bValue - aValue;
    }
}

// Define a custom sorter function for date values
function dateSorter(a, b, order, column) {
    const dateA = new Date(a);
    const dateB = new Date(b);

    if (order === 'asc') {
        return dateA - dateB;
    } else {
        return dateB - dateA;
    }
}

function loadingTemplate(message) {
    return '<span class="spinner" style="width: 35px; height: 35px;"></span>'
}

function inventoryDetailFormatter(index, row) {
    var html = []
    html.push('<div class="row">');
    $.each(row, function (key, value) {
        switch (key) {
            case 'eventData':
                var purchaseDate = $(value).attr('data-purchase-date');
                var retailer = $(value).attr('data-retailer');
                var section = $(value).attr('data-section');
                var row = $(value).attr('data-row');
                var seatFrom = $(value).attr('data-seat-from');
                var seatTo = $(value).attr('data-seat-to');
                var quantity = $(value).attr('data-quantity');
                var individualCost = $(value).attr('data-individual-ticket-cost');
                var yourPrice = $(value).attr('data-your-price');
                var status = $(value).attr('data-status');
                var itemId = $(value).attr('data-item-id');

                html.push('<div class="col-md-4">');
                html.push('<p><b>Purchase Details:</b></p>');
                html.push('<p><small>You bought <b>' + quantity + '</b> tickets at <b>' + individualCost + '</b> each.</p></small>');
                if (retailer != "") {
                    html.push('<p><small>Retailer: ' + retailer + '</p></small>');
                }
                html.push('<p><small>Purchase Date: ' + purchaseDate + '</p></small>');
                html.push('<p><b>Listing Details:</b></p>');
                html.push('<p><small>Status: <b>' + status + '</b></p></small>');
                html.push('<p><small>Your Price: <b>' + yourPrice + '</b> per ticket</p></small>');

                html.push('</div>');
                html.push('<div class="col-md-4">');
                html.push('<p><b>Tickets Details:</b></p>');
                html.push('<p><small>Section: ' + section + '</p></small>');
                html.push('<p><small>Row: ' + row + '</p></small>');
                html.push('<p><small>Seats: ' + seatFrom + ' - ' + seatTo + '</p></small>');
                html.push(`<button name="edit-inventory-item" type="button" class="btn btn-soft-primary" data-item-id="${itemId}" style="margin-bottom: 20px">
                <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.4925 2.78906H7.75349C4.67849 2.78906 2.75049 4.96606 2.75049 8.04806V16.3621C2.75049 19.4441 4.66949 21.6211 7.75349 21.6211H16.5775C19.6625 21.6211 21.5815 19.4441 21.5815 16.3621V12.3341" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.82812 10.921L16.3011 3.44799C17.2321 2.51799 18.7411 2.51799 19.6721 3.44799L20.8891 4.66499C21.8201 5.59599 21.8201 7.10599 20.8891 8.03599L13.3801 15.545C12.9731 15.952 12.4211 16.181 11.8451 16.181H8.09912L8.19312 12.401C8.20712 11.845 8.43412 11.315 8.82812 10.921Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M15.1655 4.60254L19.7315 9.16854" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg> Edit Item
            </button>`);
                html.push('</div>');
                break;

            default:
                break;
        }
    });
    html.push('</div>');
    return html.join('')
}

function saleDetailFormatter(index, row) {
    var html = []
    html.push('<div class="row">');
    $.each(row, function (key, value) {
        switch (key) {
            case 'saleData':
                var saleId = $(value).attr('data-sale-id');
                var eventId = $(value).attr('data-event-id');
                var section = $(value).attr('data-section');
                var row = $(value).attr('data-row');
                var seatFrom = $(value).attr('data-seat-from');
                var seatTo = $(value).attr('data-seat-to');

                html.push('<div class="col-md-4">');
                html.push('<p><small>Sale ID: <b>' + saleId + '</b></p></small>');
                html.push('<p><small>Section: ' + section + '</p></small>');
                html.push('<p><small>Row: ' + row + '</p></small>');
                html.push('<p><small>Seats: ' + seatFrom + ' - ' + seatTo + '</p></small>');
                html.push(`<a class="btn btn-soft-info" style="margin-bottom: 20px" href="http://localhost:8080/?model=events&action=eventOverview&eventId=${eventId}">
                <i class="fa-solid fa-eye"></i> View Event
            </button>`);
                html.push('</div>');
                break;

            default:
                break;
        }
    });
    html.push('</div>');
    return html.join('')
}

function releaseDetailFormatter(index, row) {
    var html = []
    html.push('<div class="row">');
    $.each(row, function (key, value) {
        switch (key) {
            case 'releaseData':
                var itemId = $(value).attr('data-item-id');
                var retailer = $(value).attr('data-retailer');
                var earlyLink = $(value).attr('data-early-link');
                var comments = $(value).attr('data-comments');
                var author = $(value).attr('data-author');

                html.push('<div class="col-md-4">');
                html.push('<p><small>Retailer: ' + retailer + '</p></small>');
                html.push('<p><small>Early Link: ' + earlyLink + '</p></small>');
                html.push('<p><small>Comments: ' + comments + '</p></small>');
                html.push('<p><small>Release Author: <b>' + author + '</b></p></small>');
                html.push(`<a class="btn btn-soft-info" style="margin-bottom: 20px" href="http://localhost:8080/?model=events&action=releaseOverview&releaseId=${itemId}">
                <i class="fa-solid fa-eye"></i> View Release
            </button>`);
                html.push('</div>');
                break;

            default:
                break;
        }
    });
    html.push('</div>');
    return html.join('')
}