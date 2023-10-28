function validateListingData(itemData) {
    if (!itemData.eventId || itemData.eventId.length <= 0) {
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
    } else if (itemData.section !== "Floor") {
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

    const viagogoSessionId = getCookie('viagogoSessionId');
    const viagogoSessionId2 = getCookie('viagogoSessionId2');

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
                action: "add_listing",
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

function editListing(csrfToken, listingId, action) {

    const viagogoSessionId = getCookie('viagogoSessionId');
    const viagogoSessionId2 = getCookie('viagogoSessionId2');

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
                action: "edit_listing",
                sessionCookie: viagogoSessionId,
                sessionCookie2: viagogoSessionId2,
                csrfToken: csrfToken,
                listingId: listingId,
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

function editListingDetails(listingId, eventId, ticketFormatID, quantity, splitId, sectionText, rowText, seatFrom, seatTo, individualTicketCost, yourPricePerTicket) {

    const viagogoSessionId = getCookie('viagogoSessionId');
    const viagogoSessionId2 = getCookie('viagogoSessionId2');

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
                action: "edit_full",
                sessionCookie: viagogoSessionId,
                sessionCookie2: viagogoSessionId2,
                listingId: listingId,
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