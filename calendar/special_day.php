<?php
	define('__CORE_TYPE__', 'view');
	include $_SERVER['DOCUMENT_ROOT'].'/function/core.php';

	// 현재 시간 설정 === php.ini 적용 안됨 문제.
	date_default_timezone_set('Asia/Seoul');

	$year = date('Y'); // 현재 년도
	$month = date('m'); // 현재 달

	if(isset($_POST['date']) === true){
		$post_date = explode('-', $_POST['date']);
		if(count($post_date) === 2){
			if(checkdate($post_date[1], "01", $post_date[0]) === true){
				$year = $post_date[0];
				$month = $post_date[1];
			}
		}
	}

	$date = "$year-$month-01"; // 시작 날짜
	$time = strtotime($date); // 시작 날짜의 타임스탬프
	$start_week = date('w', $time); // 시작 요일
	$total_day = date('t', $time); // 현재 달의 총 날짜
	$total_week = ceil(($total_day + $start_week) / 7); // 현재 달의 총 주차

	$prev_month = date('Y-m', strtotime('-1 month', strtotime($date)));
	$next_month = date('Y-m', strtotime('+1 month', strtotime($date)));

	//////////////////////////////////외부 API 호출 및 데이터 파싱
	$ch = curl_init();
	$url = 'http://apis.data.go.kr/B090041/openapi/service/SpcdeInfoService/getRestDeInfo';
	$queryParams = '?' . urlencode('serviceKey') . '=' . 'p8Lh%2BxLFef3XgV1yD9m4DyEspeLS7vVhR%2Fzk%2BVVEQ%2BD56xjv7jad5Me9hj%2B9nTcR4pTcn%2FRokDMnPRC0fNEHkA%3D%3D';
	$queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1');
	$queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('10');
	$queryParams .= '&' . urlencode('solYear') . '=' . urlencode($year);
	$queryParams .= '&' . urlencode('solMonth') . '=' . urlencode($month);

	curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);

	// XML 데이터 파싱 (SimpleXMLElement 클래스 사용)
	$xml = new SimpleXMLElement($response);
	// 데이터 요소에 직접적으로 접근
	$items = $xml->body->items->item;

	$special_date = array();

	for($i=0; $i<count($items); $i++){
		$item = $items[$i];
		$special = (string)$item->locdate;
		$name = (string)$item->dateName;

		// 날짜에 공휴일 이름 추가
		$special_date[$special] = $name;
	}

	// 주말, 공휴일 배열
	$weekend_holidays = array();
	$public_holidays = array();

	// 주말과 공휴일을 각각의 배열에 추가
	for($day=1; $day<=$total_day; $day++){
		$current_day = sprintf("%04d%02d%02d", $year, $month, $day);
		$weekend = date('w', strtotime($current_day));

		// 토요일(6), 일요일(0) 휴일로 처리
		if($weekend == 0 || $weekend == 6){
			array_push($weekend_holidays, $day);
		}

		// 공휴일 휴일로 처리
		if(isset($special_date[$current_day])){
			array_push($public_holidays, $day);
		}
	}

	// 연속되는 휴일 확인
	$holidays = array();
	for($day=1; $day <= $total_day; $day++){
		// 연속된 휴일의 개수를 저장할 변수
		$holidays_cnt = 0;

		// 기준 날짜부터 3일 동안의 휴일 확인
		for($i=0; $i<3; $i++){
			$current_day = $day + $i;

			// 현재 날짜가 총 날짜보다 크면 종료
			if($current_day > $total_day){
				break;
			}
			// 주말 휴일 또는 공휴일인 경우 연속되는 휴일 개수 증가
			if(in_array($current_day, $weekend_holidays) || in_array($current_day, $public_holidays)){
					$holidays_cnt++;
			}else{
				// 휴일이 아닌 경우, 연속되는 휴일 개수 초기화
				$holidays_cnt = 0;
			}
		}
		// 3일 이상 연속된 휴일이 있는 경우 배열에 추가 === 주황색으로 표시해야함
		if($holidays_cnt >= 3){
			for($i=$day; $i<$day+3; $i++){
				array_push($holidays, $i);
			}
		}
	}

	// 평일이 주말 또는 공휴일 사이에 있는 경우를 확인해서 4일 연속 휴일로 변경될 수 있는 날짜 찾기
	$annual = array();
	for($day=2; $day <= $total_day - 1; $day++){
		// 주말 또는 공휴일이 아닌 경우
		if(in_array($day, $weekend_holidays) === false && in_array($day, $public_holidays) === false){
			// 주말 또는 공휴일 사이에 평일이 있는지 확인
			if((in_array($day - 1, $weekend_holidays) || in_array($day - 1, $public_holidays)) && (in_array($day + 1, $weekend_holidays) || in_array($day + 1, $public_holidays))){
				// 4일 연속 휴일로 변경될 수 있는 날짜 추가
				array_push($annual, $day);
			}
		}
	}

	// 이전이나 이후에 3일 연속된 휴일이 있는 경우에도 노란색 셀로 표시
	for($i=0; $i<count($holidays); $i++){
		$holiday = $holidays[$i];
		if($holiday > 1){
			array_push($annual, $holiday - 1);
		}
		if ($holiday < $total_day - 1) {
			array_push($annual, $holiday + 1);
		}
	}

	curl_close($ch);

	echo '<script>var year="'.$year.'";</script>';
	echo '<script>var month="'.$month.'";</script>';
?>

<section>
	<div id="btn_area">
		<!-- 이전 달 버튼 -->
		<button id='prev_month' onclick='date_change("<?=$prev_month?>")'>이전달</button>
		<h2 id='year_month'><?=$year?>년 <?=$month?>월</h2>
		<!-- 다음 달 버튼 -->
		<button id='next_month' onclick='date_change("<?=$next_month?>")'>다음달</button>
	</div>
	<table id="special_table" border="1">
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
			for ($i = 0; $i < $total_week; $i++){
		?>
			<tr>
			<?php
				for($j=0; $j<7; $j++){
					$current_date = date("j", strtotime("now")); // 현재 날짜 가져오기
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
							// 주말과 공휴일 여부 확인
							$is_weekend_holiday = in_array($day, $weekend_holidays);
							$is_public_holiday = in_array($day, $public_holidays);
							$is_holiday = in_array($day, $holidays);
							$is_annual = in_array($day, $annual);

							// 셀에 적용할 클래스 설정
							$class = '';
							$annual_info = '';
							$text_class = 'black';
							if(($is_public_holiday || $is_weekend_holiday) && $is_holiday){
								// 3일 연속 휴일인 경우 오렌지 셀로 표시
								$class = 'class="orange-cell"';
							}else if($is_annual){
								// 4일 연속 휴일로 변경될 수 있는 경우 노란색 셀로 표시
								$class = 'class="yellow-cell"';
								$annual_info = '연차 사용 시 <br> 4일 이상 연속 휴무';
							}
							// 토요일인 경우
							if($j==6){
								$text_class = 'blue-text';
							}
							//일요일 or 공휴일인 경우
							if($j==0 || $is_public_holiday){
								$text_class = 'red-text';
							}
				?>
				<td <?= $class ?>>
					<span class="<?= $text_class ?>"><?= $day ?><br><?=$annual_info?>
				<?php
					// 공휴일이면 공휴일 이름 표시
					if($is_public_holiday){
					echo $special_date[sprintf("%04d%02d%02d", $year, $month, $day)];
				}
				?></span>
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