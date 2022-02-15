//
// carousel-slider.js
//

$(document).ready(function(){
	
	// Initialize all div with carousel class
	var carousels = bulmaCarousel.attach('.hero-carousel', {
		  initialSlide: 0,
			slidesToScroll: 1,
		  slidesToShow: 1,
		  navigation: true,
		  navigationKeys: true,
		  effect: 'fade',
		  duration: '3000',
		  autoplay: true,
		  autoplaySpeed: '7000',
			loop: true,
		  infinite: true,
	});
	
	
	// Loop on each carousel initialized
	for(var i = 0; i < carousels.length; i++) {
		// Add listener to  event
		carousels[i].on('before:show', state => {
			console.log(state);
		});
	}
	
	// Access to bulmaCarousel instance of an element
	var element = document.querySelector('#carousel-slider');
	if (element && element.bulmaCarousel) {
		// bulmaCarousel instance is available as element.bulmaCarousel
		element.bulmaCarousel.on('before-show', function(state) {
			console.log(state);
		});
	}
	
});