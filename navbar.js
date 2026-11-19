let lastScrollTop = 0;
const navbar = document.getElementById('main-navbar');

window.addEventListener('scroll', () => {
  const scrollTop = window.scrollY || document.documentElement.scrollTop;

  if (scrollTop > lastScrollTop) {
    // scrolling down → hide
    navbar.style.top = '-80px'; // adjust to navbar height
  } else {
    // scrolling up → show
    navbar.style.top = '0';
  }
  lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // avoid negative
});
document.addEventListener("mousemove", function(e) {
    const navbar = document.querySelector(".navbar");
    if (!navbar) return;

    if (e.clientY < 60) { 
        // Mouse is near top of window
        navbar.classList.add("visible");
    } else {
        // Mouse moves away — hide again
        navbar.classList.remove("visible");
    }
});
let lastScrollY = window.scrollY;
window.addEventListener('mousemove', function(e) {
    if (e.clientY < 80) { // if mouse moves to top of viewport
        document.querySelector('.navbar').classList.add('show');
    } else {
        document.querySelector('.navbar').classList.remove('show');
    }
});
