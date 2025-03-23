document.addEventListener('DOMContentLoaded', function() {
    const interviewType = document.getElementById('interview_type');
    const physicalFields = document.getElementById('physical_fields');
    const onlineFields = document.getElementById('online_fields');
    const phoneFields = document.getElementById('phone_fields');

    // Function to show/hide fields based on selection
    function showFields() {
        // Hide all fields first
        physicalFields.style.display = 'none';
        onlineFields.style.display = 'none';
        phoneFields.style.display = 'none';

        // Show the selected field
        switch(interviewType.value) {
            case 'Physical':
                physicalFields.style.display = 'block';
                break;
            case 'Online':
                onlineFields.style.display = 'block';
                break;
            case 'Phone':
                phoneFields.style.display = 'block';
                break;
        }
    }

    // Add event listener to the dropdown
    if(interviewType) {
        interviewType.addEventListener('change', showFields);
        // Show initial fields based on selected value
        showFields();
    }
}); 