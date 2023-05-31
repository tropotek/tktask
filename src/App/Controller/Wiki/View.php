<?php
namespace App\Controller\Wiki;

use Dom\Mvc\PageController;
use Dom\Template;
use Symfony\Component\HttpFoundation\Request;

class View extends PageController
{

    public function __construct()
    {
        parent::__construct($this->getFactory()->createPage($this->getSystem()->makePath('/html/wiki.html')));
        $this->getPage()->setTitle('View Wiki Page');
    }

    public function doDefault(Request $request)
    {

        return $this->getPage();
    }

    public function show(): ?Template
    {
        $template = $this->getTemplate();

        if ($this->getFactory()->getAuthUser()) {
            $this->getPage()->getTemplate()->setText('username', $this->getFactory()->getAuthUser()->getUsername());
        }

        return $template;
    }

    public function __makeTemplate()
    {
        $html = <<<HTML
<div>
  <div var="content" class="clearfix">
    <div class="btn-group btn-group-sm float-end" role="group" aria-label="Small button group">
      <a href="/wiki/edit?pageId=1" title="Edit The Page" class="btn btn-outline-secondary"><i class="fa fa-fw fa-pencil"></i></a>
      <a href="/?pdf=pdf" title="Download PDF" class="btn btn-outline-secondary" target="_blank"><i class="fa fa-fw fa-file-pdf"></i></a>
      <div class="btn-group btn-group-sm" role="group">
        <a href="javascript:;" title="Page Actions" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-fw fa-circle-info"></i></a>
        <ul class="dropdown-menu">
          <li><a href="/user/history.html?pageId=1" class="dropdown-item">Revisions</a></li>
          <li><a href="javascript:;" class="dropdown-item">Page Info</a></li>
        </ul>

      </div>
    </div>

    <h1>Page Title Page Title Page</h1>

    <div class="wiki-content">
        <div class="">
<p>&nbsp;</p>
<p>
Some notes for the new wiki system:
</p>
<ul>
  <li>
  Standard registered users should only be allowed to view pages, all edit controls should be hidden.
  This will allow us to create public websites with registered users. Just need to change the templates.
  </li>
  <li>All information and actions for pages should be in the info menu button dropdown</li>
  <li>Use tabs for the edit page, and page view permissions should have explanations, consider re-introducing groups???</li>
  <li>Add reset crumb to page settings</li>
  <li>Re-create the buttons for tinymce</li>
  <li>Add stylesheets to tinymce and make formatting as close to site render as possible</li>
  <li>
    Add a release lock button in the actions dropdown
    (release the edit lock, as sometimes this can be an issue,
    bring up a confirmation with a warning on what can happen when 2 people are editing the same page)
  </li>
  <li>In page settings we could add options to show: contributors, created, modified, permissions, revision ID?</li>
  <li>
    Also I think its time to create some sort of account record implementing the Authtool (See BTC site), This would be handy
    as the wiki is used primarily to save external account info, ensure they have categories and permissions.
    Maybe they can be attached to pages as well so the can be listed in related pages. Could have a widget that shows the
    passcode and details inline to avoid password data being show accidentally???
  </li>
  <li>get a bootstrap 5 jquery image viewer (lightbox style)</li>
</ul>

<p>&nbsp;</p>
<p>&nbsp;</p>

<table border="0">
<tbody>
<tr>
<th><code>^</code></th>
<td>The pattern has to appear at the beginning of a string.</td>
<td><code>^cat</code> matches any string that begins with <code>cat</code></td>
</tr>
<tr>
<th><code>$</code></th>
<td>The pattern has to appear at the end of a string.</td>
<td><code>cat$</code> matches any string that ends with <code>cat</code></td>
</tr>
<tr>
<th><code>.</code></th>
<td>Matches any character.</td>
<td><code>cat.</code> matches <code>catT</code> and <code>cat2</code> but not <code>catty</code></td>
</tr>
<tr>
<th><code>[]</code></th>
<td>Bracket expression. Matches one of any characters enclosed.</td>
<td><code>gr[ae]y</code> matches <code>gray</code> or <code>grey</code></td>
</tr>
<tr>
<th><code>[^]</code></th>
<td>Negates a bracket expression. Matches one of any characters EXCEPT those enclosed.</td>
<td><code>1[^02]</code> matches <code>13</code> but not <code>10</code> or <code>12</code></td>
</tr>
<tr>
<th><code>[-]</code></th>
<td>Range. Matches any characters within the range.</td>
<td><code>[1-9]</code> matches any single digit EXCEPT <code>0</code></td>
</tr>
<tr>
<th><code>?</code></th>
<td>Preceeding item must match one or zero times.</td>
<td><code>colou?r</code> matches <code>color</code> or <code>colour</code> but not <code>colouur</code></td>
</tr>
<tr>
<th><code>+</code></th>
<td>Preceeding item must match one or more times.</td>
<td><code>be+</code> matches <code>be</code> or <code>bee</code> but not <code>b</code></td>
</tr>
<tr>
<th><code>*</code></th>
<td>Preceeding item must match zero or more times.</td>
<td><code>be*</code> matches <code>b</code> or <code>be</code> or <code>beeeeeeeeee</code></td>
</tr>
<tr>
<th><code>()</code></th>
<td>Parentheses. Creates a substring or item that metacharacters can be applied to</td>
<td><code>a(bee)?t</code> matches <code>at</code> or <code>abeet</code> but not <code>abet</code></td>
</tr>
<tr>
<th><code>{n}</code></th>
<td>Bound. Specifies exact number of times for the preceeding item to match.</td>
<td><code>[0-9]{3}</code> matches any three digits</td>
</tr>
<tr>
<th><code>{n,}</code></th>
<td>Bound. Specifies minimum number of times for the preceeding item to match.</td>
<td><code>[0-9]{3,}</code> matches any three or more digits</td>
</tr>
<tr>
<th><code>{n,m}</code></th>
<td>Bound. Specifies minimum and maximum number of times for the preceeding item to match.</td>
<td><code>[0-9]{3,5}</code> matches any three, four, or five digits</td>
</tr>
<tr>
<th><code>|</code></th>
<td>Alternation. One of the alternatives has to match.</td>
<td><code>July (first|1st|1)</code> will match <code>July 1st</code> but not <code>July 2</code></td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>

      </div>
    </div>



  </div>
</div>
HTML;
        return $this->loadTemplate($html);
    }

}


