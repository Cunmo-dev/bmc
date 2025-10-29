
/*load ajax once time*/
$(".load-ajax").click(function () {
	var _this = $(this);
	var _url = $(this).data('url');
	var _container = $(this).data('container');
	$(_container).append('<div class="loading-ajax-once-time">Đang cập nhật dữ liêu...</div>')
	$.ajax({
		url: _url,
		async: false,
		type: 'GET',
		success: function (data) {
			$(_container).remove('.loading-ajax-once-time');
			$(_container).html(data);
			_this.removeClass('load-ajax');
		},
		complete: function () {
		}
	});
});
function openTab(evt, tabName) {
	var i, tabcontent, tablinks;
	tabcontent = document.getElementsByClassName("tabcontent");
	for (i = 0; i < tabcontent.length; i++) {
		tabcontent[i].style.display = "none";
	}
	tablinks = document.getElementsByClassName("tablinks");
	for (i = 0; i < tablinks.length; i++) {
		tablinks[i].className = tablinks[i].className.replace(" active", "");
	}
	document.getElementById(tabName).style.display = "block";
	evt.currentTarget.className += " active";
}




/* Owl carousel */
var navRightText = '<i class="fa fa-angle-left" aria-hidden="true"></i>';
var navLeftText = '<i class="fa fa-angle-right" aria-hidden="true"></i>';

$(function () {

	$(".owl-carousel.owl-enable").each(function () {
		var config = {
			margin: 10,
			lazyLoad: true,
			navigationText: [navRightText, navLeftText]
		};

		var owl = $(this);
		if ($(this).data('slide') == 1) {
			config.singleItem = true;
		} else {
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

		$(this).owlCarousel(config);
	});
})

jQuery(document).ready(function () {
	if ($('.slides li').size() > 0) {
		$(".hrv-banner-container .slides").owlCarousel({
			singleItem: true,
			autoPlay: 5000,
			items: 1,
			itemsDesktop: [1199, 1],
			itemsDesktopSmall: [980, 1],
			itemsTablet: [768, 1],
			itemsMobile: [479, 1],
			slideSpeed: 500,
			paginationSpeed: 500,
			rewindSpeed: 500,
			addClassActive: true,
			lazyLoad: true,
			navigation: true,
			stopOnHover: true,
			pagination: false,
			scrollPerPage: true,
			afterMove: nextslide,
			afterInit: nextslide,
		});
	}
	if ($('#ProductThumbs').length) {
		var productThumb = $('#ProductThumbs');
		var productThumbInner = $('#ProductThumbs .inner');
		var productFeatureImage = $('#ProductPhoto');
		var thumbControlUp = $('.product-thumb-control .up');
		var thumbControlDown = $('.product-thumb-control .down');

		if ($(window).width() < 769) {
			productThumbInner.addClass('owl-carousel');
			productThumbInner.owlCarousel({
				items: 3,
				margin: 10,
				itemsTablet: [768, 3],
				itemsMobile: [479, 3],
			});
		} else {
			var _temp = 0;
			var _mt = parseInt(productThumbInner.css("margin-top"));
			var _maxScroll = productThumb.height() - productThumbInner.height();
			if (_maxScroll === 0) {
				$('.product-thumb-control').remove();
			}
			thumbControlUp.click(function () {
				_temp = _mt + 110;
				console.log(_mt);
				if (_temp > 0) {
					_mt = 0;
					productThumbInner.css("margin-top", _mt)
				} else {
					_mt = _temp;
					productThumbInner.css("margin-top", _mt)
				}
			});
			thumbControlDown.click(function () {
				_temp = _mt - 110;
				if (_temp < _maxScroll) {
					_mt = _maxScroll;
					productThumbInner.css("margin-top", _mt)
				} else {
					_mt = _temp;
					productThumbInner.css("margin-top", _mt)
				}
			});
		}
	}
})


/* variant click */

function convertToSlug(str) {

	str = str.toLowerCase();
	str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
	str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
	str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
	str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
	str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
	str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
	str = str.replace(/đ/g, "d");
	str = str.replace(/!|@|%|\^|\*|\(|\)|\+|\=|\<|\>|\?|\/|,|\.|\:|\;|\'| |\"|\&|\#|\[|\]|~|$|_/g, "-");
	str = str.replace(/-+-/g, "-");
	str = str.replace(/^\-+|\-+$/g, "");
	return str;
}

var swatch_size = 0;
jQuery(document).ready(function () {

	jQuery('#productQuickView').on('click', '.swatch-element label, .swatch-element .topping-content, .swatch-element .topping-name, .swatch-element .topping-price, .swatch-element img', function (e) {
		e.stopPropagation();

		// Nếu click trực tiếp vào input thì không làm gì cả
		if (e.target.tagName.toLowerCase() === 'input') {
			return;
		}

		var $swatchElement = $(this).closest('.swatch-element');
		var $input = $swatchElement.find('input.input-quickview');

		// Chỉ kích hoạt sự kiện nếu input chưa được checked
		if (!$input.prop('checked')) {
			$input.prop('checked', true).trigger('change');
		} else {
			$input.prop('checked', false).trigger('change');
		}
	});
	jQuery('#productQuickView').on('touchstart', '.swatch-element label, .swatch-element .topping-content, .swatch-element .topping-name, .swatch-element .topping-price, .swatch-element img', function (e) {
		e.stopPropagation();

		// Nếu click trực tiếp vào input thì không làm gì cả
		if (e.target.tagName.toLowerCase() === 'input') {
			return;
		}

		var $swatchElement = $(this).closest('.swatch-element');
		var $input = $swatchElement.find('input.input-quickview');

		// Chỉ kích hoạt sự kiện nếu input chưa được checked
		if (!$input.prop('checked')) {
			$input.prop('checked', true).trigger('change');
		} else {
			$input.prop('checked', false).trigger('change');
		}
	});

	jQuery('#productQuickView').on('click', 'input.input-quickview', function (e) {
		var $this = $(this);

		// Lấy số lượng hiện tại từ phần tử hiển thị
		var countElement = $this.closest('.swatch-element').find('.topping-count');
		// Kiểm tra trạng thái chọn hiện tại
		var isSelected = $this.next().hasClass('sd');

		var count;
		if (!isSelected) {
			// Nếu chưa được chọn, đặt count = 1 (lần đầu tiên)
			count = 1;
		} else {
			// Nếu đã được chọn, lấy giá trị hiện tại và tăng lên
			count = parseInt(countElement.text()) || 0;
			count = (count % 2) + 1;
		}

		// Cập nhật giá trị lên giao diện
		countElement.text(count);

		// Hiển thị hoặc ẩn số lượng
		if (count === 2) {
			// Lần thứ 4: Bỏ chọn và ẩn số lượng
			$this.next().removeClass('sd');
			countElement.css('display', 'none');
		} else {
			// Lần 1-3: Chọn và hiển thị số lượng
			$this.next().addClass('sd');
			countElement.css('display', 'block');
		}

		// Lấy tên và giá trị của topping
		var name = $this.attr('name');
		var value = $this.val();

		// Cập nhật giá trị trong select tương ứng (nếu cần)
		$('#productQuickView select[data-option=' + name + ']').val(value).trigger('change');

		// Nếu có hình ảnh liên quan, cập nhật hiển thị hình ảnh
		if ($this.data('img-src')) {
			var img_ = $this.data('img-src');
			$('#productQuickView .product-single__thumbnail[href="' + img_ + '"]').trigger('click');
		}

		// Xử lý logic cho các trường hợp swatch size, nếu có
		var swatch_size = parseInt($('#productQuickView .select-swatch').children().size());
		if (swatch_size > 1) {
			handleSwatchLogic($this, name, swatch_size);
		}
	});

	// Hàm khởi tạo phần tử hiển thị số lượng
	function initializeToppingCounts() {
		$('.swatch-element').each(function () {
			var $this = $(this);
			if ($this.find('.topping-count').length === 0) {
				$this.append('<div class="topping-count" style="position: absolute; top: 0; right: 0; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; font-size: 12px; display: none;">0</div>');
			}
		});
	}

	// Gọi hàm khởi tạo khi tài liệu sẵn sàng
	jQuery(document).ready(function () {
		initializeToppingCounts();
	});

	// Hàm xử lý logic cho swatch size
	function handleSwatchLogic($this, name, swatch_size) {
		if (swatch_size == 2) {
			if (name.indexOf('1') != -1) {
				enableSwatchOptions(1);
			}
		} else if (swatch_size == 3) {
			if (name.indexOf('1') != -1) {
				enableSwatchOptions(1);
				enableSwatchOptions(2);
			} else if (name.indexOf('2') != -1) {
				enableSwatchOptions(2);
			}
		}
	}

	// Hàm bật các tùy chọn swatch (giúp kiểm soát logic nếu cần)
	function enableSwatchOptions(optionIndex) {
		$('#variant-swatch-' + optionIndex + '-quickview .swatch-element').find('input').prop('disabled', false);
		$('#variant-swatch-' + optionIndex + '-quickview .swatch-element label').removeClass('sd');
		$('#variant-swatch-' + optionIndex + '-quickview .swatch-element').removeClass('soldout');
	}



});


jQuery(document).ready(function () {
	// Hàm tính tổng số lượng đồ uống có giá = 0
	function getTotalFreeDrinks() {
		let total = 0;
		$('.swatch-element.water-option input.input-quickview.extra-water:checked').each(function () {
			var $swatchElement = $(this).closest('.swatch-element.water-option');
			var priceText = $swatchElement.find('.water-price').text().trim();
			var price = priceText === 'Miễn phí' ? 0 : parseInt(priceText.replace(/[^0-9]/g, '')) || 0;
			if (price === 0) {
				var count = parseInt($swatchElement.find('.water-count').text()) || 0;
				total += count;
			}
		});
		return total;
	}

	// Hàm kiểm tra và điều chỉnh số lượng đồ uống miễn phí
	function adjustFreeDrinks() {
		var productQuantity = parseInt($('#product-quantity').val()) || 1;
		var totalFreeDrinks = getTotalFreeDrinks();
		if (totalFreeDrinks > productQuantity) {
			$('.swatch-element.water-option input.input-quickview.extra-water:checked').each(function () {
				var $swatchElement = $(this).closest('.swatch-element.water-option');
				var priceText = $swatchElement.find('.water-price').text().trim();
				var price = priceText === 'Miễn phí' ? 0 : parseInt(priceText.replace(/[^0-9]/g, '')) || 0;
				if (price === 0 && totalFreeDrinks > productQuantity) {
					$(this).prop('checked', false).trigger('change');
					$swatchElement.find('.water-count').text(0).css('display', 'none');
					$(this).next().removeClass('sd');
					totalFreeDrinks -= 1;
				}
			});
			//alert('Số lượng đồ uống miễn phí đã vượt quá số lượng sản phẩm chính. Một số lựa chọn đã bị bỏ.');
		}
	}
	jQuery('#AddToCardQuickView').on('click', function (e) {
		var productQuantity = parseInt($('#product-quantity').val()) || 1;
		var totalFreeDrinks = getTotalFreeDrinks();

		if (totalFreeDrinks > productQuantity) {
			e.preventDefault(); // Ngăn hành động thêm vào giỏ
			alert('Số lượng đồ uống miễn phí vượt quá số lượng sản phẩm chính. Một số lựa chọn đã bị bỏ.');
			//$('#AddToCardQuickView').hide(); // Ẩn nút
			adjustFreeDrinks(); // Bỏ chọn đồ uống miễn phí dư thừa
		}
	});
	// Xử lý sự kiện click cho các phần tử liên quan đến extraWater
	jQuery('#productQuickView').on('click', '.swatch-element.water-option label, .swatch-element.water-option .water-content, .swatch-element.water-option .water-name, .swatch-element.water-option .water-price, .swatch-element.water-option img', function (e) {
		e.preventDefault(); // Ngăn hành vi mặc định của label
		e.stopPropagation();

		var $swatchElement = $(this).closest('.swatch-element.water-option');
		var $input = $swatchElement.find('input.input-quickview.extra-water');
		var priceText = $swatchElement.find('.water-price').text().trim();
		var price = priceText === 'Miễn phí' ? 0 : parseInt(priceText.replace(/[^0-9]/g, '')) || 0;

		// Nếu giá = 0, kiểm tra số lượng sản phẩm chính trước khi chọn
		if (price === 0 && !$input.prop('checked')) {
			var productQuantity = parseInt($('#product-quantity').val()) || 1;
			var totalFreeDrinks = getTotalFreeDrinks();
			if (totalFreeDrinks >= productQuantity) {
				alert('Bạn không thể chọn thêm đồ uống miễn phí, hãy đặt thêm món.');
				return;
			}
		}

		// Chuyển đổi trạng thái checked của input
		$input.prop('checked', !$input.prop('checked')).trigger('change');
	});

	// Xử lý sự kiện touchstart cho các phần tử liên quan đến extraWater
	jQuery('#productQuickView').on('touchstart', '.swatch-element.water-option label, .swatch-element.water-option .water-content, .swatch-element.water-option .water-name, .swatch-element.water-option .water-price, .swatch-element.water-option img', function (e) {
		e.preventDefault(); // Ngăn hành vi mặc định của label
		e.stopPropagation();

		var $swatchElement = $(this).closest('.swatch-element.water-option');
		var $input = $swatchElement.find('input.input-quickview.extra-water');
		var priceText = $swatchElement.find('.water-price').text().trim();
		var price = priceText === 'Miễn phí' ? 0 : parseInt(priceText.replace(/[^0-9]/g, '')) || 0;

		// Nếu giá = 0, kiểm tra số lượng sản phẩm chính trước khi chọn
		if (price === 0 && !$input.prop('checked')) {
			var productQuantity = parseInt($('#product-quantity').val()) || 1;
			var totalFreeDrinks = getTotalFreeDrinks();
			if (totalFreeDrinks >= productQuantity) {
				alert('Bạn không thể chọn thêm đồ uống miễn phí vì đã đạt giới hạn số lượng sản phẩm chính.');
				return;
			}
		}

		// Chuyển đổi trạng thái checked của input
		$input.prop('checked', !$input.prop('checked')).trigger('change');
	});

	// Xử lý sự kiện khi input extraWater thay đổi
	jQuery('#productQuickView').on('change', 'input.input-quickview.extra-water', function (e) {
		var $this = $(this);
		var $swatchElement = $this.closest('.swatch-element.water-option');
		var countElement = $swatchElement.find('.water-count');
		var isChecked = $this.prop('checked'); // Dùng trạng thái checked thực tế

		var count;
		if (!isChecked) {
			// Nếu không được chọn, đặt count = 0 và bỏ chọn
			count = 0;
		} else {
			// Nếu được chọn, tăng count theo chu kỳ (0 -> 1 -> 2)
			count = parseInt(countElement.text()) || 0;
			count = count === 0 ? 1 : (count % 2) + 1;
		}

		// Cập nhật giao diện
		countElement.text(count);

		if (count === 0 || count === 2) {
			// Khi count = 0 hoặc 2, bỏ chọn và ẩn số lượng
			$this.prop('checked', false);
			$this.next().removeClass('sd');
			countElement.css('display', 'none');
			if (count === 2) {
				countElement.text(0); // Reset count khi đạt 2
			}
		} else {
			// Khi count = 1, chọn và hiển thị số lượng
			$this.prop('checked', true);
			$this.next().addClass('sd');
			countElement.css('display', 'block');
		}

		// Kiểm tra và điều chỉnh số lượng sau khi thay đổi
		adjustFreeDrinks();

		// Cập nhật giá trị trong select tương ứng (nếu cần)
		var name = $this.attr('name');
		var value = $this.val();
		$('#productQuickView select[data-option=' + name + ']').val(value).trigger('change');
	});

	// Xử lý sự kiện khi số lượng sản phẩm chính thay đổi
	jQuery('#product-quantity').on('input change', function () {
		adjustFreeDrinks();
	});

	// Khởi tạo phần tử hiển thị số lượng cho extraWater
	function initializeWaterCounts() {
		$('.swatch-element.water-option').each(function () {
			var $this = $(this);
			if ($this.find('.water-count').length === 0) {
				$this.append('<div class="water-count" style="position: absolute; top: 0; right: 0; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; font-size: 12px; display: none;">0</div>');
			}
		});
	}

	// Gọi hàm khởi tạo khi tài liệu sẵn sàng
	initializeWaterCounts();
});


