<button id="reservation" class="button contact-area__button-appointment-booking" onclick="RESERVATION_MODAL.classList.toggle('is-active')"> Terminvereinbarung </button>

<div id="RESERVATION_MODAL" class="modal">
	<div class="modal-background"></div>
	<div class="modal-content">
		<?php echo do_shortcode('[contact-form-7 id="2731" title="Termin vereinbaren"]'); ?>
	</div>
	<button id="close" class="modal-close is-large" aria-label="close"></button>
</div>