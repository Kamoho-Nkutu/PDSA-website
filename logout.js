document.getElementById('logout-btn').addEventListener('click', function(e) {
    e.preventDefault();
    
    // In a real app, this would call your logout API
    fetch('/api/auth/logout', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
            // Clear client-side authentication state
            localStorage.removeItem('authToken');
            sessionStorage.removeItem('user');
            
            // Redirect to login page
            window.location.href = 'login.html';
        } else {
            alert('Logout failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        alert('An error occurred during logout.');
    });
});