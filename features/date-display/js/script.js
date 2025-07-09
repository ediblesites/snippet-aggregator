jQuery(document).ready(function($) {
    function updateDateTime() {
        const now = new Date();
        const options = {
            timeZone: saDateDisplay.timezone
        };

        // Update date
        const dateOptions = { ...options };
        $('#sa-current-date').text(now.toLocaleDateString(undefined, dateOptions));

        // Update time
        const timeOptions = { ...options };
        $('#sa-current-time').text(now.toLocaleTimeString(undefined, timeOptions));
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);
}); 