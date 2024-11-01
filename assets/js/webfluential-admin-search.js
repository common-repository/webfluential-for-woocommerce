
function webfluential_search( paging = 1, sort = 'rating' ) {
	var $ = jQuery;
	
	$( '#webfuential-wc-search-results' ).remove();
	
	$( '#webfuential-wc-search-form' ).block( {
		message: null,
		overlayCSS: {
			background: '#fff',
			opacity: 0.6
		}
	} );
		
	var data = {
		action:                  			'webfluential_search',
		webfluential_country: 				$('#webfluential_country').val(),
		webfluential_channel_facebook: 		$('#webfluential_channel_facebook').prop('checked') ? $('#webfluential_channel_facebook').val() : false,
		webfluential_channel_twitter: 		$('#webfluential_channel_twitter').prop('checked') ? $('#webfluential_channel_twitter').val() : false,
		webfluential_channel_instagram: 	$('#webfluential_channel_instagram').prop('checked') ? $('#webfluential_channel_instagram').val() : false,
		webfluential_channel_blogs: 		$('#webfluential_channel_blogs').prop('checked') ? $('#webfluential_channel_blogs').val() : false,
		webfluential_channel_youtube: 		$('#webfluential_channel_youtube').prop('checked') ? $('#webfluential_channel_youtube').val() : false,
		webfluential_market: 				$('#webfluential_market').val(),
		webfluential_age: 					$('#webfluential_age').val(),
		webfluential_paging:				paging,
		webfluential_sort: 					sort,
		webfluential_nonce:					$('#webfluential_nonce').val()
	};
	
	console.log(data);
	$.post( wf_wc_data.ajax_url, data, function( response ) {
		// console.log(response);
		$( '#webfuential-wc-search-form' ).unblock();
		if ( response.error ) {
			$( '#webfuential-wc-search-form' ).append('<div id="webfuential-wc-search-results" class="error">' + response.error + '</div>');
		} else {
			$( '#webfuential-wc-search-form' ).append('<div id="webfuential-wc-search-results">' + response.results + '</div>');
		}
	});				

	return false;
}
	

jQuery( document ).ready(function() {
	
	jQuery('#webfluential-search-btn').click(function(){
        webfluential_search();
    });

});
