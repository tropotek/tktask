<?php

namespace App\Controller;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tk\Traits\SystemTrait;

/**
 *
 * @author Tropotek <http://www.tropotek.com/>
 */
class Error
{
    use SystemTrait;

    public function doDefault(\Throwable $e)
    {
        $html = $this->getExceptionHtml($e, $this->getConfig()->isDebug());
        return $html;
        //return new Response($html, $e->getCode());
    }

    /**
     * @return mixed|string
     */
    public function getExceptionHtml(\Throwable $e, bool $withTrace = false)
    {
        $config = $this->getConfig();
        $class = get_class($e);
        $msg = $e->getMessage();
        $str = '';
        $extra = '';
        $logHtml = '';

        if ($withTrace) {
            $toString = trim($e->__toString());
            $logfile = $this->getSystem()->makePath($config->get('log.system.request'));
            if (is_readable($logfile)) {
                // Add to composer require: "sensiolabs/ansi-to-html": "~1.0",
                if (class_exists('SensioLabs\AnsiConverter\AnsiToHtmlConverter')) {
                    $converter = new \SensioLabs\AnsiConverter\AnsiToHtmlConverter();
                    $sessionLog = file_get_contents($logfile);
                    $sessionLog = $converter->convert($sessionLog);
                    $logHtml = sprintf('<div class="content"><p><b>System Log:</b></p>'.
                        '<pre class="console" style="color: #666666; background-color: #000; padding: 10px 15px; font-family: monospace;">%s</pre> <p>&#160;</p></div>',
                        $sessionLog);
                }
            }

            $str = str_replace(array("&lt;?php&nbsp;<br />", 'color: #FF8000'), array('', 'color: #666'),
                highlight_string("<?php \n" . $toString, true));
            $extra = sprintf('<br/>in <em>%s:%s</em>',  $e->getFile(), $e->getLine());
        }

        $html = <<<HTML
<html lang="en">
    <head>
      <title>$class</title>
    <style>
    code, pre {
      line-height: 1.4em;
      padding: 0;margin: 0;
      overflow: auto;
    }
    </style>
    </head>
    <body style="padding: 10px;">
    <h1>$class</h1>
    <p><strong>$msg $extra</strong></p>
    <pre style="">$str</pre>
    $logHtml
    </body>
</html>
HTML;

        $html = str_replace($config->getBasePath(), '', $html);

        return $html;
    }
}