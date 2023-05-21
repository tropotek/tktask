<?php
namespace App\Api;

use App\Db\UserMap;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tk\Traits\SystemTrait;
use Tk\Uri;

/**
 * @author Tropotek <http://www.tropotek.com/>
 */
class HtmxExamples
{
    use SystemTrait;

    public function doTest(Request $request)
    {
        sleep(1);
        //vd(apache_request_headers());
        $q = $request->request->get('q');
        return "<p>The search string was: <b>$q</b></p>";
    }

    public function doFindUsers(Request $request)
    {
        sleep(1);
        $list = UserMap::create()->findFiltered(['type' => $request->query->get('type')]);
        $html = '';
        foreach ($list as $user) {
            $html .= sprintf('<option value="%s">%s</option>', $user->getId(), $user->getName());
        }
        return $html;
    }

    public function doGetTabs(Request $request)
    {
        $tab = $request->query->get('tab');
        $tabContent = [
            'Commodo normcore truffaut VHS % & duis gluten-free keffiyeh iPhone taxidermy godard ramps anim pour-over. Pitchfork vegan mollit umami quinoa aute aliquip kinfolk eiusmod live-edge cardigan ipsum locavore. Polaroid duis occaecat narwhal small batch food truck.',
            'Kitsch fanny pack yr, farm-to-table cardigan cillum commodo reprehenderit plaid dolore cronut meditation. Tattooed polaroid veniam, anim id cornhole hashtag sed forage. Microdosing pug kitsch enim, kombucha pour-over sed irony forage live-edge. Vexillologist eu nulla trust fund, street art blue bottle selvage raw denim.',
            '<span hx-get="api/htmx/button?text=DAMN" hx-target="this" hx-trigger="load, every 2s" hx-swap="innerHTML" onclick="alert(\'Ullo!!!\')"></span> Aute chia marfa echo park tote bag hammock mollit artisan listicle direct trade. Raw denim flexitarian eu godard etsy. Poke tbh la croix put a bird on it fixie polaroid aute cred air plant four loko gastropub swag non brunch. Iceland fanny pack tumeric magna activated charcoal bitters palo santo laboris quis consectetur cupidatat portland aliquip venmo.',
        ];

        $tabs = '<ul class="nav nav-tabs" role="tablist">';
        $html = '';
        foreach ($tabContent as $i => $content) {
            $selected = '';
            if ($tab == $i) {
                $selected = 'active';
                $html = sprintf('<div class="tab-content"><div class="tab-pane fade show active m-3" role="tabpanel"><p>%s</p></div></div>', $content);
            }
            $tabs .= sprintf('<li class="nav-item"><button class="nav-link %s" hx-get="api/htmx/tabs?tab=%s">Tab %s</button></li>', $selected, $i, $i+1);
        }
        $tabs .= '</ul>';
        return sprintf('%s %s', $tabs, $html);
    }

    public function doButton(Request $request)
    {
        $idx = $this->getSession()->get('btn-test', 0);
        $idx++;
        if ($idx > 9) $idx = 0;
        $this->getSession()->set('btn-test', $idx);
        $text = $request->request->get('text', 'Click ' . $idx);
        if ($request->query->get('text')) {
            $text = $request->query->get('text');
        }
        $html = <<<HTML
<button class="btn btn-sm btn-primary" hx-get="api/htmx/button" hx-target="this" hx-trigger="click" hx-swap="outerHTML" >$text</button>
HTML;
        $response = new Response($html, Response::HTTP_OK, []);
        return $response;
    }

    public function doUpload(Request $request)
    {
        //sleep(1);
        //vd('Upload: ', $request->request->all());
        if ($request->files->count()) {
            //vd($request->files->all());

        }
        return new Response('', Response::HTTP_NO_CONTENT);
    }

}

