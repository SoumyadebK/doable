/**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    mobileNavToggleBtn.classList.toggle('bi-list');
    mobileNavToggleBtn.classList.toggle('bi-x');
  }
  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
  }

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navmenu a').forEach(navmenu => {
    navmenu.addEventListener('click', () => {
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
    });

  });

  /**
   * Toggle mobile nav dropdowns
   */
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(navmenu => {
    navmenu.addEventListener('click', function(e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation();
    });
  });

  

  /**
   * Scroll top button
   */
  let scrollTop = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  scrollTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);
/** Animation on scroll function and init **/
function aosInit() {
  AOS.init({
    duration: 600,
    easing: 'ease-in-out',
    once: true,
    mirror: false
  });
}
window.addEventListener('load', aosInit);

/**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

$(document).ready(function () {
  

  $('.hero-carousel').owlCarousel({
      loop:true,
      margin:0,
      nav:false,
      autoplay: true,
      items:1,
      dots: false,
      autoplayTimeout:3000,
      animateOut: 'fadeOut' 
  });
  $('.product-carousel').owlCarousel({
      loop:true,
      margin:20,
      nav:false,
      autoplay: true,
      dots: true,
      autoplayTimeout:3000,
      responsive:{
        0:{
            items:2,
        },
        600:{
            items:3,
        },
        1000:{
            items:3,
        },
        1200:{
            items:4,
        }
    }
  });

  $('.testimonial-carousel').owlCarousel({
      loop:true,
      margin:20,
      nav:false,
      autoplay: true,
      dots: true,
      autoplayTimeout:3000,
      responsive:{
        0:{
            items:1,
        },
        600:{
            items:2,
        },
        1000:{
            items:3,
        },
        1200:{
            items:3,
        }
    }
  });

  $('.watch-carousel').owlCarousel({
      loop:true,
      margin:0,
      nav:false,
      autoplay: true,
      dots: true,
      autoplayTimeout:3000,
      responsive:{
        0:{
            items:2,
        },
        600:{
            items:3,
        },
        1000:{
            items:4,
        },
        1200:{
            items:5,
        }
    }
  });


  $(".ct-header-search").on("click", function () {
    $(".searchbar-area").addClass("active");
  });
  $(".search-close").on("click", function () {
    $(".searchbar-area").removeClass("active");
  });


  // Open Cart
  $(".openCart").click(function () {
    $(".cart-sidebar").addClass("active");
    $(".cart-overlay").fadeIn();
  });

  // Close Cart
  $("#closeCart, .cart-overlay").click(function () {
    $(".cart-sidebar").removeClass("active");
    $(".cart-overlay").fadeOut();
  });

  // Increase Quantity
  $(".increase").click(function () {
    let qtyInput = $(this).siblings(".qty");
    let qty = parseInt(qtyInput.val());
    qtyInput.val(qty + 1);
    updateTotal();
  });

  // Decrease Quantity
  $(".decrease").click(function () {
    let qtyInput = $(this).siblings(".qty");
    let qty = parseInt(qtyInput.val());
    if (qty > 1) {
      qtyInput.val(qty - 1);
      updateTotal();
    }
  });

  function updateTotal() {
    let price = parseInt($(".price").text());
    let qty = parseInt($(".qty").val());
    $("#totalPrice").text(price * qty);
  }
});


