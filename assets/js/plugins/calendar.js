function getCalendarData(type, startDate, endDate, sort, order, successCallback, errorCallback) {
    $.ajax({
        url: '/getCalendar', // Replace with the URL of your PHP script
        type: 'GET',
        data: {
            type: type,
            startDate: startDate,
            endDate: endDate,
            sort: sort,
            order: order,
        },
        success: function (response) {
            // Handle the response here
            if (response.success) {
                successCallback(response);
            } else {
                errorCallback(response.error);
            }
        },
        error: function (xhr, status, error) {
            errorCallback(error);
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelectorAll('#calendar1').length) {
        $('#overlay').show();
        getCalendarData('merged', null, null, 'date', 'asc', (response) => {
            const inventoryEvents = response.calendar;
            let calendarEl = document.getElementById('calendar1');
            let calendar1 = new FullCalendar.Calendar(calendarEl, {
                selectable: true,
                plugins: ["timeGrid", "dayGrid", "list", "interaction"],
                timeZone: "UTC",
                defaultView: "dayGridMonth",
                contentHeight: "auto",
                eventLimit: true,
                dayMaxEvents: 4,
                header: {
                    left: "prev,next today",
                    center: "title",
                    right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek"
                },
                dateClick: function (info) {
                    $('#schedule-start-date').val(info.dateStr)
                    $('#schedule-end-date').val(info.dateStr)
                    $('#date-event').modal('show')
                },
                eventClick: function (info) {
                    const releaseId = info.event.extendedProps.releaseId;
                    if (releaseId !== undefined && releaseId && releaseId.length > 0) {
                        showReleasePopup(releaseId, info);
                    } else {
                        showInventoryItemPopup(info);
                    }

                },
                events: inventoryEvents
            });
            $('#overlay').hide();
            calendar1.render();
        }, function (error) {
            $('#overlay').hide();
            console.log(error);
        });
    }
});

function showInventoryItemPopup(info) {
    const eventId = info.event.extendedProps.eventId;
    const eventDate = info.event.extendedProps.eventDate;
    const eventGenre = info.event.extendedProps.eventGenre;
    const eventLocation = info.event.extendedProps.eventLocation;
    const eventDescription = info.event.extendedProps.eventDescription;
    const btnUrl = info.event.extendedProps.pageUrl;

    // Open a Bootstrap modal when an event is clicked
    $('#eventInfoModal').modal('show');
    // Populate the modal with event information
    $('#eventInfoModal .event-title').text(eventDescription);
    $('#eventInfoModal .event-date').text(eventDate);
    $('#eventInfoModal .event-genre').text(eventGenre);
    $('#eventInfoModal .event-location').text(eventLocation);
    if (eventId !== undefined && eventId && eventId.length > 0) {
        $('#eventInfoViewBtn').text('View Item');
    } else {
        $('#eventInfoViewBtn').text('View Event');
    }
    $('#eventInfoViewBtn').attr('href', btnUrl);
}

function showReleasePopup(releaseId, info) {
    const releaseDate = info.event.extendedProps.releaseDate;
    const eventDescription = info.event.extendedProps.eventDescription;
    const eventDate = info.event.extendedProps.eventDate;
    const eventLocation = info.event.extendedProps.eventLocation;
    const retailer = info.event.extendedProps.retailer;
    const comments = info.event.extendedProps.comments;
    const btnUrl = info.event.extendedProps.pageUrl;

    // Open a Bootstrap modal when a release is clicked
    $('#releaseInfoModal').modal('show');
    // Populate the modal with release information
    $('#releaseInfoModal .release-date').text(releaseDate);
    $('#releaseInfoModal .event-title').text(eventDescription);
    $('#releaseInfoModal .event-date').text(eventDate);
    $('#releaseInfoModal .event-location').text(eventLocation);
    $('#releaseInfoModal .early-link').attr('href', btnUrl);
    $('#releaseInfoModal .retailer').text(retailer);
    $('#releaseInfoModal .comments').text(comments);
    $('#releaseInfoLink').attr('href', btnUrl);
}

/*

        {
            title: 'Click for Google',
            url: 'http://google.com/',
            start: moment(new Date(), 'YYYY-MM-DD').add(-20, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            title: 'All Day Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(-18, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(108,117,125,0.2)',
            textColor: 'rgba(108,117,125,1)',
            borderColor: 'rgba(108,117,125,1)'
        },
        {
            title: 'Long Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(-16, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            end: moment(new Date(), 'YYYY-MM-DD').add(-13, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(8,130,12,0.2)',
            textColor: 'rgba(8,130,12,1)',
            borderColor: 'rgba(8,130,12,1)'
        },
        {
            groupId: '999',
            title: 'Repeating Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(-14, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            color: '#047685',
            backgroundColor: 'rgba(4,118,133,0.2)',
            textColor: 'rgba(4,118,133,1)',
            borderColor: 'rgba(4,118,133,1)'
        },
        {
            groupId: '999',
            title: 'Repeating Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(-12, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(235,153,27,0.2)',
            textColor: 'rgba(235,153,27,1)',
            borderColor: 'rgba(235,153,27,1)'
        },
        {
            groupId: '999',
            title: 'Repeating Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(-10, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(206,32,20,0.2)',
            textColor: 'rgba(206,32,20,1)',
            borderColor: 'rgba(206,32,20,1)'
        },
        {
            title: 'Birthday Party',
            start: moment(new Date(), 'YYYY-MM-DD').add(-8, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            title: 'Meeting',
            start: moment(new Date(), 'YYYY-MM-DD').add(-6, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            title: 'Birthday Party',
            start: moment(new Date(), 'YYYY-MM-DD').add(-5, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(235,153,27,0.2)',
            textColor: 'rgba(235,153,27,1)',
            borderColor: 'rgba(235,153,27,1)'
        },
        {
            title: 'Birthday Party',
            start: moment(new Date(), 'YYYY-MM-DD').add(-2, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(235,153,27,0.2)',
            textColor: 'rgba(235,153,27,1)',
            borderColor: 'rgba(235,153,27,1)'
        },

        {
            title: 'Meeting',
            start: moment(new Date(), 'YYYY-MM-DD').add(0, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            title: 'Click for Google',
            url: 'http://google.com/',
            start: moment(new Date(), 'YYYY-MM-DD').add(0, 'days').format('YYYY-MM-DD') + 'T06:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            groupId: '999',
            title: 'Repeating Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(0, 'days').format('YYYY-MM-DD') + 'T07:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            title: 'Birthday Party',
            start: moment(new Date(), 'YYYY-MM-DD').add(0, 'days').format('YYYY-MM-DD') + 'T08:30:00.000Z',
            backgroundColor: 'rgba(235,153,27,0.2)',
            textColor: 'rgba(235,153,27,1)',
            borderColor: 'rgba(235,153,27,1)'
        },
        {
            title: 'Doctor Meeting',
            start: moment(new Date(), 'YYYY-MM-DD').add(0, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(235,153,27,0.2)',
            textColor: 'rgba(235,153,27,1)',
            borderColor: 'rgba(235,153,27,1)'
        },
        {
            title: 'All Day Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(1, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            groupId: '999',
            title: 'Repeating Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(8, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        },
        {
            groupId: '999',
            title: 'Repeating Event',
            start: moment(new Date(), 'YYYY-MM-DD').add(10, 'days').format('YYYY-MM-DD') + 'T05:30:00.000Z',
            backgroundColor: 'rgba(58,87,232,0.2)',
            textColor: 'rgba(58,87,232,1)',
            borderColor: 'rgba(58,87,232,1)'
        }
      ]
*/