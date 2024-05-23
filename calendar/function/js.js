function api(url = '', data = {}, finish = null){
	//url 유효성 검사
	if(typeof url !== 'string'){
		alert('URL 경로가 잘못되었습니다.');
		return false;
	}

	if(url === ''){
		alert('URL 경로가 잘못되었습니다.');
		return false;
	}

	//data 유효성 검사
	if(typeof data !== 'object'){
		alert('데이터의 유효성이 올바르지 않습니다.');
		return false;
	}

	//finish 유효성 검사
	let is_function = false;
	if(finish !== null){
		if(typeof finish !== 'function'){
			alert('입력된 함수의 유효성이 올바르지 않습니다.');
			return false;
		}

		is_function = true;

	}

	url = domain + '/api/' + url + '.php';
	data = JSON.stringify(data);

	$.ajax({
		type : 'POST',
		url : url,
		headers : {"content-type" : "application/json"},
		dataType : 'text',
		data : data,
		success : function(result){

			if(is_function === true){

				if(is_json(result) === true){
					result = JSON.parse(result);
					result.senddata = JSON.parse(data);
				}

				if(__DEBUG_MODE__ === true){
					console.table(result);
				}
				finish(result);
			}
		},
		error : function(){
			alert('통신 실패!!!');
		}
	});
};

function is_json(str){
	try{
		var json = JSON.parse(str);
		return (typeof json === 'object');
	}catch(e){
		return false;
	}
}

//화면 이동 함수
function render(path, senddata = {}, method = 'post'){
	let url = domain + '/' + path + '.php';
	let form = document.createElement('form');
	form.setAttribute('id', 'form');
	form.setAttribute('action', url);
	form.setAttribute('method', method);
	form.setAttribute('style', 'display:none');
	document.body.appendChild(form);

	let keys = Object.keys(senddata);
	let html = '';
	for(let i=0; i<keys.length; i++){
		html += '<input name="'+ keys[i] +'" value="' + senddata[keys[i]] + '"/>';
	}
	html += '<input id="submit_btn" type="submit" value="전송" />';

	$('#form').html(html);
	
	$('#submit_btn').click();
}

//달의 날짜를 문자열로 변환하는 함수(10월 이전의 달은 앞에 0 붙여주기)
function format_date(year, month, day){
	let format_month = month;
	let format_day = day;

	if (month < 10) {
		format_month = '0' + month;
	}

	if (day < 10) {
		format_day = '0' + day;
	}

	return year + '' + format_month + format_day;
}
