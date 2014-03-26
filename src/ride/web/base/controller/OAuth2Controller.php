<?php

namespace ride\web\base\controller;

use ride\library\security\SecurityManager;

/**
 * Controller to manage OAuth2 authorization
 */
class OAuth2Controller extends AbstractController {

    /**
     * Action to manage OAuth authorization
     * @param ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @param string $authenticator Id of the OAuth2Authenticator dependency
     * @return null
     */
    public function authorizeAction(SecurityManager $securityManager, $authenticator = null) {
        $code = $this->request->getQueryParameter('code');
        if ($securityManager->getUser() || $code) {
            $url = $this->request->getBaseUrl();

            if ($this->request->hasSession()) {
                $session = $this->request->getSession();

                $url = $session->get(AuthenticationController::SESSION_REFERER_REQUEST, $url);

                $session->set(AuthenticationController::SESSION_REFERER_REQUEST);
                $session->set(AuthenticationController::SESSION_REFERER_CANCEL);
            }

            $this->response->setRedirect($url);

            return;
        }

        $authenticator = $this->dependencyInjector->get('ride\\library\\security\\authenticator\\OAuth2Authenticator', $authenticator);

        $this->response->setRedirect($authenticator->getAuthorizationUrl());
    }

}
