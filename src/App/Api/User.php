<?php
namespace App\Api;

use App\Db\UserMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tk\Traits\SystemTrait;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class User
{
    use SystemTrait;

    public function doTest(Request $request)
    {
        sleep(1);
        vd(apache_request_headers());
        $q = $request->request->get('q');
        return "<p>The search string was: <b>$q</b></p>";
    }


}

