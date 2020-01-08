
var swiperHeight = 70;
var blocknum = [];
var msgList = [];
var url = window.location.href;
var theRequest = new Object();
var ind = url.indexOf("?") + 1;
var str = url.substr(ind);
var arr = str.split('&');
for (var i = 0; i < arr.length; i++) {
	theRequest[arr[i].split("=")[0]] = unescape(arr[i].split("=")[1]);
}
console.log(theRequest);

$.ajax({
	url: m + '/app/ewei_shopv2_api.php?i=1&r=app.index_game',
	dataType: 'json', //服务器返回json格式数据
	type: 'post', //HTTP请求类型
	data: {
		token: theRequest.token,
		type: 2
	},
	success: function (data) {
		console.log(data);
		if (data.error == 0) {
			blocknum = data.data.list;
			msgList = data.data.log;
			$('.figure0').text(blocknum[0].reward1);
			$('.figure1').text(blocknum[1].reward2);
			$('.figure2').text(blocknum[2].reward3);
			$('.figure3').text(blocknum[3].reward4);
			$('.figure4').text(blocknum[4].reward5);
			$('.figure5').text(blocknum[5].reward6);
			$('.figure6').text(blocknum[6].reward7);
			$('.figure7').text(blocknum[7].reward8);
			$('.kaluliyu').text('折扣宝余额' + data.data.credit3);
			$('.frequency span').text(data.data.num);
			// 获奖名单
			for (var i = 0; i < msgList.length; i++) {
				$(".list-swiper").append(
					"<div class='swiper-boder' style='top:" + i * swiperHeight + "'>" +
					"<div class='nickname'>" + msgList[i].nickname + "</div>" +
					"<div class='cellphone'>" + msgList[i].mobile + "</div>" +
					"<div class='num'>" + msgList[i].remark + "</div>"
					+ "</div>"
				)
			};
		} else {
			alert(data.message);
		}
	},
	error: function (xhr, type, errorThrown) {
		console.log('456');
	}
});

// 获奖名单动画
// 切换下一页
var currentIndex = 0;
function nextPage() {
	currentIndex++;
	if (currentIndex >= msgList.length) {
		currentIndex = 0;
	}
	var top = -3 * currentIndex;
	$(".list-swiper").css("top", top + "rem");
};
// 当动画结束时触发
$(".list-swiper").on("transitionend", function () {
	if (currentIndex == msgList.length - 1) {
		$(".list-swiper").removeClass("ani");
		$(".list-swiper").css("top", 0 + "rem");
		currentIndex = 0;
		setTimeout(() => {
			$(".list-swiper").addClass("ani");
		}, 100);
	}
});
// 调用时间函数
var timer;
function startRunning() {
	timer = setInterval(function () {
		nextPage();
	}, 2000);
}
startRunning();



// 抽奖
// 进入页面时缓慢切换
var index = 0;
var divI = $(".lottery-unit").find('.cartoon');
var motion = setInterval(function () {
	if (index <= 7) {
		$(".sign" + [index]).addClass("cartoonCss");
		$(".sign" + [index - 1]).removeClass("cartoonCss");
		index++;
	} else {
		index = 1;
		$(".sign7").removeClass("cartoonCss");
		$(".sign0").addClass("cartoonCss");
	}
}, 700);

// 点击抽奖
var indexB = 0;//动画开始位置
var awardId = 0;
var cartoontime = 150;
$(".divbtn").click(function (e) {
	clearInterval(motion);
	console.log(index);

	indexB = index;

	$.ajax({
		url: m + '/app/ewei_shopv2_api.php?i=1&r=app.index_getreward',
		dataType: 'json', //服务器返回json格式数据
		type: 'post', //HTTP请求类型
		data: {
			token: 'NDE2ODMsQ0VUVEtFbjl4dTlaVDNpb2NUSzluOTllWGNPVE1IM1V4OUNF',
			type: 0,
			credit: 'credit3',
			money: 10
		},
		success: function (data) {
			console.log(data);
			if (data.error == 0) {
				$('.text-top-count').text(data.data.num);
				$('.kaluliyu').text('折扣宝余额' + data.data.credit3);
				$('.frequency span').text(data.data.remain);
				chou();
			} else {
				alert(data.message);
			}
		},
		error: function (xhr, type, errorThrown) {
			console.log('456');
		}
	});
});
function chou() {
	// 后台返回抽中的值，动画显示
	award3 = setInterval(function () {
		useFunction();
		awardId++;
		if (awardId > 30) {
			cartoontime = 500;
		}
		if (awardId > 50) {
			clearInterval(award3);

			$(".sign" + [indexB - 1]).removeClass("cartoonCss");
			$('.mask-relative').removeClass('vanish');
		}
	}, cartoontime);

	var useFunction = function () {
		if (indexB <= 7) {
			$(".sign" + [indexB]).addClass("cartoonCss");
			$(".sign" + [indexB - 1]).removeClass("cartoonCss");
			indexB++;
		} else {
			indexB = 1;
			$(".sign7").removeClass("cartoonCss");
			$(".sign0").addClass("cartoonCss");
		}
	}
}

// 关闭弹出框
$('.closeBtn').click(function () {
	$('.mask-relative').addClass('vanish');

	indexB = 0;
	setInterval(function () {
		if (index <= 7) {
			$(".sign" + [index]).addClass("cartoonCss");
			$(".sign" + [index - 1]).removeClass("cartoonCss");
			index++;
		} else {
			index = 1;
			$(".sign7").removeClass("cartoonCss");
			$(".sign0").addClass("cartoonCss");
		}
	}, 700);
});



// 点击邀请交互
function inviteBtn() {
	jsToOcFunction1();
}

function jsToOcFunction1() {
	console.log('123');
	if (theRequest.type == 0) {
		console.log('123');
	} else if (theRequest.type == 1) {
		console.log('456');
		window.webkit.messageHandlers.SkipViewController.postMessage({ type: 1 });
	} else if (theRequest.type == 2) {
		window.messageHandlers.pushServeDetail();
	}
}