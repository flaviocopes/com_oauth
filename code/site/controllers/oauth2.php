<?php 
/**
 * @version		0.1.0
 * @package		com_oauth
 * @copyright	Copyright (C) 2010 Beyounic SA & Joocode. All rights reserved.
 * @license		GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.beyounic.com - http://www.joocode.com
 */

class ComOauthControllerOauth2 extends ComOauthControllerOauth 
{
	/**
	 * 
	 * 
	 * @param string $layout
	 * @param string $view
	 */
	protected function _processDefault($layout, $view)
	{
		$site = KFactory::tmp('site::com.oauth.model.sites')->slug($view)->getItem();

		if (!KRequest::get('get.code', 'raw'))
		{
			$app = KFactory::tmp('lib.joomla.application');
			$url = KRequest::get('session.caller_url', 'string');
			$message = 'Old Token';
			$app->redirect($url, $message); 
		}
		else 
		{
			$model = KFactory::tmp('site::com.'.$view.'.model.apis');
			$model->initialize(array($site->consumer_key, $site->consumer_secret));
			$model->fetch(
				$model->accessTokenURL().
				(strpbrk($model->accessTokenURL(), '?') ? '&' : '?').
				'client_id='.$site->consumer_key.
				'&client_secret='.$site->consumer_secret.
				'&code='.KRequest::get('get.code', 'raw').
				'&redirect_uri='.urlencode($model->getRedirectUri()).
				$model->getOtherAccessTokenParameters()
				);

			$access_token = $model->extractAccessToken();
		 	$model->setToken($access_token, 0);

		 	KRequest::set('session.service', $view);
		 	$model->redirect($model->storeToken($access_token));
		}
	}
		
	/**
	 * 
	 * 
	 * @param string $layout
	 * @param string $view
	 */
	protected function _processRedirect($layout, $view)
	{
		$service = KFactory::tmp('site::com.oauth.model.sites')->slug($view)->getItem();
		$model = KFactory::tmp('site::com.'.$view.'.model.apis');
		$model->initialize(array($service->consumer_key, $service->consumer_secret));
		
		if (!$service->title)
		{
			echo 'Service not enabled';
		}
		else
		{
			KFactory::tmp('lib.joomla.application')->redirect(
				$model->authenticateURL().
				(strpbrk($model->authenticateURL(), '&') ? '&' : '?').
				'client_id='.$service->consumer_key.
				'&response_type=code'.
				'&redirect_uri='.urlencode($model->getRedirectUri()).
				$model->getOtherAuthenticateParameters());
		}
	}
}