document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.querySelector('iframe.content-frame');
    const defaultPage = '/minipro/patdash/overview.php'; // Default page to load

    function openInIframe(src) {
        iframe.src = src;
        iframe.style.display = 'block';
        sessionStorage.setItem('patLastPage', src);  // Changed to patLastPage
        // Set flag to indicate user has navigated
        if (src !== defaultPage) {
            sessionStorage.setItem('patHasNavigated', 'true');  // Changed to patHasNavigated
        }
    }

    // Check if user has navigated before
    const hasNavigated = sessionStorage.getItem('patHasNavigated');  // Changed to patHasNavigated
    
    if (!hasNavigated) {
        // First time - show overview
        openInIframe(defaultPage);
    } else {
        // User has navigated before - show last page
        const lastPage = sessionStorage.getItem('patLastPage') || defaultPage;  // Changed to patLastPage
        openInIframe(lastPage);
    }
    document.getElementById('dash').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/overview.php');
    });
    // Add event listeners to sidebar items
    document.getElementById('profile').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/profile.php');
    });

    document.getElementById('pwd').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/forgot_pwd.php');
    });
    
    document.getElementById('messages').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/appt/appdec.php');
    }); 
    document.getElementById('msg').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/cons.php');
    }); 

    document.getElementById('appointment-booking').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/appt/appt.php');
    });


    document.getElementById('cancel_appt').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/appt/cancel_appt.php');
    });

    document.getElementById('logout').addEventListener('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Logout',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout!',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Clear session storage before logout
                sessionStorage.removeItem('patLastPage');
                sessionStorage.removeItem('patHasNavigated');
                window.location.href = '/minipro/signlog/logout.php';
            }
        });
    });
});
