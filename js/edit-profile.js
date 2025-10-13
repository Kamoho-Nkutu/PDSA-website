document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const editProfileForm = document.getElementById('edit-profile-form');
    const logoutBtn = document.getElementById('logout-btn');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navList = document.querySelector('.nav-list');

    // Current user data (in a real app, this would come from an API)
    const currentUser = {
        firstName: "John",
        lastName: "Doe",
        email: "john@example.com",
        phone: "+44 7123 456789",
        dob: "1985-03-15",
        addressLine1: "123 Pet Street",
        addressLine2: "",
        city: "London",
        postcode: "SW1A 1AA",
        country: "UK",
        primaryOwner: "yes",
        vetPreferences: "dr_smith",
        emailNotifications: true,
        smsNotifications: true,
        postalMail: false
    };

    // Initialize form with user data
    function initFormData() {
        document.getElementById('first-name').value = currentUser.firstName;
        document.getElementById('last-name').value = currentUser.lastName;
        document.getElementById('email').value = currentUser.email;
        document.getElementById('phone').value = currentUser.phone;
        document.getElementById('dob').value = currentUser.dob;
        document.getElementById('address-line1').value = currentUser.addressLine1;
        document.getElementById('address-line2').value = currentUser.addressLine2;
        document.getElementById('city').value = currentUser.city;
        document.getElementById('postcode').value = currentUser.postcode;
        document.getElementById('country').value = currentUser.country;
        
        // Radio buttons
        document.querySelector(`input[name="primary_owner"][value="${currentUser.primaryOwner}"]`).checked = true;
        
        // Select dropdown
        document.getElementById('vet-preferences').value = currentUser.vetPreferences;
        
        // Checkboxes
        document.querySelector('input[name="email_notifications"]').checked = currentUser.emailNotifications;
        document.querySelector('input[name="sms_notifications"]').checked = currentUser.smsNotifications;
        document.querySelector('input[name="postal_mail"]').checked = currentUser.postalMail;
    }

    // Form Validation
    function validateForm() {
        let isValid = true;
        const requiredFields = editProfileForm.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                isValid = false;
            } else {
                field.classList.remove('error');
            }
        });

        // Email validation
        const emailField = document.getElementById('email');
        if (emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
            emailField.classList.add('error');
            isValid = false;
        }

        return isValid;
    }

    // Form Submission
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                // In a real app, this would submit to a server
                const formData = {
                    firstName: document.getElementById('first-name').value,
                    lastName: document.getElementById('last-name').value,
                    email: document.getElementById('email').value,
                    phone: document.getElementById('phone').value,
                    dob: document.getElementById('dob').value,
                    addressLine1: document.getElementById('address-line1').value,
                    addressLine2: document.getElementById('address-line2').value,
                    city: document.getElementById('city').value,
                    postcode: document.getElementById('postcode').value,
                    country: document.getElementById('country').value,
                    primaryOwner: document.querySelector('input[name="primary_owner"]:checked').value,
                    vetPreferences: document.getElementById('vet-preferences').value,
                    emailNotifications: document.querySelector('input[name="email_notifications"]').checked,
                    smsNotifications: document.querySelector('input[name="sms_notifications"]').checked,
                    postalMail: document.querySelector('input[name="postal_mail"]').checked
                };

                // Simulate API call
                setTimeout(() => {
                    showNotification('Profile updated successfully!', 'success');
                    // In a real app, you might redirect or update the UI
                }, 1000);
            } else {
                showNotification('Please fill in all required fields correctly.', 'error');
            }
        });
    }

    // Input Validation on Blur
    const formInputs = editProfileForm.querySelectorAll('input, select');
    formInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.required && !this.value.trim()) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
            
            // Special validation for email
            if (this.type === 'email' && this.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
                this.classList.add('error');
            }
        });
    });

    // Mobile Menu Toggle
    if (mobileMenuBtn && navList) {
        mobileMenuBtn.addEventListener('click', function() {
            navList.classList.toggle('active');
            this.classList.toggle('open');
        });
    }

    // Logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // In a real app, this would call a logout API
            localStorage.removeItem('authToken');
            sessionStorage.removeItem('user');
            window.location.href = 'login.html';
        });
    }

    // Notification Function
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-notification">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
        
        notification.querySelector('.close-notification').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }

    // Initialize the form
    initFormData();
});