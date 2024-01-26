function validateListingData(itemData) {
    if (!itemData.viagogoEventId || itemData.viagogoEventId.length <= 0) {
        throw new Error('event ID is required');
    }
    if (!itemData.ticketType || itemData.ticketType.length <= 0) {
        throw new Error('ticket type is required');
    }
    if (!itemData.quantityRemain || itemData.quantityRemain <= 0) {
        throw new Error('remaining quantity must be greater than 0');
    }
    if (!itemData.splitType || itemData.splitType.length <= 0) {
        throw new Error('split type is required');
    }
    if (!itemData.section || itemData.section.length <= 0) {
        throw new Error('section is required');
    } else if (!itemData.section.toLowerCase().includes('floor') && !itemData.section.toLowerCase().includes('general admission')) {
        if (!itemData.row || itemData.row.length <= 0) {
            throw new Error('row is required');
        }
        if (!itemData.seatFrom || itemData.seatFrom.length <= 0) {
            throw new Error('seats are required');
        }
        if (!itemData.seatTo || itemData.seatTo.length <= 0) {
            throw new Error('seats are required');
        }
    }
    if (!itemData.individualTicketCost || itemData.individualTicketCost.amount <= 0) {
        throw new Error('ticket face value is required');
    }
    if (!itemData.yourPricePerTicket || itemData.yourPricePerTicket.amount <= 0) {
        throw new Error('your ticket price is required');
    }
}

function createListing(eventId, ticketFormatID, quantity, splitId, sectionText, rowText, seatFrom, seatTo, individualTicketCost, yourPricePerTicket, restrictions, ticketDetails) {

    const viagogoSessionId = window.viagogoUser.wsu2Cookie;
    const viagogoSessionId2 = window.viagogoUser.rvtCookie;

    function makeApiRequest(resolve, reject) {
        $.ajax({
            url: `${API_BASE_URL}service-integration/viagogo/list`,
            type: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                sessionCookie: viagogoSessionId,
                sessionCookie2: viagogoSessionId2,
                eventId: eventId,
                ticketFormatID: ticketFormatID,
                quantity: quantity,
                splitId: splitId,
                sectionText: sectionText,
                rowText: rowText,
                seatFrom: seatFrom,
                seatTo: seatTo,
                individualTicketCost: individualTicketCost,
                yourPricePerTicket: yourPricePerTicket,
                restrictions: restrictions,
                ticketDetails: ticketDetails,
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

        makeApiRequest(resolve, reject); // Pass resolve and reject as arguments

    });
}

function editListing(csrfToken, listingId, action) {

    const viagogoSessionId = window.viagogoUser.wsu2Cookie;
    const viagogoSessionId2 = window.viagogoUser.rvtCookie;

    function makeApiRequest(resolve, reject) {
        $.ajax({
            url: `${API_BASE_URL}service-integration/viagogo/listings/status/${listingId}`,
            type: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                sessionCookie: viagogoSessionId,
                sessionCookie2: viagogoSessionId2,
                csrfToken: csrfToken,
                listingAction: action,
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

        makeApiRequest(resolve, reject); // Pass resolve and reject as arguments

    });
}

function editListingDetails(listingId, eventId, ticketFormatID, quantity, splitId, sectionText, rowText, seatFrom, seatTo, individualTicketCost, yourPricePerTicket) {

    const viagogoSessionId = window.viagogoUser.wsu2Cookie;
    const viagogoSessionId2 = window.viagogoUser.rvtCookie;

    function makeApiRequest(resolve, reject) {
        $.ajax({
            url: `${API_BASE_URL}service-integration/viagogo/listings/{listingId}`,
            type: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            dataType: 'json',
            data: JSON.stringify({
                sessionCookie: viagogoSessionId,
                sessionCookie2: viagogoSessionId2,
                eventId: eventId,
                ticketFormatID: ticketFormatID,
                quantity: quantity,
                splitId: splitId,
                sectionText: sectionText,
                rowText: rowText,
                seatFrom: seatFrom,
                seatTo: seatTo,
                individualTicketCost: individualTicketCost,
                yourPricePerTicket: yourPricePerTicket,
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

        makeApiRequest(resolve, reject); // Pass resolve and reject as arguments

    });
}