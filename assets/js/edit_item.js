document.addEventListener('DOMContentLoaded', function () {
    /**
     * Only display floor seats quantity if selected section is Floor
     * 
     */

    var rowContainers = document.querySelectorAll('.rowContainer');
    var floorSeatsContainers = document.querySelectorAll('.floorSeatsContainer');
    var seatsContainers = document.querySelectorAll('.seatsContainer');

    const sectionSelects = document.querySelectorAll(".sectionSelect");
    const sectionCustomInputs = document.querySelectorAll(".customSection");

    const onFloorSelected = () => {
        floorSeatsContainers.forEach(el => {
            el.style.display = 'block';
        });

        seatsContainers.forEach(el => {
            el.style.display = 'none';
        });

        rowContainers.forEach(el => {
            el.style.display = 'none';
        });
    };

    const onSectionSelected = () => {
        floorSeatsContainers.forEach(el => {
            el.style.display = 'none';
        });

        seatsContainers.forEach(el => {
            el.style.display = 'block';
        });

        rowContainers.forEach(el => {
            el.style.display = 'block';
        });
    };

    const handleSectionChange = (el) => {
        if (el.target.value.toLowerCase().includes("floor")) {
            onFloorSelected();
        } else {
            onSectionSelected();
        }
    };

    sectionSelects.forEach(el => {
        el.addEventListener("change", handleSectionChange);
    });
    sectionCustomInputs.forEach(el => {
        el.addEventListener("input", handleSectionChange);
    });

});
