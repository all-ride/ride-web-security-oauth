## OAuth Client

The [ride\library\security\OAuth2Client](/api/class/ride/library/security/OAuth2Client) offers you a interface to authenticate users through a OAuth2 server.
Once authenticated, the client can be used as a HTTP client to make request which have transparant authorization.

### Google

To create a Google OAuth client, set the following parameters as provided by [Google API Access](https://code.google.com/apis/console):

* __google.auth.redirect.uri__:
Redirect URI as set in the Google API Access. This should something like http://yourapp/login/google.
* __google.auth.scope__:
Scope(s) for the OAuth server
* __google.client.id__:
Client ID as provided by the Google API Access
* __google.client.secret__:
Client secret as provided by the Google API Access

This should result in something like;

    google.auth.redirect.uri = "http://myapp/login/google"
    google.auth.scope.1 = "https://www.googleapis.com/auth/userinfo.profile"
    google.auth.scope.2 = "https://www.googleapis.com/auth/userinfo.email"
    google.client.id = "123456789.apps.googleusercontent.com"
    google.client.secret = "123456789"

You can then create a Google OAuth client by adding the following dependency:

    {
        "dependencies": [
            {
                "interfaces": "ride\\library\\security\\oauth\\OAuth2Client",
                "class": "ride\\library\\security\\oauth\\GoogleOAuth2Client",
                "id": "google",
                "calls": [
                    {
                        "method": "setClientId",
                        "arguments": [
                            {
                                "name": "clientId",
                                "type": "parameter",
                                "properties": {
                                    "key": "google.client.id"
                                }
                            }
                        ]
                    },
                    {
                        "method": "setClientSecret",
                        "arguments": [
                            {
                                "name": "clientSecret",
                                "type": "parameter",
                                "properties": {
                                    "key": "google.client.secret"
                                }
                            }
                        ]
                    },
                    {
                        "method": "setRedirectUri",
                        "arguments": [
                            {
                                "name": "redirectUri",
                                "type": "parameter",
                                "properties": {
                                    "key": "google.auth.redirect.uri"
                                }
                            }
                        ]
                    },
                    {
                        "method": "setScope",
                        "arguments": [
                            {
                                "name": "scope",
                                "type": "parameter",
                                "properties": {
                                    "key": "google.auth.scope"
                                }
                            }
                        ]
                    },
                    "setLog"
                ]
            }
        ]
    }

## OAuth Authenticator

You now have a OAuth client, but it's not yet integrated in the security library.
Use the following dependencies to integrate:

    {
        "dependencies": [
            {
                "interfaces": ["ride\\library\\security\\authenticator\\Authenticator", "ride\\library\\security\\authenticator\\OAuth2Authenticator"],
                "class": "ride\\library\\security\\authenticator\\OAuth2Authenticator",
                "id": "google",
                "calls": [
                    {
                        "method": "__construct",
                        "arguments": [
                            {
                                "name": "client",
                                "type": "dependency",
                                "properties": {
                                    "interface": "ride\\library\\security\\OAuth2Client",
                                    "id": "google"
                                }
                            }
                        ]
                    }
                ]
            },
            {
                "interfaces": "ride\\library\\security\\authenticator\\Authenticator",
                "extends": "chain",
                "id": "chain",
                "calls": [
                    {
                        "method": "addAuthenticator",
                        "arguments": [
                            {
                                "name": "authenticator",
                                "type": "dependency",
                                "properties": {
                                    "interface": "ride\\library\\security\\authenticator\\Authenticator",
                                    "id": "google"
                                }
                            }
                        ]
                    }
                ]
            }
        ]
    }

The only thing left to do is add a route which will handle the authorization of the user.
You can use the following route configuration:

    {
        "routes": [
            {
                "path": "/login/google",
                "controller": "ride\\web\\base\\controller\\OAuth2Controller",
                "action": "authorizeAction",
                "id": "login.google",
                "methods": ["head", "get"],
                "arguments": [
                    {
                        "name": "authenticator",
                        "type": "scalar",
                        "properties": {
                            "value": "google"
                        }
                    }
                ]
            }
        ]
    }

The _authenticator_ argument value should be the dependency id of your OAuth authenticator.

### Connect New Users

Users are validated through their email address.
When a user could not be found, but is succesfully authenticated by the OAuth server, the [zibo\security\authenticator\ConnectPolicy](/api/class/zibo/security/authenticator/ConnectPolicy) interface jumps into action.
The implementation, if set, will decide which users are allowed and will create them so they can authenticate.

Check the following sample of a ConnectPolicy:

    <?php

    namespace vendor\app\security;

    use ride\library\security\model\SecurityModel;
    use ride\library\security\oauth\ConnectPolicy;

    class VendorConnectPolicy implements ConnectPolicy {

        public function connectUser(SecurityModel $securityModel, array $userInfo) {
            if (strpos($userInfo['email'], 'vendor.com') != strlen($userInfo['email']) - 10) {
                return null;
            }

            $user = $securityModel->createUser();
            $user->setDisplayName($userInfo['name']);
            $user->setUserName($userInfo['email']);
            $user->setEmail($userInfo['email']);
            $user->setIsActive(true);

            $securityModel->saveUser($user);

            $roles = array(
                $securityModel->getRoleByName('User'),
            );

            $securityModel->setRolesToUser($user, $roles);

            return $user;
        }

    }

You can then use the dependencies to add your policy to your authenticator:

    {
        "dependencies": [
            {
                "interfaces": ["ride\\library\\security\\authenticator\\Authenticator", "ride\\library\\security\\authenticator\\OAuth2Authenticator"],
                "class": "ride\\library\\security\\authenticator\\OAuth2Authenticator",
                "id": "google",
                "calls": [
                    {
                        "method": "__construct",
                        "arguments": [
                            {
                                "name": "client",
                                "type": "dependency",
                                "properties": {
                                    "interface": "ride\\library\\security\\OAuth2Client",
                                    "id": "google"
                                }
                            }
                        ]
                    },
                    {
                        "method": "setConnectPolicy",
                        "arguments": [
                            {
                                "name": "connectPolicy",
                                "type": "dependency",
                                "properties": {
                                    "interface": "vendor\\app\\security\\VendorConnectPolicy"
                                }
                            }
                        ]
                    }
                ]
            }
        ]
    }
