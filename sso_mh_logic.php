<?php
error_reporting(0);
include_once('class.DotEnv.php');

use DevCoder\DotEnv;
(new DotEnv(__DIR__ . '/.env'))->load();

function getToken( $baseUrl ){
	$userName = getenv("MH_USERNAME"); 
	$passwd = getenv("MH_PASS"); 

	$ch = curl_init();
	curl_setopt_array($ch, array(
			CURLOPT_URL => "$baseUrl/v2/auth/",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
		)
	);
	curl_setopt($ch, CURLOPT_USERPWD, $userName . ":" . $passwd);
	$tokenResponse = curl_exec($ch);
	$tokenError = curl_error($ch);
	curl_close($ch);
	if( $tokenError ) {
		echo "Oops, something goes wrong:" . $tokenError;
		return "";
	}

	// decode result
	$tokenResult = json_decode($tokenResponse, true);
	$token = $tokenResult["token"];

	return $token;
}


function getCompanyId($baseUrl, $apiKey, $token, $partnerCode) {
	$ch = curl_init();
	curl_setopt_array($ch, array(
			CURLOPT_URL => "$baseUrl/v2/campaigns/partners/$partnerCode/",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
		)
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'token: ' . $token,
			'MHUB-API-KEY: ' . $apiKey,
			'Content-Type: application/json',
		));
	$rsp = curl_exec($ch);
	$rsError = curl_error($ch);
	curl_close($ch);
	if( $rsError ) {
		echo "Oops, something goes wrong:" . $rsError;
		return "";
	}

	$res = json_decode($rsp, true);
	$companyId = $res["company_id"];

	return $companyId;
}

function setCompanyId($baseUrl, $apiKey, $token, $partnerCode, $companyIdPrefix) {
	$ch = curl_init();
	curl_setopt_array($ch, array(
			CURLOPT_URL => "$baseUrl/v2/campaigns/partners/$partnerCode/",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
		)
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'token: ' . $token,
			'MHUB-API-KEY: ' . $apiKey,
			'Content-Type: application/json'
		));
	$data = array(
			"company_id" => $companyIdPrefix.$partnerCode
		);
	$pdata = json_encode($data);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $pdata);
	$rsp = curl_exec($ch);
	$rsError = curl_error($ch);
	curl_close($ch);
	if( $rsError ) {
		echo "Oops, something goes wrong:" . $rsError;
		return false;
	}

	return true;
}

function updateAgent($baseUrl, $apiKey, $token, $userEmail, $agentId){
	$ch = curl_init();
	curl_setopt_array($ch, array(
			CURLOPT_URL => "$baseUrl/v2/campaigns/agents/$agentId/",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "PUT",
		)
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'token: ' . $token,
			'MHUB-API-KEY: ' . $apiKey,
			'Content-Type: application/json'
		));
	$data = array(
			"user_id" => $userEmail
		);
	$pdata = json_encode($data);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $pdata);
	$rsp = curl_exec($ch);
	$rsError = curl_error($ch);
	curl_close($ch);
	if( $rsError ) {
		echo "Oops, something goes wrong:" . $rsError;
		return false;
	}
	return true;
}

function createOrUpdateAgent($baseUrl, $apiKey, $token, $partnerCode, $userEmail, $firstName, $lastName, $mobile) {
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => "$baseUrl/v2/campaigns/all-agents/$partnerCode/",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 10,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
	)
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'token: ' . $token,
		'MHUB-API-KEY: ' . $apiKey,
		'Content-Type: application/json',
	));
	$rsp = curl_exec($ch);
	$rsError = curl_error($ch);
	curl_close($ch);
	if( $rsError ) {
		echo "Oops, something goes wrong:" . $rsError;
	}
	//echo "|".$rsp."|<br><br>";
	$res = json_decode($rsp, true);
	$agents = $res["agents"];
	foreach($agents as $ag){
		if( $ag["email"] == $userEmail )
		{
			$userId = $ag["user_id"];
			if( empty($userId) || $userId != $userEmail ){
				updateAgent($baseUrl, $apiKey, $token, $userEmail, $ag["agent_id"]);
			}
			return true;
		}
	}

	newAgent($baseUrl, $apiKey, $token, $partnerCode, $userEmail, $firstName, $lastName, $mobile);
	return true;
}

function newAgent($baseUrl, $apiKey, $token, $partnerCode, $userEmail, $firstName, $lastName, $mobile) {
	$ch = curl_init();
	curl_setopt_array($ch, array(
			CURLOPT_URL => "$baseUrl/v2/campaigns/agents/",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
		)
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'token: ' . $token,
			'MHUB-API-KEY: ' . $apiKey,
			'Content-Type: application/json',
		));
	$pdata = json_encode(array(
		"partner_code" => $partnerCode,
		"user_id" => $userEmail,
		"first_name" => $firstName,
		"last_name" => $lastName,
		"email" => $userEmail,
		"mobile_phone" => $mobile
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $pdata);
	$rsp = curl_exec($ch);
	$rsError = curl_error($ch);
	curl_close($ch);
	if( $rsError ) {
		echo "Oops, something goes wrong:" . $rsError;
		return false;
	}
	$result = json_decode($rsp, true);
	if( !empty($result["error"]) ){
		echo "createAgent: ".$rsp."<br>";
		die();
	} 
	return $result["successful"];
}

function getEmbedString($baseUrl, $apiKey, $token, $companyId, $userEmail){
	$data = array(
		'company_id' => $companyId, 
		'user_id' => $userEmail,
		'agent_email' => $userEmail	
	);
	$pdata = json_encode($data);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "$baseUrl/v2/campaigns/agent-portal-code");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($curl, CURLOPT_POSTFIELDS, $pdata);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'token: ' . $token,
		'MHUB-API-KEY: ' . $apiKey,
		'Content-Type: application/json'
	));

	$resp  = curl_exec($curl);
	$respError = curl_error($curl);
	curl_close($curl);
	if( $respError ) {
		echo "Oops, something goes wrong:" . $respError;
	}

	$htmlResult = json_decode($resp, true);
	$htmlString = $htmlResult["html_string"];

	return $htmlString;
}

?>


