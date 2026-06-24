function bookConsultation() {
    window.location.href = '/minipro/signlog/log.html?redirectToBooking=true';
}

function login() {
    window.location.href = '/minipro/signlog/log.html';
}

function signUp() {
    window.location.href = '/minipro/signlog/sign.php'; 
}

function toggleMenu() {
    var button = document.querySelector('.menu-button');
    var dropdown = document.querySelector('.menu-dropdown');
    button.classList.toggle('active');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}
