
jQuery(document).ready(function() {

	jQuery('#gal-tabs').find('a').click(function() {
			jQuery('#gal-tabs').find('a').removeClass('nav-tab-active');
			jQuery('.galtab').removeClass('active');
			var id = jQuery(this).attr('id').replace('-tab','');
			jQuery('#' + id + '-section').addClass('active');
			jQuery(this).addClass('nav-tab-active');
	});
	
	var active_tab = window.location.hash.replace('#','');
	
	if ( active_tab == '' || active_tab == '#_=_') {
		active_tab = jQuery('.galtab').attr('id');
	}

	var activeSection = jQuery('#' + active_tab + '-section');
	var activeTab = jQuery('#' + active_tab + '-tab');

	if (activeSection.length && activeTab.length) {
		jQuery('#gal-tabs').find('a').removeClass('nav-tab-active');
		jQuery('.galtab').removeClass('active');

		activeSection.addClass('active');
		activeTab.addClass('nav-tab-active');
	}
	
	// Dependent fields in premium
	clickfn = function() {
		jQuery('#ga_defaultrole').prop('disabled',  !jQuery('#input_ga_autocreate').is(':checked'));
	};
	jQuery('#input_ga_autocreate').on('click', clickfn);
	clickfn();
	
	clickfn2 = function() {
		jQuery('#input_ga_hidewplogin').prop('disabled',  !jQuery('#input_ga_disablewplogin').is(':checked'));
	};
	jQuery('#input_ga_disablewplogin').on('click', clickfn2);
	clickfn2();
}); 
