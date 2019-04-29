<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件
function err($code = 0, $msg, $more = "") {

	try {
		if (is_array($msg)) {
			return array('stat' => 0, 'errcode' => $code, "errmsg" => json_encode($msg, JSON_UNESCAPED_UNICODE) . $more, "errmsg_en" => "");
		}
		$msg = mb_substr($msg, 0, 150, "UTF-8");
		$zh = $msg;
		$en = $msg;
		$lang = db('Nlang');
		if ($lang->where(["sys_lang" => $msg])->count() == 0) {
			$lang->insert(["sys_lang" => $msg]);
		} else {
			$enlang = $lang->where(["sys_lang" => $msg])->find();
			if ($enlang && !empty($enlang['cn_lang'])) {
				$zh = $enlang['cn_lang'];
				$en = $enlang['en_lang'];
			}
		}
		return array('stat' => 0, 'errcode' => $code, "errmsg" => $zh . $more, "errmsg_en" => $en);
	} catch (\Exception $exc) {
		return array('stat' => 0, 'errcode' => $code, "errmsg" => $exc->getMessage(), "errmsg_en" => $exc->getMessage());
	}

}

function suc($data = array()) {
	return array('stat' => 1, 'data' => $data);
}
function mexit($msg = "错误", $code = 1001) {
	exit(json_encode(err($code, $msg), JSON_UNESCAPED_UNICODE));
}
function write($data) {
	if (is_array($data)) {
		\think\Log::write(print_r($data, true));
	} else {
		\think\Log::write($data);
	}

}
function mlog($msg) {
	if (is_array($msg)) {
		$data["content"] = request()->action() . print_r($msg, true);
		$data["add_time"] = date("Y-m-d H:i:s");
		$log = Think\Db::table('Nlog');
		$log->insert($data);
	} else {
		$data["content"] = $msg;
		$data["add_time"] = date("Y-m-d H:i:s");
		$log = Think\Db::table('Nlog');
		$log->insert($data);
	}

}
function actionlog($content = "", $dataf = "") {
	$request = request();
	if (is_array($dataf)) {
		$data["module"] = $request->module();
		$data["controller"] = $request->controller();
		$data["action"] = $request->action();
		$data["content_des"] = $content;
		$data["data"] = json_encode($dataf);
		$data["add_time"] = date("Y-m-d H:i:s");
		$log = Think\Db::table('NenterUserlog');
		$log->insert($data);
	} else {
		$data["module"] = $request->module();
		$data["controller"] = $request->controller();
		$data["action"] = $request->action();
		$data["content_des"] = $content;
		$data["data"] = $dataf;
		$data["add_time"] = date("Y-m-d H:i:s");
		$log = Think\Db::table('NenterUserlog');
		$log->insert($data);
	}

}
function curl_post($url, $post = NULL, array $options = array()) {
	$defaults = array(
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_URL => $url,
		CURLOPT_FRESH_CONNECT => 1,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_FORBID_REUSE => 1,
		CURLOPT_TIMEOUT => 4,
		CURLOPT_POSTFIELDS => $post,
	);

	$ch = curl_init();
	curl_setopt_array($ch, ($options + $defaults));
	if (!$result = curl_exec($ch)) {
		trigger_error(curl_error($ch));
	}
	curl_close($ch);
	return $result;
}
function utf_8($data) {
	if (empty($data)) {
		return $data;
	}
	$new_array = array();
	foreach ($data as $k => $v) {
		$new = array();
		foreach ($v as $kk => $vv) {
			$code = mb_detect_encoding($kk, "GBK");
			if ($code) {
				$kk = mb_convert_encoding($kk, "UTF-8", "GBK");
			}
			$new[$kk] = $vv;
		}
		$new_array[] = $new;
	}
	return $new_array;
}
function gbk($data) {
	if (empty($data)) {
		return $data;
	}
	$new_array = array();
	foreach ($data as $k => $v) {
		$new = array();
		foreach ($v as $kk => $vv) {
			$code = mb_detect_encoding($kk, "UTF-8,GBK");
			if ($code) {
				$kk = mb_convert_encoding($kk, "GBK", "UTF-8");
			}
			$new[$kk] = $vv;

		}
		$new_array[] = $new;
	}
	return $new_array;
}
function setarray($data) {
	foreach ($data as $k => $v) {
		if (empty($v) && !is_numeric($v)) {
			unset($data[$k]);
		}
	}
	return $data;
}
function trimarray($data) {
	if (is_array($data)) {
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $kk => $vv) {
					$data[$k][$kk] = trim($vv);
					if ($kk = "ROW_NUMBER") {
						unset($data[$k][$kk]);
					}
				}
			}

		}

	}
	return $data;
}
function trimarrayone($data) {
	if (is_array($data)) {
		foreach ($data as $kk => $vv) {

			$data[$kk] = trim($vv);
			if ($kk = "ROW_NUMBER") {
				unset($data[$kk]);
			}
		}
	}

	return $data;
}

//字符串转为二进制字符串
function StrToBin($str) {
	$arr = preg_split('/(?<!^)(?!$)/u', $str);
	foreach ($arr as &$v) {
		$temp = unpack('H*', $v);
		$v = base_convert($temp[1], 16, 2);
		while ($v < 8) {
			$v = '0' . $v;
		}

		unset($temp);
	}
	return join('', $arr);
}
