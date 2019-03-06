// 假设服务端ip为127.0.0.1
ws = new WebSocket("ws://192.168.0.110:45612");
var uname = uuid(8, 16);//prompt('请输入用户名', 'user' + uuid(8, 16));
ws.onopen = function() {
	// 登录
	var str = {'msg_type':'login','uid':uname,'name':uname,'avatar':'http://thirdwx.qlogo.cn/mmopen/vi_32/OQlu6NdzInEQyXCFJ4eiaj137yqygM0aWWubuyTmrSaXxqGoGH0gvlvOIHWNtIjicavJBQAG4C8jPpl6QQ20ruEQ/132'};
    ws.send(JSON.stringify(str));
};

ws.onmessage = function(e) {
	var res = JSON.parse(e.data);
    if(res.msg_type == 'login'){
    	$(".content-box").append('<div class="msg-text">'+res.name+' '+res.content+'</div>');
    }
    if(res.msg_type == 'news'){
    	if(res.is_my == 1){
    		$(".content-box").append('<div class="content-left"><div class="content-avatar-right"><img src="'+res.avatar+'"/></div><div class="content-name-right">'+res.name+'</div><div style="clear: both;"></div><div class="content-text-right">'+res.content+'</div></div>');
    	}else{
    		$(".content-box").append('<div class="content-left"><div class="content-avatar"><img src="'+res.avatar+'"/></div><div class="content-name">'+res.name+'</div><div style="clear: both;"></div><div class="content-text">'+res.content+'</div></div>');
    	}
    }
    di();
};

function send(){
	var content = $('.text-textarea').val();
	$('.text-textarea').val('');
	$('.text-textarea').blur();
	var str = {'msg_type':'news','uid':uname,'name':uname,'avatar':'http://thirdwx.qlogo.cn/mmopen/vi_32/OQlu6NdzInEQyXCFJ4eiaj137yqygM0aWWubuyTmrSaXxqGoGH0gvlvOIHWNtIjicavJBQAG4C8jPpl6QQ20ruEQ/132','content':content};
    ws.send(JSON.stringify(str));
}

/**
 * 生产一个全局唯一ID作为用户名的默认值;
 *
 * @param len
 * @param radix
 * @returns {string}
 */
function uuid(len, radix) {
    var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
    var uuid = [], i;
    radix = radix || chars.length;

    if (len) {
        for (i = 0; i < len; i++) uuid[i] = chars[0 | Math.random() * radix];
    } else {
        var r;

        uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
        uuid[14] = '4';

        for (i = 0; i < 36; i++) {
            if (!uuid[i]) {
                r = 0 | Math.random() * 16;
                uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
            }
        }
    }

    return uuid.join('');
}

function di(){
	$(".content-box").scrollTop($(".content-box")[0].scrollHeight);
}

$(document).keyup(function(e){
	if(e.keyCode == 13){
		send();
	}
});