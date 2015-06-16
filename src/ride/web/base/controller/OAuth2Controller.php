<?php

namespace ride\web\base\controller;

use ride\library\http\Response;
use ride\library\security\exception\AuthenticationException;
use ride\library\security\exception\InactiveAuthenticationException;
use ride\library\security\SecurityManager;

/**
 * Controller to manage OAuth2 authorization
 */
class OAuth2Controller extends AbstractController {

    /**
     * Session key for the referer when submitting the login action
     * @var string
     */
    const SESSION_REFERER = 'authentication.referer';

    /**
     * Action to manage OAuth authorization
     * @param \ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @param string $authenticator Id of the OAuth2Authenticator dependency
     * @return null
     */
    public function authorizeAction(SecurityManager $securityManager, $authenticator = null) {
        $code = $this->request->getQueryParameter('code');
        $user = null;

        // when requested, make sure the current user is logged out
        if ($this->request->getQueryParameter('logout') == 'true') {
            $securityManager->logout();
        }

        // try to obtain current user
        try {
            $user = $securityManager->getUser();
        } catch (InactiveAuthenticationException $exception) {
            if ($code) {
                // coming back from oauth service
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.authentication.inactive');
            }
        } catch (AuthenticationException $exception) {
            if ($code) {
                // coming back from oauth service
                $this->response->setStatusCode(Response::STATUS_CODE_UNPROCESSABLE_ENTITY);

                $this->addError('error.authentication');
            }
        }

        if ($user || $code) {
            // there is a user or we're coming back from oauth service
            // redirect to the login referer
            $this->response->setRedirect($this->getRefererFromSession());

            return;
        }

        // update referers
        $this->setRefererToSession();

        // redirect to the oauth service
        $authenticator = $this->dependencyInjector->get('ride\\library\\security\\authenticator\\OAuth2Authenticator', $authenticator);

        $this->response->setRedirect($authenticator->getAuthorizationUrl());
    }

    /**
     * Sets the referer to redirect to when performing a login action
     * @return null
     */
    private function setRefererToSession() {
        $loginUrl = $this->getUrl('login');

        $requestUrl = $this->getReferer();
        if ($requestUrl == $loginUrl) {
            $requestUrl = null;
        }

        $session = $this->request->getSession()->set(self::SESSION_REFERER, $requestUrl);
    }

    /**
     * Gets the referer to redirect to when performing a login action
     * @return string
     */
    private function getRefererFromSession() {
        $url = null;

        if ($this->request->hasSession()) {
            $session = $this->request->getSession();

            $url = $session->get(self::SESSION_REFERER);

            $session->set(self::SESSION_REFERER);
        }

        if ($url === null) {
            $url = $this->request->getBaseUrl();
        }

        return $url;
    }

}
