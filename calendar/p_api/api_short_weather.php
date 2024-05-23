<?php
	define('__CORE_TYPE__', 'api');
	include $_SERVER['DOCUMENT_ROOT'].'/function/core.php';

	//넘어온 nx, ny, 날짜 값 유효성 검사
	if(isset($_POST['nx']) === false){
		nowexit(false, '정보가 없습니다.');
	}
	$nx = $_POST['nx'];

	if(isset($_POST['ny']) === false){
		nowexit(false, '정보가 없습니다.');
	}
	$ny = $_POST['ny'];

	if(isset($_POST['day']) === false){
		nowexit(false, '정보가 없습니다.');
	}
	$day = $_POST['day'];

	date_default_timezone_set('Asia/Seoul');

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
	$base_time = array();
	$time_arr = array('02','05','08','11','14','17','20','23');

	for($i = count($time_arr) - 1; $i >= 0; $i--) {
		 if ($current_hour >= intval($time_arr[$i])) {
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

	// 필요한 카테고리 목록(tmp: 온도, sky: 날씨, pty: 강수 형태, pop: 강수 확률 ,pcp: 강수량, reh: 상대 습도, sno: 적설량, vec: 풍향, wsd: 풍속)
	$category_arr = array('TMP', 'SKY', 'PTY', 'POP', 'PCP', 'REH', 'SNO', 'WSD');

	$category_map = array(
		'TMP' => '기온',
		'SKY' => '하늘상태',
		'PTY' => '강수형태',
		'POP' => '강수확률',
		'PCP' => '강수량',
		'REH' => '습도',
		'SNO' => '적설량',
		'WSD' => '풍속'
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
			//풍속(WSD) 카테고리에 단위 추가
			if($category === 'WSD'){
				//풍속 값 가져오기
				$wsd = (float)$fcst_value; //풍속 값(float 타입으로 변환)

				// m/s 단위 추가
				$wsd_val = $wsd . 'm/s'; //풍속 값에 m/s 단위 추가

				//풍속 값 업데이트
				$fcst_value = $wsd_val;
			}
			//강수확률(POP) 카테고리에 단위 추가
			if($category === 'POP'){
				//강수확률 값 가져오기
				$pop = (int)$fcst_value; //강수확률 값(int 타입으로 변환)

				//% 단위 추가
				$pop_val = $pop . '%'; //강수확률 값에 % 단위 추가

				//강수확률 값 업데이트
				$fcst_value = $pop_val;
			}
			//습도(REH) 카테고리에 단위 추가
			if($category === 'REH'){
				//습도 값 가져오기
				$reh = (int)$fcst_value; //습도 값(int 타입으로 변환)

				//% 단위 추가
				$reh_val = $reh . '%'; //습도 값에 % 단위 추가

				//습도 값 업데이트
				$fcst_value = $reh_val;
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

	//오늘과 내일의 예보 데이터를 담을 배열 초기화
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
	
	$result['today_data'] = $today_data;
	$result['tomorrow_data'] = $tomorrow_data;

	nowexit(true, '데이터 불러오기에 성공했습니다.');

	curl_close($ch);
?>
