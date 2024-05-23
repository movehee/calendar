<?php
	include $_SERVER['DOCUMENT_ROOT'].'/function/php.php';

	session_start();
	$domain = 'http://'.$_SERVER['SERVER_NAME']; //도메인 변수
	$file_path = str_replace('.php', '', $_SERVER['PHP_SELF']); //php파일 경로

	//코어타입 상수가 선언x => 로그인 화면 이동
	if(defined('__CORE_TYPE__') === false){
		echo '<script>location.href ="'.$domain.'";</script>';
		exit();
	}

	//현재년도 가져오기
	$current_year = date("Y");
	//현재년도 세션에 저장
	$_SESSION['current_year'] = $current_year;

	//디버깅 모드
	define('__DEBUG_MODE__', true);

	$core_type_arr = array('view', 'api');

	//코어타입의 유효성 검사
	if(in_array(__CORE_TYPE__, $core_type_arr) === true){
		//로그인 검사가 필요하지 않은 페이지

		$no_login_page = array();

		//프로젝트1
		$no_login_page[] = 'pro/weather_report';
		$no_login_page[] = 'pro/special_day';
		$no_login_page[] = '/api/api_get_weather';
		$no_login_page[] = '/api/api_short_weather';
		$no_login_page[] = '/api/api_change_month';

		//로그인 검사가 필요한 경우
		if(in_array($file_path, $no_login_page) === false){
			if(isset($_SESSION['id']) === false){
				echo '<script>location.href = "'.$domain.'";</script>';
				exit();
			}
			if(isset($_SESSION['team_sid']) === false){
				echo '<script>location.href = "'.$domain.'";</script>';
				exit();
			}
			//아이디와 팀 코드 상수 선언
			define('__USER_ID__', $_SESSION['id']);
			define('__TEAM_SID__', $_SESSION['team_sid']);
			define('__YEAR__', $_SESSION['current_year']);

			//로그인 이후 id와 team_sid 유효성 검사(db와 비교 조회)
			$select_sql = "SELECT team_sid FROM user_data WHERE id = '".__USER_ID__."';";
			$query_result = sql($select_sql);
			$query_result = select_process($query_result);
			//조회결과가 없으면 === 없는 id라 로그인 화면으로 이동
			if($query_result['output_cnt'] === 0){
				echo '<script>location.href = "'.$domain.'";</script>';
				exit();
			}
			//로그인된 팀코드와 세션에 저장된 팀 코드가 불일치시 로그인 화면이동
			if($query_result[0]['team_sid'] !== __TEAM_SID__){
				echo '<script>location.href = "'.$domain.'";</script>';
				exit();
			}
		}

		//코어타입 상수가 view 일 때 수행, **view 타입일 때 header.php 넣기**
		if(__CORE_TYPE__ === 'view'){
			$echo_js = ''; // js script 태그 안에 한번에 출력할 문자열 변수
			$echo_js .= 'var domain ="'.$domain.'";'; //도메인 변수 선언
			if(__DEBUG_MODE__ === true){
				$echo_js .= 'var __DEBUG_MODE__ = true;';
			}else{
				$echo_js .= 'var __DEBUG_MODE__ = false;';
			}
			$echo_js .= file_get_contents($_SERVER['DOCUMENT_ROOT'].'/function/js.js'); // js.js이어 붙이기
			echo '<script>'.$echo_js.'</script>'; // js core 출력
			echo '<script src = "'.$domain.'/lib/jquery.js"></script>'; //jquery 출력

			//skin.js 파일 불러오기
			if(file_exists($_SERVER['DOCUMENT_ROOT'].'/skin/skin.js') === true){
				$skin_js = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/skin/skin.js');
				echo '<script id="skin_js">'.$skin_js.'</script>';
			}
			if(file_exists($_SERVER['DOCUMENT_ROOT'].'/skin/skin.css') === true){
				$skin_css = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/skin/skin.css');
				echo '<style id="skin_css">'.$skin_css.'</style>';
			}
			//page_css파일 불러오기
			if(file_exists($_SERVER['DOCUMENT_ROOT'].$file_path.'.css') === true){
				$page_css = file_get_contents($_SERVER['DOCUMENT_ROOT'].$file_path.'.css');
				echo '<style id="page_css">'.$page_css.'</style>';
			}
			//page_js파일 불러오기
			if(file_exists($_SERVER['DOCUMENT_ROOT'].$file_path.'.js') === true){
				$page_js = file_get_contents($_SERVER['DOCUMENT_ROOT'].$file_path.'.js');
				echo '<script id="page_js">'.$page_js.'</script>';
			}
			// 탑바 불러오기
			if(in_array($file_path, $no_login_page) === false){
				$topbar = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/skin/topbar.html');
				echo $topbar;
			}
		}
		
		//코어타입 상수가 api 일 때 수행
		if(__CORE_TYPE__ === 'api'){
			$_POST = file_get_contents('php://input');
			$_POST = json_decode($_POST, true);
		}
	}
?>