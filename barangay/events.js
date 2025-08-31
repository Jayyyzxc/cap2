document.addEventListener('DOMContentLoaded', function() {
    // Highlight calendar days with events on hover
    const eventDays = document.querySelectorAll('.calendar-day.has-event');
    
    eventDays.forEach(day => {
        day.addEventListener('mouseover', function() {
            this.style.backgroundColor = '#d4e6f1';
        });
        
        day.addEventListener('mouseout', function() {
            this.style.backgroundColor = '#e3f2fd';
        });
        
        day.addEventListener('click', function() {
            // You could implement a modal or scroll to the event list
            // For now, just scroll to events section
            document.querySelector('.events-list').scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Set minimum date for event creation to today
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const today = new Date();
        const dd = String(today.getDate()).padStart(2, '0');
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const yyyy = today.getFullYear();
        dateInput.min = `${yyyy}-${mm}-${dd}`;
    }
    
    // You can add more interactive features here
    // For example, a chart of event participation could be added
    // This would require additional data from your database
});