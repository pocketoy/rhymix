<?php
namespace Rhymix\Framework\Drivers\Social;



/**
 * The dummy SMS driver.
 */
class Discord extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	const DISCORD_API_URL = 'https://discord.com/';
	
	function createAuthUrl($type)
	{
		$params = array(
			'client_id'        => \Sociallogin::getConfig()->discord_client_id,
			'redirect_uri'    => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'discord'),
			'response_type' => 'code',
			'scope' => 'identify email',
			'state' => $_SESSION['sociallogin_auth']['state'],
		);

		return self::DISCORD_API_URL . 'oauth2/authorize?' . http_build_query($params, '', '&');
	}
	
	function authenticate()
	{
		$code = \Context::get('code');
		
		$config = \Sociallogin::getConfig();
		$post = [
			"grant_type" => "authorization_code",
			"client_id" => $config->discord_client_id,
			"client_secret" => $config->discord_client_secret,
			"redirect_uri" => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback', 'service', 'discord'),
			"code" => $code,
		];
		$token = $this->requestAPI('api/oauth2/token', $post);

		$this->setAccessToken($token['access_token']);
		$this->setRefreshToken($token['refresh_token']);
	}

	function loading()
	{
		// 토큰 체크
		$token = $this->getAccessToken();
		if (!$token)
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}
		
		$headers = array(
			'Authorization' => "Bearer {$token}",
		);
		$user_info = $this->requestAPI('api/users/@me', [], $headers);

		// ID, 이름, 프로필 이미지, 프로필 URL
		$this->setEmail($user_info['email']);
		$this->setId($user_info['id']);
		$this->setName($user_info['username']);
		// 프로필 인증
		$this->setVerified(true);

		// 전체 데이터
		$this->setProfileEtc($user_info);
	}

	function requestAPI($url, $post = array(), $authorization = null, $delete = false)
	{
		return json_decode(\FileHandler::getRemoteResource(self::DISCORD_API_URL . $url, null, 3, empty($post) ? 'GET' : 'POST', null, $authorization, array(), $post, array()), true);
	}
}