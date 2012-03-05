(function($){
	$(document).ready(
		function() {
			AnyPriceRoundUpDonationModifier.init();
			AnyPriceRoundUpDonationModifier.hideAndSeekOtherValue();
		}
	);
})(jQuery);

var AnyPriceRoundUpDonationModifier = {

	formSelector: "#AnyPriceRoundUpDonationModifier_Form_AnyPriceRoundUpDonationModifier",

	actionsSelector: ".Actions",

	loadingClass: "loading",

	init: function() {
		var options = {
			beforeSubmit: AnyPriceRoundUpDonationModifier.showRequest,  // pre-submit callback
			success: AnyPriceRoundUpDonationModifier.showResponse,  // post-submit callback
			dataType: "json"
		};
		jQuery(AnyPriceRoundUpDonationModifier.formSelector).ajaxForm(options);
		jQuery(AnyPriceRoundUpDonationModifier.formSelector + " " + AnyPriceRoundUpDonationModifier.actionsSelector).hide();
		jQuery(AnyPriceRoundUpDonationModifier.formSelector+ " input").change(
			function() {
				if(jQuery(this).attr("type") == "checkbox") {
					AnyPriceRoundUpDonationModifier.hideAndSeekOtherValue();
				};
				jQuery(AnyPriceRoundUpDonationModifier.formSelector).submit();
			}
		);
	},

	// pre-submit callback
	showRequest: function (formData, jqForm, options) {
		jQuery(AnyPriceRoundUpDonationModifier.formSelector).addClass(AnyPriceRoundUpDonationModifier.loadingClass);
		return true;
	},

	// post-submit callback
	showResponse: function (responseText, statusText)  {
		jQuery(AnyPriceRoundUpDonationModifier.formSelector).removeClass(AnyPriceRoundUpDonationModifier.loadingClass);
		EcomCart.setChanges(responseText);
	},

	hideAndSeekOtherValue: function(){
		if(jQuery("input[name='AddDonation']").is(":checked")) {
			jQuery("#OtherValue").hide();
			jQuery("#OtherValue input").val(0.00);
		}
		else {
			jQuery("#OtherValue").show();
		}
	}



}

