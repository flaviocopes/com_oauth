<?php 
/**
 * @version		0.1.0
 * @package		com_oauth
 * @copyright	Copyright (C) 2010 Beyounic SA & Joocode. All rights reserved.
 * @license		GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.beyounic.com - http://www.joocode.com
 */

class ComOauthControllerOauth extends ComDefaultControllerDefault 
{	
	protected function _actionRead()
	{
		$layout = KRequest::get('get.layout', 'string');
		$view = KRequest::get('get.view', 'string');
		
		if ($view == 'oauth') 
		{		
			$layout = KRequest::get('get.layout', 'string');
			KRequest::set('session.caller_url', JRoute::_(base64_decode(KRequest::get('get.caller_url', 'url'))));
			KRequest::set('session.return_url', JRoute::_(base64_decode(KRequest::get('get.return_url', 'url'))));
						
			$user = KFactory::get('lib.joomla.user');
			$url = '';
		
			if ($layout == 'add')
			{
				$url = JRoute::_('index.php?option=com_oauth&view='.KRequest::get('get.service', 'string').'&layout=redirect'); 
			}
			else
			{
				$hasToken = false;		
				
				if ($user->id)
				{
					if (KFactory::tmp('site::com.oauth.model.tokens')
						->set('service', KRequest::get('get.service', 'string'))
						->set('userid', $user->id)
						->getTotal())
					{
						$hasToken = true;
					}
				}
				else
				{
					if (KRequest::get('session.service', 'string') == KRequest::get('get.service', 'string') && KRequest::get('session.oauth_token', 'string'))
					{
						$hasToken = true;
					} 
				}
				
				if ($hasToken)
				{
					$url = JRoute::_(base64_decode(KRequest::get('get.return_url', 'url')));
				}
				else
				{	
					$url = JRoute::_('index.php?option=com_oauth&view='.KRequest::get('get.service', 'string').'&layout=redirect'); 
				}
			}
			
			KFactory::tmp('lib.joomla.application')->redirect($url);
		}
		else
		{
			if ($layout == 'redirect' || $layout == '')
			{
				$this->_processRedirect($layout, $view);
			}
			elseif ($layout == 'default')
			{
				$this->_processDefault($layout, $view);
			}
		
		}
			
		return parent::_actionRead();
	}
	
	/**
	 * 
	 * 
	 * @param string $layout
	 * @param string $view
	 */
	protected function _processDefault($layout, $view)
	{
		$site = KFactory::get('site::com.oauth.model.sites')->slug($view)->getItem();
		
		if (KRequest::get('session.request_token', 'raw') !== KRequest::get('request.oauth_token', 'raw')) 
		{	
			$app = KFactory::tmp('lib.joomla.application');
			$url = KRequest::get('session.caller_url', 'string');
			$message = 'Old Token';
			$app->redirect($url, $message); 
		}
		else
		{	
			$model = KFactory::get('site::com.oauth.model.'.$view.'s');
			$model->initialize(array($site->consumer_key, $site->consumer_secret));
			$model->setToken(KRequest::get('get.oauth_token', 'raw'), KRequest::get('session.request_token_secret', 'raw'));
		 	$model->storeToken($model->getAccessToken());   
			$model->redirect();
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
		$service = KFactory::get('site::com.oauth.model.sites')->slug($view)->getItem();
		$model = KFactory::get('site::com.oauth.model.'.KInflector::pluralize($view));
		$model->initialize(array($service->consumer_key, $service->consumer_secret));
		 
		if (!$service->title)
		{
			echo 'Service not enabled';
		}
		else
		{
			/* Get temporary credentials. */
			$request_token = $model->getRequestToken($model->requestTokenURL(), 'http://'.$_SERVER['HTTP_HOST'].JRoute::_('index.php?option=com_oauth&view='.$view.'&layout=default'));  
			KRequest::set('session.request_token', $request_token['oauth_token']);
			KRequest::set('session.request_token_secret', $request_token['oauth_token_secret']);
			KFactory::tmp('lib.joomla.application')->redirect($model->authorizeURL().'?oauth_token='.$request_token['oauth_token']);
		}
	}
}