<?php
	define('__CORE_TYPE__', 'api');
	include $_SERVER['DOCUMENT_ROOT'].'/function/core.php';

	//넘어온 지역 아이디 유효성 검사
	if(isset($_POST['regId']) === false){
		nowexit(false, '정보가 없습니다.');
	}
	$regId = $_POST['regId'];

	//기본 지역 설정(부울)
	$ch = curl_init();
	$url = 'http://apis.data.go.kr/1360000/MidFcstInfoService/getMidLandFcst'; /*URL*/
	$queryParams = '?' . urlencode('serviceKey') . '=' . 'p8Lh%2BxLFef3XgV1yD9m4DyEspeLS7vVhR%2Fzk%2BVVEQ%2BD56xjv7jad5Me9hj%2B9nTcR4pTcn%2FRokDMnPRC0fNEHkA%3D%3D';

	$queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1'); /**/
	$queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('10'); /**/
	$queryParams .= '&' . urlencode('dataType') . '=' . urlencode('XML'); /**/
	$queryParams .= '&' . urlencode('regId') . '=' . urlencode($regId); /**/

	//현재 날짜 & 시간
	$current_date = date('Ymd');
	$current_time = date('H');
	$tmFc = array();
	// 현재 시간이 6시 이전이면 어제의 18시 예보 가져오기
	if($current_time < 6){
		$tmFc = date('Ymd', strtotime('-1 day')) . '1800';
	}
	// 현재 시간이 18시 이전이면 현재 6시 예보 가져오기
	if($current_time < 18){
		$tmFc = $current_date . '0600';
	}else{ // 현재 시간이 18시 이후면 당일의 18시 예보 가져오기
		$tmFc = $current_date . '1800';
	}
	$queryParams .= '&' . urlencode('tmFc') .'='. urlencode($tmFc);

	curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);

	//xml 데이터 파싱 (simplexmlelement 클래스 사용)
	$xml = new SimpleXMLElement($response);
	//데이터 요소에 직접적으로 접근
	$weather = $xml->body->items->item;

	$result['weather_data'] = $weather;

	nowexit(true, '데이터 불러오기에 성공했습니다.');

	curl_close($ch);

?>