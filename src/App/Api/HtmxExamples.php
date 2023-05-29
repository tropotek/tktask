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

    /**
     * Markup to be placed in the page:
     * <div aria-live="polite" aria-atomic="true" class="toastPanel position-relative"
     *    hx-get="/api/htmx/toast" hx-trigger="load" hx-target="this" hx-swap="outerHTML"
     * ></div>
     *
     */
    public function doToast(Request $request): string
    {

        //hx-get="/api/htmx/toast" hx-trigger="submit from:form" hx-sync="form:queue last" hx-target="this" hx-swap="outerHTML"
        $toasts = <<<HTML
<div aria-live="polite" aria-atomic="true" class="toastPanel position-relative" var="alertPanel">
  <div class="toast-container top-0 end-0 p-3">
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" repeat="panel">
      <div class="toast-header">
        <!--<img src="..." class="rounded mr-2" alt="...">-->
        <svg choice="svg" class="bd-placeholder-img rounded mr-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img"><rect fill="#007aff" width="100%" height="100%" var="svg" /></svg>&nbsp;
        <i class="bd-placeholder-img rounded mr-2" style="width: 20px;height: 20px; line-height: 1.5em;" choice="icon"></i>
        <strong class="me-auto" var="title"> Alert</strong>
        <small class="text-muted" var="time"></small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" var="message"></div>
    </div>
  </div>
</div>
HTML;

        $template = $this->loadTemplate($toasts);

        $template->setAttr('alertPanel', 'hx-get', Uri::create('/api/htmx/toast'));
        foreach ($this->getFactory()->getSession()->getFlashBag()->all() as $type => $flash) {
            foreach ($flash as $a) {
                $a = unserialize($a);
                $r = $template->getRepeat('panel');
                if ($a->icon) {
                    $r->addCss('icon', $a->icon);
                    $r->addCss('icon', 'text-'.$type);
                    $r->setVisible('icon');
                } else {
                    $colorMap = [
                        'primary' => '#0d6efd',
                        'secondary' => '#0d6efd',
                        'success' => '#198754',
                        'info' => '#0dcaf0',
                        'warning' => '#ffc107',
                        'danger' => '#dc3545',
                        'error' => '#dc3545',
                        'light' => '#f8f9fa',
                        'dark' => '#212529',
                    ];
                    $r->setAttr('svg', 'fill', $colorMap[$type]);
                    $r->setVisible('svg');
                }
                if ($a->title) {
                    $r->setText('title', $a->title);
                } else {
                    $r->setText('title', ucfirst(strtolower($type)));
                }

                $r->insertHtml('message', $a->message);
                $r->appendRepeat();
            }
        }

        return $template->toString();
    }

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

