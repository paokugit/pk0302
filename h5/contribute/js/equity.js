var url = window.location.href;
var theRequest = new Object();
var ind = url.indexOf("?") + 1;
var str = url.substr(ind);
var arr = str.split('&');
for (var i = 0; i < arr.length; i++) {
	theRequest[arr[i].split("=")[0]] = unescape(arr[i].split("=")[1]);
}
console.log(theRequest);


var str = window.location.href;
var ind = str.indexOf("=") + 1;
var type = str.substr(ind);

$.ajax({
	url: m + '/app/ewei_shopv2_api.php?i=1&r=app.index_level_detail',
	dataType: 'json', //服务器返回json格式数据
	type: 'post', //HTTP请求类型
	data: {
		id: 5
	},
	success: function (data) {
		console.log(data);
		if (data.error == 0) {
			var str = data.data.goods.goods;
			for (var i = 0; i < str.length; i++) {
				$(".swiper-wrapper").append(
					'<div class="swiper-slide">' +
					'<img style="width:95%;" src="' + str[i].image + '" imgid="' + str[i].id + '" onclick="imgbtn(this)" alt="">' +
					'</div>'
				);
			};

			var mySwiper = new Swiper('.swiper-container', {
				autoplay: {
					delay: 2000,
					stopOnLastSlide: false,
					disableOnInteraction: false
				},
				loop: true,
				delay: 1000,
				pagination: {
					el: '.swiper-pagination',
				}
			})

		} else {
			alert(data.message);
		}
	},
	error: function (xhr, type, errorThrown) {
		console.log('456');
	}
});

function imgbtn(e) {
	var goodid = e.getAttribute("imgid");
	if (theRequest.type == 0) {
		console.log('123');
	} else if (theRequest.type == 1) {
		console.log('456');
		window.webkit.messageHandlers.SkipViewController.postMessage({
			id:goodid,
			type:2
		});
	} else if (theRequest.type == 2) {
		window.messageHandlers.pushServeDetail(e.getAttribute("imgid"));
	}
}

function dredgeBtn(){
	if (theRequest.type == 0) {
		console.log('123');
	} else if (theRequest.type == 1) {
		console.log('456');
		window.webkit.messageHandlers.SkipViewController.postMessage({
			type:3
		});
	} else if (theRequest.type == 2) {
		window.messageHandlers.pushServeDetail(e.getAttribute("imgid"));
	}
} 