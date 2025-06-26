<?php
namespace App\Controller\User;

use App\Db\User;
use Bs\Mvc\ControllerAdmin;
use Bs\Factory;
use Dom\Template;
use Tk\Alert;
use Tk\Collection;
use Tk\Config;
use Tk\Date;
use Tk\Log;
use Tk\Uri;

/**
 * @see https://github.com/AndrewRose/oauth.php/tree/master
 * @see https://www.sipponen.com/archives/4024
 */
class Ssi extends ControllerAdmin
{

    public function __construct()
    {
        $this->setPageTemplate(Config::getValue('path.template.login'));
    }

    public function doDefault(): void
    {
        $this->getPage()->setTitle('SSI');
        $settings = Collection::dotToMulti(Config::getGroup('auth', true));

        if (!isset($_GET['state'])) {
            Alert::addError("Invalid page access");
            Uri::create('/login')->redirect();
        }
        if (isset($_GET['error'])) {
            Alert::addError("SSI redirect error");
            Uri::create('/login')->redirect();
        }

        if (isset($_GET['code'])) {
            $oAuthType = $_GET['state'] ?? '';
            $params = $settings[$oAuthType] ?? [];

            $oauthUser = $this->getOauthUser($params);
            if (is_null($oauthUser)) {
                Alert::addError("SSI authentication error");
                Uri::create('/login')->redirect();
            }

            $email = $oauthUser->{$params['emailIdentifier']} ?? '';
            if (empty($email)) {
                Alert::addError("SSI email authentication error");
                Uri::create('/login')->redirect();
            }
            // Find/create system user
            $user = User::findByEmail($email);
            if (!$user) {
                if ($params['createUser'] ?? false) {
                    [$username, $domain] = explode('@', $email);
                    $user = new User();
                    $user->type       = $params['userType'];
                    $user->givenName  = $oauthUser->givenName ?? $oauthUser->name ?? $username;
                    $user->familyName = $oauthUser->surname ?? '';
                    $user->phone      = $oauthUser->mobilePhone ?? '';
                    $user->save();

                    $auth = $user->getAuth();
                    $auth->uid        = $oauthUser->id ?? '';
                    $auth->username   = $this->uniqueUsername($username);
                    $auth->email      = $email;
                    $auth->external   = $oAuthType;
                    $auth->active     = true;
                    $auth->save();
                    $user->save();

                    \App\Email\User::sendWelcome($user, true);
                } else {
                    Alert::addWarning("User account not found, please contact site administrator to setup your account containing the email $email");
                    Uri::create('/')->redirect();
                }
            }

            if (!$user->active) {
                Alert::addWarning("User account disabled, please contact site administrator to activate your account containing the email $email");
                Uri::create('/')->redirect();
            }

            try {
                // log user into the site
                Factory::instance()->getAuthController()->getStorage()->write($user->username);

                // Update users login data
                $auth = $user->getAuth();
                $auth->lastLogin = Date::create('now', $auth->timezone ?: null);
                $auth->sessionId = strval(session_id());
                $auth->save();

                // set the SSI auth type in the session for logout
                $_SESSION['_OAUTH'] = $oAuthType;

                // redirect to user home
                $user->getHomeUrl()->redirect();
            } catch (\Exception $e) {
                Alert::addError("SSI authentication error");
                Uri::create('/login')->redirect();
            }
        }
    }

    /**
     * @param array $params The oAuth settings array from the Config
     */
    public function getToken(array $params): string
    {
        if (!($params['enabled'] ?? false)) return '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $params['endpointToken']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'client_id'     => $params['clientId'],
            'client_secret' => $params['clientSecret'],
            'redirect_uri'  => Uri::create('/_ssi')->toString(),
            'code'          => $_GET['code'],
            'grant_type'    => 'authorization_code',
            'scope'         => $params['scope'],
        ]);

        $response = curl_exec($ch);
        //$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        if ($errno) {
            $err = curl_error($ch);
            Log::error("error getting oAuth token: {$params['endpointToken']}. Error: {$err}");
            return '';
        }

        if (!is_string($response)) return '';
        $response = json_decode($response);
        return $response->access_token ?? '';
    }

    public function getOauthUser(array $params): ?\stdClass
    {
        if (!($params['enabled'] ?? false)) return null;

        $token = $this->getToken($params);
        if (empty($token)) {
            Log::error("error getting oAuth token: {$params['endpointToken']}.");
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $params['endpointScope']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}"]);

        $response = curl_exec($ch);
        //$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        if ($errno) {
            $err = curl_error($ch);
            Log::error("error getting oAuth user: {$params['endpointScope']}. Error: {$err}");
            return null;
        }

        if (!is_string($response)) return null;
        $response = json_decode($response);
        if (is_null($response)) return null;

        if(!isset($response->{$params['emailIdentifier']})) {
            Log::error("invalid user data for SSI endpoint: {$params['endpointScope']}");
            return null;
        }

        if(isset($response->verified_email) && !$response->verified_email) { // google specific
            Log::error("invalid user identity for SSI endpoint: {$params['endpointScope']}");
            return null;
        }

        if (isset($response->error)) {
            Log::error("ssi error: {$params['endpointScope']} Error: {$response->error}");
            return null;
        }

        $email = $response->{$params['emailIdentifier']};
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::error("ssi returned invalid user email: {$params['endpointScope']} Email: {$email}");
            return null;
        }

        return $response;
    }

    /**
     * Check if the username exists append a number until it is unique
     * Only use when creating users.
     */
    protected function uniqueUsername(string $username): string
    {
        $num = 0;
        $user = User::findByUsername($username);
        while($user instanceof User) {
            $num++;
            $user = User::findByUsername($username);
        }
        return $username.($num > 0 ? $num : '');
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        return $template;
    }

    public function __makeTemplate(): ?Template
    {
        $html = <<<HTML
<div>
    <h1 class="text-center h3 mb-3 fw-normal">Login</h1>
    <div var="content"></div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}