document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const userAvatarSmall = document.getElementById('user-avatar-small');
    const userAvatarLarge = document.getElementById('user-avatar-large');
    const changeAvatarBtn = document.getElementById('change-avatar-btn');
    const avatarUpload = document.getElementById('avatar-upload');
    const avatarModal = document.getElementById('avatar-modal');
    const closeModal = document.querySelector('.close-modal');
    const cancelAvatarChange = document.getElementById('cancel-avatar-change');
    const saveAvatarBtn = document.getElementById('save-avatar');
    const uploadArea = document.getElementById('upload-area');
    const avatarPreview = document.getElementById('avatar-preview');
    const previewImage = document.getElementById('preview-image');
    const defaultAvatars = document.querySelectorAll('.default-avatar');
    const printProfileBtn = document.getElementById('print-profile');
    const enable2faBtn = document.getElementById('enable-2fa');
    const logoutBtn = document.getElementById('logout-btn');

    // Current user data (in a real app, this would come from an API)
    const currentUser = {
        name: "John Doe",
        email: "john@example.com",
        phone: "+44 7123 456789",
        address: "123 Pet Street, London, UK, SW1A 1AA",
        dob: "15/03/1985",
        memberSince: "15 June 2020",
        avatar: "assets/images/user-avatar.jpg"
    };

    // Initialize profile data
    function initProfileData() {
        document.getElementById('user-name').textContent = currentUser.name;
        document.getElementById('user-email').textContent = currentUser.email;
        document.getElementById('user-phone').textContent = currentUser.phone;
        document.getElementById('user-address').textContent = currentUser.address;
        document.getElementById('user-dob').textContent = currentUser.dob;
        document.getElementById('member-since').textContent = currentUser.memberSince;
        
        if (userAvatarSmall) userAvatarSmall.src = currentUser.avatar;
        if (userAvatarLarge) userAvatarLarge.src = currentUser.avatar;
    }

    // Avatar Upload Handling
    if (changeAvatarBtn && avatarUpload) {
        changeAvatarBtn.addEventListener('click', () => avatarUpload.click());
        
        avatarUpload.addEventListener('change', function(e) {
            if (e.target.files.length) {
                const file = e.target.files[0];
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        previewImage.src = event.target.result;
                        avatarPreview.style.display = 'block';
                        uploadArea.style.display = 'none';
                        saveAvatarBtn.disabled = false;
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        });
    }

    // Avatar Modal Handling
    if (changeAvatarBtn) {
        changeAvatarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            avatarModal.classList.add('active');
        });
    }

    // Close Modal
    function closeAvatarModal() {
        avatarModal.classList.remove('active');
        uploadArea.style.display = 'flex';
        avatarPreview.style.display = 'none';
        saveAvatarBtn.disabled = true;
    }

    if (closeModal) closeModal.addEventListener('click', closeAvatarModal);
    if (cancelAvatarChange) cancelAvatarChange.addEventListener('click', closeAvatarModal);

    // Default Avatar Selection
    defaultAvatars.forEach(avatar => {
        avatar.addEventListener('click', function() {
            defaultAvatars.forEach(a => a.classList.remove('selected'));
            this.classList.add('selected');
            previewImage.src = this.src;
            avatarPreview.style.display = 'block';
            uploadArea.style.display = 'none';
            saveAvatarBtn.disabled = false;
        });
    });

    // Save New Avatar
    if (saveAvatarBtn) {
        saveAvatarBtn.addEventListener('click', function() {
            // In a real app, this would upload to a server
            const newAvatar = previewImage.src;
            currentUser.avatar = newAvatar;
            
            if (userAvatarSmall) userAvatarSmall.src = newAvatar;
            if (userAvatarLarge) userAvatarLarge.src = newAvatar;
            
            showNotification('Profile picture updated successfully!', 'success');
            closeAvatarModal();
        });
    }

    // Print Profile
    if (printProfileBtn) {
        printProfileBtn.addEventListener('click', function() {
            window.print();
        });
    }

    // Enable 2FA
    if (enable2faBtn) {
        enable2faBtn.addEventListener('click', function() {
            // In a real app, this would initiate 2FA setup
            showNotification('Two-factor authentication setup initiated. Check your email for instructions.', 'info');
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

    // Drag and Drop for Avatar Upload
    if (uploadArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            uploadArea.classList.add('highlight');
        }

        function unhighlight() {
            uploadArea.classList.remove('highlight');
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            avatarUpload.files = files;
            const event = new Event('change');
            avatarUpload.dispatchEvent(event);
        }
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

    // Initialize the page
    initProfileData();
});