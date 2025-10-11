document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const devicesList = document.querySelector('.devices-list');
    const signOutAllBtn = document.getElementById('sign-out-all');
    const confirmModal = document.getElementById('confirm-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const confirmActionBtn = document.getElementById('confirm-action');
    const cancelActionBtn = document.getElementById('cancel-action');
    const closeModalBtn = document.querySelector('.close-modal');
    const logoutBtn = document.getElementById('logout-btn');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navList = document.querySelector('.nav-list');

    // Device data (in a real app, this would come from an API)
    const devices = [
        {
            id: 'device1',
            name: 'MacBook Pro',
            type: 'laptop',
            browser: 'Chrome',
            os: 'macOS',
            location: 'London, UK',
            lastActive: 'Just now',
            ip: '192.168.1.100',
            current: true,
            suspicious: false
        },
        {
            id: 'device2',
            name: 'iPhone 13',
            type: 'mobile',
            browser: 'Safari',
            os: 'iOS',
            location: 'Manchester, UK',
            lastActive: '2 hours ago',
            ip: '86.145.32.10',
            current: false,
            suspicious: false
        },
        {
            id: 'device3',
            name: 'iPad Air',
            type: 'tablet',
            browser: 'Safari',
            os: 'iPadOS',
            location: 'London, UK',
            lastActive: '3 days ago',
            ip: '86.145.32.12',
            current: false,
            suspicious: false
        },
        {
            id: 'device4',
            name: 'Windows PC',
            type: 'desktop',
            browser: 'Firefox',
            os: 'Windows',
            location: 'Birmingham, UK',
            lastActive: '2 weeks ago',
            ip: '81.103.45.67',
            current: false,
            suspicious: true
        }
    ];

    // Device type icons mapping
    const deviceIcons = {
        'laptop': 'fa-laptop',
        'mobile': 'fa-mobile-alt',
        'tablet': 'fa-tablet-alt',
        'desktop': 'fa-desktop'
    };

    // Initialize the devices list
    function initDevicesList() {
        devicesList.innerHTML = '';
        
        devices.forEach(device => {
            const deviceCard = document.createElement('div');
            deviceCard.className = `device-card ${device.current ? 'current-device' : ''}`;
            deviceCard.dataset.deviceId = device.id;
            
            deviceCard.innerHTML = `
                <div class="device-header">
                    <div class="device-info">
                        <div class="device-icon">
                            <i class="fas ${deviceIcons[device.type]}"></i>
                        </div>
                        <div>
                            <div class="device-name">${device.name}${device.current ? ' (Current Device)' : ''}</div>
                            <div class="device-meta">${device.browser} • ${device.os} • ${device.location}</div>
                        </div>
                    </div>
                    <div class="device-actions">
                        ${device.current ? 
                            '<span class="badge badge-success">Active now</span>' : 
                            `<button class="btn btn-outline btn-sm sign-out-btn" data-device-id="${device.id}">
                                <i class="fas fa-sign-out-alt"></i> Sign out
                            </button>`
                        }
                    </div>
                </div>
                <div class="last-active">
                    Last active: ${device.lastActive} • IP: ${device.ip}
                </div>
                ${device.suspicious ? 
                    `<div class="security-alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Unusual login location detected</span>
                    </div>` : ''
                }
            `;
            
            devicesList.appendChild(deviceCard);
        });
        
        // Add event listeners to sign out buttons
        document.querySelectorAll('.sign-out-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const deviceId = this.dataset.deviceId;
                showSignOutConfirmation(deviceId);
            });
        });
    }

    // Show sign out confirmation modal
    function showSignOutConfirmation(deviceId) {
        const device = devices.find(d => d.id === deviceId);
        if (!device) return;
        
        modalTitle.textContent = 'Sign Out Device';
        modalMessage.textContent = `Are you sure you want to sign out your ${device.name} (${device.os})?`;
        confirmActionBtn.textContent = 'Sign Out';
        confirmActionBtn.dataset.action = 'sign-out';
        confirmActionBtn.dataset.deviceId = deviceId;
        
        confirmModal.classList.add('active');
    }

    // Show sign out all confirmation modal
    function showSignOutAllConfirmation() {
        modalTitle.textContent = 'Sign Out All Devices';
        modalMessage.textContent = 'This will sign you out of all devices except this one. Are you sure?';
        confirmActionBtn.textContent = 'Sign Out All';
        confirmActionBtn.dataset.action = 'sign-out-all';
        
        confirmModal.classList.add('active');
    }

    // Sign out a specific device
    function signOutDevice(deviceId) {
        // In a real app, this would call an API
        console.log(`Signing out device: ${deviceId}`);
        
        // Remove device from the list (simulating API call)
        const index = devices.findIndex(d => d.id === deviceId);
        if (index !== -1) {
            devices.splice(index, 1);
            initDevicesList();
        }
        
        showNotification(`Device has been signed out successfully.`, 'success');
    }

    // Sign out all devices
    function signOutAllDevices() {
        // In a real app, this would call an API
        console.log('Signing out all devices');
        
        // Keep only the current device (simulating API call)
        const currentDevice = devices.find(d => d.current);
        devices.length = 0;
        if (currentDevice) {
            devices.push(currentDevice);
        }
        
        initDevicesList();
        showNotification(`All other devices have been signed out successfully.`, 'success');
    }

    // Close modal
    function closeModal() {
        confirmModal.classList.remove('active');
    }

    // Show notification
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

    // Event Listeners
    signOutAllBtn.addEventListener('click', showSignOutAllConfirmation);
    
    confirmActionBtn.addEventListener('click', function() {
        const action = this.dataset.action;
        
        if (action === 'sign-out') {
            const deviceId = this.dataset.deviceId;
            signOutDevice(deviceId);
        } else if (action === 'sign-out-all') {
            signOutAllDevices();
        }
        
        closeModal();
    });
    
    cancelActionBtn.addEventListener('click', closeModal);
    closeModalBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    confirmModal.addEventListener('click', function(e) {
        if (e.target === confirmModal) {
            closeModal();
        }
    });
    
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
    
    // Mobile menu toggle
    if (mobileMenuBtn && navList) {
        mobileMenuBtn.addEventListener('click', function() {
            navList.classList.toggle('active');
            this.classList.toggle('open');
        });
    }

    // Initialize the page
    initDevicesList();
});