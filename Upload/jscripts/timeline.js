$('.profile-posts').on('click', function(e) {
	$('.profile-tabs div').removeClass('active');
	$('.profile-posts').addClass('active');
	$('.profile-contents div').removeClass('active');
	$('.profile-posts-content').addClass('active');

});

$('.profile-about').on('click', function(e) {
	$('.profile-tabs div').removeClass('active');
	$('.profile-about').addClass('active');
	$('.profile-contents div').removeClass('active');
	$('.profile-about-content').addClass('active');	
});

$('.profile-friends').on('click', function(e) {
	$('.profile-tabs div').removeClass('active');
	$('.profile-friends').addClass('active');
	$('.profile-contents div').removeClass('active');
	$('.profile-friends-content').addClass('active');
	
});

$('.profile-threads').on('click', function(e) {
	$('.profile-tabs div').removeClass('active');
	$('.profile-threads').addClass('active');
	$('.profile-contents div').removeClass('active');
	$('.profile-threads-content').addClass('active');
	
});

$('.about-overview').on('click', function(e) {
	$('.about-menu div').removeClass('current');
	$('.about-overview').addClass('current');	
	$('.about-info div').removeClass('act');
	$('.about-info-overview').addClass('act');
});

$('.about-contact').on('click', function(e) {
	$('.about-menu div').removeClass('current');
	$('.about-contact').addClass('current');
	$('.about-info div').removeClass('act');
	$('.about-info-contact').addClass('act');
});

$('.about-activity').on('click', function(e) {
	$('.about-menu div').removeClass('current');
	$('.about-activity').addClass('current');
	$('.about-info div').removeClass('act');
	$('.about-info-activity').addClass('act');
});

$('.cpchange').on('click', function(e) {
	$('.cpchange').hide();
	$('.coverpicForm').show();
});