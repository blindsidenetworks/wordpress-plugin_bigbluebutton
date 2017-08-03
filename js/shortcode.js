jQuery(document).ready(function($){
	renderShortcode();

	$('input[name=bbb_link_type]').change(function() {
		renderShortcode();
	});
	$('select#bbb-categories').change(function() {
		renderShortcode();
	});
	$('select#bbb-post-ids').change(function() {
		renderShortcode();
	});
});

function renderShortcode() {
    var linktype = $('input[name=bbb_link_type]:checked').val();
    var linktypestring = ' link_type="' +  linktype + '"';
    var categories = $('select#bbb-categories').val() || [];
    var categories_string = '';
    if (categories.length) {
        categories_string = ' bbb_categories="' + categories.join(",") + '" ';
    }
    var postids = $('select#bbb-post-ids').val() || [];
    var postsidstring = '';
    if (postids.length) {
        postsidstring = ' bbb_posts="' + postids.join(",") +'" ';
    }
    $('p#shortcode').text('[bbb ' + linktypestring + categories_string  + postsidstring + ']');
}

function goToNewPageNew(dropdownlist) {
    var url = dropdownlist.options[dropdownlist.selectedIndex].value;
    if (url !== "") {
        window.open(url);
    }
}
