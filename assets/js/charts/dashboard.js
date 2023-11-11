function getChartsData(type, startDate, endDate, sort, order, successCallback, errorCallback) {
  $.ajax({
    url: `/api/user/chart/${type}`, // Replace with the URL of your PHP script
    type: 'GET',
    data: {
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

(function (jQuery) {
  "use strict";
  if (document.querySelectorAll('#myChart').length) {
    const options = {
      series: [55, 75],
      chart: {
        height: 230,
        type: 'radialBar',
      },
      colors: ["#4bc7d2", "#3a57e8"],
      plotOptions: {
        radialBar: {
          hollow: {
            margin: 10,
            size: "50%",
          },
          track: {
            margin: 10,
            strokeWidth: '50%',
          },
          dataLabels: {
            show: false,
          }
        }
      },
      labels: ['Apples', 'Oranges'],
    };
    if (ApexCharts !== undefined) {
      const chart = new ApexCharts(document.querySelector("#myChart"), options);
      chart.render();
      document.addEventListener('ColorChange', (e) => {
        const newOpt = { colors: [e.detail.detail2, e.detail.detail1], }
        chart.updateOptions(newOpt)

      })
    }
  }
  if (document.querySelectorAll('#d-floor-prices').length && $('#d-floor-prices').attr('event-id').length > 0) {
    getEventOverviewData($('#d-floor-prices').attr('event-id'), (response) => {
      if ($("#floorPriceRating").length) {
        // Update floor price rating
        var floorPriceRating = response.floorPriceRating;
        var ratingHtml = '';
        var decimalRating;
        var integerRating = Math.floor(floorPriceRating);
        if (integerRating >= 5) {
          integerRating = 5;
          decimalRating = 0;
        } else {
          decimalRating = response.floorPriceRating - Math.floor(floorPriceRating);
        }

        for (var i = 0; i < integerRating; i++) {
          ratingHtml += `
              <svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" fill="orange">
                  <path d="M2.047 14.668a.994.994 0 0 0 .465.607l1.91 1.104v2.199a1 1 0 0 0 1 1h2.199l1.104 1.91a1.01 1.01 0 0 0 .866.5c.174 0 .347-.046.501-.135L12 20.75l1.91 1.104a1.001 1.001 0 0 0 1.366-.365l1.103-1.91h2.199a1 1 0 0 0 1-1V16.38l1.91-1.104a1 1 0 0 0 .365-1.367L20.75 12l1.104-1.908a1 1 0 0 0-.365-1.366l-1.91-1.104v-2.2a1 1 0 0 0-1-1H16.38l-1.103-1.909a1.008 1.008 0 0 0-.607-.466.993.993 0 0 0-.759.1L12 3.25l-1.909-1.104a1 1 0 0 0-1.366.365l-1.104 1.91H5.422a1 1 0 0 0-1 1V7.62l-1.91 1.104a1.003 1.003 0 0 0-.365 1.368L3.251 12l-1.104 1.908a1.009 1.009 0 0 0-.1.76zM12 13c-3.48 0-4-1.879-4-3 0-1.287 1.029-2.583 3-2.915V6.012h2v1.109c1.734.41 2.4 1.853 2.4 2.879h-1l-1 .018C13.386 9.638 13.185 9 12 9c-1.299 0-2 .515-2 1 0 .374 0 1 2 1 3.48 0 4 1.879 4 3 0 1.287-1.029 2.583-3 2.915V18h-2v-1.08c-2.339-.367-3-2.003-3-2.92h2c.011.143.159 1 2 1 1.38 0 2-.585 2-1 0-.325 0-1-2-1z" />
              </svg>`;
        }

        if (decimalRating >= 0.5) {
          ratingHtml += `
          <svg height="24" viewBox="0 0 12 24" width="12" xmlns="http://www.w3.org/2000/svg" fill="orange">
              <path d="M2.047 14.668a.994.994 0 0 0 .465.607l1.91 1.104v2.199a1 1 0 0 0 1 1h2.199l1.104 1.91a1.01 1.01 0 0 0 .866.5c.174 0 .347-.046.501-.135L12 20.75l1.91 1.104a1.001 1.001 0 0 0 1.366-.365l1.103-1.91h2.199a1 1 0 0 0 1-1V16.38l1.91-1.104a1 1 0 0 0 .365-1.367L20.75 12l1.104-1.908a1 1 0 0 0-.365-1.366l-1.91-1.104v-2.2a1 1 0 0 0-1-1H16.38l-1.103-1.909a1.008 1.008 0 0 0-.607-.466.993.993 0 0 0-.759.1L12 3.25l-1.909-1.104a1 1 0 0 0-1.366.365l-1.104 1.91H5.422a1 1 0 0 0-1 1V7.62l-1.91 1.104a1.003 1.003 0 0 0-.365 1.368L3.251 12l-1.104 1.908a1.009 1.009 0 0 0-.1.76zM12 13c-3.48 0-4-1.879-4-3 0-1.287 1.029-2.583 3-2.915V6.012h2v1.109c1.734.41 2.4 1.853 2.4 2.879h-1l-1 .018C13.386 9.638 13.185 9 12 9c-1.299 0-2 .515-2 1 0 .374 0 1 2 1 3.48 0 4 1.879 4 3 0 1.287-1.029 2.583-3 2.915V18h-2v-1.08c-2.339-.367-3-2.003-3-2.92h2c.011.143.159 1 2 1 1.38 0 2-.585 2-1 0-.325 0-1-2-1z" />
          </svg>`;
        }

        $("#floorPriceRating").html(ratingHtml);
      }

      const sections = response.sectionsData;
      const labels = [];
      const values = [];
      let currency;
      // Populate series
      sections.forEach(function (section) {
        labels.push(section.Section);
        currency = section.Price.charAt(0); // Get the first character (currency symbol
        const amount = section.Price.slice(1);
        values.push(amount);
      });

      const options = {
        series: [{
          name: 'Lowest Price',
          data: values
        }],
        chart: {
          type: 'bar',
          height: 340,
          stacked: true,
          toolbar: {
            show: false
          }
        },
        colors: ["#3a57e8", "#4bc7d2"],
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '48%',
            endingShape: 'rounded',
            borderRadius: 5,
          },
        },
        legend: {
          show: false
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          show: true,
          width: 2,
          colors: ['transparent']
        },
        xaxis: {
          categories: labels,
          labels: {
            minHeight: 28,
            maxHeight: 28,
            style: {
              colors: "#8A92A6",
            },
          }
        },
        yaxis: {
          title: {
            text: ''
          },
          labels: {
            minWidth: 19,
            maxWidth: 19,
            style: {
              colors: "#8A92A6",
            },
          }
        },
        fill: {
          opacity: 1
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return currency + " " + val
            }
          }
        }
      };

      const chart = new ApexCharts(document.querySelector("#d-floor-prices"), options);
      chart.render();
      document.addEventListener('ColorChange', (e) => {
        const newOpt = { colors: [e.detail.detail1, e.detail.detail2], }
        chart.updateOptions(newOpt)
      });

    }, function (error) {
      console.error(error);
    });
  }

  if (document.querySelectorAll('#d-inventory').length) {
    getChartsData('inventory', null, null, 'date', 'asc', (response) => {
      const inventoryValues = response.series;
      $('.chart-legend-2.header-title h4').text(response.total.toFixed(2) + " " + response.currency);

      var options = {
        series: [
          {
            name: 'Value',
            data: inventoryValues,
          },
        ],
        chart: {
          id: 'area-datetime',
          type: 'area',
          height: 350,
          zoom: {
            autoScaleYaxis: true
          }
        },
        legend: {
          show: false
        },
        dataLabels: {
          enabled: false
        },
        markers: {
          size: 0,
          style: 'hollow',
        },
        xaxis: {
          type: 'datetime',
          tickAmount: 6,
        },
        tooltip: {
          x: {
            format: 'dd MMM yyyy'
          }
        },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.9,
            stops: [0, 100]
          }
        },
      };

      const chart = new ApexCharts(document.querySelector("#d-inventory"), options);
      chart.render();

      document.addEventListener('ColorChange', (e) => {
        console.log(e)
        const newOpt = {
          colors: [e.detail.detail1, e.detail.detail2],
          fill: {
            type: 'gradient',
            gradient: {
              shade: 'dark',
              type: "vertical",
              shadeIntensity: 0,
              gradientToColors: [e.detail.detail1, e.detail.detail2], // optional, if not defined - uses the shades of same color in series
              inverseColors: true,
              opacityFrom: .4,
              opacityTo: .1,
              stops: [0, 50, 60],
              colors: [e.detail.detail1, e.detail.detail2],
            }
          },
        }
        chart.updateOptions(newOpt)
      })

    }, function (error) {
      console.error(error);
    });
  }

  if (document.querySelectorAll('#d-main').length) {

    /* Ajax call to fetch purchases & sales data */

    getChartsData('sales', null, null, 'date', 'asc', (response) => {
      const sales = response.series;
      const salesTotal = response.total

      getChartsData('purchases', null, null, 'date', 'asc', (response) => {
        const purchases = response.series;
        const purchasesTotal = response.total;
        const realizedProfit = (salesTotal - purchasesTotal).toFixed(2);
        $('.chart-legend-1.header-title h4').text(realizedProfit + " " + response.currency);

        var options = {
          series: [
            {
              name: 'Sales',
              data: sales,
            },
            {
              name: 'Purchases', // Name for the second series
              data: purchases,
            },
          ],
          chart: {
            id: 'area-datetime',
            type: 'area',
            height: 350,
            zoom: {
              autoScaleYaxis: true
            }
          },
          legend: {
            show: false
          },
          dataLabels: {
            enabled: false
          },
          markers: {
            size: 0,
            style: 'hollow',
          },
          xaxis: {
            type: 'datetime',
            tickAmount: 6,
          },
          tooltip: {
            x: {
              format: 'dd MMM yyyy'
            }
          },
          fill: {
            type: 'gradient',
            gradient: {
              shadeIntensity: 1,
              opacityFrom: 0.7,
              opacityTo: 0.9,
              stops: [0, 100]
            }
          },
        };

        const chart = new ApexCharts(document.querySelector("#d-main"), options);
        chart.render();

        document.addEventListener('ColorChange', (e) => {
          console.log(e)
          const newOpt = {
            colors: [e.detail.detail1, e.detail.detail2],
            fill: {
              type: 'gradient',
              gradient: {
                shade: 'dark',
                type: "vertical",
                shadeIntensity: 0,
                gradientToColors: [e.detail.detail1, e.detail.detail2], // optional, if not defined - uses the shades of same color in series
                inverseColors: true,
                opacityFrom: .4,
                opacityTo: .1,
                stops: [0, 50, 60],
                colors: [e.detail.detail1, e.detail.detail2],
              }
            },
          }
          chart.updateOptions(newOpt)
        })

      }, function (error) {
        console.error(error);
      });
    }, function (error) {
      console.error(error);
    });
  }

  if (jQuery('#mainChartSelector').length) {
    document.getElementById('mainChartSelector').addEventListener("click", function (event) {
      // Check if the clicked element is an <li> within the <ul>
      if (event.target.tagName === "LI") {
        var selectedChart = event.target.textContent;

        switch (selectedChart) {
          case 'Sales & Purchases':
            $('#d-inventory-container').hide();
            $('#chart-legend-2').hide();
            $('.chart-legend-2.header-title').hide();
            $('#d-main-container').show();
            $('#chart-legend-1').show();
            $('.chart-legend-1.header-title').show();
            break;

          case 'Inventory Value':
            $('#d-main-container').hide();
            $('#chart-legend-1').hide();
            $('.chart-legend-1.header-title').hide();
            $('#d-inventory-container').show();
            $('#chart-legend-2').show();
            $('.chart-legend-2.header-title').show();
            break;

          default:
            break;
        }
      }
    });
  }


  if ($('.d-slider1').length > 0) {
    const options = {
      centeredSlides: false,
      loop: false,
      slidesPerView: 4,
      autoplay: false,
      spaceBetween: 32,
      breakpoints: {
        320: { slidesPerView: 1 },
        550: { slidesPerView: 2 },
        991: { slidesPerView: 3 },
        1400: { slidesPerView: 3 },
        1500: { slidesPerView: 4 },
        1920: { slidesPerView: 6 },
        2040: { slidesPerView: 7 },
        2440: { slidesPerView: 8 }
      },
      pagination: {
        el: '.swiper-pagination'
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev'
      },

      // And if we need scrollbar
      scrollbar: {
        el: '.swiper-scrollbar'
      }
    }
    let swiper = new Swiper('.d-slider1', options);

    document.addEventListener('ChangeMode', (e) => {
      if (e.detail.rtl === 'rtl' || e.detail.rtl === 'ltr') {
        swiper.destroy(true, true)
        setTimeout(() => {
          swiper = new Swiper('.d-slider1', options);
        }, 500);
      }
    })
  }

})(jQuery)
