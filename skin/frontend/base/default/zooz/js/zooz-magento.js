function startZooz(isSandbox, formObject, url, uniqueId, returnUrl, cancelUrl){
		var sandbox = (isSandbox==1);
		var customStyleA = "https://app.zooz.com/ZoozCustomWC3/company/base-styles-a.css";
		var customStyleB = "https://app.zooz.com/ZoozCustomWC3/company/base-styles-b.css";
		var customStyle = "";//customStyleB;
		//DYO.chooseVariation(93);
	//	if(zooz_button_color == 'gold')
	//		customStyle = customStyleB;
		
		jQuery('.zooz-payment-loading').show();
        	jQuery.ajax({
            		type: formObject.attr('method'),
            		url: url,
            		data: formObject.serialize(),
            		cache: false,
			success: function (response) {
            			jQuery('.zooz-payment-loading').hide();
                		eval(response);
				zoozStartCheckout({
					token : data.token,
					uniqueId : uniqueId,
					isSandbox : sandbox,
					customStylesheet: customStyle,
					returnUrl : returnUrl,
					cancelUrl : cancelUrl
					
				});
            		}
        	});
        return false;
}
