(function( $ ){
	//set equal height for element
	$.fn.suploResize = function() {
		var maxHeight = 0;
		$(this).each(function(){
			if(maxHeight < $(this).height()){
				maxHeight = $(this).height();
			}
		})
		$(this).height(maxHeight);
	};

	//add product to cart
	//id : id của sản phẩm muốn thêm vào giỏ hàng
	//quantity: số lượng sản phẩm muôn add vào giỏ hàng
	//properties: thông tin thêm sản phẩm theo đơn hàng vd: {Note: "sản phẩm này đã được thêm ghi chú", Thêm: "Một 1 người"}
	$.fn.addProduct = function(id, quantity, properties, callback){
		var params = {
			type: 'POST',
			url: '/cart/add.js',
			async : false,
			data: {quantity:quantity,id:id, properties: properties},
			dataType: 'json',
			success: function(line_item) {
				if (typeof properties === 'function' && arguments.length < 3) {
					callback = properties;
					properties = null;
					callback();
				}else{
					callback();
				}
			},
			error: function(XMLHttpRequest, textStatus) {
				Haravan.onError(XMLHttpRequest, textStatus);
			}
		};
		jQuery.ajax(params);
	}

	$.fn.updateCartAttributes = function(attributes, callback){
		var params = {
			type: 'POST',
			url: '/cart/update.js',
			async : false,
			data: {attributes: attributes},
			dataType: 'json',
			success: function(line_item) {
				if (typeof callback === 'function') {
					callback();
				}
			},
			error: function(XMLHttpRequest, textStatus) {
				Haravan.onError(XMLHttpRequest, textStatus);
			}
		};
		jQuery.ajax(params);
	}

})( jQuery );

/* Tablinks*/
$('.tablinks').on('click', function(){
	var target = $(this).data('target');
	var content = $(this).data('content');
	$('.tablinks').removeClass('active');
	$(this).addClass('active');
	$(content).hide();
	$(target).fadeIn( "fast" );
})

/*Accordion with symbols  - Copyright www.w3schools.com*/
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
	acc[i].onclick = function() {
		this.classList.toggle("active");
		var panel = this.nextElementSibling;
		if (panel.style.maxHeight){
			panel.style.maxHeight = null;
		} else {
			panel.style.maxHeight = panel.scrollHeight + 1000 + "px";
		} 
	}
}

/* Owl carousel */
var navRightText = '<i class="fa fa-angle-right" aria-hidden="true"></i>';
var navLeftText = '<i class="fa fa-angle-left" aria-hidden="true"></i>';

$(function(){
	$(".owl-carousel").each(function(){
		if($(this).data('enable')){
			var config = {
				navigationText: [navRightText, navLeftText]
			};
			var owl = $(this);
			if( $(this).data( 'items') == 1 ){
				config.singleItem = true;
			}else {
				config.items = $(this).data('items');
			}
			if ($(this).data('items')) {
				config.itemsDesktop = $(this).data('items');
			}
			if ($(this).data('desktop')) {
				config.itemsDesktop = $(this).data('desktop');
			}
			if ($(this).data('desktopsmall')) {
				config.itemsDesktopSmall = $(this).data('desktopsmall');
			}
			if ($(this).data('tablet')) {
				config.itemsTablet = $(this).data('tablet');
			}
			if ($(this).data('tabletsmall')) {
				config.itemsTabletSmall = $(this).data('tabletsmall');
			}
			if ($(this).data('mobile')) {
				config.itemsMobile = $(this).data('mobile');
			}
			if ($(this).data('autoplay')) {
				config.autoPlay = $(this).data('autoplay');
			}
			if ($(this).data('nav')) {
				config.navigation = $(this).data('nav');
			}

			$(this).owlCarousel( config );
		}
	});
})

//load ngay khi trang sẵn sàng
$(document).ready(function(){
	//tabslink
	if($('.tablinks').length > 0){
		$('.tablinks:first').trigger('click');
	}
})

//on load sẽ check khi nào mà trang được load xong thì mới chạy function bên trong nó
$(window).on('load', function(){
	$('.text-blog-resize').suploResize();
})
$(window).on('load', function(){
	$('.text-product-resize').suploResize();
})
$(window).on('load', function(){
	$('.thumbnail-resize').suploResize();
})
$(window).on('load', function(){
	$('.image-resize-retated').suploResize();
})
