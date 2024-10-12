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
use Tk\Uri;

/**
 *
 * @see https://github.com/AndrewRose/oauth.php/tree/master
 * @see https://www.sipponen.com/archives/4024
 */
class Ssi extends ControllerAdmin
{

    public function __construct()
    {
        $this->setPageTemplate($this->getConfig()->get('path.template.login'));
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

        $oAuth = $_GET['state'] ?? '';
        $oAuthTypes = [];
        if ($settings['microsoft']['enabled'] ?? false) {
            $oAuthTypes[] = 'microsoft';
        }
        if ($settings['google']['enabled'] ?? false) {
            $oAuthTypes[] = 'google';
        }
        if ($settings['facebook']['enabled'] ?? false) {
            $oAuthTypes[] = 'facebook';
        }

        if (isset($_GET['code']) && in_array($oAuth, $oAuthTypes)) {
            $ssiUri = Uri::create('/_ssi');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $settings[$oAuth]['endpointToken']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'client_id' => $settings[$oAuth]['clientId'],
                'client_secret' => $settings[$oAuth]['clientSecret'],
                'redirect_uri' => $ssiUri->toString(),
                'code' => $_GET['code'],
                'grant_type' => 'authorization_code',
                'scope' => $settings[$oAuth]['scope']]
            );
            $data = json_decode(curl_exec($ch), true);

            if(!$data || !isset($data['access_token'])) {
                Alert::addError("Invalid login token");
                Uri::create('/login')->redirect();
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $settings[$oAuth]['endpointScope']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer '.$data['access_token']]);
            $data = json_decode(curl_exec($ch), true);

            if(!$data || !isset($data[$settings[$oAuth]['emailIdentifier']])) {
                Alert::addError("Invalid user data");
                Uri::create('/login')->redirect();
            }

            // google specific
            if(isset($data['verified_email']) && !$data['verified_email']) {
                Alert::addError("Invalid user identity");
                Uri::create('/login')->redirect();
            }

            if (isset($data['error'])) {
                Alert::addError("Authentication error");
                Uri::create('/login')->redirect();
            }
            vd($data);

            // get email
            $email = $data[$settings[$oAuth]['emailIdentifier']] ?? '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Alert::addError("SSI server returned invalid user email account");
                Uri::create('/')->redirect();
            }

            // Find user
            $user = User::findByEmail($email);
            if (!$user) {
                if ($settings[$oAuth]['createUser'] ?? false) {
                    [$username, $domain] = explode('@', $email);
                    $user = new User();
                    $user->type       = $settings[$oAuth]['userType'];
                    $user->givenName  = $data['givenName'] ?? $data['name'] ?? $username;
                    $user->familyName = $data['surname'] ?? '';
                    $user->phone      = $data['mobilePhone'] ?? '';
                    $user->save();
                    $auth = $user->getAuth();
                    $auth->uid      = $data['id'] ?? '';
                    $auth->username = $this->uniqueUsername($username);
                    $auth->email    = $email;
                    $auth->external = $oAuth;
                    $auth->active   = true;
                    $auth->save();
                    $user->save();

                    // TODO: send welcome email to new user

                } else {
                    Alert::addWarning("User account not found, please contact site administrator to setup your account containing the email $email");
                    Uri::create('/')->redirect();
                }
            }

            if (!$user->active) {
                Alert::addWarning("User account disabled, please contact site administrator to activate your account containing the email $email");
                Uri::create('/')->redirect();
            }

            // set the SSI auth type in the session for logout
            $_SESSION['_OAUTH'] = $oAuth;

            // log user into the site
            Factory::instance()->getAuthController()->getStorage()->write($user->username);
            // Update users login data
            $auth = $user->getAuth();
            $auth->lastLogin = Date::create('now', $auth->timezone ?: null);
            $auth->sessionId = session_id();
            $auth->save();

            // redirect to user home
            $user->getHomeUrl()->redirect();
        }

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


    // note: be sure to check other oAuthTypes returned data packets
    // microsoft $data (unimelb):
    // Array[12]
    // (
    //     [@odata.context] => 'https://graph.microsoft.com/v1.0/$metadata#users/$entity'
    //     [businessPhones] => Array[0]( )
    //     [displayName] => 'Mick Mifsud'
    //     [givenName] => 'Mick'
    //     [jobTitle] => {NULL}
    //     [mail] => 'michael.mifsud@unimelb.edu.au'
    //     [mobilePhone] => {NULL}
    //     [officeLocation] => {NULL}
    //     [preferredLanguage] => {NULL}
    //     [surname] => 'Mifsud'
    //     [userPrincipalName] => 'michael.mifsud@unimelb.edu.au'
    //     [id] => 'dcf05f6c-1d0f-4522-8d48-38fd01bb5365'
    // )
    // google $data:
    // Array[4]
    // (
    //     [id] => '105376429387704831053'
    //     [email] => 'mick.mifsud@oum.edu.ws'
    //     [verified_email] => {true}
    //     [picture] => 'https://lh3.googleusercontent.com/a-/ALV-UjV2wCsAIc5jRVAA7rNvU1woxrCkQ-gGxtlBEqP7dIOHB8Xr=s96-c'
    // )

}