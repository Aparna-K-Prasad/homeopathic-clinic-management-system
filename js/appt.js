const calendarTableBody = document.querySelector('.calendar-table tbody');
const timeSlotsContainer = document.querySelector('.time-slots');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const monthDisplay = document.getElementById('monthDisplay');
const yearSelect = document.getElementById('yearSelect');
const timeSlotHeader = document.querySelector('.time-slot-header');

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();
let selectedDate = null;
let selectedTimeSlot = null;

function populateYearSelect(startYear, endYear) {
    for (let year = startYear; year <= endYear; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearSelect.appendChild(option);
    }
    yearSelect.value = currentYear;
}

function generateCalendar(month, year) {
    calendarTableBody.innerHTML = '';
    monthDisplay.textContent = monthNames[month];
    yearSelect.value = year;

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDayOfMonth = new Date(year, month, 1).getDay();

    let date = 1;
    for (let i = 0; i < 6; i++) {
        let row = document.createElement('tr');

        for (let j = 0; j < 7; j++) {
            let cell = document.createElement('td');
            if (i === 0 && j < firstDayOfMonth) {
                cell.textContent = '';
            } else if (date <= daysInMonth) {
                cell.textContent = date;
                const formattedDate = `${year}-${(month + 1).toString().padStart(2, '0')}-${date.toString().padStart(2, '0')}`;
                cell.setAttribute('data-date', formattedDate);

                const selectedDateTime = new Date(formattedDate);
                const currentDateTime = new Date();
                currentDateTime.setHours(0, 0, 0, 0);

                if (selectedDateTime < currentDateTime) {
                    cell.classList.add('past-date');
                    cell.style.pointerEvents = 'none';
                    cell.style.color = '#ccc';
                    cell.style.backgroundColor = '#f5f5f5';
                } else {
                    cell.addEventListener('click', function() {
                        selectDate(this, j);
                    });
                }
                date++;
            }
            row.appendChild(cell);
        }
        calendarTableBody.appendChild(row);
    }
}

function selectDate(cell, dayOfWeek) {
    if (selectedDate === cell) {
        cell.classList.remove('selected');
        selectedDate = null;
        timeSlotsContainer.innerHTML = '';
        timeSlotHeader.style.display = 'none';
        return;
    }

    if (selectedDate) {
        selectedDate.classList.remove('selected');
    }
    cell.classList.add('selected');
    selectedDate = cell;

    const selectedDay = cell.textContent;
    const formattedDate = `${currentYear}-${(currentMonth + 1).toString().padStart(2, '0')}-${selectedDay.toString().padStart(2, '0')}`;
    
    document.getElementById('appointment_date').value = formattedDate;
    timeSlotHeader.style.display = 'block';

    if (dayOfWeek === 0) { 
        timeSlotsContainer.innerHTML = '<p>No clinic on Sundays</p>';
    } else if (dayOfWeek === 6) { 
        generateTimeSlots(['09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '05:00 PM', '05:30 PM', '06:00 PM', '06:30 PM', '07:00 PM', '07:30 PM']);
    } else { 
        generateTimeSlots(['08:00 AM', '08:30 AM', '09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM', '04:00 PM', '04:30 PM', '05:00 PM', '05:30 PM', '06:00 PM', '06:30 PM']);
    }
}

function generateTimeSlots(slotsArray) {
    timeSlotsContainer.innerHTML = '';
    const currentDateTime = new Date();
    const selectedDateStr = selectedDate.getAttribute('data-date');

    slotsArray.forEach(time => {
        const button = document.createElement('button');
        button.classList.add('time-slot');
        button.textContent = time;

        const [timeHours, timeMinutes, period] = parseTime(time);
        let hours = timeHours;
        if (period === 'PM' && hours !== 12) hours += 12;
        if (period === 'AM' && hours === 12) hours = 0;

        const slotDateTime = new Date(selectedDateStr);
        slotDateTime.setHours(hours, timeMinutes, 0, 0);

        if (slotDateTime <= currentDateTime) {
            button.classList.add('past-date');
            button.style.pointerEvents = 'none';
            button.style.color = '#ccc';
            button.style.backgroundColor = '#f5f5f5';
            button.disabled = true;
        } else {
            button.addEventListener('click', function() {
                selectTimeSlot(this);
            });
        }

        timeSlotsContainer.appendChild(button);
    });
}

function parseTime(timeStr) {
    const [time, period] = timeStr.split(' ');
    const [hours, minutes] = time.split(':').map(Number);
    return [hours, minutes, period];
}

function selectTimeSlot(button) {
    if (selectedTimeSlot === button) {
        button.classList.remove('selected');
        selectedTimeSlot = null;
        document.getElementById('appointment_time').value = '';
    } else {
        if (selectedTimeSlot) {
            selectedTimeSlot.classList.remove('selected');
        }
        button.classList.add('selected');
        selectedTimeSlot = button;
        document.getElementById('appointment_time').value = button.textContent;
    }
}

prevBtn.addEventListener('click', function() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentMonth, currentYear);
});

nextBtn.addEventListener('click', function() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentMonth, currentYear);
});

yearSelect.addEventListener('change', function() {
    currentYear = parseInt(this.value);
    generateCalendar(currentMonth, currentYear);
});

populateYearSelect(currentYear, currentYear + 10);
generateCalendar(currentMonth, currentYear);
