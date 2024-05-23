<?php
	define('__CORE_TYPE__', 'view');
	include $_SERVER['DOCUMENT_ROOT'].'/function/core.php';

	//현재 시간 설정 === php.ini 적용 안됨 문제.
	date_default_timezone_set('Asia/Seoul');

	//현재 페이지 좌표 데이터 가져오기
	// $nx = $_POST['nx'];
	// $ny = $_POST['ny'];
	$nx = '79';
	$ny = '97';

	$year = date('Y'); //현재 년도
	$month = date('n'); //현재 달
	$today = date('d'); //현재 일
	$date = "$year-$month-01"; // 시작 날짜
	$time = strtotime($date); // 시작 날짜의 타임스탬프
	$start_week = date('w', $time); // 시작 요일
	$total_day = date('t', $time); // 현재 달의 총 날짜
	$total_week = ceil(($total_day + $start_week) / 7); //현재 달의 총 주차

	//셀렉트바에 사용할 지역 코드
	$regions = array(
		array('code' => '11B00000', 'name' => '서울, 인천, 경기도'),
		array('code' => '11D10000', 'name' => '강원도영서'),
		array('code' => '11D20000', 'name' => '강원도영동'),
		array('code' => '11C20000', 'name' => '대전, 세종, 충청남도'),
		array('code' => '11C10000', 'name' => '충청북도'),
		array('code' => '11F20000', 'name' => '광주, 전라남도'),
		array('code' => '11F10000', 'name' => '전라북도'),
		array('code' => '11H10000', 'name' => '대구, 경상북도'),
		array('code' => '11H20000', 'name' => '부산, 울산, 경상남도'),
		array('code' => '11G00000', 'name' => '제주도')
	);

	// 단기api 데이터 가져오기
	$ch = curl_init();
	$url = 'http://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getVilageFcst';
	$queryParams = '?' . urlencode('serviceKey') . '=' . 'p8Lh%2BxLFef3XgV1yD9m4DyEspeLS7vVhR%2Fzk%2BVVEQ%2BD56xjv7jad5Me9hj%2B9nTcR4pTcn%2FRokDMnPRC0fNEHkA%3D%3D';
	$queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1');
	$queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('556'); //하루치 필요 데이터 398 //이틀치 556
	$queryParams .= '&' . urlencode('dataType') . '=' . urlencode('XML');

	//현재 날짜와 시간
	$year = date('Y'); // 현재 연도
	$month = date('m'); // 현재 월
	$today = date('d'); // 현재 날짜
	$current_hour = intval(date('H')); // 현재 시간

	//sprintf를 통하여 문자열로 만들기
	$current_day = sprintf("%04d%02d%02d", $year, $month, $today);

	//기준 날짜 설정
	if($current_hour < 2){
		// 2시 이전이면 전날 데이터 조회
		$base_date = date('Ymd', strtotime('-1 day', strtotime($current_day)));
	}else{
		// 2시 이후이면 오늘 데이터 조회
		$base_date = $current_day;
	}

	//기준 시간 설정
	$base_time = '';
	$time_arr = array('02','05','08','11','14','17','20','23');

	for($i = count($time_arr) - 1; $i >= 0; $i--) {
		 if($current_hour >= intval($time_arr[$i])){
			$base_time = sprintf("%02d00", $time_arr[$i]);
			break;
		}
	}

	$queryParams .= '&' . urlencode('base_date') . '=' . urlencode($base_date);
	$queryParams .= '&' . urlencode('base_time') . '=' . urlencode($base_time);
	$queryParams .= '&' . urlencode('nx') . '=' . urlencode($nx);
	$queryParams .= '&' . urlencode('ny') . '=' . urlencode($ny);

	curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);

	//xml 데이터 파싱 (simplexmlelement 클래스 사용)
	$xml = new SimpleXMLElement($response);

	//단기예보 데이터 루프 돌리기
	$item_cnt = count($xml->body->items->item);

	// 필요한 카테고리 목록(tmp: 온도, sky: 날씨, pty: 강수 형태)
	$category_arr = array('TMP', 'SKY', 'PTY');

	$category_map = array(
		'TMP' => '기온',
		'SKY' => '하늘상태',
		'PTY' => '강수형태',
	);

	//단기예보 데이터 담을 빈 배열
	$short_weather_data = array();
	for($i=0; $i<$item_cnt; $i++){
		$item = $xml->body->items->item[$i];
		$category = (string)$item->category; //카테고리
		$fcst_time = (string)$item->fcstTime; //예보시간(단기예보 발표시간과 다름, 시간대에 맞는 예보이다.)
		$fcst_value = (string)$item->fcstValue; //예보시간에 맞는 카테고리의 값

		//필요한 카테고리만 추출하기
		if(in_array($category, $category_arr)){
			//하늘상태(SKY) 코드 : 맑음(1), 구름많음(3), 흐림(4)
			if($category === 'SKY'){
				if($fcst_value == 1){
					$fcst_value = '맑음';
				}
				if($fcst_value == 3){
					$fcst_value = '구름많음';
				}
				if($fcst_value == 4){
					$fcst_value = '흐림';
				}
			}
			//강수형태(PTY) 코드 :없음(0), 비(1), 비/눈(2), 눈(3), 소나기(4)
			if($category === 'PTY'){
				if($fcst_value == 0){
					$fcst_value = '없음';
				}
				if($fcst_value == 1){
					$fcst_value = '비';
				}
				if($fcst_value == 2){
					$fcst_value = '비 또는 눈';
				}
				if($fcst_value == 3){
					$fcst_value = '눈';
				}
				if($fcst_value == 4){
					$fcst_value = '소나기';
				}
			}
			//기온(TMP) 카테고리에 단위 추가
			if($category === 'TMP'){
				//기온 값 가져오기
				$tmp = (float)$fcst_value; // 기온 값(float 타입으로 변환)

				//섭씨(℃) 단위 추가
				$tmp_val = $tmp . '℃'; // 기온 값에 섭씨(℃) 단위 추가

				//기온 값 업데이트
				$fcst_value = $tmp_val;
			}
			//빈 배열 하나 생성 후 카테고리, 예보시간, 카테고리 값 array_push
			$temp = array();
			$temp['카테고리'] = $category_map[$category];
			$temp['예보시간'] = $fcst_time;
			$temp['날씨정보'] = $fcst_value;

			array_push($short_weather_data, $temp);
		}
	}
	$weather_data_cnt = count($short_weather_data);

	// 오늘과 내일의 예보 데이터를 담을 배열 초기화
	$today_data = array();
	$tomorrow_data = array();

	//데이터가 내일로 넘어가는지 확인
	$found_day = false;

	//파싱한 데이터를 오늘과 내일로 분류
	for($i=0; $i<$weather_data_cnt; $i++){
		$data = $short_weather_data[$i];
		$fcst_time = strtotime($data['예보시간']);
		$hour = date('H', $fcst_time);

		// 첫 번째 0000시를 만나면 내일로 분류
		if($hour === '00' && $found_day === false){
			$found_day = true;
		}
		if($found_day === false){
			// 내일로 분류되지 않은 데이터는 오늘로 분류
			array_push($today_data, $data);
		}else{
			// 내일로 분류된 데이터는 내일로 분류
			array_push($tomorrow_data, $data);
		}
	}

	curl_close($ch);

	echo '<script>var year="'.$year.'";</script>';
	echo '<script>var month="'.$month.'";</script>';
	echo '<script>var total_day="'.$total_day.'";</script>';
	echo '<script>var start_week="'.$start_week.'";</script>';
?>

중기예보(3일 후~7일 후까지 예보):
<select onchange='get_weather(this.value)' id='reg_select'>
	<option value="" selected disabled>지역선택</option>
	<?php
	for($i=0; $i<count($regions); $i++){
		$code = $regions[$i]['code'];
		$name = $regions[$i]['name'];
	?>
	<option value='<?=$code?>'><?=$name?></option>
	<?php
	}
	?>
</select>
<div id="popup"></div>
<section>
	<div id='btn_area'>
		<!-- 이전 달 버튼 -->
		<button id='prev_month' onclick='prev_month()'>이전달</button>
		<h2 id='year_month'><?=$year?>년 <?=$month?>월</h2>
		<!-- 다음 달 버튼 -->
		<button id='next_month' onclick='next_month()'>다음달</button>
	</div>
	<table id="weather_table" border="1">
		<thead>
			<tr>
				<th>일</th>
				<th>월</th>
				<th>화</th>
				<th>수</th>
				<th>목</th>
				<th>금</th>
				<th>토</th>
			</tr>
		</thead>
		<tbody>
		<?php
		// 달력 출력
		$day = 1;
		$prev_month_last_day = date('t', strtotime('-1 month', $time)); // 이전달 마지막 날
		for($i=0; $i<$total_week; $i++){
		?>
			<tr>
			<?php
			for($j=0; $j<7; $j++){
				$current_date = date("j", strtotime("now")); // 현재 날짜 가져오기
				$tomorrow_date = date("j", strtotime('+ 1 day'));
				$holiday_date = date("w", strtotime("$year-$month-$day"));
				// 첫 주의 시작 요일 이전은 이전 달의 날짜로 채우기
				if($i==0 && $j<$start_week){
					$prev_month_day = $prev_month_last_day - $start_week + $j + 1;
			?>
				<td class="other_month"><?= $prev_month_day ?></td>
				<?php
					}else{
					// 마지막 날짜 이후는 다음 달의 날짜로 채우기
					if($day > $total_day){
						$next_month_day = $day - $total_day;
				?>
				<td class="other_month"><?= $next_month_day ?></td>
				<?php
						$day++;
						}else{
							//현재 달의 날짜 출력
							// 현재 날짜에 클래스 추가
							$class = '';
							$weather_info = array();
							if ($holiday_date == 6){ // 토요일일 경우
								$class = 'saturday';
							}
							if($holiday_date == 0){ // 일요일일 경우
								$class = 'sunday';
							}
							if($day == $tomorrow_date && $holiday_date != 0 && $holiday_date != 6){ // 내일인 경우, 단 내일이 토요일이거나 일요일은 제외
								$class = 'tomorrow_date';
							}
							// 내일이 일요일인 경우, 내일을 나타내는 클래스와 일요일을 나타내는 클래스 모두 할당
							if($day == $tomorrow_date && ($holiday_date == 0 || $holiday_date == 6)){
								$class = 'tomorrow_date_holiday';
							}
							if($day == $current_date){ // 오늘인 경우
								$class = 'current_date';
								//데이터는 3개만 나오도록
								$today_data_slice = array_slice($today_data, 0, 3);
								for($k=0; $k<count($today_data_slice); $k++){
									$temp = array();
									$temp['카테고리'] = $today_data[$k]['카테고리'];
									$temp['날씨정보'] = $today_data[$k]['날씨정보'];

									array_push($weather_info, $temp);
								}
							}
							if($day == $tomorrow_date){ // 내일인 경우
								$tomorrow_data_slice = array_slice($tomorrow_data, 0, 3);
								for($k=0; $k<count($tomorrow_data_slice); $k++){
									$temp = array();
									$temp['카테고리'] = $tomorrow_data[$k]['카테고리'];
									$temp['날씨정보'] = $tomorrow_data[$k]['날씨정보'];

									array_push($weather_info, $temp);
								}
							}
							// 오늘과 내일의 셀에 클릭 이벤트 추가
							if($class == 'current_date' || $class == 'tomorrow_date' || $class == 'tomorrow_date_holiday'){
								$click_event = "onclick='show_weather_popup($day)'";
							}else{
								$click_event = '';
							}
				?>
				<td class='<?=$class?>' <?=$click_event?>><span class="date"><?=$day?></span><br>
				<?php
							//배열에 저장된 모든 날씨 정보 출력
							for($l=0; $l<count($weather_info); $l++){
								echo $weather_info[$l]['카테고리'] . ' : ';
								echo $weather_info[$l]['날씨정보'] . '<br>';
							}
					?>
				</td>
			<?php
							$day++;
						}
					}
				}
			?>
			</tr>
		<?php
		}
		?>
		</tbody>
	</table>
</section>