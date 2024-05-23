// 다음달로 이동하는 함수
function next_month(){
	month++; // 현재 월에 1을 더하여 다음 달로 이동
	if(month > 12){
		month = 1;
		year++; // 연도를 1 증가시킴
	}
	
	$('#year_month').text(year + '년 ' + month + '월'); // 연도와 월을 업데이트
	draw_calender();
	return null
}

// 이전달로 이동하는 함수
function prev_month(){
	month--; // 현재 월에서 1을 빼 이전 달로 이동
	if(month < 1){
		month = 12;
		year--; // 연도를 1 감소시킴
	}

	$('#year_month').text(year + '년 ' + month + '월'); // 연도와 월을 업데이트
	draw_calender();
	return null;
}

//기본 페이지 지역을 위한 window.onload
window.onload = function(){
	show_weather();
};

//기본 페이지 지역을 위한 함수
function show_weather(){
	if(navigator.geolocation){
		navigator.geolocation.getCurrentPosition(function(position) {
			var latitude = position.coords.latitude;
			var longitude = position.coords.longitude;
			
			var xy = dfs_xy_conv("toXY", latitude, longitude);
			var nx = xy.x;
			var ny = xy.y;
			
			get_weather_data(nx, ny);
		});
	}
}

function get_weather_data(nx, ny){
    var xhr = new XMLHttpRequest();
    var url = 'weather_report.php'; // 요청을 처리할 PHP 파일의 경로
    var params = 'nx=' + nx + '&ny=' + ny; // POST 데이터 생성

    xhr.open('POST', url, true);

    // 요청 헤더 설정
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function(){
        if(xhr.readyState == 4 && xhr.status == 200){
            // 요청이 성공하면 처리할 코드
            console.log(xhr.responseText);
        }
    }

    xhr.send(params); // POST 데이터 전송
}

function draw_calender(){
	let html = '';

	let current_date = new Date(); // 현재 날짜
	let start_date = new Date(year, month - 1, 1); // 이번 달 시작 날짜
	let end_date = new Date(year, month, 0); // 이번 달 마지막 날짜
	let prev_month_end_date = new Date(year, month - 1, 0); // 이전 달 마지막 날짜
	let total_days = end_date.getDate(); // 이번 달의 총 일 수
	let total_weeks = Math.ceil((total_days + start_date.getDay()) / 7); // 이번 달의 총 주차 계산

	// 이전 달의 날짜
	let prev_month_days = prev_month_end_date.getDate();
	let prev_month_start_day = start_date.getDay();
	let prev_month_dates = [];
	for(let i=prev_month_days - prev_month_start_day + 1; i <= prev_month_days; i++){
		prev_month_dates.push(i);
	}

	// 다음 달의 날짜
	let next_month_days = 1;
	let next_month_dates = [];
	let remaining_cells = total_weeks * 7 - (prev_month_dates.length + total_days);
	for (let i=1; i <= remaining_cells; i++){
		next_month_dates.push(next_month_days);
		next_month_days++;
	}

	// 달력 헤더
	html += '<thead>';
		html += '<tr>';
			html += '<th>일</th>';
			html += '<th>월</th>';
			html += '<th>화</th>';
			html += '<th>수</th>';
			html += '<th>목</th>';
			html += '<th>금</th>';
			html += '<th>토</th>';
		html += '</tr>';
	html += '</thead>';
	
	html += '<tbody>';
	let day = 1;
	let prev_month_index = 0;
	let next_month_index = 0;
	for (let i = 0; i < total_weeks; i++) {
		html += '<tr>';
		for(let j=0; j<7; j++){
			if(i === 0 && j < start_date.getDay()){
				// 이전 달 날짜 표시
				html += '<td class="other_month">' + prev_month_dates[prev_month_index] + '</td>';
				prev_month_index++;
			}else if(day > total_days){
				// 다음 달 날짜 표시
				html += '<td class="other_month">' + next_month_dates[next_month_index] + '</td>';
				next_month_index++;
			}else{
				let class_name = '';
				let weekend_color = 'black';
				if(year === current_date.getFullYear() && month === current_date.getMonth() + 1 && day === current_date.getDate()){
					class_name = 'current_date';
				}
				if(j === 6){ // 토요일
					weekend_color = 'blue';
				}
				if(j === 0){ // 일요일
					weekend_color = 'red';
				}
				html += '<td class="' + class_name + '" style="color: ' + weekend_color + '">' + day + '</td>';
				day++;
			}
		}
		html += '</tr>';
		}
	html += '</tbody>';
	$('#weather_table').html(html);
}



//중기예보 api데이터 함수
function get_weather(regId){

	let senddata = new Object();	
	senddata.regId = regId; //지역 아이디 api로 전송

	api('p_api/api_get_weather', senddata, function(output){
		if(output.is_success){
			//api에서 추출한 날씨 데이터 js에서 그리기
			weather_data = output.weather_data;

			draw(weather_data);
		}else{
			alert(output.msg);
		}
	});
}

//기상청 격자 <--> 위경도 변환
// (사용 예)
// var rs = dfs_xy_conv("toLL","60","127");
// console.log(rs.lat, rs.lng);
//
var RE = 6371.00877; // 지구 반경(km)
var GRID = 5.0; // 격자 간격(km)
var SLAT1 = 30.0; // 투영 위도1(degree)
var SLAT2 = 60.0; // 투영 위도2(degree)
var OLON = 126.0; // 기준점 경도(degree)
var OLAT = 38.0; // 기준점 위도(degree)
var XO = 43; // 기준점 X좌표(GRID)
var YO = 136; // 기1준점 Y좌표(GRID)

function dfs_xy_conv(code, v1, v2) {
	var DEGRAD = Math.PI / 180.0;
	var RADDEG = 180.0 / Math.PI;

	var re = RE / GRID;
	var slat1 = SLAT1 * DEGRAD;
	var slat2 = SLAT2 * DEGRAD;
	var olon = OLON * DEGRAD;
	var olat = OLAT * DEGRAD;

	var sn = Math.tan(Math.PI * 0.25 + slat2 * 0.5) / Math.tan(Math.PI * 0.25 + slat1 * 0.5);
	sn = Math.log(Math.cos(slat1) / Math.cos(slat2)) / Math.log(sn);
	var sf = Math.tan(Math.PI * 0.25 + slat1 * 0.5);
	sf = Math.pow(sf, sn) * Math.cos(slat1) / sn;
	var ro = Math.tan(Math.PI * 0.25 + olat * 0.5);
	ro = re * sf / Math.pow(ro, sn);
	var rs = {};
	if(code == "toXY"){
		rs['lat'] = v1;
		rs['lng'] = v2;
		var ra = Math.tan(Math.PI * 0.25 + (v1) * DEGRAD * 0.5);
		ra = re * sf / Math.pow(ra, sn);
		var theta = v2 * DEGRAD - olon;
		if (theta > Math.PI) theta -= 2.0 * Math.PI;
		if (theta < -Math.PI) theta += 2.0 * Math.PI;
		theta *= sn;
		rs['x'] = Math.floor(ra * Math.sin(theta) + XO + 0.5);
		rs['y'] = Math.floor(ro - ra * Math.cos(theta) + YO + 0.5);
	}else{
		rs['x'] = v1;
		rs['y'] = v2;
		var xn = v1 - XO;
		var yn = ro - v2 + YO;
		ra = Math.sqrt(xn * xn + yn * yn);
		if (sn < 0.0) - ra;
		var alat = Math.pow((re * sf / ra), (1.0 / sn));
		alat = 2.0 * Math.atan(alat) - Math.PI * 0.5;
		if (Math.abs(xn) <= 0.0) {
			theta = 0.0;
		}else{
			if (Math.abs(yn) <= 0.0) {
				theta = Math.PI * 0.5;
				if (xn < 0.0) - theta;
			}else theta = Math.atan2(xn, yn);
		}
		var alon = theta / sn + olon;
		rs['lat'] = alat * RADDEG;
		rs['lng'] = alon * RADDEG;
	}
	return rs;
}

function show_weather_popup(day){
	if(navigator.geolocation){ // GPS를 지원하면
		navigator.geolocation.getCurrentPosition(function(position){
			// 네비게이션에서 가져온 위도 경도 선언
			var latitude = position.coords.latitude;
			var longitude = position.coords.longitude;

			// 기상청 좌표로 변환
			var xy = dfs_xy_conv('toXY', latitude, longitude);
			var nx = xy.x;
			var ny = xy.y;

			let senddata = new Object();
			senddata.nx = nx;
			senddata.ny = ny;
			senddata.day = day;

			// 단기예보 API 호출
			api('p_api/api_short_weather', senddata, function(output){
				if(output.is_success){
					today_data = output.today_data;
					tomorrow_data = output.tomorrow_data;
					// 팝업 창에 표시할 내용 생성(단기예보, 날짜)
					popup_draw(today_data, tomorrow_data, day);
				}else{
					alert(output.msg);
				}
			});
		}, function(error){
			console.error(error);
			alert('위치 정보를 가져오지 못했습니다.');
		}, {
			enableHighAccuracy: false,
			maximumAge: 0,
			timeout: Infinity
		});
	}else{
		alert('GPS를 지원하지 않습니다');
	}
}

function popup_draw(today_data, tomorrow_data, day){
	// 팝업 창에 표시할 내용 생성
	let weather = '';
	// CSS 스타일 직접 지정
	weather += '<style>';
	weather += '.popup-container {';
	weather += '    display: flex;';
	weather += '}';
	weather += '.popup-content {';
	weather += '    flex: 1;';
	weather += '    margin-right: 10px;';
	weather += '}';
	weather += '#popup_table {';
	weather += '    width: 100%;';
	weather += '    border-collapse: collapse;';
	weather += '    table-layout: fixed;';
	weather += '}';
	weather += '#popup_table th,';
	weather += '#popup_table td {';
	weather += '    padding: 10px;';
	weather += '    text-align: center;';
	weather += '}';
	weather += '#popup_table th {';
	weather += '    background-color: blanchedalmond;';
	weather += '}';
	weather += '#popup_table td:hover {';
	weather += '    background-color: darkgrey;';
	weather += '}';
	weather += '</style>';
	// 예보 데이터를 팝업 내용에 추가
	weather += '<div class="popup-container">';
		weather += '<div class="popup-content">';
			weather += `<h3>${day}일 단기예보</h3>`;
			weather += '<table id="popup_table" border="1">';
				weather +='<tr>';
					weather +='<th>예보시간</th>';
					weather +='<th>카테고리</th>';
					weather +='<th>날씨정보</th>';
				weather +='</tr>';
			// today_data 루프 돌리기
			for(let i=0; i<today_data.length; i++){
				let item = today_data[i];
				weather += '<tr>';
					weather += `<td>${item['예보시간']}</td>`;
					weather += `<td>${item['카테고리']}</td>`;
					weather += `<td>${item['날씨정보']}</td>`;
				weather += '</tr>';
			}
			weather +=  '</table>';
		weather += '</div>';

		// 예보 데이터를 팝업 내용에 추가
		weather += '<div class="popup-content">';
			weather += `<h3>${day + 1}일 단기예보</h3>`;
			weather += '<table id="popup_table" border="1">';
				weather +='<tr>';
					weather +='<th>예보시간</th>';
					weather +='<th>카테고리</th>';
					weather +='<th>날씨정보</th>';
				weather +='</tr>';
			// tomorrow_data 루프 돌리기
			for(let i=0; i<tomorrow_data.length; i++){
				let item = tomorrow_data[i];
				weather += '<tr>';
					weather += `<td>${item['예보시간']}</td>`;
					weather += `<td>${item['카테고리']}</td>`;
					weather += `<td>${item['날씨정보']}</td>`;
				weather += '</tr>';
				// 만약 예보시간이 00시고 다음날 00시가 나오면 루프 중지
				if(item['예보시간'] === '0000' && i>7){
					break;
				}
			}
			weather +=  '</table>';
		weather += '</div>';
	weather += '</div>';
	// 팝업 창에 내용 추가 (따로 사용한 url없음, _blank로 새 창 띄우기)
	window.open('', '_blank').document.write(weather);
}

function draw(weather_data){
	let html = '';
	let days = new Date(); // 현재 날짜
	let start_day = new Date(days.getFullYear(), days.getMonth(), 1).getDay(); // 이번달 시작 요일
	let total_days = new Date(days.getFullYear(), days.getMonth() + 1, 0).getDate(); // 이번달 총 일 수 === 이번달이 몇일 까지 있는지

	// 이번 달의 총 주차 계산
	let total_weeks = Math.ceil((total_days + start_day) / 7);

	// 날씨 데이터
	let weather_days = []; // 오늘부터 3일 후까지의 날씨 데이터를 표시할 날짜를 넣을 배열
	for(let i=0; i<5; i++){ // 3일 후 ~ 7일후 까지의 데이터 추출
		let date = new Date(days.getFullYear(), days.getMonth(), days.getDate() + 2 + i);
		weather_days.push(date.getDate());
	}

	// 달력 헤더
	html += '<thead>';
		html += '<tr>';
			html += '<th>일</th>';
			html += '<th>월</th>';
			html += '<th>화</th>';
			html += '<th>수</th>';
			html += '<th>목</th>';
			html += '<th>금</th>';
			html += '<th>토</th>';
		html += '</tr>';
	html += '</thead>';
	html += '<tbody>';

	// 달력 내용
	let day = 1; // 첫 번째 날을 1일로 고정
	let index = 0; // 날씨 데이터 인덱스
	for(let i=0; i<total_weeks; i++){
		html += '<tr>';
		for(let j=0; j<7; j++){
			if(i === 0 && j < start_day){
				// 이전 달의 마지막 날짜를 가져오기
				let prev_month = new Date(days.getFullYear(), days.getMonth(), 0).getDate();
				html += '<td class="other_month">' + (prev_month - start_day + j + 1) + '</td>';
			}else{
				if(day > total_days){
				//다음달 날짜 가져오기
			html += '<td class="other_month">' + (day - total_days) + '</td>';
				}else{
					//오늘을 찾아서 노란색으로 표시
					let background_color = 'white';
					let today = (day === days.getDate() && days.getMonth() === days.getMonth() && days.getFullYear() === days.getFullYear());
					if(today){
						background_color = 'yellow';
					}
					// 주말인 경우 배경색 변경
					let weekend_color = 'black';
					if(j === 0){ // 일요일(0)
						weekend_color = 'red';
					}
					if(j === 6){ // 토요일(6)
						weekend_color = 'blue';
					}
					// 날씨 데이터를 표시
					if(weather_days.includes(day)){
						let weather_am = weather_data[`wf${index + 3}Am`];
						let weather_pm = weather_data[`wf${index + 3}Pm`];
						let rain_am = weather_data[`rnSt${index + 3}Am`];
						let rain_pm = weather_data[`rnSt${index + 3}Pm`];
				html += '<td style="background-color: ' + background_color + '; color: ' + weekend_color + '">' + day + '<br>' + '<span class="weather-info" style="color: black;">오전 날씨: ' +  weather_am + '<br>' + '오전 강수 확률: ' + rain_am + '%<br>' + '오후 날씨: ' + weather_pm + '<br>' + '오후 강수 확률: ' + rain_pm + '%</span></td>';

				index++;
					}else{
				html += '<td style="background-color: ' + background_color + '; color: ' + weekend_color + '">' + day + '</td>';
					}
				day++;
				}
			}
		}
		html += '</tr>';
	}
	html += '</tbody>';

	// 테이블 내용 업데이트
	$('#weather_table').html(html);
}