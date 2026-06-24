document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const iframe = document.querySelector('iframe.content-frame');
    const defaultPage = '/minipro/docdash/others/overview.php'; // Default page to load

    function openInIframe(src) {
        iframe.src = src;
        iframe.style.display = 'block';
        sessionStorage.setItem('docLastPage', src);  // Changed to docLastPage
        // Set flag to indicate user has navigated
        if (src !== defaultPage) {
            sessionStorage.setItem('docHasNavigated', 'true');  // Changed to docHasNavigated
        }
    }

    // Check if user has navigated before
    const hasNavigated = sessionStorage.getItem('docHasNavigated');  // Changed to docHasNavigated
    
    if (!hasNavigated) {
        // First time - show overview
        openInIframe(defaultPage);
    } else {
        // User has navigated before - show last page
        const lastPage = sessionStorage.getItem('docLastPage') || defaultPage;  // Changed to docLastPage
        openInIframe(lastPage);
    }


    // Toggle dropdown menus
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdownMenu = document.getElementById(this.dataset.toggle + '-menu');
            const isActive = dropdownMenu.style.display === 'block';

            // Hide all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });

            // Show the clicked dropdown
            dropdownMenu.style.display = isActive ? 'none' : 'block';
            dropdownToggles.forEach(item => item.classList.remove('active'));
            if (!isActive) {
                this.classList.add('active');
            }
        });
    });

    // Event listeners for navigation
    document.getElementById('dash').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/others/overview.php');
    });
    document.getElementById('appt_list').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/appointments/appt_list.php');
    });

    document.getElementById('rqst').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/appointments/request.php');
    });

    document.getElementById('pat-list').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/patients/pat_list.php');
    });

    document.getElementById('reg_pat').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/patients/dreg.php');
    });

    document.getElementById('pat-rec').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/patients/rec.php');
    });
    document.getElementById('new-med').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/medicine/new_med.php');
    });
    document.getElementById('add-med').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/medicine/add_med.php');
    });
    document.getElementById('view-med').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/medicine/med_list.php');
    });
        
        document.getElementById('cons').addEventListener('click', function(e) {
            e.preventDefault();
            openInIframe('/minipro/docdash/consultation/pat.php');
        });

    
    document.getElementById('messages').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/docdash/others/messages.php');
    });

    document.getElementById('pwd').addEventListener('click', function(e) {
        e.preventDefault();
        openInIframe('/minipro/patdash/forgot_pwd.php');
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