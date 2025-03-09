// Category validation
function validatecategory() {
    const category = document.getElementById('category').value;
    const error = document.getElementById('categoryerror');
    const deliveryDocs = document.getElementById('delivery-docs');
    const licenseSelect = document.getElementById('license_required');
    const badgeSelect = document.getElementById('badge_required');
    
    if (!category) {
        error.textContent = "Category is required";
        return false;
    }
    
    if (category === "Delivery and logistics") {
        deliveryDocs.style.display = "block";
    } else {
        deliveryDocs.style.display = "none";
        // Clear both license and badge values when category changes
        licenseSelect.value = "";
        badgeSelect.value = "";
        // Also clear any error messages
        document.getElementById('licenseerror').textContent = "";
        document.getElementById('badgeerror').textContent = "";
    }
    
    error.textContent = "";
    return true;
}

// Job Title validation
function validateJobTitle() {
    const jobTitle = document.getElementById('job_title').value.trim();
    const error = document.getElementById("titleerror");
    
    if (!jobTitle) {
        error.textContent = "Job title is required.";
        return false;
    }
    if (jobTitle.length < 3 || jobTitle.length > 50) {
        error.textContent = "Job title must be between 3 and 50 characters.";
        return false;
    }
    if (!/^[a-zA-Z\s]+$/.test(jobTitle)) {
        error.textContent = "Only letters and spaces are allowed";
        return false;
    }
    
    error.textContent = "";
    return true;
}

// Job Description validation
function validateJobDescription() {
    const jobDescription = document.getElementById('job_description').value.trim();
    const error = document.getElementById("descriptionerror");
    
    if (!jobDescription) {
        error.textContent = "Job description is required.";
        return false;
    }
    if (jobDescription.length < 5 || jobDescription.length > 250) {
        error.textContent = "Job description must be between 5 and 250 characters.";
        return false;
    }
    if (!/^[a-zA-Z0-9\s.,!?-]+$/.test(jobDescription)) {
        error.textContent = "Special characters are not allowed";
        return false;
    }
    
    error.textContent = "";
    return true;
}

// Location validation
function validateLocation() {
    const location = document.getElementById('location').value;
    const error = document.getElementById("locationerror");
    
    if (!location) {
        error.textContent = "Please select a location";
        return false;
    }
    
    error.textContent = "";
    return true;
}

// Town data and selection functionality
const districtTowns = {
'Thiruvananthapuram': ['Thiruvananthapuram', 'Neyyattinkara', 'Attingal', 'Varkala', 'Nedumangad', 'Kazhakkoottam', 'Kallambalam', 'Kovalam', 'Balaramapuram', 'Pothencode'],
'Kollam': ['Kollam', 'Paravur', 'Punalur', 'Karunagappally', 'Kottarakkara', 'Chavara', 'Kundara', 'Anchal', 'Oachira', 'Sasthamkotta'],
'Pathanamthitta': ['Pathanamthitta', 'Adoor', 'Thiruvalla', 'Ranni', 'Pandalam', 'Konni', 'Mallappally', 'Kozhencherry', 'Mannarakulanji', 'Seethathode'],
'Alappuzha': ['Alappuzha', 'Chengannur', 'Kayamkulam', 'Mavelikkara', 'Haripad', 'Cherthala', 'Ambalappuzha', 'Thakazhi', 'Mannar', 'Edathua'],
'Kottayam': ['Kottayam', 'Pala', 'Changanassery', 'Vaikom', 'Ettumanoor', 'Erattupetta', 'Kuravilangad', 'Kanjirappally', 'Pampady', 'Kidangoor'],
'Idukki': ['Thodupuzha', 'Munnar', 'Adimali', 'Kumily', 'Kattappana', 'Nedumkandam', 'Vagamon', 'Devikulam', 'Peermade', 'Udumalpettai'],
'Ernakulam': ['Kochi', 'Aluva', 'Angamaly', 'Muvattupuzha', 'Perumbavoor', 'Kothamangalam', 'North Paravur', 'Kakkanad', 'Piravom', 'Kaloor'],
'Thrissur': ['Thrissur', 'Chalakudy', 'Kodungallur', 'Irinjalakuda', 'Guruvayur', 'Kunnamkulam', 'Wadakkanchery', 'Pavaratty', 'Kecheri', 'Mannuthy'],
'Palakkad': ['Palakkad', 'Ottapalam', 'Chittur', 'Pattambi', 'Shoranur', 'Mannarkkad', 'Alathur', 'Nemmara', 'Cherpulassery', 'Kongad'],
'Malappuram': ['Malappuram', 'Tirur', 'Ponnani', 'Manjeri', 'Perinthalmanna', 'Nilambur', 'Kottakkal', 'Parappanangadi', 'Edappal', 'Kondotty'],
'Kozhikode': ['Kozhikode', 'Vadakara', 'Koyilandy', 'Ramanattukara', 'Feroke', 'Koduvally', 'Balussery', 'Mavoor', 'Chelannur', 'Thamarassery'],
'Wayanad': ['Kalpetta', 'Sulthan Bathery', 'Mananthavady', 'Meenangadi', 'Panamaram', 'Vythiri', 'Pulpally', 'Ambalavayal', 'Muttil', 'Thariode'],
'Kannur': ['Kannur', 'Thalassery', 'Payyanur', 'Mattannur', 'Iritty', 'Koothuparamba', 'Taliparamba', 'Kuthuparamba', 'Panoor', 'Chirakkal'],
'Kasaragod': ['Kasaragod', 'Kanhangad', 'Nileshwar', 'Cheruvathur', 'Uppala', 'Manjeshwar', 'Periya', 'Hosdurg', 'Bekal', 'Mogral Puthur']
};


function showTowns() {
    const selectedDistrict = document.getElementById('location').value;
    const townSelect = document.getElementById('tvm_towns');
    const townLabel = document.getElementById('townLabel');
    
    // Clear existing options
    townSelect.innerHTML = '<option value="">Select Town</option>';
    
    if (selectedDistrict) {
        // Show town dropdown and label
        townSelect.style.display = 'block';
        townLabel.style.display = 'block';
        
        // Get towns for selected district
        const towns = districtTowns[selectedDistrict] || [];
        
        // Add town options
        towns.forEach(town => {
            const option = document.createElement('option');
            option.value = town;
            option.textContent = town;
            townSelect.appendChild(option);
        });
    } else {
        // Hide town dropdown and label if no district is selected
        townSelect.style.display = 'none';
        townLabel.style.display = 'none';
    }
}

// Vacancy Date validation
function validateDate() {
    const dateInput = document.getElementById('date').value;
    const error = document.getElementById('dateerror');
    
    if (!dateInput) {
        error.textContent = "Vacancy date is required.";
        return false;
    }
    
    const selectedDate = new Date(dateInput);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        error.textContent = "Vacancy date cannot be in the past.";
        return false;
    }
    
    const oneMonthFromToday = new Date(today);
    oneMonthFromToday.setMonth(today.getMonth() + 1);
    
    if (selectedDate > oneMonthFromToday) {
        error.textContent = "Vacancy date must be  one month from today.";
        return false;
    }
    
    error.textContent = "";
    return true;
}

// Time Selection validation
function validateTimeSelection() {
    let startTime = document.getElementById("start-time").value;
    let endTime = document.getElementById("end-time").value;
    let error = document.getElementById("timeerror");

    // If both times are empty, that's okay
    if (!startTime && !endTime) {
        error.textContent = "";
        return true;
    }

    // If one time is selected but not the other, show error
    if ((!startTime && endTime) || (startTime && !endTime)) {
        error.textContent = "Please select both start and end time, or leave both empty";
        return false;
    }

    // If both times are selected, validate that end is after start
    let startMinutes = convertTimeToMinutes(startTime);
    let endMinutes = convertTimeToMinutes(endTime);

    if (endMinutes <= startMinutes) {
        error.textContent = "End time must be later than start time.";
        return false;
    }

    error.textContent = "";
    return true;
}

function convertTimeToMinutes(time) {
    let [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}

// Vacancy Number validation
function validateVacancy() {
    const vacancy = document.getElementById('vacancy').value.trim();
    const error = document.getElementById('vacancyerror');
    
    if (!vacancy) {
        error.textContent = "Number of vacancies is required.";
        return false;
    }
    if (!/^\d+$/.test(vacancy)) {
        error.textContent = "Only numbers are allowed";
        return false;
    }
    if (parseInt(vacancy) <= 0) {
        error.textContent = "Number of vacancies must be greater than 0.";
        return false;
    }
    
    error.textContent = "";
    return true;
}

// Salary validation
function validateSalary() {
    const salary = document.getElementById('salary').value.trim();
    const error = document.getElementById('salaryerror');
    
    if (!salary) {
        error.textContent = "Salary is required.";
        return false;
    }
    if (!/^\d+$/.test(salary)) {
        error.textContent = "Only numbers are allowed";
        return false;
    }
    if (parseInt(salary) <= 0) {
        error.textContent = "Salary must be greater than 0.";
        return false;
    }
    
    error.textContent = "";
    return true;
}

// Last Date validation
function validateLastDate() {
    const lastDate = document.getElementById('last_date').value;
    const vacancyDate = document.getElementById('date').value;
    const error = document.getElementById('lastdateerror');
    
    if (!lastDate) {
        error.textContent = "Application deadline is required.";
        return false;
    }
    
    const deadline = new Date(lastDate);
    const today = new Date();
    const vacancy = new Date(vacancyDate);
    
    if (deadline < today) 
    {
        error.textContent = "Application deadline cannot be in the past.";
        return false;
    }
    if (deadline > vacancy) 
    {
        error.textContent = "Application deadline must be before vacancy date.";
        return false;
    }
    error.textContent = "";
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize time selectors
    let startTimeSelect = document.getElementById("start-time");
    let endTimeSelect = document.getElementById("end-time");

    // Generate time options
    function generateTimeOptions(selectElement) {
        selectElement.innerHTML = '<option value="">Select Time</option>';
        for (let hour = 0; hour < 24; hour++) {
            for (let min = 0; min < 60; min += 30) { // 30-minute intervals
                let formattedHour = hour % 12 || 12; // Convert to 12-hour format
                let amPm = hour < 12 ? "AM" : "PM";
                let formattedMinute = min.toString().padStart(2, "0");
                let timeValue = `${hour.toString().padStart(2, "0")}:${formattedMinute}`;
                let timeText = `${formattedHour}:${formattedMinute} ${amPm}`;
                
                let option = document.createElement('option');
                option.value = timeValue;
                option.textContent = timeText;
                selectElement.appendChild(option);
            }
        }
    }

    // Generate options for both selects
    generateTimeOptions(startTimeSelect);
    generateTimeOptions(endTimeSelect);

    // Add change event listeners
    startTimeSelect.addEventListener('change', validateTimeSelection);
    endTimeSelect.addEventListener('change', validateTimeSelection);
});

function validateDeliveryDocs() 
{
    const category = document.getElementById('category').value;
    if (category === "Delivery and logistics") {
        const license = document.getElementById('license_required').value;
        const badge = document.getElementById('badge_required').value;
        const licenseError = document.getElementById('licenseerror');
        const badgeError = document.getElementById('badgeerror');
        let isValid = true;

        if (!license) {
            licenseError.textContent = "Please select license requirement";
            isValid = false;
        } else {
            licenseError.textContent = "";
        }

        if (!badge) {
            badgeError.textContent = "Please select badge requirement";
            isValid = false;
        } else {
            badgeError.textContent = "";
        }

        return isValid;
    }
    return true;
}

// Working Hour validation
function validateWorkingHour() {
    return validateTimeSelection(); // Since we already have time validation
}

// Phone validation
function validatePhone() {
    const phone = document.getElementById('phone').value.trim();
    const error = document.getElementById('phoneerror');
    
    if (!phone) 
    {
        error.textContent = "Phone number is required.";
        return false;
    }
    else if(!/^[6-9]/.test(phone))
    {
        error.textContent = "Phone number must start with 6, 7, 8, or 9.";
        return false;
    }
    else if (!/^\d{10}$/.test(phone)) 
    {
        error.textContent = "Phone number must be 10 digits.";
        return false;
    }
    error.textContent = "";
    return true;
}

// Working Days validation
function validateWorkingDays() {
    const workingDays = document.getElementById('working_days').value;
    const error = document.getElementById('workingdayserror');
    
    if (!workingDays) {
        error.textContent = "Please select working days";
        return false;
    }
    
    error.textContent = "";
    return true;
}

// Add event listeners for real-time validation
document.addEventListener('DOMContentLoaded', function() 
{
    // Real-time validation for text inputs
    document.getElementById('job_title').addEventListener('input', validateJobTitle);
    document.getElementById('job_description').addEventListener('input', validateJobDescription);
    document.getElementById('working_hour').addEventListener('input', validateWorkingHour);
    document.getElementById('vacancy').addEventListener('input', validateVacancy);
    document.getElementById('salary').addEventListener('input', validateSalary);
    
    // Validation for date inputs
    document.getElementById('date').addEventListener('change', function() 
    {
        validateDate();
        validateLastDate(); // Revalidate last date when vacancy date changes
    });
    document.getElementById('last_date').addEventListener('change', validateLastDate);

    document.getElementById('category').addEventListener('change', validatecategory);
    document.getElementById('license_required').addEventListener('change', validateDeliveryDocs);
    document.getElementById('badge_required').addEventListener('change', validateDeliveryDocs);
});

// Main form validation
function validateForm() {
    let isValid = true;
    
    // Run all validations
    const validations = [
        validatecategory(),
        validateJobTitle(),
        validateLocation(),
        validateJobDescription(),
        validateWorkingHour(),
        validateDate(),
        validateVacancy(),
        validateSalary(),
        validateLastDate(),
        validateTimeSelection(),
        validateDeliveryDocs(),
        validatePhone(),
        validateWorkingDays()
    ];

    // If any validation fails, form is invalid
    if (validations.includes(false)) {
        isValid = false;
    }

    return isValid;
}

// Form submission handler
document.getElementById('add_job').addEventListener('submit', function(e) {
    if (!validateForm()) {
        e.preventDefault(); // Prevent form submission if validation fails
    }
});