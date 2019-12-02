console.log(m);
$.ajax({
	url: m+'/app/ewei_shopv2_api.php?i=1&r=app.rebate.rebate_devote_detail',
	dataType: 'json', //服务器返回json格式数据
	type: 'get', //HTTP请求类型
	success: function(data) {
		console.log(data);

		var str = data.data.content;
		console.log(str);
		$('.details-area').html(str);

 
	},
	error: function(xhr, type, errorThrown) {
		console.log('456');
	}
});

