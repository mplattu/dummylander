<?php

$AUTH_METHODS = Array(
  'file' => null  // Uses the default file path "settings.php"
);

$VERSION = "Dummylander 0.5";
$DATAPATH = "data/";

$PAGE_PROPERTIES = Array(
  'title',
  'favicon-ico',
  'description',
  'image',
  'keywords',
  'style-css'
);

$SECTION_PROPERTIES = Array(
  'margin',
  'padding',
  'height',
  'color',
  'background-image',
  'font-family-google'
);

// Characters allowed in filenames. See FileStorage class.
$FILE_UPLOAD_ALLOWED_CHARS_REGEX = 'a-zA-Z01234567890\-_.';

?>
<?php

// Log levels:
// 0 - fatal errors
// 1 - some messages
// 2 - everything
$LOG_LEVEL = 1;
$s = new Settings();
$s_log_level = $s->get_value('LOG_LEVEL');
if (!is_null($s_log_level)) {
  $LOG_LEVEL = $s_log_level;
}

$admin_auth = new AdminAuth($AUTH_METHODS);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
  $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'uploadlimitexceeded', "Too large file");
  echo($admin_api->execute());
  exit(0);
}

log_message("QUERY_STRING:".@$_SERVER['QUERY_STRING'], null, 2);
if (@$_SERVER['QUERY_STRING'] == "admin") {
  $admin_ui = new ShowAdminUI();
}
elseif (@$_POST['password'] != "") {
  $is_admin = false;
  $admin_message = null;

  try {
    $is_admin = $admin_auth->is_admin($_POST['password']);
  }
  catch (Exception $e) {
    $admin_message = $e->getMessage();
    log_message("Authentication error: ".$admin_message);
  }

  if (is_null($admin_message)) {
    $admin_message = $admin_auth->get_last_error();
  }

  if ($is_admin) {
    $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), @$_POST['function'], @$_POST['data']);
    $response = $admin_api->execute();
  }
  else {
    $admin_api = new AdminAPI(remove_trailing_slash($DATAPATH), 'loginfailed', $admin_message);
    $response = $admin_api->execute();
  }

  log_message("Admin response: ".print_r($response, true));
  echo($response);
}
else {
  $show_page = new ShowPage($VERSION, remove_trailing_slash($DATAPATH));
  echo($show_page->get_html_page());
}

// Normal termination
exit(0);

?>
<?php

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class Parsedown
{
    # ~

    const version = '1.8.0-beta-7';

    # ~

    function text($text)
    {
        $Elements = $this->textElements($text);

        # convert to markup
        $markup = $this->elements($Elements);

        # trim line breaks
        $markup = trim($markup, "\n");

        return $markup;
    }

    protected function textElements($text)
    {
        # make sure no definitions are set
        $this->DefinitionData = array();

        # standardize line breaks
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # iterate through lines to identify blocks
        return $this->linesElements($lines);
    }

    #
    # Setters
    #

    function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    protected $breaksEnabled;

    function setMarkupEscaped($markupEscaped)
    {
        $this->markupEscaped = $markupEscaped;

        return $this;
    }

    protected $markupEscaped;

    function setUrlsLinked($urlsLinked)
    {
        $this->urlsLinked = $urlsLinked;

        return $this;
    }

    protected $urlsLinked = true;

    function setSafeMode($safeMode)
    {
        $this->safeMode = (bool) $safeMode;

        return $this;
    }

    protected $safeMode;

    function setStrictMode($strictMode)
    {
        $this->strictMode = (bool) $strictMode;

        return $this;
    }

    protected $strictMode;

    protected $safeLinksWhitelist = array(
        'http://',
        'https://',
        'ftp://',
        'ftps://',
        'mailto:',
        'tel:',
        'data:image/png;base64,',
        'data:image/gif;base64,',
        'data:image/jpeg;base64,',
        'irc:',
        'ircs:',
        'git:',
        'ssh:',
        'news:',
        'steam:',
    );

    #
    # Lines
    #

    protected $BlockTypes = array(
        '#' => array('Header'),
        '*' => array('Rule', 'List'),
        '+' => array('List'),
        '-' => array('SetextHeader', 'Table', 'Rule', 'List'),
        '0' => array('List'),
        '1' => array('List'),
        '2' => array('List'),
        '3' => array('List'),
        '4' => array('List'),
        '5' => array('List'),
        '6' => array('List'),
        '7' => array('List'),
        '8' => array('List'),
        '9' => array('List'),
        ':' => array('Table'),
        '<' => array('Comment', 'Markup'),
        '=' => array('SetextHeader'),
        '>' => array('Quote'),
        '[' => array('Reference'),
        '_' => array('Rule'),
        '`' => array('FencedCode'),
        '|' => array('Table'),
        '~' => array('FencedCode'),
    );

    # ~

    protected $unmarkedBlockTypes = array(
        'Code',
    );

    #
    # Blocks
    #

    protected function lines(array $lines)
    {
        return $this->elements($this->linesElements($lines));
    }

    protected function linesElements(array $lines)
    {
        $Elements = array();
        $CurrentBlock = null;

        foreach ($lines as $line)
        {
            if (chop($line) === '')
            {
                if (isset($CurrentBlock))
                {
                    $CurrentBlock['interrupted'] = (isset($CurrentBlock['interrupted'])
                        ? $CurrentBlock['interrupted'] + 1 : 1
                    );
                }

                continue;
            }

            while (($beforeTab = strstr($line, "\t", true)) !== false)
            {
                $shortage = 4 - mb_strlen($beforeTab, 'utf-8') % 4;

                $line = $beforeTab
                    . str_repeat(' ', $shortage)
                    . substr($line, strlen($beforeTab) + 1)
                ;
            }

            $indent = strspn($line, ' ');

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # ~

            if (isset($CurrentBlock['continuable']))
            {
                $methodName = 'block' . $CurrentBlock['type'] . 'Continue';
                $Block = $this->$methodName($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $CurrentBlock = $Block;

                    continue;
                }
                else
                {
                    if ($this->isBlockCompletable($CurrentBlock['type']))
                    {
                        $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
                        $CurrentBlock = $this->$methodName($CurrentBlock);
                    }
                }
            }

            # ~

            $marker = $text[0];

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->BlockTypes[$marker]))
            {
                foreach ($this->BlockTypes[$marker] as $blockType)
                {
                    $blockTypes []= $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType)
            {
                $Block = $this->{"block$blockType"}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $Block['type'] = $blockType;

                    if ( ! isset($Block['identified']))
                    {
                        if (isset($CurrentBlock))
                        {
                            $Elements[] = $this->extractElement($CurrentBlock);
                        }

                        $Block['identified'] = true;
                    }

                    if ($this->isBlockContinuable($blockType))
                    {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and $CurrentBlock['type'] === 'Paragraph')
            {
                $Block = $this->paragraphContinue($Line, $CurrentBlock);
            }

            if (isset($Block))
            {
                $CurrentBlock = $Block;
            }
            else
            {
                if (isset($CurrentBlock))
                {
                    $Elements[] = $this->extractElement($CurrentBlock);
                }

                $CurrentBlock = $this->paragraph($Line);

                $CurrentBlock['identified'] = true;
            }
        }

        # ~

        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type']))
        {
            $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
            $CurrentBlock = $this->$methodName($CurrentBlock);
        }

        # ~

        if (isset($CurrentBlock))
        {
            $Elements[] = $this->extractElement($CurrentBlock);
        }

        # ~

        return $Elements;
    }

    protected function extractElement(array $Component)
    {
        if ( ! isset($Component['element']))
        {
            if (isset($Component['markup']))
            {
                $Component['element'] = array('rawHtml' => $Component['markup']);
            }
            elseif (isset($Component['hidden']))
            {
                $Component['element'] = array();
            }
        }

        return $Component['element'];
    }

    protected function isBlockContinuable($Type)
    {
        return method_exists($this, 'block' . $Type . 'Continue');
    }

    protected function isBlockCompletable($Type)
    {
        return method_exists($this, 'block' . $Type . 'Complete');
    }

    #
    # Code

    protected function blockCode($Line, $Block = null)
    {
        if (isset($Block) and $Block['type'] === 'Paragraph' and ! isset($Block['interrupted']))
        {
            return;
        }

        if ($Line['indent'] >= 4)
        {
            $text = substr($Line['body'], 4);

            $Block = array(
                'element' => array(
                    'name' => 'pre',
                    'element' => array(
                        'name' => 'code',
                        'text' => $text,
                    ),
                ),
            );

            return $Block;
        }
    }

    protected function blockCodeContinue($Line, $Block)
    {
        if ($Line['indent'] >= 4)
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

                unset($Block['interrupted']);
            }

            $Block['element']['element']['text'] .= "\n";

            $text = substr($Line['body'], 4);

            $Block['element']['element']['text'] .= $text;

            return $Block;
        }
    }

    protected function blockCodeComplete($Block)
    {
        return $Block;
    }

    #
    # Comment

    protected function blockComment($Line)
    {
        if ($this->markupEscaped or $this->safeMode)
        {
            return;
        }

        if (strpos($Line['text'], '<!--') === 0)
        {
            $Block = array(
                'element' => array(
                    'rawHtml' => $Line['body'],
                    'autobreak' => true,
                ),
            );

            if (strpos($Line['text'], '-->') !== false)
            {
                $Block['closed'] = true;
            }

            return $Block;
        }
    }

    protected function blockCommentContinue($Line, array $Block)
    {
        if (isset($Block['closed']))
        {
            return;
        }

        $Block['element']['rawHtml'] .= "\n" . $Line['body'];

        if (strpos($Line['text'], '-->') !== false)
        {
            $Block['closed'] = true;
        }

        return $Block;
    }

    #
    # Fenced Code

    protected function blockFencedCode($Line)
    {
        $marker = $Line['text'][0];

        $openerLength = strspn($Line['text'], $marker);

        if ($openerLength < 3)
        {
            return;
        }

        $infostring = trim(substr($Line['text'], $openerLength), "\t ");

        if (strpos($infostring, '`') !== false)
        {
            return;
        }

        $Element = array(
            'name' => 'code',
            'text' => '',
        );

        if ($infostring !== '')
        {
            /**
             * https://www.w3.org/TR/2011/WD-html5-20110525/elements.html#classes
             * Every HTML element may have a class attribute specified.
             * The attribute, if specified, must have a value that is a set
             * of space-separated tokens representing the various classes
             * that the element belongs to.
             * [...]
             * The space characters, for the purposes of this specification,
             * are U+0020 SPACE, U+0009 CHARACTER TABULATION (tab),
             * U+000A LINE FEED (LF), U+000C FORM FEED (FF), and
             * U+000D CARRIAGE RETURN (CR).
             */
            $language = substr($infostring, 0, strcspn($infostring, " \t\n\f\r"));

            $Element['attributes'] = array('class' => "language-$language");
        }

        $Block = array(
            'char' => $marker,
            'openerLength' => $openerLength,
            'element' => array(
                'name' => 'pre',
                'element' => $Element,
            ),
        );

        return $Block;
    }

    protected function blockFencedCodeContinue($Line, $Block)
    {
        if (isset($Block['complete']))
        {
            return;
        }

        if (isset($Block['interrupted']))
        {
            $Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

            unset($Block['interrupted']);
        }

        if (($len = strspn($Line['text'], $Block['char'])) >= $Block['openerLength']
            and chop(substr($Line['text'], $len), ' ') === ''
        ) {
            $Block['element']['element']['text'] = substr($Block['element']['element']['text'], 1);

            $Block['complete'] = true;

            return $Block;
        }

        $Block['element']['element']['text'] .= "\n" . $Line['body'];

        return $Block;
    }

    protected function blockFencedCodeComplete($Block)
    {
        return $Block;
    }

    #
    # Header

    protected function blockHeader($Line)
    {
        $level = strspn($Line['text'], '#');

        if ($level > 6)
        {
            return;
        }

        $text = trim($Line['text'], '#');

        if ($this->strictMode and isset($text[0]) and $text[0] !== ' ')
        {
            return;
        }

        $text = trim($text, ' ');

        $Block = array(
            'element' => array(
                'name' => 'h' . $level,
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $text,
                    'destination' => 'elements',
                )
            ),
        );

        return $Block;
    }

    #
    # List

    protected function blockList($Line, array $CurrentBlock = null)
    {
        list($name, $pattern) = $Line['text'][0] <= '-' ? array('ul', '[*+-]') : array('ol', '[0-9]{1,9}+[.\)]');

        if (preg_match('/^('.$pattern.'([ ]++|$))(.*+)/', $Line['text'], $matches))
        {
            $contentIndent = strlen($matches[2]);

            if ($contentIndent >= 5)
            {
                $contentIndent -= 1;
                $matches[1] = substr($matches[1], 0, -$contentIndent);
                $matches[3] = str_repeat(' ', $contentIndent) . $matches[3];
            }
            elseif ($contentIndent === 0)
            {
                $matches[1] .= ' ';
            }

            $markerWithoutWhitespace = strstr($matches[1], ' ', true);

            $Block = array(
                'indent' => $Line['indent'],
                'pattern' => $pattern,
                'data' => array(
                    'type' => $name,
                    'marker' => $matches[1],
                    'markerType' => ($name === 'ul' ? $markerWithoutWhitespace : substr($markerWithoutWhitespace, -1)),
                ),
                'element' => array(
                    'name' => $name,
                    'elements' => array(),
                ),
            );
            $Block['data']['markerTypeRegex'] = preg_quote($Block['data']['markerType'], '/');

            if ($name === 'ol')
            {
                $listStart = ltrim(strstr($matches[1], $Block['data']['markerType'], true), '0') ?: '0';

                if ($listStart !== '1')
                {
                    if (
                        isset($CurrentBlock)
                        and $CurrentBlock['type'] === 'Paragraph'
                        and ! isset($CurrentBlock['interrupted'])
                    ) {
                        return;
                    }

                    $Block['element']['attributes'] = array('start' => $listStart);
                }
            }

            $Block['li'] = array(
                'name' => 'li',
                'handler' => array(
                    'function' => 'li',
                    'argument' => !empty($matches[3]) ? array($matches[3]) : array(),
                    'destination' => 'elements'
                )
            );

            $Block['element']['elements'] []= & $Block['li'];

            return $Block;
        }
    }

    protected function blockListContinue($Line, array $Block)
    {
        if (isset($Block['interrupted']) and empty($Block['li']['handler']['argument']))
        {
            return null;
        }

        $requiredIndent = ($Block['indent'] + strlen($Block['data']['marker']));

        if ($Line['indent'] < $requiredIndent
            and (
                (
                    $Block['data']['type'] === 'ol'
                    and preg_match('/^[0-9]++'.$Block['data']['markerTypeRegex'].'(?:[ ]++(.*)|$)/', $Line['text'], $matches)
                ) or (
                    $Block['data']['type'] === 'ul'
                    and preg_match('/^'.$Block['data']['markerTypeRegex'].'(?:[ ]++(.*)|$)/', $Line['text'], $matches)
                )
            )
        ) {
            if (isset($Block['interrupted']))
            {
                $Block['li']['handler']['argument'] []= '';

                $Block['loose'] = true;

                unset($Block['interrupted']);
            }

            unset($Block['li']);

            $text = isset($matches[1]) ? $matches[1] : '';

            $Block['indent'] = $Line['indent'];

            $Block['li'] = array(
                'name' => 'li',
                'handler' => array(
                    'function' => 'li',
                    'argument' => array($text),
                    'destination' => 'elements'
                )
            );

            $Block['element']['elements'] []= & $Block['li'];

            return $Block;
        }
        elseif ($Line['indent'] < $requiredIndent and $this->blockList($Line))
        {
            return null;
        }

        if ($Line['text'][0] === '[' and $this->blockReference($Line))
        {
            return $Block;
        }

        if ($Line['indent'] >= $requiredIndent)
        {
            if (isset($Block['interrupted']))
            {
                $Block['li']['handler']['argument'] []= '';

                $Block['loose'] = true;

                unset($Block['interrupted']);
            }

            $text = substr($Line['body'], $requiredIndent);

            $Block['li']['handler']['argument'] []= $text;

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $text = preg_replace('/^[ ]{0,'.$requiredIndent.'}+/', '', $Line['body']);

            $Block['li']['handler']['argument'] []= $text;

            return $Block;
        }
    }

    protected function blockListComplete(array $Block)
    {
        if (isset($Block['loose']))
        {
            foreach ($Block['element']['elements'] as &$li)
            {
                if (end($li['handler']['argument']) !== '')
                {
                    $li['handler']['argument'] []= '';
                }
            }
        }

        return $Block;
    }

    #
    # Quote

    protected function blockQuote($Line)
    {
        if (preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches))
        {
            $Block = array(
                'element' => array(
                    'name' => 'blockquote',
                    'handler' => array(
                        'function' => 'linesElements',
                        'argument' => (array) $matches[1],
                        'destination' => 'elements',
                    )
                ),
            );

            return $Block;
        }
    }

    protected function blockQuoteContinue($Line, array $Block)
    {
        if (isset($Block['interrupted']))
        {
            return;
        }

        if ($Line['text'][0] === '>' and preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches))
        {
            $Block['element']['handler']['argument'] []= $matches[1];

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $Block['element']['handler']['argument'] []= $Line['text'];

            return $Block;
        }
    }

    #
    # Rule

    protected function blockRule($Line)
    {
        $marker = $Line['text'][0];

        if (substr_count($Line['text'], $marker) >= 3 and chop($Line['text'], " $marker") === '')
        {
            $Block = array(
                'element' => array(
                    'name' => 'hr',
                ),
            );

            return $Block;
        }
    }

    #
    # Setext

    protected function blockSetextHeader($Line, array $Block = null)
    {
        if ( ! isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted']))
        {
            return;
        }

        if ($Line['indent'] < 4 and chop(chop($Line['text'], ' '), $Line['text'][0]) === '')
        {
            $Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';

            return $Block;
        }
    }

    #
    # Markup

    protected function blockMarkup($Line)
    {
        if ($this->markupEscaped or $this->safeMode)
        {
            return;
        }

        if (preg_match('/^<[\/]?+(\w*)(?:[ ]*+'.$this->regexHtmlAttribute.')*+[ ]*+(\/)?>/', $Line['text'], $matches))
        {
            $element = strtolower($matches[1]);

            if (in_array($element, $this->textLevelElements))
            {
                return;
            }

            $Block = array(
                'name' => $matches[1],
                'element' => array(
                    'rawHtml' => $Line['text'],
                    'autobreak' => true,
                ),
            );

            return $Block;
        }
    }

    protected function blockMarkupContinue($Line, array $Block)
    {
        if (isset($Block['closed']) or isset($Block['interrupted']))
        {
            return;
        }

        $Block['element']['rawHtml'] .= "\n" . $Line['body'];

        return $Block;
    }

    #
    # Reference

    protected function blockReference($Line)
    {
        if (strpos($Line['text'], ']') !== false
            and preg_match('/^\[(.+?)\]:[ ]*+<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*+$/', $Line['text'], $matches)
        ) {
            $id = strtolower($matches[1]);

            $Data = array(
                'url' => $matches[2],
                'title' => isset($matches[3]) ? $matches[3] : null,
            );

            $this->DefinitionData['Reference'][$id] = $Data;

            $Block = array(
                'element' => array(),
            );

            return $Block;
        }
    }

    #
    # Table

    protected function blockTable($Line, array $Block = null)
    {
        if ( ! isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted']))
        {
            return;
        }

        if (
            strpos($Block['element']['handler']['argument'], '|') === false
            and strpos($Line['text'], '|') === false
            and strpos($Line['text'], ':') === false
            or strpos($Block['element']['handler']['argument'], "\n") !== false
        ) {
            return;
        }

        if (chop($Line['text'], ' -:|') !== '')
        {
            return;
        }

        $alignments = array();

        $divider = $Line['text'];

        $divider = trim($divider);
        $divider = trim($divider, '|');

        $dividerCells = explode('|', $divider);

        foreach ($dividerCells as $dividerCell)
        {
            $dividerCell = trim($dividerCell);

            if ($dividerCell === '')
            {
                return;
            }

            $alignment = null;

            if ($dividerCell[0] === ':')
            {
                $alignment = 'left';
            }

            if (substr($dividerCell, - 1) === ':')
            {
                $alignment = $alignment === 'left' ? 'center' : 'right';
            }

            $alignments []= $alignment;
        }

        # ~

        $HeaderElements = array();

        $header = $Block['element']['handler']['argument'];

        $header = trim($header);
        $header = trim($header, '|');

        $headerCells = explode('|', $header);

        if (count($headerCells) !== count($alignments))
        {
            return;
        }

        foreach ($headerCells as $index => $headerCell)
        {
            $headerCell = trim($headerCell);

            $HeaderElement = array(
                'name' => 'th',
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $headerCell,
                    'destination' => 'elements',
                )
            );

            if (isset($alignments[$index]))
            {
                $alignment = $alignments[$index];

                $HeaderElement['attributes'] = array(
                    'style' => "text-align: $alignment;",
                );
            }

            $HeaderElements []= $HeaderElement;
        }

        # ~

        $Block = array(
            'alignments' => $alignments,
            'identified' => true,
            'element' => array(
                'name' => 'table',
                'elements' => array(),
            ),
        );

        $Block['element']['elements'] []= array(
            'name' => 'thead',
        );

        $Block['element']['elements'] []= array(
            'name' => 'tbody',
            'elements' => array(),
        );

        $Block['element']['elements'][0]['elements'] []= array(
            'name' => 'tr',
            'elements' => $HeaderElements,
        );

        return $Block;
    }

    protected function blockTableContinue($Line, array $Block)
    {
        if (isset($Block['interrupted']))
        {
            return;
        }

        if (count($Block['alignments']) === 1 or $Line['text'][0] === '|' or strpos($Line['text'], '|'))
        {
            $Elements = array();

            $row = $Line['text'];

            $row = trim($row);
            $row = trim($row, '|');

            preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches);

            $cells = array_slice($matches[0], 0, count($Block['alignments']));

            foreach ($cells as $index => $cell)
            {
                $cell = trim($cell);

                $Element = array(
                    'name' => 'td',
                    'handler' => array(
                        'function' => 'lineElements',
                        'argument' => $cell,
                        'destination' => 'elements',
                    )
                );

                if (isset($Block['alignments'][$index]))
                {
                    $Element['attributes'] = array(
                        'style' => 'text-align: ' . $Block['alignments'][$index] . ';',
                    );
                }

                $Elements []= $Element;
            }

            $Element = array(
                'name' => 'tr',
                'elements' => $Elements,
            );

            $Block['element']['elements'][1]['elements'] []= $Element;

            return $Block;
        }
    }

    #
    # ~
    #

    protected function paragraph($Line)
    {
        return array(
            'type' => 'Paragraph',
            'element' => array(
                'name' => 'p',
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $Line['text'],
                    'destination' => 'elements',
                ),
            ),
        );
    }

    protected function paragraphContinue($Line, array $Block)
    {
        if (isset($Block['interrupted']))
        {
            return;
        }

        $Block['element']['handler']['argument'] .= "\n".$Line['text'];

        return $Block;
    }

    #
    # Inline Elements
    #

    protected $InlineTypes = array(
        '!' => array('Image'),
        '&' => array('SpecialCharacter'),
        '*' => array('Emphasis'),
        ':' => array('Url'),
        '<' => array('UrlTag', 'EmailTag', 'Markup'),
        '[' => array('Link'),
        '_' => array('Emphasis'),
        '`' => array('Code'),
        '~' => array('Strikethrough'),
        '\\' => array('EscapeSequence'),
    );

    # ~

    protected $inlineMarkerList = '!*_&[:<`~\\';

    #
    # ~
    #

    public function line($text, $nonNestables = array())
    {
        return $this->elements($this->lineElements($text, $nonNestables));
    }

    protected function lineElements($text, $nonNestables = array())
    {
        # standardize line breaks
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        $Elements = array();

        $nonNestables = (empty($nonNestables)
            ? array()
            : array_combine($nonNestables, $nonNestables)
        );

        # $excerpt is based on the first occurrence of a marker

        while ($excerpt = strpbrk($text, $this->inlineMarkerList))
        {
            $marker = $excerpt[0];

            $markerPosition = strlen($text) - strlen($excerpt);

            $Excerpt = array('text' => $excerpt, 'context' => $text);

            foreach ($this->InlineTypes[$marker] as $inlineType)
            {
                # check to see if the current inline type is nestable in the current context

                if (isset($nonNestables[$inlineType]))
                {
                    continue;
                }

                $Inline = $this->{"inline$inlineType"}($Excerpt);

                if ( ! isset($Inline))
                {
                    continue;
                }

                # makes sure that the inline belongs to "our" marker

                if (isset($Inline['position']) and $Inline['position'] > $markerPosition)
                {
                    continue;
                }

                # sets a default inline position

                if ( ! isset($Inline['position']))
                {
                    $Inline['position'] = $markerPosition;
                }

                # cause the new element to 'inherit' our non nestables


                $Inline['element']['nonNestables'] = isset($Inline['element']['nonNestables'])
                    ? array_merge($Inline['element']['nonNestables'], $nonNestables)
                    : $nonNestables
                ;

                # the text that comes before the inline
                $unmarkedText = substr($text, 0, $Inline['position']);

                # compile the unmarked text
                $InlineText = $this->inlineText($unmarkedText);
                $Elements[] = $InlineText['element'];

                # compile the inline
                $Elements[] = $this->extractElement($Inline);

                # remove the examined text
                $text = substr($text, $Inline['position'] + $Inline['extent']);

                continue 2;
            }

            # the marker does not belong to an inline

            $unmarkedText = substr($text, 0, $markerPosition + 1);

            $InlineText = $this->inlineText($unmarkedText);
            $Elements[] = $InlineText['element'];

            $text = substr($text, $markerPosition + 1);
        }

        $InlineText = $this->inlineText($text);
        $Elements[] = $InlineText['element'];

        foreach ($Elements as &$Element)
        {
            if ( ! isset($Element['autobreak']))
            {
                $Element['autobreak'] = false;
            }
        }

        return $Elements;
    }

    #
    # ~
    #

    protected function inlineText($text)
    {
        $Inline = array(
            'extent' => strlen($text),
            'element' => array(),
        );

        $Inline['element']['elements'] = self::pregReplaceElements(
            $this->breaksEnabled ? '/[ ]*+\n/' : '/(?:[ ]*+\\\\|[ ]{2,}+)\n/',
            array(
                array('name' => 'br'),
                array('text' => "\n"),
            ),
            $text
        );

        return $Inline;
    }

    protected function inlineCode($Excerpt)
    {
        $marker = $Excerpt['text'][0];

        if (preg_match('/^(['.$marker.']++)[ ]*+(.+?)[ ]*+(?<!['.$marker.'])\1(?!'.$marker.')/s', $Excerpt['text'], $matches))
        {
            $text = $matches[2];
            $text = preg_replace('/[ ]*+\n/', ' ', $text);

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'code',
                    'text' => $text,
                ),
            );
        }
    }

    protected function inlineEmailTag($Excerpt)
    {
        $hostnameLabel = '[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?';

        $commonMarkEmail = '[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]++@'
            . $hostnameLabel . '(?:\.' . $hostnameLabel . ')*';

        if (strpos($Excerpt['text'], '>') !== false
            and preg_match("/^<((mailto:)?$commonMarkEmail)>/i", $Excerpt['text'], $matches)
        ){
            $url = $matches[1];

            if ( ! isset($matches[2]))
            {
                $url = "mailto:$url";
            }

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
        }
    }

    protected function inlineEmphasis($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return;
        }

        $marker = $Excerpt['text'][0];

        if ($Excerpt['text'][1] === $marker and preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'strong';
        }
        elseif (preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'em';
        }
        else
        {
            return;
        }

        return array(
            'extent' => strlen($matches[0]),
            'element' => array(
                'name' => $emphasis,
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $matches[1],
                    'destination' => 'elements',
                )
            ),
        );
    }

    protected function inlineEscapeSequence($Excerpt)
    {
        if (isset($Excerpt['text'][1]) and in_array($Excerpt['text'][1], $this->specialCharacters))
        {
            return array(
                'element' => array('rawHtml' => $Excerpt['text'][1]),
                'extent' => 2,
            );
        }
    }

    protected function inlineImage($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[')
        {
            return;
        }

        $Excerpt['text']= substr($Excerpt['text'], 1);

        $Link = $this->inlineLink($Excerpt);

        if ($Link === null)
        {
            return;
        }

        $Inline = array(
            'extent' => $Link['extent'] + 1,
            'element' => array(
                'name' => 'img',
                'attributes' => array(
                    'src' => $Link['element']['attributes']['href'],
                    'alt' => $Link['element']['handler']['argument'],
                ),
                'autobreak' => true,
            ),
        );

        $Inline['element']['attributes'] += $Link['element']['attributes'];

        unset($Inline['element']['attributes']['href']);

        return $Inline;
    }

    protected function inlineLink($Excerpt)
    {
        $Element = array(
            'name' => 'a',
            'handler' => array(
                'function' => 'lineElements',
                'argument' => null,
                'destination' => 'elements',
            ),
            'nonNestables' => array('Url', 'Link'),
            'attributes' => array(
                'href' => null,
                'title' => null,
            ),
        );

        $extent = 0;

        $remainder = $Excerpt['text'];

        if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches))
        {
            $Element['handler']['argument'] = $matches[1];

            $extent += strlen($matches[0]);

            $remainder = substr($remainder, $extent);
        }
        else
        {
            return;
        }

        if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches))
        {
            $Element['attributes']['href'] = $matches[1];

            if (isset($matches[2]))
            {
                $Element['attributes']['title'] = substr($matches[2], 1, - 1);
            }

            $extent += strlen($matches[0]);
        }
        else
        {
            if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches))
            {
                $definition = strlen($matches[1]) ? $matches[1] : $Element['handler']['argument'];
                $definition = strtolower($definition);

                $extent += strlen($matches[0]);
            }
            else
            {
                $definition = strtolower($Element['handler']['argument']);
            }

            if ( ! isset($this->DefinitionData['Reference'][$definition]))
            {
                return;
            }

            $Definition = $this->DefinitionData['Reference'][$definition];

            $Element['attributes']['href'] = $Definition['url'];
            $Element['attributes']['title'] = $Definition['title'];
        }

        return array(
            'extent' => $extent,
            'element' => $Element,
        );
    }

    protected function inlineMarkup($Excerpt)
    {
        if ($this->markupEscaped or $this->safeMode or strpos($Excerpt['text'], '>') === false)
        {
            return;
        }

        if ($Excerpt['text'][1] === '/' and preg_match('/^<\/\w[\w-]*+[ ]*+>/s', $Excerpt['text'], $matches))
        {
            return array(
                'element' => array('rawHtml' => $matches[0]),
                'extent' => strlen($matches[0]),
            );
        }

        if ($Excerpt['text'][1] === '!' and preg_match('/^<!---?[^>-](?:-?+[^-])*-->/s', $Excerpt['text'], $matches))
        {
            return array(
                'element' => array('rawHtml' => $matches[0]),
                'extent' => strlen($matches[0]),
            );
        }

        if ($Excerpt['text'][1] !== ' ' and preg_match('/^<\w[\w-]*+(?:[ ]*+'.$this->regexHtmlAttribute.')*+[ ]*+\/?>/s', $Excerpt['text'], $matches))
        {
            return array(
                'element' => array('rawHtml' => $matches[0]),
                'extent' => strlen($matches[0]),
            );
        }
    }

    protected function inlineSpecialCharacter($Excerpt)
    {
        if (substr($Excerpt['text'], 1, 1) !== ' ' and strpos($Excerpt['text'], ';') !== false
            and preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt['text'], $matches)
        ) {
            return array(
                'element' => array('rawHtml' => '&' . $matches[1] . ';'),
                'extent' => strlen($matches[0]),
            );
        }

        return;
    }

    protected function inlineStrikethrough($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return;
        }

        if ($Excerpt['text'][1] === '~' and preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'del',
                    'handler' => array(
                        'function' => 'lineElements',
                        'argument' => $matches[1],
                        'destination' => 'elements',
                    )
                ),
            );
        }
    }

    protected function inlineUrl($Excerpt)
    {
        if ($this->urlsLinked !== true or ! isset($Excerpt['text'][2]) or $Excerpt['text'][2] !== '/')
        {
            return;
        }

        if (strpos($Excerpt['context'], 'http') !== false
            and preg_match('/\bhttps?+:[\/]{2}[^\s<]+\b\/*+/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE)
        ) {
            $url = $matches[0][0];

            $Inline = array(
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );

            return $Inline;
        }
    }

    protected function inlineUrlTag($Excerpt)
    {
        if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<(\w++:\/{2}[^ >]++)>/i', $Excerpt['text'], $matches))
        {
            $url = $matches[1];

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
        }
    }

    # ~

    protected function unmarkedText($text)
    {
        $Inline = $this->inlineText($text);
        return $this->element($Inline['element']);
    }

    #
    # Handlers
    #

    protected function handle(array $Element)
    {
        if (isset($Element['handler']))
        {
            if (!isset($Element['nonNestables']))
            {
                $Element['nonNestables'] = array();
            }

            if (is_string($Element['handler']))
            {
                $function = $Element['handler'];
                $argument = $Element['text'];
                unset($Element['text']);
                $destination = 'rawHtml';
            }
            else
            {
                $function = $Element['handler']['function'];
                $argument = $Element['handler']['argument'];
                $destination = $Element['handler']['destination'];
            }

            $Element[$destination] = $this->{$function}($argument, $Element['nonNestables']);

            if ($destination === 'handler')
            {
                $Element = $this->handle($Element);
            }

            unset($Element['handler']);
        }

        return $Element;
    }

    protected function handleElementRecursive(array $Element)
    {
        return $this->elementApplyRecursive(array($this, 'handle'), $Element);
    }

    protected function handleElementsRecursive(array $Elements)
    {
        return $this->elementsApplyRecursive(array($this, 'handle'), $Elements);
    }

    protected function elementApplyRecursive($closure, array $Element)
    {
        $Element = call_user_func($closure, $Element);

        if (isset($Element['elements']))
        {
            $Element['elements'] = $this->elementsApplyRecursive($closure, $Element['elements']);
        }
        elseif (isset($Element['element']))
        {
            $Element['element'] = $this->elementApplyRecursive($closure, $Element['element']);
        }

        return $Element;
    }

    protected function elementApplyRecursiveDepthFirst($closure, array $Element)
    {
        if (isset($Element['elements']))
        {
            $Element['elements'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['elements']);
        }
        elseif (isset($Element['element']))
        {
            $Element['element'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['element']);
        }

        $Element = call_user_func($closure, $Element);

        return $Element;
    }

    protected function elementsApplyRecursive($closure, array $Elements)
    {
        foreach ($Elements as &$Element)
        {
            $Element = $this->elementApplyRecursive($closure, $Element);
        }

        return $Elements;
    }

    protected function elementsApplyRecursiveDepthFirst($closure, array $Elements)
    {
        foreach ($Elements as &$Element)
        {
            $Element = $this->elementApplyRecursiveDepthFirst($closure, $Element);
        }

        return $Elements;
    }

    protected function element(array $Element)
    {
        if ($this->safeMode)
        {
            $Element = $this->sanitiseElement($Element);
        }

        # identity map if element has no handler
        $Element = $this->handle($Element);

        $hasName = isset($Element['name']);

        $markup = '';

        if ($hasName)
        {
            $markup .= '<' . $Element['name'];

            if (isset($Element['attributes']))
            {
                foreach ($Element['attributes'] as $name => $value)
                {
                    if ($value === null)
                    {
                        continue;
                    }

                    $markup .= " $name=\"".self::escape($value).'"';
                }
            }
        }

        $permitRawHtml = false;

        if (isset($Element['text']))
        {
            $text = $Element['text'];
        }
        // very strongly consider an alternative if you're writing an
        // extension
        elseif (isset($Element['rawHtml']))
        {
            $text = $Element['rawHtml'];

            $allowRawHtmlInSafeMode = isset($Element['allowRawHtmlInSafeMode']) && $Element['allowRawHtmlInSafeMode'];
            $permitRawHtml = !$this->safeMode || $allowRawHtmlInSafeMode;
        }

        $hasContent = isset($text) || isset($Element['element']) || isset($Element['elements']);

        if ($hasContent)
        {
            $markup .= $hasName ? '>' : '';

            if (isset($Element['elements']))
            {
                $markup .= $this->elements($Element['elements']);
            }
            elseif (isset($Element['element']))
            {
                $markup .= $this->element($Element['element']);
            }
            else
            {
                if (!$permitRawHtml)
                {
                    $markup .= self::escape($text, true);
                }
                else
                {
                    $markup .= $text;
                }
            }

            $markup .= $hasName ? '</' . $Element['name'] . '>' : '';
        }
        elseif ($hasName)
        {
            $markup .= ' />';
        }

        return $markup;
    }

    protected function elements(array $Elements)
    {
        $markup = '';

        $autoBreak = true;

        foreach ($Elements as $Element)
        {
            if (empty($Element))
            {
                continue;
            }

            $autoBreakNext = (isset($Element['autobreak'])
                ? $Element['autobreak'] : isset($Element['name'])
            );
            // (autobreak === false) covers both sides of an element
            $autoBreak = !$autoBreak ? $autoBreak : $autoBreakNext;

            $markup .= ($autoBreak ? "\n" : '') . $this->element($Element);
            $autoBreak = $autoBreakNext;
        }

        $markup .= $autoBreak ? "\n" : '';

        return $markup;
    }

    # ~

    protected function li($lines)
    {
        $Elements = $this->linesElements($lines);

        if ( ! in_array('', $lines)
            and isset($Elements[0]) and isset($Elements[0]['name'])
            and $Elements[0]['name'] === 'p'
        ) {
            unset($Elements[0]['name']);
        }

        return $Elements;
    }

    #
    # AST Convenience
    #

    /**
     * Replace occurrences $regexp with $Elements in $text. Return an array of
     * elements representing the replacement.
     */
    protected static function pregReplaceElements($regexp, $Elements, $text)
    {
        $newElements = array();

        while (preg_match($regexp, $text, $matches, PREG_OFFSET_CAPTURE))
        {
            $offset = $matches[0][1];
            $before = substr($text, 0, $offset);
            $after = substr($text, $offset + strlen($matches[0][0]));

            $newElements[] = array('text' => $before);

            foreach ($Elements as $Element)
            {
                $newElements[] = $Element;
            }

            $text = $after;
        }

        $newElements[] = array('text' => $text);

        return $newElements;
    }

    #
    # Deprecated Methods
    #

    function parse($text)
    {
        $markup = $this->text($text);

        return $markup;
    }

    protected function sanitiseElement(array $Element)
    {
        static $goodAttribute = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
        static $safeUrlNameToAtt  = array(
            'a'   => 'href',
            'img' => 'src',
        );

        if ( ! isset($Element['name']))
        {
            unset($Element['attributes']);
            return $Element;
        }

        if (isset($safeUrlNameToAtt[$Element['name']]))
        {
            $Element = $this->filterUnsafeUrlInAttribute($Element, $safeUrlNameToAtt[$Element['name']]);
        }

        if ( ! empty($Element['attributes']))
        {
            foreach ($Element['attributes'] as $att => $val)
            {
                # filter out badly parsed attribute
                if ( ! preg_match($goodAttribute, $att))
                {
                    unset($Element['attributes'][$att]);
                }
                # dump onevent attribute
                elseif (self::striAtStart($att, 'on'))
                {
                    unset($Element['attributes'][$att]);
                }
            }
        }

        return $Element;
    }

    protected function filterUnsafeUrlInAttribute(array $Element, $attribute)
    {
        foreach ($this->safeLinksWhitelist as $scheme)
        {
            if (self::striAtStart($Element['attributes'][$attribute], $scheme))
            {
                return $Element;
            }
        }

        $Element['attributes'][$attribute] = str_replace(':', '%3A', $Element['attributes'][$attribute]);

        return $Element;
    }

    #
    # Static Methods
    #

    protected static function escape($text, $allowQuotes = false)
    {
        return htmlspecialchars($text, $allowQuotes ? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
    }

    protected static function striAtStart($string, $needle)
    {
        $len = strlen($needle);

        if ($len > strlen($string))
        {
            return false;
        }
        else
        {
            return strtolower(substr($string, 0, $len)) === strtolower($needle);
        }
    }

    static function instance($name = 'default')
    {
        if (isset(self::$instances[$name]))
        {
            return self::$instances[$name];
        }

        $instance = new static();

        self::$instances[$name] = $instance;

        return $instance;
    }

    private static $instances = array();

    #
    # Fields
    #

    protected $DefinitionData;

    #
    # Read-Only

    protected $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '~'
    );

    protected $StrongRegex = array(
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
    );

    protected $EmRegex = array(
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    );

    protected $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    protected $voidElements = array(
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
    );

    protected $textLevelElements = array(
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'del', 'code',          'strike', 'marquee',
        'q', 'rt', 'ins', 'font',          'strong',
        's', 'tt', 'kbd', 'mark',
        'u', 'xm', 'sub', 'nobr',
                   'sup', 'ruby',
                   'var', 'span',
                   'wbr', 'time',
    );
}

?>
<?php

function log_message ($message, $exit_level = null, $log_level=2) {
  global $LOG_LEVEL;

  if (defined('STDIN')) {
    // Executed from CLI (tests?)
    echo("LOG: ".$message."\n");
  }
  elseif ($log_level <= $LOG_LEVEL) {
    // Write to server log
    error_log($message, 4);
  }

  if (!is_null($exit_level)) {
    exit($exit_level);
  }
}

function remove_trailing_slash($path) {
  return preg_replace('/[\\\\\/]+$/', '', $path);
}

function get_my_url($url = null) {
  if (is_null($url)) {
    if (@$_SERVER['TEST_MY_URL'] != "") {
      $url = $_SERVER['TEST_MY_URL'];
    }
    else {
      $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
    }
  }

  $url = preg_replace('/[^\/]*?$/', '', $url);

  return $url;
}

?>

<?php

class AdminAPI {
  private $page_storage = null;
  private $file_storage = null;
  private $function = null;
  private $data = null;
  private $data_path = null;

  function __construct($data_path, $function, $data) {
    $this->page_storage = new PageStorage($data_path."/content.json");
    $this->file_storage = new FileStorage($data_path);
    $this->function = $function;
    $this->data = $data;
    $this->data_path = $data_path;
  }

  private function get_return_data($success, $data = null, $message = null) {
    $return_data = Array(
      'success' => $success,
      'message' => ''
    );

    if (!is_null($data)) {
      $return_data['data'] = $data;
    }

    if (!is_null($message)) {
      $return_data['message'] = $message;
    }

    return json_encode($return_data);
  }

  private function is_json($str) {
    $obj = json_decode($str);
    if (is_null($obj)) {
      return false;
    }

    return true;
  }

  private function get_preview_html($page_data) {

    if ($this->is_json($page_data)) {
      $show_page = new ShowPage("0", $this->data_path, $page_data);
      return Array(
        'html' => $show_page->get_html_preview(),
        'head' => $show_page->get_html_googlefonts()
      );
    }
    else {
      return "<p>Given parameter is not a JSON-formatted object</p>";
    }
  }

  function execute() {
    log_message("Execute, function: '".$this->function."' data: ".print_r($this->data, true));

    if ($this->function == "get") {
      $data = $this->page_storage->get_data_json();

      if (!$data) {
        return $this->get_return_data(false);
      }

      return $this->get_return_data(true, json_decode($data, true));
    }

    if ($this->function == "set") {
      if (!$this->page_storage->set_data_json($this->data)) {
        return $this->get_return_data(false);
      }

      return $this->get_return_data(true);
    }

    if ($this->function == "preview") {
      return $this->get_return_data(true, $this->get_preview_html($this->data));
    }

    if ($this->function == "file_list") {
      return $this->get_return_data(true, $this->file_storage->get_file_list());
    }

    if ($this->function == "file_upload") {
      log_message(print_r($_FILES['file_upload'], true));
      $upload_success = $this->file_storage->upload_file($_FILES['file_upload']);
      return $this->get_return_data($upload_success, $this->file_storage->get_file_list(), $this->file_storage->get_last_error());
    }

    if ($this->function == "file_delete") {
      $delete_success = $this->file_storage->delete_file($this->data);
      return $this->get_return_data($delete_success, $this->file_storage->get_file_list(), $this->file_storage->get_last_error());
    }

    if ($this->function == "loginfailed") {
      return $this->get_return_data(false, null, $this->data);
    }

    if ($this->function == "uploadlimitexceeded") {
      return $this->get_return_data(false, null, $this->data);
    }
  }
}

?>

<?php

class AdminAuth {
  private $methods;
  private $last_error;

  function __construct($methods=null) {
    if (!is_array($methods) or sizeof($methods) < 1) {
      $this->raise_exception("AdminAuth requires authentication methods as an array");
      $this->methods = null;
    }
    else {
      $this->methods = $methods;
    }
    $this->last_error = null;
  }

  private function log_message($message) {
    log_message("AdminAuth error: ".$message);
  }

  private function set_last_error($message) {
    $this->last_error = $message;
    $this->log_message($message);
  }

  private function raise_exception($message) {
    $this->set_last_error($message);
    throw new Exception($message);
  }

  function get_last_error() {
    $error = $this->last_error;
    $this->last_error = null;
    return $error;
  }

  function is_admin($password) {
    if (is_null($this->methods)) {
      $this->raise_exception("No authentication methods defined");
    }

    foreach ($this->methods as $method => $param) {
      $auth_success = $this->is_admin_method($method, $param, $password);
      if ($auth_success) {
        return true;
      }
    }

    return false;
  }

  private function is_admin_method($method, $method_param, $authentication) {
    if ($method == "file") {
      return $this->is_admin_file($method_param, $authentication);
    }

    $this->raise_exception("Unknown authentication method: ".$method);
  }

  private function is_admin_file($filename, $password) {
    $s = new Settings($filename);
    $file_password = $s->get_value('ADMIN_PASSWORD');

    if (is_null($file_password) or $file_password === "") {
      $this->set_last_error("Password in ".$s->get_filename()." has not been set");
      return false;
    }

    if ($file_password === $password) {
      return true;
    }

    return false;

  }
}

?>

<?php

class FileStorage {
  private $IGNORE_FILES = null;
  private $data_path = null;
  private $last_error = "";

  public function __construct($data_path) {
    $this->data_path = $data_path;

    $this->IGNORE_FILES = Array('', '.', '..', 'content.json');
  }

  public function get_last_error() {
    $last_error = $this->last_error;
    $this->last_error = "";
    return $last_error;
  }

  private function set_last_error($error) {
    $this->last_error = $error;
  }

  public function get_file_list() {
    $file_list = Array();

    if ($handle = opendir($this->data_path)) {
      while (false !== ($entry = readdir($handle))) {
        if (!in_array($entry, $this->IGNORE_FILES)) {
          $entry_data = Array(
            'name' => $entry,
            'size' => filesize($this->data_path.DIRECTORY_SEPARATOR.$entry)
          );
          array_push($file_list, $entry_data);
        }
      }
    }

    return $file_list;
  }

  private function valid_filename($filename) {
    global $FILE_UPLOAD_ALLOWED_CHARS_REGEX;

    // Make sure the filename does not contain a directory separator
    if (strpos(DIRECTORY_SEPARATOR, $filename) !== false) {
      return null;
    }

    // Allow only listed characters
    $filename = basename($filename);
    $filename = preg_replace('/[^'.$FILE_UPLOAD_ALLOWED_CHARS_REGEX.']/', '', $filename);

    // Make sure the filename is not one of the forbidden files
    if (in_array($filename, $this->IGNORE_FILES)) {
      return null;
    }

    return $this->data_path.DIRECTORY_SEPARATOR.$filename;
  }

  public function upload_file($upload_file_data) {
    if ($upload_file_data['error']) {
      if (is_file($upload_file_data['tmp_name'])) {
        unlink($upload_file_data['tmp_name']);
      }
      return false;
    }

    $valid_filename = $this->valid_filename($upload_file_data['name']);
    if (is_null($valid_filename)) {
      if (is_file($upload_file_data['tmp_name'])) {
        unlink($upload_file_data['tmp_name']);
      }
      return false;
    }

    if (file_exists($valid_filename)) {
      if (is_file($upload_file_data['tmp_name'])) {
        unlink($upload_file_data['tmp_name']);
      }
      $this->set_last_error("'".$upload_file_data['name']."' already exists");
      return false;
    }

    return move_uploaded_file($upload_file_data['tmp_name'], $valid_filename);
  }

  public function delete_file($filename) {
    $valid_filename = $this->valid_filename($filename);
    if (is_null($valid_filename)) {
      return false;
    }

    if (unlink($valid_filename) > 0) {
      return true;
    }

    $this->set_last_error("Unable to remove file '".$filename."'");

    return false;
  }
}

?>

<?php

class PageContent {
  public $page_data = null;
  private $data_path = "";

  // This field values should possibly be added with $this->data_path prefix
  private $DATA_PATH_FIELD = Array(
    'page' => Array(
      'favicon-ico',
      'image'
    ),
    'part' => Array(
      'background-image',
      'text'
    )
  );

  public function __construct($page_data, $data_path = "") {
    // By default the $page_data is a JSON-encoded string
    $page_data_obj = json_decode($page_data, true);
    if (is_null($page_data_obj)) {
      // $page_data was not a JSON-formatted string, treat it as a filename
      $json = file_get_contents($page_data);
      $this->page_data = json_decode($json, true);
    }
    else {
      // $page_data was a JSON-formatted string
      $this->page_data = $page_data_obj;
    }
    $this->data_path = $data_path;
  }

  private function add_datapath_prefix_one($value) {
    if (!filter_var($value, FILTER_VALIDATE_URL) and !preg_match('/[\/]/', $value)) {
      log_message("add_datapath_prefix returning value with prefix: ".$this->data_path.'/'.$value, null, 2);
      return $this->data_path.'/'.$value;
    }

    return $value;
  }

  private function add_datapath_prefix_text($value) {
    // NB! This does not handle well cases where an image link exists outside and inside backticks
    //     See PageContent_test for more info

    $replacement_count = 0;
    $original_value = $value;

    do {
      $value = preg_replace('/^([^`]*)(!*)\[(.*)\]\(([^\/]*)\)([^`]*)$/m', '$1$2[$3]('.$this->data_path.'/$4)$5', $value, 1, $replacements_made);
      if ($replacements_made > 0) {
        $replacement_count++;
      }
    } while ($replacements_made > 0);

    if ($replacement_count > 0) {
      log_message('add_datapath_prefix_text: '.$replacement_count.' changes:', null, 2);
      log_message('original string: '.$original_value, null, 2);
      log_message('final string   : '.$value, null, 2);
    }
    return $value;
  }

  public function add_datapath_prefix($scope, $field, $value) {
    if (in_array($field, $this->DATA_PATH_FIELD[$scope])) {
      log_message("add_datapath_prefix scope: $scope, field: $field, value: $value", null, 2);
      if ($scope == "part" and $field == "text") {
        return $this->add_datapath_prefix_text($value);
      }

      return $this->add_datapath_prefix_one($value);
    }

    return $value;
  }

  public function get_page_value($field, $default=null) {
    if (is_null($this->page_data) or
      !array_key_exists('page_values', $this->page_data) or
      !array_key_exists($field, $this->page_data['page_values'])) {
        return $default;
    }

    return $this->add_datapath_prefix('page', $field, $this->page_data['page_values'][$field]);
  }

  // Returns value you can give to Google Fonts CSS tag, e.g. "Playfair+Display|Tomorrow"
  // <link href="https://fonts.googleapis.com/css?family=Playfair+Display|Tomorrow&display=swap" rel="stylesheet" />
  // In case no Google Fonts are used returns null

  public function get_page_google_fonts_value() {
    $fonts_used = Array();

    for ($n=0; $n < $this->get_parts_count(); $n++) {
      $this_font = $this->get_part($n, 'font-family-google');
      if (!is_null($this_font) and $this_font != "") {
        array_push($fonts_used, urlencode($this->get_part($n, 'font-family-google')));
      }
    }

    if (count($fonts_used) > 0) {
      return join('|', $fonts_used);
    }

    return null;
  }

  public function get_parts_count() {
    if (is_null($this->page_data) or !array_key_exists('parts', $this->page_data)) {
      return null;
    }

    return count($this->page_data['parts']);
  }

  public function get_part($index, $field, $default=null) {
    if (!array_key_exists('parts', $this->page_data)) {
      log_message("The page content has no field 'parts'", null, 2);
      return $default;
    }

    if (!array_key_exists($index, $this->page_data['parts'])) {
      log_message("The page content has not field 'parts'->$index", null, 2);
      return $default;
    }

    if (!array_key_exists($field, $this->page_data['parts'][$index])) {
      log_message("The page content has no field 'parts'->$index->$field", null, 2);
      return $default;
    }

    return $this->add_datapath_prefix('part', $field, $this->page_data['parts'][$index][$field]);
  }

}

?>

<?php

class PageStorage {
  private $data_file = null;

  public function __construct($data_file) {
    $this->data_file = $data_file;
  }

  public function get_data_json() {
    return file_get_contents($this->data_file);
  }

  public function set_data_json($json) {
    return file_put_contents($this->data_file, $json, LOCK_EX);
  }
}

?>

<?php

class Settings {
  private $filename = null;
  private $rules = null;

  function __construct($filename=null) {
    if (is_null($filename) or ($filename === "")) {
      $this->filename = "settings.php";
    }
    else {
      $this->filename = $filename;
    }

    $this->rules = Array(
      'ADMIN_PASSWORD' => "string",
      'LOG_LEVEL' => "integer"
    );
  }

  function get_filename() {
    return $this->filename;
  }

  function set_value($field, $value) {
    $field = strtoupper($field);

    if (!array_key_exists($field, $this->rules)) {
      log_message("Trying to set field $field which does not exist");
      return false;
    }

    if (gettype($value) != $this->rules[$field]) {
      log_message("Trying to set field $field to value $value, which is illegal type ".gettype($value));
      return false;
    }

    $settings = $this->read_settings_file();
    $settings[$field] = $value;
    return $this->write_settings_file($settings);
  }

  function get_value($field) {
    $field = strtoupper($field);

    if (!array_key_exists($field, $this->rules)) {
      log_message("Trying to get field $field which does not exist");
      return false;
    }

    $settings = $this->read_settings_file();
    return @$settings[$field];
  }

  private function read_settings_file() {
    if (!is_readable($this->filename)) {
      log_message("Settings file ".$this->filename." is not readable");
      return Array();
    }

    $file = file_get_contents($this->filename);

    $settings = Array();

    if (preg_match('/(\{.*\})/', $file, $matches)) {
      $settings = json_decode($matches[1], true);
    }

    return $settings;
  }

  private function write_settings_file($settings) {
    if (!is_writable($this->filename)) {
      log_message("Settings file ".$this->filename." is not writable");
      return false;
    }

    $c = Array();
    array_push($c, "<?php");
    array_push($c, "/*");
    array_push($c, json_encode($settings));
    array_push($c, "*/");
    array_push($c, "?>");

    $bytes_written = file_put_contents($this->filename, join("\n", $c)."\n");

    if ($bytes_written == false) {
      return false;
    }

    return true;
  }
}

?>

<?php

class ShowAdminUI {
  function __construct() {
    ?>
    <!DOCTYPE html>
<html>
<head>
  <title>DummyLander AdminUI</title>
  <meta charset="UTF-8">
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <!-- Gear Icon from set Noto Emoji Objects Icons (C) Google, License: Apache 2.0 -->
<link rel="shortcut icon" href="data:image/x-icon;base64,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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAampqBWpqagZpaWkGa2trBgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAG1tbQpqamo1ZWVlXmNjY4FhYWGfYGBguWBgYM5eXl7eX19f7l1dXftbW1v/W1tb/1tbW/9bW1v/XFxc/l5eXvReXl7hXl5e0V5eXrxfX1+jX19fhmBgYGRhYWE7YmJiDgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAcXFxAmpqamljY2POXV1d/FtbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v9XV1d1F1dXXldXV0IAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHJycgNoaGiyW1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1tbW/9bW1v/W1tb/1xcXMZdXV0KAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAb29va1tbW/9bW1v/W1tb/1tbW/9cXFz/XV1d/15eXv9fX1//YGBg/2FhYf9hYWH/YWFh/2JiYv9iYmL/YmJi/2JiYv9iYmL/YmJi/2JiYv9hYWH/YWFh/2BgYP9fX1//Xl5e/11dXf9cXFz/W1tb/1tbW/9bW1v/W1tb/11dXYgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABmZmbJXFxc/1xcXP9eXl7/YmJi/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9iYmL/X19f/1xcXP9cXFz/XV1d5wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAGVlZfNcXFz/XFxc/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/Y2Nj/2NjY/9jY2P/XFxc/1xcXP9dXV3/X19fFQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB3d3cWX19f/11dXf9dXV3/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9dXV3/XV1d/11dXf9fX182AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHR0dDddXV3/XV1d/19fX/9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/2RkZP9kZGT/ZGRk/19fX/9dXV3/XV1d/19fX1gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df98fHz/oKCg/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/mpqa/3R0dP90dHT/dHR0/3R0dP90dHT/dHR0/3Nzc/9qamr/ampq/2pqav9ra2vcbW1tEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB6enoZcHBw52pqav9qamr/bGxs/3R0dP90dHT/dHR0/3R0dP90dHT/dHR0/3R0dP+ampr/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/n5+f/3p6ev91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df92dnb/lZWV/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/62trf+tra3/ra2t/6Wlpf93d3f/dHR0/3R0dP90dHT/dHR0/3R0dP90dHT/bGxs/2pqav9qamr/a2tr9m1tbTEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB6enpAbGxs+2tra/9ra2v/b29v/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3h4eP+mpqb/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/5qamv92dnb/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3R0dP90dHT/c3Nz/3Nzc/90dHT/dHR0/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/jo6O/62trf+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/66urv+srKz/f39//3V1df91dXX/dXV1/3V1df91dXX/dXV1/29vb/9ra2v/a2tr/2tra/9ubm5jAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAB5eXl4a2tr/2tra/9ra2v/cnJy/3V1df91dXX/dXV1/3V1df91dXX/dXV1/4eHh/+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/66urv+Xl5f/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df9zc3P/cHBw/25ubv9sbGz/a2tr/2tra/9ra2v/a2tr/2tra/9ra2v/bGxs/25ubv9wcHD/c3Nz/3R0dP91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/dXV1/3V1df91dXX/ioqK/62trf+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/66urv+urq7/rq6u/5SUlP91dXX/dXV1/3V1df91dXX/dXV1/3V1df9ycnL/a2tr/2tra/9ra2v/bm5unQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlZWVRoiIiP+IiIj/jIyM/5qamv+ampr/mpqa/5qamv+ampr/mpqa/5qamv+ampr/mpqa/5qamv+ampr/mpqa/5qamv+ampr/mpqa/5qamv+ampr/mpqa/5qamv+ampr/mpqa/5qamv+ampr/mpqa/5qamv+ampr/jY2N/4iIiP+IiIj/jY2NZwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACZmZkliYmJ/4mJif+Li4v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+Li4v/iYmJ/4mJif+Pj49GAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJqamgaMjIz8iYmJ/4mJif+ampr/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/m5ub/5ubm/+bm5v/mpqa/4mJif+JiYn/iYmJ/5CQkCQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJCQkN+Kior/ioqK/5eXl/+cnJz/nJyc/5ycnP+cnJz/nJyc/5ycnP+cnJz/nJyc/5ycnP+cnJz/nJyc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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA///////////////////////////////////////////////////////////////////////////////////////////////8P//////////////////wAAAP////////////////gAAAAf///////////////wAAAAD///////////////8AAAAA////////////////AAAAAP///////////////wAAAAB///////////////4AAAAAf//////////////+AAAAAH////////////8P/gAAAAB/8P/////////8A/4AAAAAf8A/////////+AH+AAAAAH+AH/////////AA/gAAAAB/AA/////////AAD4AAAAAPAAD////////gAAcAAAAADgAAf///////wAADAAAAAAwAAD///////4AAAAAAAAAAAAAf//////8AAAAAAAAAAAAAD//////+AAAAAAAAAAAAAAf//////AAAAAAAAAAAAAAD//////gAAAAAAAAAAAAAAf/////wAAAAAAAAAAAAAAD/////4AAAAAAAAAAAAAAAf////+AAAAAAAAAAAAAAAD/////AAAAAAAAAAAAAAAA/////gAAAAAAAAAAAAAAAH////wAAAAAAAAAAAAAAAA////8AAAAAAAAAAAAAAAAP///+AAAAAAAAAAAAAAAAB////gAAAAAAAAAAAAAAAAf///4AAAAAAAAAAAAAAAAH///+AAAAAAAAAAAAAAAAB////gAAAAAAAAAAAAAAAAf///8AAAAAAAAAAAAAAAAP////gAAAAAAAAAAAAAAAD////4AAAAAAAAAAAAAAAB/////AAAAAAAAAAAAAAAA/////4AAAAAAAAAAAAAAAf/////AAAAAAAAAAAAAAAP/////wAAAAAAAAAAAAAAD/////+AAAAAAAAAAAAAAB//////gAAAAAAPAAAAAAAf/////4AAAAAA//AAAAAAH////8AAAAAAA//+AAAAAAA///wAAAAAAA///wAAAAAAAf/wAAAAAAA////AAAAAAAD/8AAAAAAAf///4AAAAAAA/+AAAAAAAP////AAAAAAAH/gAAAAAAH////4AAAAAAB/4AAAAAAB////+AAAAAAAf+AAAAAAA/////wAAAAAAH/gAAAAAAf////+AAAAAAB/4AAAAAAH/////gAAAAAAf+AAAAAAB/////8AAAAAAD/gAAAAAA//////AAAAAAA/wAAAAAAP/////wAAAAAAP8AAAAAAD/////8AAAAAAD/AAAAAAB//////gAAAAAA/wAAAAAAf/////4AAAAAAP8AAAAAAH/////+AAAAAAD/AAAAAAB//////gAAAAAA/wAAAAAAf/////4AAAAAAP8AAAAAAH/////+AAAAAAD/AAAAAAA//////gAAAAAA/wAAAAAAP/////wAAAAAAP8AAAAAAD/////8AAAAAAD/AAAAAAA//////AAAAAAA/4AAAAAAH/////gAAAAAAP+AAAAAAB/////4AAAAAAH/gAAAAAAP////8AAAAAAB/4AAAAAAD/////AAAAAAAf+AAAAAAAf////gAAAAAAH/gAAAAAAD////wAAAAAAB/8AAAAAAAf///4AAAAAAAf/AAAAAAAD///8AAAAAAAP/4AAAAAAAf//+AAAAAAAH//gAAAAAAB//+AAAAAAAH///8AAAAAAH//AAAAAAD/////4AAAAAAf+AAAAAAH//////AAAAAAAAAAAAAAB//////gAAAAAAAAAAAAAAf/////wAAAAAAAAAAAAAAD/////4AAAAAAAAAAAAAAAf////+AAAAAAAAAAAAAAAD/////AAAAAAAAAAAAAAAA/////gAAAAAAAAAAAAAAAH////wAAAAAAAAAAAAAAAA////8AAAAAAAAAAAAAAAAH///+AAAAAAAAAAAAAAAAB////gAAAAAAAAAAAAAAAAf///4AAAAAAAAAAAAAAAAH///+AAAAAAAAAAAAAAAAB////gAAAAAAAAAAAAAAAAf///8AAAAAAAAAAAAAAAAP////gAAAAAAAAAAAAAAAH////4AAAAAAAAAAAAAAAB/////AAAAAAAAAAAAAAAA/////4AAAAAAAAAAAAAAAf/////AAAAAAAAAAAAAAAP/////4AAAAAAAAAAAAAAH//////AAAAAAAAAAAAAAB//////4AAAAAAAAAAAAAA///////AAAAAAAAAAAAAAf//////4AAAAAAAAAAAAAP///////AAAAAAAAAAAAAH///////4AADAAAAAAwAAH////////AAD4AAAAAPAAD////////4AB+AAAAAH4AB/////////AA/gAAAAB/AA/////////8Af4AAAAAf8A//////////gf+AAAAAH/gf////////////gAAAAB///////////////4AAAAAf//////////////+AAAAAH///////////////wAAAAB///////////////8AAAAA////////////////AAAAAP///////////////4AAAAH////////////////AAAAD/////////////////gAAf/////////////////////////////////////////////////////////////////////////////////////////////8oAAAAYAAAAMAAAAABACAAAAAAAICUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABkZGQMX19f/lxcXP5iYmL+Y2Nj/mNjY/5jY2P+Y2Nj/mNjY/5jY2P+Y2Nj/mNjY/5jY2P+Y2Nj/mNjY/5jY2P+Y2Nj/mNjY/5jY2P+Y2Nj/mNjY/5jY2P+YmJi/lxcXP5cXFz+X19fJAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABzc3MuXV1d/l1dXf9kZGT/ZGRk/mRkZP9kZGT/ZGRk/mRkZP9kZGT/ZGRk/mRkZP9kZGT/ZGRk/mRkZP9kZGT/ZGRk/mRkZP9kZGT/ZGRk/mRkZP9kZGT+ZGRk/11dXf9dXV3+YGBgRgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA///////////////////////////////////////////////////////AA/////////////gAAB////////////AAAA////////////AAAA////////////AAAAf//////////+AAAAf//////////+AAAAf////////+D+AAAAfwf//////8B+AAAAfgP//////wAeAAAAeAD//////gAOAAAAcAB//////AAEAAAAIAA/////+AAAAAAAAAAf////8AAAAAAAAAAP////4AAAAAAAAAAH////wAAAAAAAAAAD////gAAAAAAAAAAB////AAAAAAAAAAAA////AAAAAAAAAAAAf//+AAAAAAAAAAAAf//8AAAAAAAAAAAAP//8AAAAAAAAAAAAP//8AAAAAAAAAAAAH//8AAAAAAAAAAAAH//8AAAAAAAAAAAAP//8AAAAAAAAAAAAP//+AAAAAAAAAAAAf///AAAAAAAAAAAA////gAAAAAAAAAAB////wAAAAAAAAAAB////wAAAAAAAAAAD////wAAAAH4AAAAD///4AAAAA//AAAAAH/+AAAAAB//wAAAAAf8AAAAAH//4AAAAAP8AAAAAP//8AAAAAP8AAAAAf//+AAAAAP8AAAAA////AAAAAH4AAAAA////AAAAAH4AAAAB////gAAAAH4AAAAB////gAAAAH4AAAAB////gAAAAH4AAAAD////wAAAAH4AAAAD////wAAAAH4AAAAD////wAAAAH4AAAAD////wAAAAH4AAAAD////wAAAAH4AAAAD////wAAAAH4AAAAB////wAAAAH4AAAAB////gAAAAH4AAAAB////gAAAAH4AAAAA////AAAAAH4AAAAA////AAAAAH8AAAAAf//+AAAAAP8AAAAAP//8AAAAAP8AAAAAH//4AAAAAP+AAAAAD//wAAAAAf/AAAAAA//gAAAAA///wAAAAP8AAAAB////wAAAAAAAAAAD////wAAAAAAAAAAD////gAAAAAAAAAAB////AAAAAAAAAAAA////AAAAAAAAAAAAf//+AAAAAAAAAAAAf//8AAAAAAAAAAAAP//8AAAAAAAAAAAAP//8AAAAAAAAAAAAH//8AAAAAAAAAAAAP//8AAAAAAAAAAAAP//+AAAAAAAAAAAAP//+AAAAAAAAAAAAf///AAAAAAAAAAAA////gAAAAAAAAAAB////wAAAAAAAAAAD////4AAAAAAAAAAH////8AAAAAAAAAAH////+AAAAAAAAAAP/////AAAAAAAAAA//////gAGAAAAcAB//////wAeAAAAeAD//////4A+AAAAfAH//////8B+AAAAfgP///////H+AAAAf4/////////+AAAAf///////////AAAAf///////////AAAA////////////AAAA////////////gAAB////////////4AAH//////////////////////////////////////////////////////KAAAAEgAAACQAAAAAQAgAAAAAABgVAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABiYmIDYWFhDWBgYB5fX18sX19fM2BgYDNfX18vX19fIF5eXg5eXl4EAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAjY2NAo6OjhiOjo46jo6OVY2NjWeMjIxzi4uLdouLi3WMjIxyjIyMZYyMjFONjY06jY2NGY2NjQIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP///////////wAAAP///////////wAAAP////4Af////wAAAP///+AAB////wAAAP///+AAB////wAAAP///+AAA////wAAAP///8AAA////wAAAP//x8AAA+P//wAAAP//A8AAA8D//wAAAP/+AMAAA4B//wAAAP/8AEAAAgA//wAAAP/4AAAAAAAP/wAAAP/wAAAAAAAH/wAAAP/gAAAAAAAH/wAAAP/AAAAAAAAD/wAAAP+AAAAAAAAB/wAAAP8AAAAAAAAA/wAAAP4AAAAAAAAAfwAAAP4AAAAAAAAAfwAAAP4AAAAAAAAAfwAAAP4AAAAAAAAAfwAAAP8AAAAAAAAA/wAAAP+AAAAAAAAA/wAAAP+AAAAAAAAB/wAAAP/AAAAAAAAD/wAAAP/gAAAAAAAH/wAAAP4AAAD/AAAAfwAAAOAAAAH/wAAABwAAAOAAAAf/4AAABwAAAOAAAA//8AAABwAAAMAAAB//+AAAAwAAAMAAAB//+AAAAwAAAMAAAD///AAAAwAAAMAAAD///AAAAwAAAMAAAD///AAAAwAAAMAAAD///AAAAwAAAMAAAD///AAAAwAAAMAAAD///AAAAwAAAMAAAD///AAAAwAAAMAAAD///AAAAwAAAMAAAB//+AAAAwAAAMAAAB//+AAAAwAAAOAAAA//8AAABwAAAOAAAAf/4AAABwAAAOAAAAP/wAAABwAAAPgAAAD/AAAAHwAAAP/AAAAAAAAD/wAAAP/AAAAAAAAD/wAAAP+AAAAAAAAB/wAAAP+AAAAAAAAB/wAAAP8AAAAAAAAA/wAAAP4AAAAAAAAAfwAAAP4AAAAAAAAAfwAAAP4AAAAAAAAAfwAAAP4AAAAAAAAAfwAAAP8AAAAAAAAA/wAAAP+AAAAAAAAB/wAAAP+AAAAAAAAB/wAAAP/AAAAAAAAD/wAAAP/gAAAAAAAH/wAAAP/wAAAAAAAP/wAAAP/4AAAAAAAf/wAAAP/8AMAAAwA//wAAAP//AcAAA4D//wAAAP//h8AAA+H//wAAAP///8AAA////wAAAP///+AAA////wAAAP///+AAB////wAAAP///+AAB////wAAAP////gAH////wAAAP///////////wAA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AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/////////////////////////8AD////////AAD///////8AAP///////gAAf/////8+AAB8/////A4AAHA////4BgAAYB////ACAABAD///4AAAAAAH///AAAAAAAP//4AAAAAAAf//AAAAAAAA//4AAAAAAAB//gAAAAAAAH/8AAAAAAAAP/wAAAAAAAA//AAAAAAAAD/+AAAAAAAAf/8AAAAAAAD//4AAAAAAAf//gAAAAAAB//wAAAfgAAA/4AAAH/gAAAfgAAA//AAAA8AAAH/+AAADwAAA//8AAAPAAAD//wAAA8AAAf//gAADwAAB//+AAAPAAAH//4AAA8AAAf//gAADwAAB//+AAAPAAAH//4AAA8AAAP//AAADwAAA//8AAAPAAAB//gAAA8AAAD/8AAAD4AAAH/gAAAfwAAAH8AAAD/+AAAAAAAH//4AAAAAAAf//AAAAAAAA//4AAAAAAAB//gAAAAAAAH/8AAAAAAAAP/wAAAAAAAA//AAAAAAAAD/+AAAAAAAAf/8AAAAAAAD//4AAAAAAAf//wAAAAAAD///gAAAAAAP///AAAAAAD///+AYAAGAf///8DgAAcD////4eAAB4f/////4AAH///////wAAf///////AAD///////+AAf////////////////////////8oAAAAMAAAAGAAAAABACAAAAAAAIAlAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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" />

  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
  <style>
    /*!
 * Bootstrap Colorpicker - Bootstrap Colorpicker is a modular color picker plugin for Bootstrap 4.
 * @package bootstrap-colorpicker
 * @version v3.1.2
 * @license MIT
 * @link https://farbelous.github.io/bootstrap-colorpicker/
 * @link https://github.com/farbelous/bootstrap-colorpicker.git
 */
.colorpicker{position:relative;display:none;font-size:inherit;color:inherit;text-align:left;list-style:none;background-color:#fff;background-clip:padding-box;border:1px solid rgba(0,0,0,.2);padding:.75rem .75rem;width:148px;border-radius:4px;-webkit-box-sizing:content-box;box-sizing:content-box}.colorpicker.colorpicker-disabled,.colorpicker.colorpicker-disabled *{cursor:default!important}.colorpicker div{position:relative}.colorpicker-popup{position:absolute;top:100%;left:0;float:left;margin-top:1px;z-index:1060}.colorpicker-popup.colorpicker-bs-popover-content{position:relative;top:auto;left:auto;float:none;margin:0;z-index:initial;border:none;padding:.25rem 0;border-radius:0;background:0 0;-webkit-box-shadow:none;box-shadow:none}.colorpicker:after,.colorpicker:before{content:"";display:table;clear:both;line-height:0}.colorpicker-clear{clear:both;display:block}.colorpicker:before{content:'';display:inline-block;border-left:7px solid transparent;border-right:7px solid transparent;border-bottom:7px solid #ccc;border-bottom-color:rgba(0,0,0,.2);position:absolute;top:-7px;left:auto;right:6px}.colorpicker:after{content:'';display:inline-block;border-left:6px solid transparent;border-right:6px solid transparent;border-bottom:6px solid #fff;position:absolute;top:-6px;left:auto;right:7px}.colorpicker.colorpicker-with-alpha{width:170px}.colorpicker.colorpicker-with-alpha .colorpicker-alpha{display:block}.colorpicker-saturation{position:relative;width:126px;height:126px;background:-webkit-gradient(linear,left top,left bottom,from(transparent),to(black)),-webkit-gradient(linear,left top,right top,from(white),to(rgba(255,255,255,0)));background:linear-gradient(to bottom,transparent 0,#000 100%),linear-gradient(to right,#fff 0,rgba(255,255,255,0) 100%);cursor:crosshair;float:left;-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.2);box-shadow:0 0 0 1px rgba(0,0,0,.2);margin-bottom:6px}.colorpicker-saturation .colorpicker-guide{display:block;height:6px;width:6px;border-radius:6px;border:1px solid #000;-webkit-box-shadow:0 0 0 1px rgba(255,255,255,.8);box-shadow:0 0 0 1px rgba(255,255,255,.8);position:absolute;top:0;left:0;margin:-3px 0 0 -3px}.colorpicker-alpha,.colorpicker-hue{position:relative;width:16px;height:126px;float:left;cursor:row-resize;margin-left:6px;margin-bottom:6px}.colorpicker-alpha-color{position:absolute;top:0;left:0;width:100%;height:100%}.colorpicker-alpha-color,.colorpicker-hue{-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.2);box-shadow:0 0 0 1px rgba(0,0,0,.2)}.colorpicker-alpha .colorpicker-guide,.colorpicker-hue .colorpicker-guide{display:block;height:4px;background:rgba(255,255,255,.8);border:1px solid rgba(0,0,0,.4);position:absolute;top:0;left:0;margin-left:-2px;margin-top:-2px;right:-2px;z-index:1}.colorpicker-hue{background:-webkit-gradient(linear,left bottom,left top,from(red),color-stop(8%,#ff8000),color-stop(17%,#ff0),color-stop(25%,#80ff00),color-stop(33%,#0f0),color-stop(42%,#00ff80),color-stop(50%,#0ff),color-stop(58%,#0080ff),color-stop(67%,#00f),color-stop(75%,#8000ff),color-stop(83%,#ff00ff),color-stop(92%,#ff0080),to(red));background:linear-gradient(to top,red 0,#ff8000 8%,#ff0 17%,#80ff00 25%,#0f0 33%,#00ff80 42%,#0ff 50%,#0080ff 58%,#00f 67%,#8000ff 75%,#ff00ff 83%,#ff0080 92%,red 100%)}.colorpicker-alpha{background:linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),#fff;background-size:10px 10px;background-position:0 0,5px 5px;display:none}.colorpicker-bar{min-height:16px;margin:6px 0 0 0;clear:both;text-align:center;font-size:10px;line-height:normal;max-width:100%;-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.2);box-shadow:0 0 0 1px rgba(0,0,0,.2)}.colorpicker-bar:before{content:"";display:table;clear:both}.colorpicker-bar.colorpicker-bar-horizontal{height:126px;width:16px;margin:0 0 6px 0;float:left}.colorpicker-input-addon{position:relative}.colorpicker-input-addon i{display:inline-block;cursor:pointer;vertical-align:text-top;height:16px;width:16px;position:relative}.colorpicker-input-addon:before{content:"";position:absolute;width:16px;height:16px;display:inline-block;vertical-align:text-top;background:linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),#fff;background-size:10px 10px;background-position:0 0,5px 5px}.colorpicker.colorpicker-inline{position:relative;display:inline-block;float:none;z-index:auto;vertical-align:text-bottom}.colorpicker.colorpicker-horizontal{width:126px;height:auto}.colorpicker.colorpicker-horizontal .colorpicker-bar{width:126px}.colorpicker.colorpicker-horizontal .colorpicker-saturation{float:none;margin-bottom:0}.colorpicker.colorpicker-horizontal .colorpicker-alpha,.colorpicker.colorpicker-horizontal .colorpicker-hue{float:none;width:126px;height:16px;cursor:col-resize;margin-left:0;margin-top:6px;margin-bottom:0}.colorpicker.colorpicker-horizontal .colorpicker-alpha .colorpicker-guide,.colorpicker.colorpicker-horizontal .colorpicker-hue .colorpicker-guide{position:absolute;display:block;bottom:-2px;left:0;right:auto;height:auto;width:4px}.colorpicker.colorpicker-horizontal .colorpicker-hue{background:-webkit-gradient(linear,right top,left top,from(red),color-stop(8%,#ff8000),color-stop(17%,#ff0),color-stop(25%,#80ff00),color-stop(33%,#0f0),color-stop(42%,#00ff80),color-stop(50%,#0ff),color-stop(58%,#0080ff),color-stop(67%,#00f),color-stop(75%,#8000ff),color-stop(83%,#ff00ff),color-stop(92%,#ff0080),to(red));background:linear-gradient(to left,red 0,#ff8000 8%,#ff0 17%,#80ff00 25%,#0f0 33%,#00ff80 42%,#0ff 50%,#0080ff 58%,#00f 67%,#8000ff 75%,#ff00ff 83%,#ff0080 92%,red 100%)}.colorpicker.colorpicker-horizontal .colorpicker-alpha{background:linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),#fff;background-size:10px 10px;background-position:0 0,5px 5px}.colorpicker-inline:before,.colorpicker-no-arrow:before,.colorpicker-popup.colorpicker-bs-popover-content:before{content:none;display:none}.colorpicker-inline:after,.colorpicker-no-arrow:after,.colorpicker-popup.colorpicker-bs-popover-content:after{content:none;display:none}.colorpicker-alpha,.colorpicker-hue,.colorpicker-saturation{-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}.colorpicker-alpha.colorpicker-visible,.colorpicker-bar.colorpicker-visible,.colorpicker-hue.colorpicker-visible,.colorpicker-saturation.colorpicker-visible,.colorpicker.colorpicker-visible{display:block}.colorpicker-alpha.colorpicker-hidden,.colorpicker-bar.colorpicker-hidden,.colorpicker-hue.colorpicker-hidden,.colorpicker-saturation.colorpicker-hidden,.colorpicker.colorpicker-hidden{display:none}.colorpicker-inline.colorpicker-visible{display:inline-block}.colorpicker.colorpicker-disabled:after{border:none;content:'';display:block;width:100%;height:100%;background:rgba(233,236,239,.33);top:0;left:0;right:auto;z-index:2;position:absolute}.colorpicker.colorpicker-disabled .colorpicker-guide{display:none}.colorpicker-preview{background:linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),#fff;background-size:10px 10px;background-position:0 0,5px 5px}.colorpicker-preview>div{position:absolute;left:0;top:0;width:100%;height:100%}.colorpicker-bar.colorpicker-swatches{-webkit-box-shadow:none;box-shadow:none;height:auto}.colorpicker-swatches--inner{clear:both;margin-top:-6px}.colorpicker-swatch{position:relative;cursor:pointer;float:left;height:16px;width:16px;margin-right:6px;margin-top:6px;margin-left:0;display:block;-webkit-box-shadow:0 0 0 1px rgba(0,0,0,.2);box-shadow:0 0 0 1px rgba(0,0,0,.2);background:linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%,rgba(0,0,0,.1) 0),#fff;background-size:10px 10px;background-position:0 0,5px 5px}.colorpicker-swatch--inner{position:absolute;top:0;left:0;width:100%;height:100%}.colorpicker-swatch:nth-of-type(7n+0){margin-right:0}.colorpicker-with-alpha .colorpicker-swatch:nth-of-type(7n+0){margin-right:6px}.colorpicker-with-alpha .colorpicker-swatch:nth-of-type(8n+0){margin-right:0}.colorpicker-horizontal .colorpicker-swatch:nth-of-type(6n+0){margin-right:0}.colorpicker-horizontal .colorpicker-swatch:nth-of-type(7n+0){margin-right:6px}.colorpicker-horizontal .colorpicker-swatch:nth-of-type(8n+0){margin-right:6px}.colorpicker-swatch:last-of-type:after{content:"";display:table;clear:both}.colorpicker-element input[dir=rtl],.colorpicker-element[dir=rtl] input,[dir=rtl] .colorpicker-element input{direction:ltr;text-align:right}
/*# sourceMappingURL=bootstrap-colorpicker.min.css.map */


    #message_loginfailed { display: none; }
    .navbar-nav-center { margin: auto; }
    .navbar-nav-center .button_single { margin-left: 5px; margin-right: 5px; margin-top: 2px; }

    .label_text { margin-top: 20px; font-weight: bold; }
    .button_part {margin-top: 10px; margin-bottom: 20px; margin-right: 10px; font-size: 120%; }

    .button_header {font-size: 120%; }

    #button_file_upload {cursor:pointer;}
  </style>
  <script type="text/javascript">
    /*! jQuery v3.4.1 | (c) JS Foundation and other contributors | jquery.org/license */
!function(e,t){"use strict";"object"==typeof module&&"object"==typeof module.exports?module.exports=e.document?t(e,!0):function(e){if(!e.document)throw new Error("jQuery requires a window with a document");return t(e)}:t(e)}("undefined"!=typeof window?window:this,function(C,e){"use strict";var t=[],E=C.document,r=Object.getPrototypeOf,s=t.slice,g=t.concat,u=t.push,i=t.indexOf,n={},o=n.toString,v=n.hasOwnProperty,a=v.toString,l=a.call(Object),y={},m=function(e){return"function"==typeof e&&"number"!=typeof e.nodeType},x=function(e){return null!=e&&e===e.window},c={type:!0,src:!0,nonce:!0,noModule:!0};function b(e,t,n){var r,i,o=(n=n||E).createElement("script");if(o.text=e,t)for(r in c)(i=t[r]||t.getAttribute&&t.getAttribute(r))&&o.setAttribute(r,i);n.head.appendChild(o).parentNode.removeChild(o)}function w(e){return null==e?e+"":"object"==typeof e||"function"==typeof e?n[o.call(e)]||"object":typeof e}var f="3.4.1",k=function(e,t){return new k.fn.init(e,t)},p=/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;function d(e){var t=!!e&&"length"in e&&e.length,n=w(e);return!m(e)&&!x(e)&&("array"===n||0===t||"number"==typeof t&&0<t&&t-1 in e)}k.fn=k.prototype={jquery:f,constructor:k,length:0,toArray:function(){return s.call(this)},get:function(e){return null==e?s.call(this):e<0?this[e+this.length]:this[e]},pushStack:function(e){var t=k.merge(this.constructor(),e);return t.prevObject=this,t},each:function(e){return k.each(this,e)},map:function(n){return this.pushStack(k.map(this,function(e,t){return n.call(e,t,e)}))},slice:function(){return this.pushStack(s.apply(this,arguments))},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},eq:function(e){var t=this.length,n=+e+(e<0?t:0);return this.pushStack(0<=n&&n<t?[this[n]]:[])},end:function(){return this.prevObject||this.constructor()},push:u,sort:t.sort,splice:t.splice},k.extend=k.fn.extend=function(){var e,t,n,r,i,o,a=arguments[0]||{},s=1,u=arguments.length,l=!1;for("boolean"==typeof a&&(l=a,a=arguments[s]||{},s++),"object"==typeof a||m(a)||(a={}),s===u&&(a=this,s--);s<u;s++)if(null!=(e=arguments[s]))for(t in e)r=e[t],"__proto__"!==t&&a!==r&&(l&&r&&(k.isPlainObject(r)||(i=Array.isArray(r)))?(n=a[t],o=i&&!Array.isArray(n)?[]:i||k.isPlainObject(n)?n:{},i=!1,a[t]=k.extend(l,o,r)):void 0!==r&&(a[t]=r));return a},k.extend({expando:"jQuery"+(f+Math.random()).replace(/\D/g,""),isReady:!0,error:function(e){throw new Error(e)},noop:function(){},isPlainObject:function(e){var t,n;return!(!e||"[object Object]"!==o.call(e))&&(!(t=r(e))||"function"==typeof(n=v.call(t,"constructor")&&t.constructor)&&a.call(n)===l)},isEmptyObject:function(e){var t;for(t in e)return!1;return!0},globalEval:function(e,t){b(e,{nonce:t&&t.nonce})},each:function(e,t){var n,r=0;if(d(e)){for(n=e.length;r<n;r++)if(!1===t.call(e[r],r,e[r]))break}else for(r in e)if(!1===t.call(e[r],r,e[r]))break;return e},trim:function(e){return null==e?"":(e+"").replace(p,"")},makeArray:function(e,t){var n=t||[];return null!=e&&(d(Object(e))?k.merge(n,"string"==typeof e?[e]:e):u.call(n,e)),n},inArray:function(e,t,n){return null==t?-1:i.call(t,e,n)},merge:function(e,t){for(var n=+t.length,r=0,i=e.length;r<n;r++)e[i++]=t[r];return e.length=i,e},grep:function(e,t,n){for(var r=[],i=0,o=e.length,a=!n;i<o;i++)!t(e[i],i)!==a&&r.push(e[i]);return r},map:function(e,t,n){var r,i,o=0,a=[];if(d(e))for(r=e.length;o<r;o++)null!=(i=t(e[o],o,n))&&a.push(i);else for(o in e)null!=(i=t(e[o],o,n))&&a.push(i);return g.apply([],a)},guid:1,support:y}),"function"==typeof Symbol&&(k.fn[Symbol.iterator]=t[Symbol.iterator]),k.each("Boolean Number String Function Array Date RegExp Object Error Symbol".split(" "),function(e,t){n["[object "+t+"]"]=t.toLowerCase()});var h=function(n){var e,d,b,o,i,h,f,g,w,u,l,T,C,a,E,v,s,c,y,k="sizzle"+1*new Date,m=n.document,S=0,r=0,p=ue(),x=ue(),N=ue(),A=ue(),D=function(e,t){return e===t&&(l=!0),0},j={}.hasOwnProperty,t=[],q=t.pop,L=t.push,H=t.push,O=t.slice,P=function(e,t){for(var n=0,r=e.length;n<r;n++)if(e[n]===t)return n;return-1},R="checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped",M="[\\x20\\t\\r\\n\\f]",I="(?:\\\\.|[\\w-]|[^\0-\\xa0])+",W="\\["+M+"*("+I+")(?:"+M+"*([*^$|!~]?=)"+M+"*(?:'((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\"|("+I+"))|)"+M+"*\\]",$=":("+I+")(?:\\((('((?:\\\\.|[^\\\\'])*)'|\"((?:\\\\.|[^\\\\\"])*)\")|((?:\\\\.|[^\\\\()[\\]]|"+W+")*)|.*)\\)|)",F=new RegExp(M+"+","g"),B=new RegExp("^"+M+"+|((?:^|[^\\\\])(?:\\\\.)*)"+M+"+$","g"),_=new RegExp("^"+M+"*,"+M+"*"),z=new RegExp("^"+M+"*([>+~]|"+M+")"+M+"*"),U=new RegExp(M+"|>"),X=new RegExp($),V=new RegExp("^"+I+"$"),G={ID:new RegExp("^#("+I+")"),CLASS:new RegExp("^\\.("+I+")"),TAG:new RegExp("^("+I+"|[*])"),ATTR:new RegExp("^"+W),PSEUDO:new RegExp("^"+$),CHILD:new RegExp("^:(only|first|last|nth|nth-last)-(child|of-type)(?:\\("+M+"*(even|odd|(([+-]|)(\\d*)n|)"+M+"*(?:([+-]|)"+M+"*(\\d+)|))"+M+"*\\)|)","i"),bool:new RegExp("^(?:"+R+")$","i"),needsContext:new RegExp("^"+M+"*[>+~]|:(even|odd|eq|gt|lt|nth|first|last)(?:\\("+M+"*((?:-\\d)?\\d*)"+M+"*\\)|)(?=[^-]|$)","i")},Y=/HTML$/i,Q=/^(?:input|select|textarea|button)$/i,J=/^h\d$/i,K=/^[^{]+\{\s*\[native \w/,Z=/^(?:#([\w-]+)|(\w+)|\.([\w-]+))$/,ee=/[+~]/,te=new RegExp("\\\\([\\da-f]{1,6}"+M+"?|("+M+")|.)","ig"),ne=function(e,t,n){var r="0x"+t-65536;return r!=r||n?t:r<0?String.fromCharCode(r+65536):String.fromCharCode(r>>10|55296,1023&r|56320)},re=/([\0-\x1f\x7f]|^-?\d)|^-$|[^\0-\x1f\x7f-\uFFFF\w-]/g,ie=function(e,t){return t?"\0"===e?"\ufffd":e.slice(0,-1)+"\\"+e.charCodeAt(e.length-1).toString(16)+" ":"\\"+e},oe=function(){T()},ae=be(function(e){return!0===e.disabled&&"fieldset"===e.nodeName.toLowerCase()},{dir:"parentNode",next:"legend"});try{H.apply(t=O.call(m.childNodes),m.childNodes),t[m.childNodes.length].nodeType}catch(e){H={apply:t.length?function(e,t){L.apply(e,O.call(t))}:function(e,t){var n=e.length,r=0;while(e[n++]=t[r++]);e.length=n-1}}}function se(t,e,n,r){var i,o,a,s,u,l,c,f=e&&e.ownerDocument,p=e?e.nodeType:9;if(n=n||[],"string"!=typeof t||!t||1!==p&&9!==p&&11!==p)return n;if(!r&&((e?e.ownerDocument||e:m)!==C&&T(e),e=e||C,E)){if(11!==p&&(u=Z.exec(t)))if(i=u[1]){if(9===p){if(!(a=e.getElementById(i)))return n;if(a.id===i)return n.push(a),n}else if(f&&(a=f.getElementById(i))&&y(e,a)&&a.id===i)return n.push(a),n}else{if(u[2])return H.apply(n,e.getElementsByTagName(t)),n;if((i=u[3])&&d.getElementsByClassName&&e.getElementsByClassName)return H.apply(n,e.getElementsByClassName(i)),n}if(d.qsa&&!A[t+" "]&&(!v||!v.test(t))&&(1!==p||"object"!==e.nodeName.toLowerCase())){if(c=t,f=e,1===p&&U.test(t)){(s=e.getAttribute("id"))?s=s.replace(re,ie):e.setAttribute("id",s=k),o=(l=h(t)).length;while(o--)l[o]="#"+s+" "+xe(l[o]);c=l.join(","),f=ee.test(t)&&ye(e.parentNode)||e}try{return H.apply(n,f.querySelectorAll(c)),n}catch(e){A(t,!0)}finally{s===k&&e.removeAttribute("id")}}}return g(t.replace(B,"$1"),e,n,r)}function ue(){var r=[];return function e(t,n){return r.push(t+" ")>b.cacheLength&&delete e[r.shift()],e[t+" "]=n}}function le(e){return e[k]=!0,e}function ce(e){var t=C.createElement("fieldset");try{return!!e(t)}catch(e){return!1}finally{t.parentNode&&t.parentNode.removeChild(t),t=null}}function fe(e,t){var n=e.split("|"),r=n.length;while(r--)b.attrHandle[n[r]]=t}function pe(e,t){var n=t&&e,r=n&&1===e.nodeType&&1===t.nodeType&&e.sourceIndex-t.sourceIndex;if(r)return r;if(n)while(n=n.nextSibling)if(n===t)return-1;return e?1:-1}function de(t){return function(e){return"input"===e.nodeName.toLowerCase()&&e.type===t}}function he(n){return function(e){var t=e.nodeName.toLowerCase();return("input"===t||"button"===t)&&e.type===n}}function ge(t){return function(e){return"form"in e?e.parentNode&&!1===e.disabled?"label"in e?"label"in e.parentNode?e.parentNode.disabled===t:e.disabled===t:e.isDisabled===t||e.isDisabled!==!t&&ae(e)===t:e.disabled===t:"label"in e&&e.disabled===t}}function ve(a){return le(function(o){return o=+o,le(function(e,t){var n,r=a([],e.length,o),i=r.length;while(i--)e[n=r[i]]&&(e[n]=!(t[n]=e[n]))})})}function ye(e){return e&&"undefined"!=typeof e.getElementsByTagName&&e}for(e in d=se.support={},i=se.isXML=function(e){var t=e.namespaceURI,n=(e.ownerDocument||e).documentElement;return!Y.test(t||n&&n.nodeName||"HTML")},T=se.setDocument=function(e){var t,n,r=e?e.ownerDocument||e:m;return r!==C&&9===r.nodeType&&r.documentElement&&(a=(C=r).documentElement,E=!i(C),m!==C&&(n=C.defaultView)&&n.top!==n&&(n.addEventListener?n.addEventListener("unload",oe,!1):n.attachEvent&&n.attachEvent("onunload",oe)),d.attributes=ce(function(e){return e.className="i",!e.getAttribute("className")}),d.getElementsByTagName=ce(function(e){return e.appendChild(C.createComment("")),!e.getElementsByTagName("*").length}),d.getElementsByClassName=K.test(C.getElementsByClassName),d.getById=ce(function(e){return a.appendChild(e).id=k,!C.getElementsByName||!C.getElementsByName(k).length}),d.getById?(b.filter.ID=function(e){var t=e.replace(te,ne);return function(e){return e.getAttribute("id")===t}},b.find.ID=function(e,t){if("undefined"!=typeof t.getElementById&&E){var n=t.getElementById(e);return n?[n]:[]}}):(b.filter.ID=function(e){var n=e.replace(te,ne);return function(e){var t="undefined"!=typeof e.getAttributeNode&&e.getAttributeNode("id");return t&&t.value===n}},b.find.ID=function(e,t){if("undefined"!=typeof t.getElementById&&E){var n,r,i,o=t.getElementById(e);if(o){if((n=o.getAttributeNode("id"))&&n.value===e)return[o];i=t.getElementsByName(e),r=0;while(o=i[r++])if((n=o.getAttributeNode("id"))&&n.value===e)return[o]}return[]}}),b.find.TAG=d.getElementsByTagName?function(e,t){return"undefined"!=typeof t.getElementsByTagName?t.getElementsByTagName(e):d.qsa?t.querySelectorAll(e):void 0}:function(e,t){var n,r=[],i=0,o=t.getElementsByTagName(e);if("*"===e){while(n=o[i++])1===n.nodeType&&r.push(n);return r}return o},b.find.CLASS=d.getElementsByClassName&&function(e,t){if("undefined"!=typeof t.getElementsByClassName&&E)return t.getElementsByClassName(e)},s=[],v=[],(d.qsa=K.test(C.querySelectorAll))&&(ce(function(e){a.appendChild(e).innerHTML="<a id='"+k+"'></a><select id='"+k+"-\r\\' msallowcapture=''><option selected=''></option></select>",e.querySelectorAll("[msallowcapture^='']").length&&v.push("[*^$]="+M+"*(?:''|\"\")"),e.querySelectorAll("[selected]").length||v.push("\\["+M+"*(?:value|"+R+")"),e.querySelectorAll("[id~="+k+"-]").length||v.push("~="),e.querySelectorAll(":checked").length||v.push(":checked"),e.querySelectorAll("a#"+k+"+*").length||v.push(".#.+[+~]")}),ce(function(e){e.innerHTML="<a href='' disabled='disabled'></a><select disabled='disabled'><option/></select>";var t=C.createElement("input");t.setAttribute("type","hidden"),e.appendChild(t).setAttribute("name","D"),e.querySelectorAll("[name=d]").length&&v.push("name"+M+"*[*^$|!~]?="),2!==e.querySelectorAll(":enabled").length&&v.push(":enabled",":disabled"),a.appendChild(e).disabled=!0,2!==e.querySelectorAll(":disabled").length&&v.push(":enabled",":disabled"),e.querySelectorAll("*,:x"),v.push(",.*:")})),(d.matchesSelector=K.test(c=a.matches||a.webkitMatchesSelector||a.mozMatchesSelector||a.oMatchesSelector||a.msMatchesSelector))&&ce(function(e){d.disconnectedMatch=c.call(e,"*"),c.call(e,"[s!='']:x"),s.push("!=",$)}),v=v.length&&new RegExp(v.join("|")),s=s.length&&new RegExp(s.join("|")),t=K.test(a.compareDocumentPosition),y=t||K.test(a.contains)?function(e,t){var n=9===e.nodeType?e.documentElement:e,r=t&&t.parentNode;return e===r||!(!r||1!==r.nodeType||!(n.contains?n.contains(r):e.compareDocumentPosition&&16&e.compareDocumentPosition(r)))}:function(e,t){if(t)while(t=t.parentNode)if(t===e)return!0;return!1},D=t?function(e,t){if(e===t)return l=!0,0;var n=!e.compareDocumentPosition-!t.compareDocumentPosition;return n||(1&(n=(e.ownerDocument||e)===(t.ownerDocument||t)?e.compareDocumentPosition(t):1)||!d.sortDetached&&t.compareDocumentPosition(e)===n?e===C||e.ownerDocument===m&&y(m,e)?-1:t===C||t.ownerDocument===m&&y(m,t)?1:u?P(u,e)-P(u,t):0:4&n?-1:1)}:function(e,t){if(e===t)return l=!0,0;var n,r=0,i=e.parentNode,o=t.parentNode,a=[e],s=[t];if(!i||!o)return e===C?-1:t===C?1:i?-1:o?1:u?P(u,e)-P(u,t):0;if(i===o)return pe(e,t);n=e;while(n=n.parentNode)a.unshift(n);n=t;while(n=n.parentNode)s.unshift(n);while(a[r]===s[r])r++;return r?pe(a[r],s[r]):a[r]===m?-1:s[r]===m?1:0}),C},se.matches=function(e,t){return se(e,null,null,t)},se.matchesSelector=function(e,t){if((e.ownerDocument||e)!==C&&T(e),d.matchesSelector&&E&&!A[t+" "]&&(!s||!s.test(t))&&(!v||!v.test(t)))try{var n=c.call(e,t);if(n||d.disconnectedMatch||e.document&&11!==e.document.nodeType)return n}catch(e){A(t,!0)}return 0<se(t,C,null,[e]).length},se.contains=function(e,t){return(e.ownerDocument||e)!==C&&T(e),y(e,t)},se.attr=function(e,t){(e.ownerDocument||e)!==C&&T(e);var n=b.attrHandle[t.toLowerCase()],r=n&&j.call(b.attrHandle,t.toLowerCase())?n(e,t,!E):void 0;return void 0!==r?r:d.attributes||!E?e.getAttribute(t):(r=e.getAttributeNode(t))&&r.specified?r.value:null},se.escape=function(e){return(e+"").replace(re,ie)},se.error=function(e){throw new Error("Syntax error, unrecognized expression: "+e)},se.uniqueSort=function(e){var t,n=[],r=0,i=0;if(l=!d.detectDuplicates,u=!d.sortStable&&e.slice(0),e.sort(D),l){while(t=e[i++])t===e[i]&&(r=n.push(i));while(r--)e.splice(n[r],1)}return u=null,e},o=se.getText=function(e){var t,n="",r=0,i=e.nodeType;if(i){if(1===i||9===i||11===i){if("string"==typeof e.textContent)return e.textContent;for(e=e.firstChild;e;e=e.nextSibling)n+=o(e)}else if(3===i||4===i)return e.nodeValue}else while(t=e[r++])n+=o(t);return n},(b=se.selectors={cacheLength:50,createPseudo:le,match:G,attrHandle:{},find:{},relative:{">":{dir:"parentNode",first:!0}," ":{dir:"parentNode"},"+":{dir:"previousSibling",first:!0},"~":{dir:"previousSibling"}},preFilter:{ATTR:function(e){return e[1]=e[1].replace(te,ne),e[3]=(e[3]||e[4]||e[5]||"").replace(te,ne),"~="===e[2]&&(e[3]=" "+e[3]+" "),e.slice(0,4)},CHILD:function(e){return e[1]=e[1].toLowerCase(),"nth"===e[1].slice(0,3)?(e[3]||se.error(e[0]),e[4]=+(e[4]?e[5]+(e[6]||1):2*("even"===e[3]||"odd"===e[3])),e[5]=+(e[7]+e[8]||"odd"===e[3])):e[3]&&se.error(e[0]),e},PSEUDO:function(e){var t,n=!e[6]&&e[2];return G.CHILD.test(e[0])?null:(e[3]?e[2]=e[4]||e[5]||"":n&&X.test(n)&&(t=h(n,!0))&&(t=n.indexOf(")",n.length-t)-n.length)&&(e[0]=e[0].slice(0,t),e[2]=n.slice(0,t)),e.slice(0,3))}},filter:{TAG:function(e){var t=e.replace(te,ne).toLowerCase();return"*"===e?function(){return!0}:function(e){return e.nodeName&&e.nodeName.toLowerCase()===t}},CLASS:function(e){var t=p[e+" "];return t||(t=new RegExp("(^|"+M+")"+e+"("+M+"|$)"))&&p(e,function(e){return t.test("string"==typeof e.className&&e.className||"undefined"!=typeof e.getAttribute&&e.getAttribute("class")||"")})},ATTR:function(n,r,i){return function(e){var t=se.attr(e,n);return null==t?"!="===r:!r||(t+="","="===r?t===i:"!="===r?t!==i:"^="===r?i&&0===t.indexOf(i):"*="===r?i&&-1<t.indexOf(i):"$="===r?i&&t.slice(-i.length)===i:"~="===r?-1<(" "+t.replace(F," ")+" ").indexOf(i):"|="===r&&(t===i||t.slice(0,i.length+1)===i+"-"))}},CHILD:function(h,e,t,g,v){var y="nth"!==h.slice(0,3),m="last"!==h.slice(-4),x="of-type"===e;return 1===g&&0===v?function(e){return!!e.parentNode}:function(e,t,n){var r,i,o,a,s,u,l=y!==m?"nextSibling":"previousSibling",c=e.parentNode,f=x&&e.nodeName.toLowerCase(),p=!n&&!x,d=!1;if(c){if(y){while(l){a=e;while(a=a[l])if(x?a.nodeName.toLowerCase()===f:1===a.nodeType)return!1;u=l="only"===h&&!u&&"nextSibling"}return!0}if(u=[m?c.firstChild:c.lastChild],m&&p){d=(s=(r=(i=(o=(a=c)[k]||(a[k]={}))[a.uniqueID]||(o[a.uniqueID]={}))[h]||[])[0]===S&&r[1])&&r[2],a=s&&c.childNodes[s];while(a=++s&&a&&a[l]||(d=s=0)||u.pop())if(1===a.nodeType&&++d&&a===e){i[h]=[S,s,d];break}}else if(p&&(d=s=(r=(i=(o=(a=e)[k]||(a[k]={}))[a.uniqueID]||(o[a.uniqueID]={}))[h]||[])[0]===S&&r[1]),!1===d)while(a=++s&&a&&a[l]||(d=s=0)||u.pop())if((x?a.nodeName.toLowerCase()===f:1===a.nodeType)&&++d&&(p&&((i=(o=a[k]||(a[k]={}))[a.uniqueID]||(o[a.uniqueID]={}))[h]=[S,d]),a===e))break;return(d-=v)===g||d%g==0&&0<=d/g}}},PSEUDO:function(e,o){var t,a=b.pseudos[e]||b.setFilters[e.toLowerCase()]||se.error("unsupported pseudo: "+e);return a[k]?a(o):1<a.length?(t=[e,e,"",o],b.setFilters.hasOwnProperty(e.toLowerCase())?le(function(e,t){var n,r=a(e,o),i=r.length;while(i--)e[n=P(e,r[i])]=!(t[n]=r[i])}):function(e){return a(e,0,t)}):a}},pseudos:{not:le(function(e){var r=[],i=[],s=f(e.replace(B,"$1"));return s[k]?le(function(e,t,n,r){var i,o=s(e,null,r,[]),a=e.length;while(a--)(i=o[a])&&(e[a]=!(t[a]=i))}):function(e,t,n){return r[0]=e,s(r,null,n,i),r[0]=null,!i.pop()}}),has:le(function(t){return function(e){return 0<se(t,e).length}}),contains:le(function(t){return t=t.replace(te,ne),function(e){return-1<(e.textContent||o(e)).indexOf(t)}}),lang:le(function(n){return V.test(n||"")||se.error("unsupported lang: "+n),n=n.replace(te,ne).toLowerCase(),function(e){var t;do{if(t=E?e.lang:e.getAttribute("xml:lang")||e.getAttribute("lang"))return(t=t.toLowerCase())===n||0===t.indexOf(n+"-")}while((e=e.parentNode)&&1===e.nodeType);return!1}}),target:function(e){var t=n.location&&n.location.hash;return t&&t.slice(1)===e.id},root:function(e){return e===a},focus:function(e){return e===C.activeElement&&(!C.hasFocus||C.hasFocus())&&!!(e.type||e.href||~e.tabIndex)},enabled:ge(!1),disabled:ge(!0),checked:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&!!e.checked||"option"===t&&!!e.selected},selected:function(e){return e.parentNode&&e.parentNode.selectedIndex,!0===e.selected},empty:function(e){for(e=e.firstChild;e;e=e.nextSibling)if(e.nodeType<6)return!1;return!0},parent:function(e){return!b.pseudos.empty(e)},header:function(e){return J.test(e.nodeName)},input:function(e){return Q.test(e.nodeName)},button:function(e){var t=e.nodeName.toLowerCase();return"input"===t&&"button"===e.type||"button"===t},text:function(e){var t;return"input"===e.nodeName.toLowerCase()&&"text"===e.type&&(null==(t=e.getAttribute("type"))||"text"===t.toLowerCase())},first:ve(function(){return[0]}),last:ve(function(e,t){return[t-1]}),eq:ve(function(e,t,n){return[n<0?n+t:n]}),even:ve(function(e,t){for(var n=0;n<t;n+=2)e.push(n);return e}),odd:ve(function(e,t){for(var n=1;n<t;n+=2)e.push(n);return e}),lt:ve(function(e,t,n){for(var r=n<0?n+t:t<n?t:n;0<=--r;)e.push(r);return e}),gt:ve(function(e,t,n){for(var r=n<0?n+t:n;++r<t;)e.push(r);return e})}}).pseudos.nth=b.pseudos.eq,{radio:!0,checkbox:!0,file:!0,password:!0,image:!0})b.pseudos[e]=de(e);for(e in{submit:!0,reset:!0})b.pseudos[e]=he(e);function me(){}function xe(e){for(var t=0,n=e.length,r="";t<n;t++)r+=e[t].value;return r}function be(s,e,t){var u=e.dir,l=e.next,c=l||u,f=t&&"parentNode"===c,p=r++;return e.first?function(e,t,n){while(e=e[u])if(1===e.nodeType||f)return s(e,t,n);return!1}:function(e,t,n){var r,i,o,a=[S,p];if(n){while(e=e[u])if((1===e.nodeType||f)&&s(e,t,n))return!0}else while(e=e[u])if(1===e.nodeType||f)if(i=(o=e[k]||(e[k]={}))[e.uniqueID]||(o[e.uniqueID]={}),l&&l===e.nodeName.toLowerCase())e=e[u]||e;else{if((r=i[c])&&r[0]===S&&r[1]===p)return a[2]=r[2];if((i[c]=a)[2]=s(e,t,n))return!0}return!1}}function we(i){return 1<i.length?function(e,t,n){var r=i.length;while(r--)if(!i[r](e,t,n))return!1;return!0}:i[0]}function Te(e,t,n,r,i){for(var o,a=[],s=0,u=e.length,l=null!=t;s<u;s++)(o=e[s])&&(n&&!n(o,r,i)||(a.push(o),l&&t.push(s)));return a}function Ce(d,h,g,v,y,e){return v&&!v[k]&&(v=Ce(v)),y&&!y[k]&&(y=Ce(y,e)),le(function(e,t,n,r){var i,o,a,s=[],u=[],l=t.length,c=e||function(e,t,n){for(var r=0,i=t.length;r<i;r++)se(e,t[r],n);return n}(h||"*",n.nodeType?[n]:n,[]),f=!d||!e&&h?c:Te(c,s,d,n,r),p=g?y||(e?d:l||v)?[]:t:f;if(g&&g(f,p,n,r),v){i=Te(p,u),v(i,[],n,r),o=i.length;while(o--)(a=i[o])&&(p[u[o]]=!(f[u[o]]=a))}if(e){if(y||d){if(y){i=[],o=p.length;while(o--)(a=p[o])&&i.push(f[o]=a);y(null,p=[],i,r)}o=p.length;while(o--)(a=p[o])&&-1<(i=y?P(e,a):s[o])&&(e[i]=!(t[i]=a))}}else p=Te(p===t?p.splice(l,p.length):p),y?y(null,t,p,r):H.apply(t,p)})}function Ee(e){for(var i,t,n,r=e.length,o=b.relative[e[0].type],a=o||b.relative[" "],s=o?1:0,u=be(function(e){return e===i},a,!0),l=be(function(e){return-1<P(i,e)},a,!0),c=[function(e,t,n){var r=!o&&(n||t!==w)||((i=t).nodeType?u(e,t,n):l(e,t,n));return i=null,r}];s<r;s++)if(t=b.relative[e[s].type])c=[be(we(c),t)];else{if((t=b.filter[e[s].type].apply(null,e[s].matches))[k]){for(n=++s;n<r;n++)if(b.relative[e[n].type])break;return Ce(1<s&&we(c),1<s&&xe(e.slice(0,s-1).concat({value:" "===e[s-2].type?"*":""})).replace(B,"$1"),t,s<n&&Ee(e.slice(s,n)),n<r&&Ee(e=e.slice(n)),n<r&&xe(e))}c.push(t)}return we(c)}return me.prototype=b.filters=b.pseudos,b.setFilters=new me,h=se.tokenize=function(e,t){var n,r,i,o,a,s,u,l=x[e+" "];if(l)return t?0:l.slice(0);a=e,s=[],u=b.preFilter;while(a){for(o in n&&!(r=_.exec(a))||(r&&(a=a.slice(r[0].length)||a),s.push(i=[])),n=!1,(r=z.exec(a))&&(n=r.shift(),i.push({value:n,type:r[0].replace(B," ")}),a=a.slice(n.length)),b.filter)!(r=G[o].exec(a))||u[o]&&!(r=u[o](r))||(n=r.shift(),i.push({value:n,type:o,matches:r}),a=a.slice(n.length));if(!n)break}return t?a.length:a?se.error(e):x(e,s).slice(0)},f=se.compile=function(e,t){var n,v,y,m,x,r,i=[],o=[],a=N[e+" "];if(!a){t||(t=h(e)),n=t.length;while(n--)(a=Ee(t[n]))[k]?i.push(a):o.push(a);(a=N(e,(v=o,m=0<(y=i).length,x=0<v.length,r=function(e,t,n,r,i){var o,a,s,u=0,l="0",c=e&&[],f=[],p=w,d=e||x&&b.find.TAG("*",i),h=S+=null==p?1:Math.random()||.1,g=d.length;for(i&&(w=t===C||t||i);l!==g&&null!=(o=d[l]);l++){if(x&&o){a=0,t||o.ownerDocument===C||(T(o),n=!E);while(s=v[a++])if(s(o,t||C,n)){r.push(o);break}i&&(S=h)}m&&((o=!s&&o)&&u--,e&&c.push(o))}if(u+=l,m&&l!==u){a=0;while(s=y[a++])s(c,f,t,n);if(e){if(0<u)while(l--)c[l]||f[l]||(f[l]=q.call(r));f=Te(f)}H.apply(r,f),i&&!e&&0<f.length&&1<u+y.length&&se.uniqueSort(r)}return i&&(S=h,w=p),c},m?le(r):r))).selector=e}return a},g=se.select=function(e,t,n,r){var i,o,a,s,u,l="function"==typeof e&&e,c=!r&&h(e=l.selector||e);if(n=n||[],1===c.length){if(2<(o=c[0]=c[0].slice(0)).length&&"ID"===(a=o[0]).type&&9===t.nodeType&&E&&b.relative[o[1].type]){if(!(t=(b.find.ID(a.matches[0].replace(te,ne),t)||[])[0]))return n;l&&(t=t.parentNode),e=e.slice(o.shift().value.length)}i=G.needsContext.test(e)?0:o.length;while(i--){if(a=o[i],b.relative[s=a.type])break;if((u=b.find[s])&&(r=u(a.matches[0].replace(te,ne),ee.test(o[0].type)&&ye(t.parentNode)||t))){if(o.splice(i,1),!(e=r.length&&xe(o)))return H.apply(n,r),n;break}}}return(l||f(e,c))(r,t,!E,n,!t||ee.test(e)&&ye(t.parentNode)||t),n},d.sortStable=k.split("").sort(D).join("")===k,d.detectDuplicates=!!l,T(),d.sortDetached=ce(function(e){return 1&e.compareDocumentPosition(C.createElement("fieldset"))}),ce(function(e){return e.innerHTML="<a href='#'></a>","#"===e.firstChild.getAttribute("href")})||fe("type|href|height|width",function(e,t,n){if(!n)return e.getAttribute(t,"type"===t.toLowerCase()?1:2)}),d.attributes&&ce(function(e){return e.innerHTML="<input/>",e.firstChild.setAttribute("value",""),""===e.firstChild.getAttribute("value")})||fe("value",function(e,t,n){if(!n&&"input"===e.nodeName.toLowerCase())return e.defaultValue}),ce(function(e){return null==e.getAttribute("disabled")})||fe(R,function(e,t,n){var r;if(!n)return!0===e[t]?t.toLowerCase():(r=e.getAttributeNode(t))&&r.specified?r.value:null}),se}(C);k.find=h,k.expr=h.selectors,k.expr[":"]=k.expr.pseudos,k.uniqueSort=k.unique=h.uniqueSort,k.text=h.getText,k.isXMLDoc=h.isXML,k.contains=h.contains,k.escapeSelector=h.escape;var T=function(e,t,n){var r=[],i=void 0!==n;while((e=e[t])&&9!==e.nodeType)if(1===e.nodeType){if(i&&k(e).is(n))break;r.push(e)}return r},S=function(e,t){for(var n=[];e;e=e.nextSibling)1===e.nodeType&&e!==t&&n.push(e);return n},N=k.expr.match.needsContext;function A(e,t){return e.nodeName&&e.nodeName.toLowerCase()===t.toLowerCase()}var D=/^<([a-z][^\/\0>:\x20\t\r\n\f]*)[\x20\t\r\n\f]*\/?>(?:<\/\1>|)$/i;function j(e,n,r){return m(n)?k.grep(e,function(e,t){return!!n.call(e,t,e)!==r}):n.nodeType?k.grep(e,function(e){return e===n!==r}):"string"!=typeof n?k.grep(e,function(e){return-1<i.call(n,e)!==r}):k.filter(n,e,r)}k.filter=function(e,t,n){var r=t[0];return n&&(e=":not("+e+")"),1===t.length&&1===r.nodeType?k.find.matchesSelector(r,e)?[r]:[]:k.find.matches(e,k.grep(t,function(e){return 1===e.nodeType}))},k.fn.extend({find:function(e){var t,n,r=this.length,i=this;if("string"!=typeof e)return this.pushStack(k(e).filter(function(){for(t=0;t<r;t++)if(k.contains(i[t],this))return!0}));for(n=this.pushStack([]),t=0;t<r;t++)k.find(e,i[t],n);return 1<r?k.uniqueSort(n):n},filter:function(e){return this.pushStack(j(this,e||[],!1))},not:function(e){return this.pushStack(j(this,e||[],!0))},is:function(e){return!!j(this,"string"==typeof e&&N.test(e)?k(e):e||[],!1).length}});var q,L=/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/;(k.fn.init=function(e,t,n){var r,i;if(!e)return this;if(n=n||q,"string"==typeof e){if(!(r="<"===e[0]&&">"===e[e.length-1]&&3<=e.length?[null,e,null]:L.exec(e))||!r[1]&&t)return!t||t.jquery?(t||n).find(e):this.constructor(t).find(e);if(r[1]){if(t=t instanceof k?t[0]:t,k.merge(this,k.parseHTML(r[1],t&&t.nodeType?t.ownerDocument||t:E,!0)),D.test(r[1])&&k.isPlainObject(t))for(r in t)m(this[r])?this[r](t[r]):this.attr(r,t[r]);return this}return(i=E.getElementById(r[2]))&&(this[0]=i,this.length=1),this}return e.nodeType?(this[0]=e,this.length=1,this):m(e)?void 0!==n.ready?n.ready(e):e(k):k.makeArray(e,this)}).prototype=k.fn,q=k(E);var H=/^(?:parents|prev(?:Until|All))/,O={children:!0,contents:!0,next:!0,prev:!0};function P(e,t){while((e=e[t])&&1!==e.nodeType);return e}k.fn.extend({has:function(e){var t=k(e,this),n=t.length;return this.filter(function(){for(var e=0;e<n;e++)if(k.contains(this,t[e]))return!0})},closest:function(e,t){var n,r=0,i=this.length,o=[],a="string"!=typeof e&&k(e);if(!N.test(e))for(;r<i;r++)for(n=this[r];n&&n!==t;n=n.parentNode)if(n.nodeType<11&&(a?-1<a.index(n):1===n.nodeType&&k.find.matchesSelector(n,e))){o.push(n);break}return this.pushStack(1<o.length?k.uniqueSort(o):o)},index:function(e){return e?"string"==typeof e?i.call(k(e),this[0]):i.call(this,e.jquery?e[0]:e):this[0]&&this[0].parentNode?this.first().prevAll().length:-1},add:function(e,t){return this.pushStack(k.uniqueSort(k.merge(this.get(),k(e,t))))},addBack:function(e){return this.add(null==e?this.prevObject:this.prevObject.filter(e))}}),k.each({parent:function(e){var t=e.parentNode;return t&&11!==t.nodeType?t:null},parents:function(e){return T(e,"parentNode")},parentsUntil:function(e,t,n){return T(e,"parentNode",n)},next:function(e){return P(e,"nextSibling")},prev:function(e){return P(e,"previousSibling")},nextAll:function(e){return T(e,"nextSibling")},prevAll:function(e){return T(e,"previousSibling")},nextUntil:function(e,t,n){return T(e,"nextSibling",n)},prevUntil:function(e,t,n){return T(e,"previousSibling",n)},siblings:function(e){return S((e.parentNode||{}).firstChild,e)},children:function(e){return S(e.firstChild)},contents:function(e){return"undefined"!=typeof e.contentDocument?e.contentDocument:(A(e,"template")&&(e=e.content||e),k.merge([],e.childNodes))}},function(r,i){k.fn[r]=function(e,t){var n=k.map(this,i,e);return"Until"!==r.slice(-5)&&(t=e),t&&"string"==typeof t&&(n=k.filter(t,n)),1<this.length&&(O[r]||k.uniqueSort(n),H.test(r)&&n.reverse()),this.pushStack(n)}});var R=/[^\x20\t\r\n\f]+/g;function M(e){return e}function I(e){throw e}function W(e,t,n,r){var i;try{e&&m(i=e.promise)?i.call(e).done(t).fail(n):e&&m(i=e.then)?i.call(e,t,n):t.apply(void 0,[e].slice(r))}catch(e){n.apply(void 0,[e])}}k.Callbacks=function(r){var e,n;r="string"==typeof r?(e=r,n={},k.each(e.match(R)||[],function(e,t){n[t]=!0}),n):k.extend({},r);var i,t,o,a,s=[],u=[],l=-1,c=function(){for(a=a||r.once,o=i=!0;u.length;l=-1){t=u.shift();while(++l<s.length)!1===s[l].apply(t[0],t[1])&&r.stopOnFalse&&(l=s.length,t=!1)}r.memory||(t=!1),i=!1,a&&(s=t?[]:"")},f={add:function(){return s&&(t&&!i&&(l=s.length-1,u.push(t)),function n(e){k.each(e,function(e,t){m(t)?r.unique&&f.has(t)||s.push(t):t&&t.length&&"string"!==w(t)&&n(t)})}(arguments),t&&!i&&c()),this},remove:function(){return k.each(arguments,function(e,t){var n;while(-1<(n=k.inArray(t,s,n)))s.splice(n,1),n<=l&&l--}),this},has:function(e){return e?-1<k.inArray(e,s):0<s.length},empty:function(){return s&&(s=[]),this},disable:function(){return a=u=[],s=t="",this},disabled:function(){return!s},lock:function(){return a=u=[],t||i||(s=t=""),this},locked:function(){return!!a},fireWith:function(e,t){return a||(t=[e,(t=t||[]).slice?t.slice():t],u.push(t),i||c()),this},fire:function(){return f.fireWith(this,arguments),this},fired:function(){return!!o}};return f},k.extend({Deferred:function(e){var o=[["notify","progress",k.Callbacks("memory"),k.Callbacks("memory"),2],["resolve","done",k.Callbacks("once memory"),k.Callbacks("once memory"),0,"resolved"],["reject","fail",k.Callbacks("once memory"),k.Callbacks("once memory"),1,"rejected"]],i="pending",a={state:function(){return i},always:function(){return s.done(arguments).fail(arguments),this},"catch":function(e){return a.then(null,e)},pipe:function(){var i=arguments;return k.Deferred(function(r){k.each(o,function(e,t){var n=m(i[t[4]])&&i[t[4]];s[t[1]](function(){var e=n&&n.apply(this,arguments);e&&m(e.promise)?e.promise().progress(r.notify).done(r.resolve).fail(r.reject):r[t[0]+"With"](this,n?[e]:arguments)})}),i=null}).promise()},then:function(t,n,r){var u=0;function l(i,o,a,s){return function(){var n=this,r=arguments,e=function(){var e,t;if(!(i<u)){if((e=a.apply(n,r))===o.promise())throw new TypeError("Thenable self-resolution");t=e&&("object"==typeof e||"function"==typeof e)&&e.then,m(t)?s?t.call(e,l(u,o,M,s),l(u,o,I,s)):(u++,t.call(e,l(u,o,M,s),l(u,o,I,s),l(u,o,M,o.notifyWith))):(a!==M&&(n=void 0,r=[e]),(s||o.resolveWith)(n,r))}},t=s?e:function(){try{e()}catch(e){k.Deferred.exceptionHook&&k.Deferred.exceptionHook(e,t.stackTrace),u<=i+1&&(a!==I&&(n=void 0,r=[e]),o.rejectWith(n,r))}};i?t():(k.Deferred.getStackHook&&(t.stackTrace=k.Deferred.getStackHook()),C.setTimeout(t))}}return k.Deferred(function(e){o[0][3].add(l(0,e,m(r)?r:M,e.notifyWith)),o[1][3].add(l(0,e,m(t)?t:M)),o[2][3].add(l(0,e,m(n)?n:I))}).promise()},promise:function(e){return null!=e?k.extend(e,a):a}},s={};return k.each(o,function(e,t){var n=t[2],r=t[5];a[t[1]]=n.add,r&&n.add(function(){i=r},o[3-e][2].disable,o[3-e][3].disable,o[0][2].lock,o[0][3].lock),n.add(t[3].fire),s[t[0]]=function(){return s[t[0]+"With"](this===s?void 0:this,arguments),this},s[t[0]+"With"]=n.fireWith}),a.promise(s),e&&e.call(s,s),s},when:function(e){var n=arguments.length,t=n,r=Array(t),i=s.call(arguments),o=k.Deferred(),a=function(t){return function(e){r[t]=this,i[t]=1<arguments.length?s.call(arguments):e,--n||o.resolveWith(r,i)}};if(n<=1&&(W(e,o.done(a(t)).resolve,o.reject,!n),"pending"===o.state()||m(i[t]&&i[t].then)))return o.then();while(t--)W(i[t],a(t),o.reject);return o.promise()}});var $=/^(Eval|Internal|Range|Reference|Syntax|Type|URI)Error$/;k.Deferred.exceptionHook=function(e,t){C.console&&C.console.warn&&e&&$.test(e.name)&&C.console.warn("jQuery.Deferred exception: "+e.message,e.stack,t)},k.readyException=function(e){C.setTimeout(function(){throw e})};var F=k.Deferred();function B(){E.removeEventListener("DOMContentLoaded",B),C.removeEventListener("load",B),k.ready()}k.fn.ready=function(e){return F.then(e)["catch"](function(e){k.readyException(e)}),this},k.extend({isReady:!1,readyWait:1,ready:function(e){(!0===e?--k.readyWait:k.isReady)||(k.isReady=!0)!==e&&0<--k.readyWait||F.resolveWith(E,[k])}}),k.ready.then=F.then,"complete"===E.readyState||"loading"!==E.readyState&&!E.documentElement.doScroll?C.setTimeout(k.ready):(E.addEventListener("DOMContentLoaded",B),C.addEventListener("load",B));var _=function(e,t,n,r,i,o,a){var s=0,u=e.length,l=null==n;if("object"===w(n))for(s in i=!0,n)_(e,t,s,n[s],!0,o,a);else if(void 0!==r&&(i=!0,m(r)||(a=!0),l&&(a?(t.call(e,r),t=null):(l=t,t=function(e,t,n){return l.call(k(e),n)})),t))for(;s<u;s++)t(e[s],n,a?r:r.call(e[s],s,t(e[s],n)));return i?e:l?t.call(e):u?t(e[0],n):o},z=/^-ms-/,U=/-([a-z])/g;function X(e,t){return t.toUpperCase()}function V(e){return e.replace(z,"ms-").replace(U,X)}var G=function(e){return 1===e.nodeType||9===e.nodeType||!+e.nodeType};function Y(){this.expando=k.expando+Y.uid++}Y.uid=1,Y.prototype={cache:function(e){var t=e[this.expando];return t||(t={},G(e)&&(e.nodeType?e[this.expando]=t:Object.defineProperty(e,this.expando,{value:t,configurable:!0}))),t},set:function(e,t,n){var r,i=this.cache(e);if("string"==typeof t)i[V(t)]=n;else for(r in t)i[V(r)]=t[r];return i},get:function(e,t){return void 0===t?this.cache(e):e[this.expando]&&e[this.expando][V(t)]},access:function(e,t,n){return void 0===t||t&&"string"==typeof t&&void 0===n?this.get(e,t):(this.set(e,t,n),void 0!==n?n:t)},remove:function(e,t){var n,r=e[this.expando];if(void 0!==r){if(void 0!==t){n=(t=Array.isArray(t)?t.map(V):(t=V(t))in r?[t]:t.match(R)||[]).length;while(n--)delete r[t[n]]}(void 0===t||k.isEmptyObject(r))&&(e.nodeType?e[this.expando]=void 0:delete e[this.expando])}},hasData:function(e){var t=e[this.expando];return void 0!==t&&!k.isEmptyObject(t)}};var Q=new Y,J=new Y,K=/^(?:\{[\w\W]*\}|\[[\w\W]*\])$/,Z=/[A-Z]/g;function ee(e,t,n){var r,i;if(void 0===n&&1===e.nodeType)if(r="data-"+t.replace(Z,"-$&").toLowerCase(),"string"==typeof(n=e.getAttribute(r))){try{n="true"===(i=n)||"false"!==i&&("null"===i?null:i===+i+""?+i:K.test(i)?JSON.parse(i):i)}catch(e){}J.set(e,t,n)}else n=void 0;return n}k.extend({hasData:function(e){return J.hasData(e)||Q.hasData(e)},data:function(e,t,n){return J.access(e,t,n)},removeData:function(e,t){J.remove(e,t)},_data:function(e,t,n){return Q.access(e,t,n)},_removeData:function(e,t){Q.remove(e,t)}}),k.fn.extend({data:function(n,e){var t,r,i,o=this[0],a=o&&o.attributes;if(void 0===n){if(this.length&&(i=J.get(o),1===o.nodeType&&!Q.get(o,"hasDataAttrs"))){t=a.length;while(t--)a[t]&&0===(r=a[t].name).indexOf("data-")&&(r=V(r.slice(5)),ee(o,r,i[r]));Q.set(o,"hasDataAttrs",!0)}return i}return"object"==typeof n?this.each(function(){J.set(this,n)}):_(this,function(e){var t;if(o&&void 0===e)return void 0!==(t=J.get(o,n))?t:void 0!==(t=ee(o,n))?t:void 0;this.each(function(){J.set(this,n,e)})},null,e,1<arguments.length,null,!0)},removeData:function(e){return this.each(function(){J.remove(this,e)})}}),k.extend({queue:function(e,t,n){var r;if(e)return t=(t||"fx")+"queue",r=Q.get(e,t),n&&(!r||Array.isArray(n)?r=Q.access(e,t,k.makeArray(n)):r.push(n)),r||[]},dequeue:function(e,t){t=t||"fx";var n=k.queue(e,t),r=n.length,i=n.shift(),o=k._queueHooks(e,t);"inprogress"===i&&(i=n.shift(),r--),i&&("fx"===t&&n.unshift("inprogress"),delete o.stop,i.call(e,function(){k.dequeue(e,t)},o)),!r&&o&&o.empty.fire()},_queueHooks:function(e,t){var n=t+"queueHooks";return Q.get(e,n)||Q.access(e,n,{empty:k.Callbacks("once memory").add(function(){Q.remove(e,[t+"queue",n])})})}}),k.fn.extend({queue:function(t,n){var e=2;return"string"!=typeof t&&(n=t,t="fx",e--),arguments.length<e?k.queue(this[0],t):void 0===n?this:this.each(function(){var e=k.queue(this,t,n);k._queueHooks(this,t),"fx"===t&&"inprogress"!==e[0]&&k.dequeue(this,t)})},dequeue:function(e){return this.each(function(){k.dequeue(this,e)})},clearQueue:function(e){return this.queue(e||"fx",[])},promise:function(e,t){var n,r=1,i=k.Deferred(),o=this,a=this.length,s=function(){--r||i.resolveWith(o,[o])};"string"!=typeof e&&(t=e,e=void 0),e=e||"fx";while(a--)(n=Q.get(o[a],e+"queueHooks"))&&n.empty&&(r++,n.empty.add(s));return s(),i.promise(t)}});var te=/[+-]?(?:\d*\.|)\d+(?:[eE][+-]?\d+|)/.source,ne=new RegExp("^(?:([+-])=|)("+te+")([a-z%]*)$","i"),re=["Top","Right","Bottom","Left"],ie=E.documentElement,oe=function(e){return k.contains(e.ownerDocument,e)},ae={composed:!0};ie.getRootNode&&(oe=function(e){return k.contains(e.ownerDocument,e)||e.getRootNode(ae)===e.ownerDocument});var se=function(e,t){return"none"===(e=t||e).style.display||""===e.style.display&&oe(e)&&"none"===k.css(e,"display")},ue=function(e,t,n,r){var i,o,a={};for(o in t)a[o]=e.style[o],e.style[o]=t[o];for(o in i=n.apply(e,r||[]),t)e.style[o]=a[o];return i};function le(e,t,n,r){var i,o,a=20,s=r?function(){return r.cur()}:function(){return k.css(e,t,"")},u=s(),l=n&&n[3]||(k.cssNumber[t]?"":"px"),c=e.nodeType&&(k.cssNumber[t]||"px"!==l&&+u)&&ne.exec(k.css(e,t));if(c&&c[3]!==l){u/=2,l=l||c[3],c=+u||1;while(a--)k.style(e,t,c+l),(1-o)*(1-(o=s()/u||.5))<=0&&(a=0),c/=o;c*=2,k.style(e,t,c+l),n=n||[]}return n&&(c=+c||+u||0,i=n[1]?c+(n[1]+1)*n[2]:+n[2],r&&(r.unit=l,r.start=c,r.end=i)),i}var ce={};function fe(e,t){for(var n,r,i,o,a,s,u,l=[],c=0,f=e.length;c<f;c++)(r=e[c]).style&&(n=r.style.display,t?("none"===n&&(l[c]=Q.get(r,"display")||null,l[c]||(r.style.display="")),""===r.style.display&&se(r)&&(l[c]=(u=a=o=void 0,a=(i=r).ownerDocument,s=i.nodeName,(u=ce[s])||(o=a.body.appendChild(a.createElement(s)),u=k.css(o,"display"),o.parentNode.removeChild(o),"none"===u&&(u="block"),ce[s]=u)))):"none"!==n&&(l[c]="none",Q.set(r,"display",n)));for(c=0;c<f;c++)null!=l[c]&&(e[c].style.display=l[c]);return e}k.fn.extend({show:function(){return fe(this,!0)},hide:function(){return fe(this)},toggle:function(e){return"boolean"==typeof e?e?this.show():this.hide():this.each(function(){se(this)?k(this).show():k(this).hide()})}});var pe=/^(?:checkbox|radio)$/i,de=/<([a-z][^\/\0>\x20\t\r\n\f]*)/i,he=/^$|^module$|\/(?:java|ecma)script/i,ge={option:[1,"<select multiple='multiple'>","</select>"],thead:[1,"<table>","</table>"],col:[2,"<table><colgroup>","</colgroup></table>"],tr:[2,"<table><tbody>","</tbody></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],_default:[0,"",""]};function ve(e,t){var n;return n="undefined"!=typeof e.getElementsByTagName?e.getElementsByTagName(t||"*"):"undefined"!=typeof e.querySelectorAll?e.querySelectorAll(t||"*"):[],void 0===t||t&&A(e,t)?k.merge([e],n):n}function ye(e,t){for(var n=0,r=e.length;n<r;n++)Q.set(e[n],"globalEval",!t||Q.get(t[n],"globalEval"))}ge.optgroup=ge.option,ge.tbody=ge.tfoot=ge.colgroup=ge.caption=ge.thead,ge.th=ge.td;var me,xe,be=/<|&#?\w+;/;function we(e,t,n,r,i){for(var o,a,s,u,l,c,f=t.createDocumentFragment(),p=[],d=0,h=e.length;d<h;d++)if((o=e[d])||0===o)if("object"===w(o))k.merge(p,o.nodeType?[o]:o);else if(be.test(o)){a=a||f.appendChild(t.createElement("div")),s=(de.exec(o)||["",""])[1].toLowerCase(),u=ge[s]||ge._default,a.innerHTML=u[1]+k.htmlPrefilter(o)+u[2],c=u[0];while(c--)a=a.lastChild;k.merge(p,a.childNodes),(a=f.firstChild).textContent=""}else p.push(t.createTextNode(o));f.textContent="",d=0;while(o=p[d++])if(r&&-1<k.inArray(o,r))i&&i.push(o);else if(l=oe(o),a=ve(f.appendChild(o),"script"),l&&ye(a),n){c=0;while(o=a[c++])he.test(o.type||"")&&n.push(o)}return f}me=E.createDocumentFragment().appendChild(E.createElement("div")),(xe=E.createElement("input")).setAttribute("type","radio"),xe.setAttribute("checked","checked"),xe.setAttribute("name","t"),me.appendChild(xe),y.checkClone=me.cloneNode(!0).cloneNode(!0).lastChild.checked,me.innerHTML="<textarea>x</textarea>",y.noCloneChecked=!!me.cloneNode(!0).lastChild.defaultValue;var Te=/^key/,Ce=/^(?:mouse|pointer|contextmenu|drag|drop)|click/,Ee=/^([^.]*)(?:\.(.+)|)/;function ke(){return!0}function Se(){return!1}function Ne(e,t){return e===function(){try{return E.activeElement}catch(e){}}()==("focus"===t)}function Ae(e,t,n,r,i,o){var a,s;if("object"==typeof t){for(s in"string"!=typeof n&&(r=r||n,n=void 0),t)Ae(e,s,n,r,t[s],o);return e}if(null==r&&null==i?(i=n,r=n=void 0):null==i&&("string"==typeof n?(i=r,r=void 0):(i=r,r=n,n=void 0)),!1===i)i=Se;else if(!i)return e;return 1===o&&(a=i,(i=function(e){return k().off(e),a.apply(this,arguments)}).guid=a.guid||(a.guid=k.guid++)),e.each(function(){k.event.add(this,t,i,r,n)})}function De(e,i,o){o?(Q.set(e,i,!1),k.event.add(e,i,{namespace:!1,handler:function(e){var t,n,r=Q.get(this,i);if(1&e.isTrigger&&this[i]){if(r.length)(k.event.special[i]||{}).delegateType&&e.stopPropagation();else if(r=s.call(arguments),Q.set(this,i,r),t=o(this,i),this[i](),r!==(n=Q.get(this,i))||t?Q.set(this,i,!1):n={},r!==n)return e.stopImmediatePropagation(),e.preventDefault(),n.value}else r.length&&(Q.set(this,i,{value:k.event.trigger(k.extend(r[0],k.Event.prototype),r.slice(1),this)}),e.stopImmediatePropagation())}})):void 0===Q.get(e,i)&&k.event.add(e,i,ke)}k.event={global:{},add:function(t,e,n,r,i){var o,a,s,u,l,c,f,p,d,h,g,v=Q.get(t);if(v){n.handler&&(n=(o=n).handler,i=o.selector),i&&k.find.matchesSelector(ie,i),n.guid||(n.guid=k.guid++),(u=v.events)||(u=v.events={}),(a=v.handle)||(a=v.handle=function(e){return"undefined"!=typeof k&&k.event.triggered!==e.type?k.event.dispatch.apply(t,arguments):void 0}),l=(e=(e||"").match(R)||[""]).length;while(l--)d=g=(s=Ee.exec(e[l])||[])[1],h=(s[2]||"").split(".").sort(),d&&(f=k.event.special[d]||{},d=(i?f.delegateType:f.bindType)||d,f=k.event.special[d]||{},c=k.extend({type:d,origType:g,data:r,handler:n,guid:n.guid,selector:i,needsContext:i&&k.expr.match.needsContext.test(i),namespace:h.join(".")},o),(p=u[d])||((p=u[d]=[]).delegateCount=0,f.setup&&!1!==f.setup.call(t,r,h,a)||t.addEventListener&&t.addEventListener(d,a)),f.add&&(f.add.call(t,c),c.handler.guid||(c.handler.guid=n.guid)),i?p.splice(p.delegateCount++,0,c):p.push(c),k.event.global[d]=!0)}},remove:function(e,t,n,r,i){var o,a,s,u,l,c,f,p,d,h,g,v=Q.hasData(e)&&Q.get(e);if(v&&(u=v.events)){l=(t=(t||"").match(R)||[""]).length;while(l--)if(d=g=(s=Ee.exec(t[l])||[])[1],h=(s[2]||"").split(".").sort(),d){f=k.event.special[d]||{},p=u[d=(r?f.delegateType:f.bindType)||d]||[],s=s[2]&&new RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"),a=o=p.length;while(o--)c=p[o],!i&&g!==c.origType||n&&n.guid!==c.guid||s&&!s.test(c.namespace)||r&&r!==c.selector&&("**"!==r||!c.selector)||(p.splice(o,1),c.selector&&p.delegateCount--,f.remove&&f.remove.call(e,c));a&&!p.length&&(f.teardown&&!1!==f.teardown.call(e,h,v.handle)||k.removeEvent(e,d,v.handle),delete u[d])}else for(d in u)k.event.remove(e,d+t[l],n,r,!0);k.isEmptyObject(u)&&Q.remove(e,"handle events")}},dispatch:function(e){var t,n,r,i,o,a,s=k.event.fix(e),u=new Array(arguments.length),l=(Q.get(this,"events")||{})[s.type]||[],c=k.event.special[s.type]||{};for(u[0]=s,t=1;t<arguments.length;t++)u[t]=arguments[t];if(s.delegateTarget=this,!c.preDispatch||!1!==c.preDispatch.call(this,s)){a=k.event.handlers.call(this,s,l),t=0;while((i=a[t++])&&!s.isPropagationStopped()){s.currentTarget=i.elem,n=0;while((o=i.handlers[n++])&&!s.isImmediatePropagationStopped())s.rnamespace&&!1!==o.namespace&&!s.rnamespace.test(o.namespace)||(s.handleObj=o,s.data=o.data,void 0!==(r=((k.event.special[o.origType]||{}).handle||o.handler).apply(i.elem,u))&&!1===(s.result=r)&&(s.preventDefault(),s.stopPropagation()))}return c.postDispatch&&c.postDispatch.call(this,s),s.result}},handlers:function(e,t){var n,r,i,o,a,s=[],u=t.delegateCount,l=e.target;if(u&&l.nodeType&&!("click"===e.type&&1<=e.button))for(;l!==this;l=l.parentNode||this)if(1===l.nodeType&&("click"!==e.type||!0!==l.disabled)){for(o=[],a={},n=0;n<u;n++)void 0===a[i=(r=t[n]).selector+" "]&&(a[i]=r.needsContext?-1<k(i,this).index(l):k.find(i,this,null,[l]).length),a[i]&&o.push(r);o.length&&s.push({elem:l,handlers:o})}return l=this,u<t.length&&s.push({elem:l,handlers:t.slice(u)}),s},addProp:function(t,e){Object.defineProperty(k.Event.prototype,t,{enumerable:!0,configurable:!0,get:m(e)?function(){if(this.originalEvent)return e(this.originalEvent)}:function(){if(this.originalEvent)return this.originalEvent[t]},set:function(e){Object.defineProperty(this,t,{enumerable:!0,configurable:!0,writable:!0,value:e})}})},fix:function(e){return e[k.expando]?e:new k.Event(e)},special:{load:{noBubble:!0},click:{setup:function(e){var t=this||e;return pe.test(t.type)&&t.click&&A(t,"input")&&De(t,"click",ke),!1},trigger:function(e){var t=this||e;return pe.test(t.type)&&t.click&&A(t,"input")&&De(t,"click"),!0},_default:function(e){var t=e.target;return pe.test(t.type)&&t.click&&A(t,"input")&&Q.get(t,"click")||A(t,"a")}},beforeunload:{postDispatch:function(e){void 0!==e.result&&e.originalEvent&&(e.originalEvent.returnValue=e.result)}}}},k.removeEvent=function(e,t,n){e.removeEventListener&&e.removeEventListener(t,n)},k.Event=function(e,t){if(!(this instanceof k.Event))return new k.Event(e,t);e&&e.type?(this.originalEvent=e,this.type=e.type,this.isDefaultPrevented=e.defaultPrevented||void 0===e.defaultPrevented&&!1===e.returnValue?ke:Se,this.target=e.target&&3===e.target.nodeType?e.target.parentNode:e.target,this.currentTarget=e.currentTarget,this.relatedTarget=e.relatedTarget):this.type=e,t&&k.extend(this,t),this.timeStamp=e&&e.timeStamp||Date.now(),this[k.expando]=!0},k.Event.prototype={constructor:k.Event,isDefaultPrevented:Se,isPropagationStopped:Se,isImmediatePropagationStopped:Se,isSimulated:!1,preventDefault:function(){var e=this.originalEvent;this.isDefaultPrevented=ke,e&&!this.isSimulated&&e.preventDefault()},stopPropagation:function(){var e=this.originalEvent;this.isPropagationStopped=ke,e&&!this.isSimulated&&e.stopPropagation()},stopImmediatePropagation:function(){var e=this.originalEvent;this.isImmediatePropagationStopped=ke,e&&!this.isSimulated&&e.stopImmediatePropagation(),this.stopPropagation()}},k.each({altKey:!0,bubbles:!0,cancelable:!0,changedTouches:!0,ctrlKey:!0,detail:!0,eventPhase:!0,metaKey:!0,pageX:!0,pageY:!0,shiftKey:!0,view:!0,"char":!0,code:!0,charCode:!0,key:!0,keyCode:!0,button:!0,buttons:!0,clientX:!0,clientY:!0,offsetX:!0,offsetY:!0,pointerId:!0,pointerType:!0,screenX:!0,screenY:!0,targetTouches:!0,toElement:!0,touches:!0,which:function(e){var t=e.button;return null==e.which&&Te.test(e.type)?null!=e.charCode?e.charCode:e.keyCode:!e.which&&void 0!==t&&Ce.test(e.type)?1&t?1:2&t?3:4&t?2:0:e.which}},k.event.addProp),k.each({focus:"focusin",blur:"focusout"},function(e,t){k.event.special[e]={setup:function(){return De(this,e,Ne),!1},trigger:function(){return De(this,e),!0},delegateType:t}}),k.each({mouseenter:"mouseover",mouseleave:"mouseout",pointerenter:"pointerover",pointerleave:"pointerout"},function(e,i){k.event.special[e]={delegateType:i,bindType:i,handle:function(e){var t,n=e.relatedTarget,r=e.handleObj;return n&&(n===this||k.contains(this,n))||(e.type=r.origType,t=r.handler.apply(this,arguments),e.type=i),t}}}),k.fn.extend({on:function(e,t,n,r){return Ae(this,e,t,n,r)},one:function(e,t,n,r){return Ae(this,e,t,n,r,1)},off:function(e,t,n){var r,i;if(e&&e.preventDefault&&e.handleObj)return r=e.handleObj,k(e.delegateTarget).off(r.namespace?r.origType+"."+r.namespace:r.origType,r.selector,r.handler),this;if("object"==typeof e){for(i in e)this.off(i,t,e[i]);return this}return!1!==t&&"function"!=typeof t||(n=t,t=void 0),!1===n&&(n=Se),this.each(function(){k.event.remove(this,e,n,t)})}});var je=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([a-z][^\/\0>\x20\t\r\n\f]*)[^>]*)\/>/gi,qe=/<script|<style|<link/i,Le=/checked\s*(?:[^=]|=\s*.checked.)/i,He=/^\s*<!(?:\[CDATA\[|--)|(?:\]\]|--)>\s*$/g;function Oe(e,t){return A(e,"table")&&A(11!==t.nodeType?t:t.firstChild,"tr")&&k(e).children("tbody")[0]||e}function Pe(e){return e.type=(null!==e.getAttribute("type"))+"/"+e.type,e}function Re(e){return"true/"===(e.type||"").slice(0,5)?e.type=e.type.slice(5):e.removeAttribute("type"),e}function Me(e,t){var n,r,i,o,a,s,u,l;if(1===t.nodeType){if(Q.hasData(e)&&(o=Q.access(e),a=Q.set(t,o),l=o.events))for(i in delete a.handle,a.events={},l)for(n=0,r=l[i].length;n<r;n++)k.event.add(t,i,l[i][n]);J.hasData(e)&&(s=J.access(e),u=k.extend({},s),J.set(t,u))}}function Ie(n,r,i,o){r=g.apply([],r);var e,t,a,s,u,l,c=0,f=n.length,p=f-1,d=r[0],h=m(d);if(h||1<f&&"string"==typeof d&&!y.checkClone&&Le.test(d))return n.each(function(e){var t=n.eq(e);h&&(r[0]=d.call(this,e,t.html())),Ie(t,r,i,o)});if(f&&(t=(e=we(r,n[0].ownerDocument,!1,n,o)).firstChild,1===e.childNodes.length&&(e=t),t||o)){for(s=(a=k.map(ve(e,"script"),Pe)).length;c<f;c++)u=e,c!==p&&(u=k.clone(u,!0,!0),s&&k.merge(a,ve(u,"script"))),i.call(n[c],u,c);if(s)for(l=a[a.length-1].ownerDocument,k.map(a,Re),c=0;c<s;c++)u=a[c],he.test(u.type||"")&&!Q.access(u,"globalEval")&&k.contains(l,u)&&(u.src&&"module"!==(u.type||"").toLowerCase()?k._evalUrl&&!u.noModule&&k._evalUrl(u.src,{nonce:u.nonce||u.getAttribute("nonce")}):b(u.textContent.replace(He,""),u,l))}return n}function We(e,t,n){for(var r,i=t?k.filter(t,e):e,o=0;null!=(r=i[o]);o++)n||1!==r.nodeType||k.cleanData(ve(r)),r.parentNode&&(n&&oe(r)&&ye(ve(r,"script")),r.parentNode.removeChild(r));return e}k.extend({htmlPrefilter:function(e){return e.replace(je,"<$1></$2>")},clone:function(e,t,n){var r,i,o,a,s,u,l,c=e.cloneNode(!0),f=oe(e);if(!(y.noCloneChecked||1!==e.nodeType&&11!==e.nodeType||k.isXMLDoc(e)))for(a=ve(c),r=0,i=(o=ve(e)).length;r<i;r++)s=o[r],u=a[r],void 0,"input"===(l=u.nodeName.toLowerCase())&&pe.test(s.type)?u.checked=s.checked:"input"!==l&&"textarea"!==l||(u.defaultValue=s.defaultValue);if(t)if(n)for(o=o||ve(e),a=a||ve(c),r=0,i=o.length;r<i;r++)Me(o[r],a[r]);else Me(e,c);return 0<(a=ve(c,"script")).length&&ye(a,!f&&ve(e,"script")),c},cleanData:function(e){for(var t,n,r,i=k.event.special,o=0;void 0!==(n=e[o]);o++)if(G(n)){if(t=n[Q.expando]){if(t.events)for(r in t.events)i[r]?k.event.remove(n,r):k.removeEvent(n,r,t.handle);n[Q.expando]=void 0}n[J.expando]&&(n[J.expando]=void 0)}}}),k.fn.extend({detach:function(e){return We(this,e,!0)},remove:function(e){return We(this,e)},text:function(e){return _(this,function(e){return void 0===e?k.text(this):this.empty().each(function(){1!==this.nodeType&&11!==this.nodeType&&9!==this.nodeType||(this.textContent=e)})},null,e,arguments.length)},append:function(){return Ie(this,arguments,function(e){1!==this.nodeType&&11!==this.nodeType&&9!==this.nodeType||Oe(this,e).appendChild(e)})},prepend:function(){return Ie(this,arguments,function(e){if(1===this.nodeType||11===this.nodeType||9===this.nodeType){var t=Oe(this,e);t.insertBefore(e,t.firstChild)}})},before:function(){return Ie(this,arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this)})},after:function(){return Ie(this,arguments,function(e){this.parentNode&&this.parentNode.insertBefore(e,this.nextSibling)})},empty:function(){for(var e,t=0;null!=(e=this[t]);t++)1===e.nodeType&&(k.cleanData(ve(e,!1)),e.textContent="");return this},clone:function(e,t){return e=null!=e&&e,t=null==t?e:t,this.map(function(){return k.clone(this,e,t)})},html:function(e){return _(this,function(e){var t=this[0]||{},n=0,r=this.length;if(void 0===e&&1===t.nodeType)return t.innerHTML;if("string"==typeof e&&!qe.test(e)&&!ge[(de.exec(e)||["",""])[1].toLowerCase()]){e=k.htmlPrefilter(e);try{for(;n<r;n++)1===(t=this[n]||{}).nodeType&&(k.cleanData(ve(t,!1)),t.innerHTML=e);t=0}catch(e){}}t&&this.empty().append(e)},null,e,arguments.length)},replaceWith:function(){var n=[];return Ie(this,arguments,function(e){var t=this.parentNode;k.inArray(this,n)<0&&(k.cleanData(ve(this)),t&&t.replaceChild(e,this))},n)}}),k.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(e,a){k.fn[e]=function(e){for(var t,n=[],r=k(e),i=r.length-1,o=0;o<=i;o++)t=o===i?this:this.clone(!0),k(r[o])[a](t),u.apply(n,t.get());return this.pushStack(n)}});var $e=new RegExp("^("+te+")(?!px)[a-z%]+$","i"),Fe=function(e){var t=e.ownerDocument.defaultView;return t&&t.opener||(t=C),t.getComputedStyle(e)},Be=new RegExp(re.join("|"),"i");function _e(e,t,n){var r,i,o,a,s=e.style;return(n=n||Fe(e))&&(""!==(a=n.getPropertyValue(t)||n[t])||oe(e)||(a=k.style(e,t)),!y.pixelBoxStyles()&&$e.test(a)&&Be.test(t)&&(r=s.width,i=s.minWidth,o=s.maxWidth,s.minWidth=s.maxWidth=s.width=a,a=n.width,s.width=r,s.minWidth=i,s.maxWidth=o)),void 0!==a?a+"":a}function ze(e,t){return{get:function(){if(!e())return(this.get=t).apply(this,arguments);delete this.get}}}!function(){function e(){if(u){s.style.cssText="position:absolute;left:-11111px;width:60px;margin-top:1px;padding:0;border:0",u.style.cssText="position:relative;display:block;box-sizing:border-box;overflow:scroll;margin:auto;border:1px;padding:1px;width:60%;top:1%",ie.appendChild(s).appendChild(u);var e=C.getComputedStyle(u);n="1%"!==e.top,a=12===t(e.marginLeft),u.style.right="60%",o=36===t(e.right),r=36===t(e.width),u.style.position="absolute",i=12===t(u.offsetWidth/3),ie.removeChild(s),u=null}}function t(e){return Math.round(parseFloat(e))}var n,r,i,o,a,s=E.createElement("div"),u=E.createElement("div");u.style&&(u.style.backgroundClip="content-box",u.cloneNode(!0).style.backgroundClip="",y.clearCloneStyle="content-box"===u.style.backgroundClip,k.extend(y,{boxSizingReliable:function(){return e(),r},pixelBoxStyles:function(){return e(),o},pixelPosition:function(){return e(),n},reliableMarginLeft:function(){return e(),a},scrollboxSize:function(){return e(),i}}))}();var Ue=["Webkit","Moz","ms"],Xe=E.createElement("div").style,Ve={};function Ge(e){var t=k.cssProps[e]||Ve[e];return t||(e in Xe?e:Ve[e]=function(e){var t=e[0].toUpperCase()+e.slice(1),n=Ue.length;while(n--)if((e=Ue[n]+t)in Xe)return e}(e)||e)}var Ye=/^(none|table(?!-c[ea]).+)/,Qe=/^--/,Je={position:"absolute",visibility:"hidden",display:"block"},Ke={letterSpacing:"0",fontWeight:"400"};function Ze(e,t,n){var r=ne.exec(t);return r?Math.max(0,r[2]-(n||0))+(r[3]||"px"):t}function et(e,t,n,r,i,o){var a="width"===t?1:0,s=0,u=0;if(n===(r?"border":"content"))return 0;for(;a<4;a+=2)"margin"===n&&(u+=k.css(e,n+re[a],!0,i)),r?("content"===n&&(u-=k.css(e,"padding"+re[a],!0,i)),"margin"!==n&&(u-=k.css(e,"border"+re[a]+"Width",!0,i))):(u+=k.css(e,"padding"+re[a],!0,i),"padding"!==n?u+=k.css(e,"border"+re[a]+"Width",!0,i):s+=k.css(e,"border"+re[a]+"Width",!0,i));return!r&&0<=o&&(u+=Math.max(0,Math.ceil(e["offset"+t[0].toUpperCase()+t.slice(1)]-o-u-s-.5))||0),u}function tt(e,t,n){var r=Fe(e),i=(!y.boxSizingReliable()||n)&&"border-box"===k.css(e,"boxSizing",!1,r),o=i,a=_e(e,t,r),s="offset"+t[0].toUpperCase()+t.slice(1);if($e.test(a)){if(!n)return a;a="auto"}return(!y.boxSizingReliable()&&i||"auto"===a||!parseFloat(a)&&"inline"===k.css(e,"display",!1,r))&&e.getClientRects().length&&(i="border-box"===k.css(e,"boxSizing",!1,r),(o=s in e)&&(a=e[s])),(a=parseFloat(a)||0)+et(e,t,n||(i?"border":"content"),o,r,a)+"px"}function nt(e,t,n,r,i){return new nt.prototype.init(e,t,n,r,i)}k.extend({cssHooks:{opacity:{get:function(e,t){if(t){var n=_e(e,"opacity");return""===n?"1":n}}}},cssNumber:{animationIterationCount:!0,columnCount:!0,fillOpacity:!0,flexGrow:!0,flexShrink:!0,fontWeight:!0,gridArea:!0,gridColumn:!0,gridColumnEnd:!0,gridColumnStart:!0,gridRow:!0,gridRowEnd:!0,gridRowStart:!0,lineHeight:!0,opacity:!0,order:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{},style:function(e,t,n,r){if(e&&3!==e.nodeType&&8!==e.nodeType&&e.style){var i,o,a,s=V(t),u=Qe.test(t),l=e.style;if(u||(t=Ge(s)),a=k.cssHooks[t]||k.cssHooks[s],void 0===n)return a&&"get"in a&&void 0!==(i=a.get(e,!1,r))?i:l[t];"string"===(o=typeof n)&&(i=ne.exec(n))&&i[1]&&(n=le(e,t,i),o="number"),null!=n&&n==n&&("number"!==o||u||(n+=i&&i[3]||(k.cssNumber[s]?"":"px")),y.clearCloneStyle||""!==n||0!==t.indexOf("background")||(l[t]="inherit"),a&&"set"in a&&void 0===(n=a.set(e,n,r))||(u?l.setProperty(t,n):l[t]=n))}},css:function(e,t,n,r){var i,o,a,s=V(t);return Qe.test(t)||(t=Ge(s)),(a=k.cssHooks[t]||k.cssHooks[s])&&"get"in a&&(i=a.get(e,!0,n)),void 0===i&&(i=_e(e,t,r)),"normal"===i&&t in Ke&&(i=Ke[t]),""===n||n?(o=parseFloat(i),!0===n||isFinite(o)?o||0:i):i}}),k.each(["height","width"],function(e,u){k.cssHooks[u]={get:function(e,t,n){if(t)return!Ye.test(k.css(e,"display"))||e.getClientRects().length&&e.getBoundingClientRect().width?tt(e,u,n):ue(e,Je,function(){return tt(e,u,n)})},set:function(e,t,n){var r,i=Fe(e),o=!y.scrollboxSize()&&"absolute"===i.position,a=(o||n)&&"border-box"===k.css(e,"boxSizing",!1,i),s=n?et(e,u,n,a,i):0;return a&&o&&(s-=Math.ceil(e["offset"+u[0].toUpperCase()+u.slice(1)]-parseFloat(i[u])-et(e,u,"border",!1,i)-.5)),s&&(r=ne.exec(t))&&"px"!==(r[3]||"px")&&(e.style[u]=t,t=k.css(e,u)),Ze(0,t,s)}}}),k.cssHooks.marginLeft=ze(y.reliableMarginLeft,function(e,t){if(t)return(parseFloat(_e(e,"marginLeft"))||e.getBoundingClientRect().left-ue(e,{marginLeft:0},function(){return e.getBoundingClientRect().left}))+"px"}),k.each({margin:"",padding:"",border:"Width"},function(i,o){k.cssHooks[i+o]={expand:function(e){for(var t=0,n={},r="string"==typeof e?e.split(" "):[e];t<4;t++)n[i+re[t]+o]=r[t]||r[t-2]||r[0];return n}},"margin"!==i&&(k.cssHooks[i+o].set=Ze)}),k.fn.extend({css:function(e,t){return _(this,function(e,t,n){var r,i,o={},a=0;if(Array.isArray(t)){for(r=Fe(e),i=t.length;a<i;a++)o[t[a]]=k.css(e,t[a],!1,r);return o}return void 0!==n?k.style(e,t,n):k.css(e,t)},e,t,1<arguments.length)}}),((k.Tween=nt).prototype={constructor:nt,init:function(e,t,n,r,i,o){this.elem=e,this.prop=n,this.easing=i||k.easing._default,this.options=t,this.start=this.now=this.cur(),this.end=r,this.unit=o||(k.cssNumber[n]?"":"px")},cur:function(){var e=nt.propHooks[this.prop];return e&&e.get?e.get(this):nt.propHooks._default.get(this)},run:function(e){var t,n=nt.propHooks[this.prop];return this.options.duration?this.pos=t=k.easing[this.easing](e,this.options.duration*e,0,1,this.options.duration):this.pos=t=e,this.now=(this.end-this.start)*t+this.start,this.options.step&&this.options.step.call(this.elem,this.now,this),n&&n.set?n.set(this):nt.propHooks._default.set(this),this}}).init.prototype=nt.prototype,(nt.propHooks={_default:{get:function(e){var t;return 1!==e.elem.nodeType||null!=e.elem[e.prop]&&null==e.elem.style[e.prop]?e.elem[e.prop]:(t=k.css(e.elem,e.prop,""))&&"auto"!==t?t:0},set:function(e){k.fx.step[e.prop]?k.fx.step[e.prop](e):1!==e.elem.nodeType||!k.cssHooks[e.prop]&&null==e.elem.style[Ge(e.prop)]?e.elem[e.prop]=e.now:k.style(e.elem,e.prop,e.now+e.unit)}}}).scrollTop=nt.propHooks.scrollLeft={set:function(e){e.elem.nodeType&&e.elem.parentNode&&(e.elem[e.prop]=e.now)}},k.easing={linear:function(e){return e},swing:function(e){return.5-Math.cos(e*Math.PI)/2},_default:"swing"},k.fx=nt.prototype.init,k.fx.step={};var rt,it,ot,at,st=/^(?:toggle|show|hide)$/,ut=/queueHooks$/;function lt(){it&&(!1===E.hidden&&C.requestAnimationFrame?C.requestAnimationFrame(lt):C.setTimeout(lt,k.fx.interval),k.fx.tick())}function ct(){return C.setTimeout(function(){rt=void 0}),rt=Date.now()}function ft(e,t){var n,r=0,i={height:e};for(t=t?1:0;r<4;r+=2-t)i["margin"+(n=re[r])]=i["padding"+n]=e;return t&&(i.opacity=i.width=e),i}function pt(e,t,n){for(var r,i=(dt.tweeners[t]||[]).concat(dt.tweeners["*"]),o=0,a=i.length;o<a;o++)if(r=i[o].call(n,t,e))return r}function dt(o,e,t){var n,a,r=0,i=dt.prefilters.length,s=k.Deferred().always(function(){delete u.elem}),u=function(){if(a)return!1;for(var e=rt||ct(),t=Math.max(0,l.startTime+l.duration-e),n=1-(t/l.duration||0),r=0,i=l.tweens.length;r<i;r++)l.tweens[r].run(n);return s.notifyWith(o,[l,n,t]),n<1&&i?t:(i||s.notifyWith(o,[l,1,0]),s.resolveWith(o,[l]),!1)},l=s.promise({elem:o,props:k.extend({},e),opts:k.extend(!0,{specialEasing:{},easing:k.easing._default},t),originalProperties:e,originalOptions:t,startTime:rt||ct(),duration:t.duration,tweens:[],createTween:function(e,t){var n=k.Tween(o,l.opts,e,t,l.opts.specialEasing[e]||l.opts.easing);return l.tweens.push(n),n},stop:function(e){var t=0,n=e?l.tweens.length:0;if(a)return this;for(a=!0;t<n;t++)l.tweens[t].run(1);return e?(s.notifyWith(o,[l,1,0]),s.resolveWith(o,[l,e])):s.rejectWith(o,[l,e]),this}}),c=l.props;for(!function(e,t){var n,r,i,o,a;for(n in e)if(i=t[r=V(n)],o=e[n],Array.isArray(o)&&(i=o[1],o=e[n]=o[0]),n!==r&&(e[r]=o,delete e[n]),(a=k.cssHooks[r])&&"expand"in a)for(n in o=a.expand(o),delete e[r],o)n in e||(e[n]=o[n],t[n]=i);else t[r]=i}(c,l.opts.specialEasing);r<i;r++)if(n=dt.prefilters[r].call(l,o,c,l.opts))return m(n.stop)&&(k._queueHooks(l.elem,l.opts.queue).stop=n.stop.bind(n)),n;return k.map(c,pt,l),m(l.opts.start)&&l.opts.start.call(o,l),l.progress(l.opts.progress).done(l.opts.done,l.opts.complete).fail(l.opts.fail).always(l.opts.always),k.fx.timer(k.extend(u,{elem:o,anim:l,queue:l.opts.queue})),l}k.Animation=k.extend(dt,{tweeners:{"*":[function(e,t){var n=this.createTween(e,t);return le(n.elem,e,ne.exec(t),n),n}]},tweener:function(e,t){m(e)?(t=e,e=["*"]):e=e.match(R);for(var n,r=0,i=e.length;r<i;r++)n=e[r],dt.tweeners[n]=dt.tweeners[n]||[],dt.tweeners[n].unshift(t)},prefilters:[function(e,t,n){var r,i,o,a,s,u,l,c,f="width"in t||"height"in t,p=this,d={},h=e.style,g=e.nodeType&&se(e),v=Q.get(e,"fxshow");for(r in n.queue||(null==(a=k._queueHooks(e,"fx")).unqueued&&(a.unqueued=0,s=a.empty.fire,a.empty.fire=function(){a.unqueued||s()}),a.unqueued++,p.always(function(){p.always(function(){a.unqueued--,k.queue(e,"fx").length||a.empty.fire()})})),t)if(i=t[r],st.test(i)){if(delete t[r],o=o||"toggle"===i,i===(g?"hide":"show")){if("show"!==i||!v||void 0===v[r])continue;g=!0}d[r]=v&&v[r]||k.style(e,r)}if((u=!k.isEmptyObject(t))||!k.isEmptyObject(d))for(r in f&&1===e.nodeType&&(n.overflow=[h.overflow,h.overflowX,h.overflowY],null==(l=v&&v.display)&&(l=Q.get(e,"display")),"none"===(c=k.css(e,"display"))&&(l?c=l:(fe([e],!0),l=e.style.display||l,c=k.css(e,"display"),fe([e]))),("inline"===c||"inline-block"===c&&null!=l)&&"none"===k.css(e,"float")&&(u||(p.done(function(){h.display=l}),null==l&&(c=h.display,l="none"===c?"":c)),h.display="inline-block")),n.overflow&&(h.overflow="hidden",p.always(function(){h.overflow=n.overflow[0],h.overflowX=n.overflow[1],h.overflowY=n.overflow[2]})),u=!1,d)u||(v?"hidden"in v&&(g=v.hidden):v=Q.access(e,"fxshow",{display:l}),o&&(v.hidden=!g),g&&fe([e],!0),p.done(function(){for(r in g||fe([e]),Q.remove(e,"fxshow"),d)k.style(e,r,d[r])})),u=pt(g?v[r]:0,r,p),r in v||(v[r]=u.start,g&&(u.end=u.start,u.start=0))}],prefilter:function(e,t){t?dt.prefilters.unshift(e):dt.prefilters.push(e)}}),k.speed=function(e,t,n){var r=e&&"object"==typeof e?k.extend({},e):{complete:n||!n&&t||m(e)&&e,duration:e,easing:n&&t||t&&!m(t)&&t};return k.fx.off?r.duration=0:"number"!=typeof r.duration&&(r.duration in k.fx.speeds?r.duration=k.fx.speeds[r.duration]:r.duration=k.fx.speeds._default),null!=r.queue&&!0!==r.queue||(r.queue="fx"),r.old=r.complete,r.complete=function(){m(r.old)&&r.old.call(this),r.queue&&k.dequeue(this,r.queue)},r},k.fn.extend({fadeTo:function(e,t,n,r){return this.filter(se).css("opacity",0).show().end().animate({opacity:t},e,n,r)},animate:function(t,e,n,r){var i=k.isEmptyObject(t),o=k.speed(e,n,r),a=function(){var e=dt(this,k.extend({},t),o);(i||Q.get(this,"finish"))&&e.stop(!0)};return a.finish=a,i||!1===o.queue?this.each(a):this.queue(o.queue,a)},stop:function(i,e,o){var a=function(e){var t=e.stop;delete e.stop,t(o)};return"string"!=typeof i&&(o=e,e=i,i=void 0),e&&!1!==i&&this.queue(i||"fx",[]),this.each(function(){var e=!0,t=null!=i&&i+"queueHooks",n=k.timers,r=Q.get(this);if(t)r[t]&&r[t].stop&&a(r[t]);else for(t in r)r[t]&&r[t].stop&&ut.test(t)&&a(r[t]);for(t=n.length;t--;)n[t].elem!==this||null!=i&&n[t].queue!==i||(n[t].anim.stop(o),e=!1,n.splice(t,1));!e&&o||k.dequeue(this,i)})},finish:function(a){return!1!==a&&(a=a||"fx"),this.each(function(){var e,t=Q.get(this),n=t[a+"queue"],r=t[a+"queueHooks"],i=k.timers,o=n?n.length:0;for(t.finish=!0,k.queue(this,a,[]),r&&r.stop&&r.stop.call(this,!0),e=i.length;e--;)i[e].elem===this&&i[e].queue===a&&(i[e].anim.stop(!0),i.splice(e,1));for(e=0;e<o;e++)n[e]&&n[e].finish&&n[e].finish.call(this);delete t.finish})}}),k.each(["toggle","show","hide"],function(e,r){var i=k.fn[r];k.fn[r]=function(e,t,n){return null==e||"boolean"==typeof e?i.apply(this,arguments):this.animate(ft(r,!0),e,t,n)}}),k.each({slideDown:ft("show"),slideUp:ft("hide"),slideToggle:ft("toggle"),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(e,r){k.fn[e]=function(e,t,n){return this.animate(r,e,t,n)}}),k.timers=[],k.fx.tick=function(){var e,t=0,n=k.timers;for(rt=Date.now();t<n.length;t++)(e=n[t])()||n[t]!==e||n.splice(t--,1);n.length||k.fx.stop(),rt=void 0},k.fx.timer=function(e){k.timers.push(e),k.fx.start()},k.fx.interval=13,k.fx.start=function(){it||(it=!0,lt())},k.fx.stop=function(){it=null},k.fx.speeds={slow:600,fast:200,_default:400},k.fn.delay=function(r,e){return r=k.fx&&k.fx.speeds[r]||r,e=e||"fx",this.queue(e,function(e,t){var n=C.setTimeout(e,r);t.stop=function(){C.clearTimeout(n)}})},ot=E.createElement("input"),at=E.createElement("select").appendChild(E.createElement("option")),ot.type="checkbox",y.checkOn=""!==ot.value,y.optSelected=at.selected,(ot=E.createElement("input")).value="t",ot.type="radio",y.radioValue="t"===ot.value;var ht,gt=k.expr.attrHandle;k.fn.extend({attr:function(e,t){return _(this,k.attr,e,t,1<arguments.length)},removeAttr:function(e){return this.each(function(){k.removeAttr(this,e)})}}),k.extend({attr:function(e,t,n){var r,i,o=e.nodeType;if(3!==o&&8!==o&&2!==o)return"undefined"==typeof e.getAttribute?k.prop(e,t,n):(1===o&&k.isXMLDoc(e)||(i=k.attrHooks[t.toLowerCase()]||(k.expr.match.bool.test(t)?ht:void 0)),void 0!==n?null===n?void k.removeAttr(e,t):i&&"set"in i&&void 0!==(r=i.set(e,n,t))?r:(e.setAttribute(t,n+""),n):i&&"get"in i&&null!==(r=i.get(e,t))?r:null==(r=k.find.attr(e,t))?void 0:r)},attrHooks:{type:{set:function(e,t){if(!y.radioValue&&"radio"===t&&A(e,"input")){var n=e.value;return e.setAttribute("type",t),n&&(e.value=n),t}}}},removeAttr:function(e,t){var n,r=0,i=t&&t.match(R);if(i&&1===e.nodeType)while(n=i[r++])e.removeAttribute(n)}}),ht={set:function(e,t,n){return!1===t?k.removeAttr(e,n):e.setAttribute(n,n),n}},k.each(k.expr.match.bool.source.match(/\w+/g),function(e,t){var a=gt[t]||k.find.attr;gt[t]=function(e,t,n){var r,i,o=t.toLowerCase();return n||(i=gt[o],gt[o]=r,r=null!=a(e,t,n)?o:null,gt[o]=i),r}});var vt=/^(?:input|select|textarea|button)$/i,yt=/^(?:a|area)$/i;function mt(e){return(e.match(R)||[]).join(" ")}function xt(e){return e.getAttribute&&e.getAttribute("class")||""}function bt(e){return Array.isArray(e)?e:"string"==typeof e&&e.match(R)||[]}k.fn.extend({prop:function(e,t){return _(this,k.prop,e,t,1<arguments.length)},removeProp:function(e){return this.each(function(){delete this[k.propFix[e]||e]})}}),k.extend({prop:function(e,t,n){var r,i,o=e.nodeType;if(3!==o&&8!==o&&2!==o)return 1===o&&k.isXMLDoc(e)||(t=k.propFix[t]||t,i=k.propHooks[t]),void 0!==n?i&&"set"in i&&void 0!==(r=i.set(e,n,t))?r:e[t]=n:i&&"get"in i&&null!==(r=i.get(e,t))?r:e[t]},propHooks:{tabIndex:{get:function(e){var t=k.find.attr(e,"tabindex");return t?parseInt(t,10):vt.test(e.nodeName)||yt.test(e.nodeName)&&e.href?0:-1}}},propFix:{"for":"htmlFor","class":"className"}}),y.optSelected||(k.propHooks.selected={get:function(e){var t=e.parentNode;return t&&t.parentNode&&t.parentNode.selectedIndex,null},set:function(e){var t=e.parentNode;t&&(t.selectedIndex,t.parentNode&&t.parentNode.selectedIndex)}}),k.each(["tabIndex","readOnly","maxLength","cellSpacing","cellPadding","rowSpan","colSpan","useMap","frameBorder","contentEditable"],function(){k.propFix[this.toLowerCase()]=this}),k.fn.extend({addClass:function(t){var e,n,r,i,o,a,s,u=0;if(m(t))return this.each(function(e){k(this).addClass(t.call(this,e,xt(this)))});if((e=bt(t)).length)while(n=this[u++])if(i=xt(n),r=1===n.nodeType&&" "+mt(i)+" "){a=0;while(o=e[a++])r.indexOf(" "+o+" ")<0&&(r+=o+" ");i!==(s=mt(r))&&n.setAttribute("class",s)}return this},removeClass:function(t){var e,n,r,i,o,a,s,u=0;if(m(t))return this.each(function(e){k(this).removeClass(t.call(this,e,xt(this)))});if(!arguments.length)return this.attr("class","");if((e=bt(t)).length)while(n=this[u++])if(i=xt(n),r=1===n.nodeType&&" "+mt(i)+" "){a=0;while(o=e[a++])while(-1<r.indexOf(" "+o+" "))r=r.replace(" "+o+" "," ");i!==(s=mt(r))&&n.setAttribute("class",s)}return this},toggleClass:function(i,t){var o=typeof i,a="string"===o||Array.isArray(i);return"boolean"==typeof t&&a?t?this.addClass(i):this.removeClass(i):m(i)?this.each(function(e){k(this).toggleClass(i.call(this,e,xt(this),t),t)}):this.each(function(){var e,t,n,r;if(a){t=0,n=k(this),r=bt(i);while(e=r[t++])n.hasClass(e)?n.removeClass(e):n.addClass(e)}else void 0!==i&&"boolean"!==o||((e=xt(this))&&Q.set(this,"__className__",e),this.setAttribute&&this.setAttribute("class",e||!1===i?"":Q.get(this,"__className__")||""))})},hasClass:function(e){var t,n,r=0;t=" "+e+" ";while(n=this[r++])if(1===n.nodeType&&-1<(" "+mt(xt(n))+" ").indexOf(t))return!0;return!1}});var wt=/\r/g;k.fn.extend({val:function(n){var r,e,i,t=this[0];return arguments.length?(i=m(n),this.each(function(e){var t;1===this.nodeType&&(null==(t=i?n.call(this,e,k(this).val()):n)?t="":"number"==typeof t?t+="":Array.isArray(t)&&(t=k.map(t,function(e){return null==e?"":e+""})),(r=k.valHooks[this.type]||k.valHooks[this.nodeName.toLowerCase()])&&"set"in r&&void 0!==r.set(this,t,"value")||(this.value=t))})):t?(r=k.valHooks[t.type]||k.valHooks[t.nodeName.toLowerCase()])&&"get"in r&&void 0!==(e=r.get(t,"value"))?e:"string"==typeof(e=t.value)?e.replace(wt,""):null==e?"":e:void 0}}),k.extend({valHooks:{option:{get:function(e){var t=k.find.attr(e,"value");return null!=t?t:mt(k.text(e))}},select:{get:function(e){var t,n,r,i=e.options,o=e.selectedIndex,a="select-one"===e.type,s=a?null:[],u=a?o+1:i.length;for(r=o<0?u:a?o:0;r<u;r++)if(((n=i[r]).selected||r===o)&&!n.disabled&&(!n.parentNode.disabled||!A(n.parentNode,"optgroup"))){if(t=k(n).val(),a)return t;s.push(t)}return s},set:function(e,t){var n,r,i=e.options,o=k.makeArray(t),a=i.length;while(a--)((r=i[a]).selected=-1<k.inArray(k.valHooks.option.get(r),o))&&(n=!0);return n||(e.selectedIndex=-1),o}}}}),k.each(["radio","checkbox"],function(){k.valHooks[this]={set:function(e,t){if(Array.isArray(t))return e.checked=-1<k.inArray(k(e).val(),t)}},y.checkOn||(k.valHooks[this].get=function(e){return null===e.getAttribute("value")?"on":e.value})}),y.focusin="onfocusin"in C;var Tt=/^(?:focusinfocus|focusoutblur)$/,Ct=function(e){e.stopPropagation()};k.extend(k.event,{trigger:function(e,t,n,r){var i,o,a,s,u,l,c,f,p=[n||E],d=v.call(e,"type")?e.type:e,h=v.call(e,"namespace")?e.namespace.split("."):[];if(o=f=a=n=n||E,3!==n.nodeType&&8!==n.nodeType&&!Tt.test(d+k.event.triggered)&&(-1<d.indexOf(".")&&(d=(h=d.split(".")).shift(),h.sort()),u=d.indexOf(":")<0&&"on"+d,(e=e[k.expando]?e:new k.Event(d,"object"==typeof e&&e)).isTrigger=r?2:3,e.namespace=h.join("."),e.rnamespace=e.namespace?new RegExp("(^|\\.)"+h.join("\\.(?:.*\\.|)")+"(\\.|$)"):null,e.result=void 0,e.target||(e.target=n),t=null==t?[e]:k.makeArray(t,[e]),c=k.event.special[d]||{},r||!c.trigger||!1!==c.trigger.apply(n,t))){if(!r&&!c.noBubble&&!x(n)){for(s=c.delegateType||d,Tt.test(s+d)||(o=o.parentNode);o;o=o.parentNode)p.push(o),a=o;a===(n.ownerDocument||E)&&p.push(a.defaultView||a.parentWindow||C)}i=0;while((o=p[i++])&&!e.isPropagationStopped())f=o,e.type=1<i?s:c.bindType||d,(l=(Q.get(o,"events")||{})[e.type]&&Q.get(o,"handle"))&&l.apply(o,t),(l=u&&o[u])&&l.apply&&G(o)&&(e.result=l.apply(o,t),!1===e.result&&e.preventDefault());return e.type=d,r||e.isDefaultPrevented()||c._default&&!1!==c._default.apply(p.pop(),t)||!G(n)||u&&m(n[d])&&!x(n)&&((a=n[u])&&(n[u]=null),k.event.triggered=d,e.isPropagationStopped()&&f.addEventListener(d,Ct),n[d](),e.isPropagationStopped()&&f.removeEventListener(d,Ct),k.event.triggered=void 0,a&&(n[u]=a)),e.result}},simulate:function(e,t,n){var r=k.extend(new k.Event,n,{type:e,isSimulated:!0});k.event.trigger(r,null,t)}}),k.fn.extend({trigger:function(e,t){return this.each(function(){k.event.trigger(e,t,this)})},triggerHandler:function(e,t){var n=this[0];if(n)return k.event.trigger(e,t,n,!0)}}),y.focusin||k.each({focus:"focusin",blur:"focusout"},function(n,r){var i=function(e){k.event.simulate(r,e.target,k.event.fix(e))};k.event.special[r]={setup:function(){var e=this.ownerDocument||this,t=Q.access(e,r);t||e.addEventListener(n,i,!0),Q.access(e,r,(t||0)+1)},teardown:function(){var e=this.ownerDocument||this,t=Q.access(e,r)-1;t?Q.access(e,r,t):(e.removeEventListener(n,i,!0),Q.remove(e,r))}}});var Et=C.location,kt=Date.now(),St=/\?/;k.parseXML=function(e){var t;if(!e||"string"!=typeof e)return null;try{t=(new C.DOMParser).parseFromString(e,"text/xml")}catch(e){t=void 0}return t&&!t.getElementsByTagName("parsererror").length||k.error("Invalid XML: "+e),t};var Nt=/\[\]$/,At=/\r?\n/g,Dt=/^(?:submit|button|image|reset|file)$/i,jt=/^(?:input|select|textarea|keygen)/i;function qt(n,e,r,i){var t;if(Array.isArray(e))k.each(e,function(e,t){r||Nt.test(n)?i(n,t):qt(n+"["+("object"==typeof t&&null!=t?e:"")+"]",t,r,i)});else if(r||"object"!==w(e))i(n,e);else for(t in e)qt(n+"["+t+"]",e[t],r,i)}k.param=function(e,t){var n,r=[],i=function(e,t){var n=m(t)?t():t;r[r.length]=encodeURIComponent(e)+"="+encodeURIComponent(null==n?"":n)};if(null==e)return"";if(Array.isArray(e)||e.jquery&&!k.isPlainObject(e))k.each(e,function(){i(this.name,this.value)});else for(n in e)qt(n,e[n],t,i);return r.join("&")},k.fn.extend({serialize:function(){return k.param(this.serializeArray())},serializeArray:function(){return this.map(function(){var e=k.prop(this,"elements");return e?k.makeArray(e):this}).filter(function(){var e=this.type;return this.name&&!k(this).is(":disabled")&&jt.test(this.nodeName)&&!Dt.test(e)&&(this.checked||!pe.test(e))}).map(function(e,t){var n=k(this).val();return null==n?null:Array.isArray(n)?k.map(n,function(e){return{name:t.name,value:e.replace(At,"\r\n")}}):{name:t.name,value:n.replace(At,"\r\n")}}).get()}});var Lt=/%20/g,Ht=/#.*$/,Ot=/([?&])_=[^&]*/,Pt=/^(.*?):[ \t]*([^\r\n]*)$/gm,Rt=/^(?:GET|HEAD)$/,Mt=/^\/\//,It={},Wt={},$t="*/".concat("*"),Ft=E.createElement("a");function Bt(o){return function(e,t){"string"!=typeof e&&(t=e,e="*");var n,r=0,i=e.toLowerCase().match(R)||[];if(m(t))while(n=i[r++])"+"===n[0]?(n=n.slice(1)||"*",(o[n]=o[n]||[]).unshift(t)):(o[n]=o[n]||[]).push(t)}}function _t(t,i,o,a){var s={},u=t===Wt;function l(e){var r;return s[e]=!0,k.each(t[e]||[],function(e,t){var n=t(i,o,a);return"string"!=typeof n||u||s[n]?u?!(r=n):void 0:(i.dataTypes.unshift(n),l(n),!1)}),r}return l(i.dataTypes[0])||!s["*"]&&l("*")}function zt(e,t){var n,r,i=k.ajaxSettings.flatOptions||{};for(n in t)void 0!==t[n]&&((i[n]?e:r||(r={}))[n]=t[n]);return r&&k.extend(!0,e,r),e}Ft.href=Et.href,k.extend({active:0,lastModified:{},etag:{},ajaxSettings:{url:Et.href,type:"GET",isLocal:/^(?:about|app|app-storage|.+-extension|file|res|widget):$/.test(Et.protocol),global:!0,processData:!0,async:!0,contentType:"application/x-www-form-urlencoded; charset=UTF-8",accepts:{"*":$t,text:"text/plain",html:"text/html",xml:"application/xml, text/xml",json:"application/json, text/javascript"},contents:{xml:/\bxml\b/,html:/\bhtml/,json:/\bjson\b/},responseFields:{xml:"responseXML",text:"responseText",json:"responseJSON"},converters:{"* text":String,"text html":!0,"text json":JSON.parse,"text xml":k.parseXML},flatOptions:{url:!0,context:!0}},ajaxSetup:function(e,t){return t?zt(zt(e,k.ajaxSettings),t):zt(k.ajaxSettings,e)},ajaxPrefilter:Bt(It),ajaxTransport:Bt(Wt),ajax:function(e,t){"object"==typeof e&&(t=e,e=void 0),t=t||{};var c,f,p,n,d,r,h,g,i,o,v=k.ajaxSetup({},t),y=v.context||v,m=v.context&&(y.nodeType||y.jquery)?k(y):k.event,x=k.Deferred(),b=k.Callbacks("once memory"),w=v.statusCode||{},a={},s={},u="canceled",T={readyState:0,getResponseHeader:function(e){var t;if(h){if(!n){n={};while(t=Pt.exec(p))n[t[1].toLowerCase()+" "]=(n[t[1].toLowerCase()+" "]||[]).concat(t[2])}t=n[e.toLowerCase()+" "]}return null==t?null:t.join(", ")},getAllResponseHeaders:function(){return h?p:null},setRequestHeader:function(e,t){return null==h&&(e=s[e.toLowerCase()]=s[e.toLowerCase()]||e,a[e]=t),this},overrideMimeType:function(e){return null==h&&(v.mimeType=e),this},statusCode:function(e){var t;if(e)if(h)T.always(e[T.status]);else for(t in e)w[t]=[w[t],e[t]];return this},abort:function(e){var t=e||u;return c&&c.abort(t),l(0,t),this}};if(x.promise(T),v.url=((e||v.url||Et.href)+"").replace(Mt,Et.protocol+"//"),v.type=t.method||t.type||v.method||v.type,v.dataTypes=(v.dataType||"*").toLowerCase().match(R)||[""],null==v.crossDomain){r=E.createElement("a");try{r.href=v.url,r.href=r.href,v.crossDomain=Ft.protocol+"//"+Ft.host!=r.protocol+"//"+r.host}catch(e){v.crossDomain=!0}}if(v.data&&v.processData&&"string"!=typeof v.data&&(v.data=k.param(v.data,v.traditional)),_t(It,v,t,T),h)return T;for(i in(g=k.event&&v.global)&&0==k.active++&&k.event.trigger("ajaxStart"),v.type=v.type.toUpperCase(),v.hasContent=!Rt.test(v.type),f=v.url.replace(Ht,""),v.hasContent?v.data&&v.processData&&0===(v.contentType||"").indexOf("application/x-www-form-urlencoded")&&(v.data=v.data.replace(Lt,"+")):(o=v.url.slice(f.length),v.data&&(v.processData||"string"==typeof v.data)&&(f+=(St.test(f)?"&":"?")+v.data,delete v.data),!1===v.cache&&(f=f.replace(Ot,"$1"),o=(St.test(f)?"&":"?")+"_="+kt+++o),v.url=f+o),v.ifModified&&(k.lastModified[f]&&T.setRequestHeader("If-Modified-Since",k.lastModified[f]),k.etag[f]&&T.setRequestHeader("If-None-Match",k.etag[f])),(v.data&&v.hasContent&&!1!==v.contentType||t.contentType)&&T.setRequestHeader("Content-Type",v.contentType),T.setRequestHeader("Accept",v.dataTypes[0]&&v.accepts[v.dataTypes[0]]?v.accepts[v.dataTypes[0]]+("*"!==v.dataTypes[0]?", "+$t+"; q=0.01":""):v.accepts["*"]),v.headers)T.setRequestHeader(i,v.headers[i]);if(v.beforeSend&&(!1===v.beforeSend.call(y,T,v)||h))return T.abort();if(u="abort",b.add(v.complete),T.done(v.success),T.fail(v.error),c=_t(Wt,v,t,T)){if(T.readyState=1,g&&m.trigger("ajaxSend",[T,v]),h)return T;v.async&&0<v.timeout&&(d=C.setTimeout(function(){T.abort("timeout")},v.timeout));try{h=!1,c.send(a,l)}catch(e){if(h)throw e;l(-1,e)}}else l(-1,"No Transport");function l(e,t,n,r){var i,o,a,s,u,l=t;h||(h=!0,d&&C.clearTimeout(d),c=void 0,p=r||"",T.readyState=0<e?4:0,i=200<=e&&e<300||304===e,n&&(s=function(e,t,n){var r,i,o,a,s=e.contents,u=e.dataTypes;while("*"===u[0])u.shift(),void 0===r&&(r=e.mimeType||t.getResponseHeader("Content-Type"));if(r)for(i in s)if(s[i]&&s[i].test(r)){u.unshift(i);break}if(u[0]in n)o=u[0];else{for(i in n){if(!u[0]||e.converters[i+" "+u[0]]){o=i;break}a||(a=i)}o=o||a}if(o)return o!==u[0]&&u.unshift(o),n[o]}(v,T,n)),s=function(e,t,n,r){var i,o,a,s,u,l={},c=e.dataTypes.slice();if(c[1])for(a in e.converters)l[a.toLowerCase()]=e.converters[a];o=c.shift();while(o)if(e.responseFields[o]&&(n[e.responseFields[o]]=t),!u&&r&&e.dataFilter&&(t=e.dataFilter(t,e.dataType)),u=o,o=c.shift())if("*"===o)o=u;else if("*"!==u&&u!==o){if(!(a=l[u+" "+o]||l["* "+o]))for(i in l)if((s=i.split(" "))[1]===o&&(a=l[u+" "+s[0]]||l["* "+s[0]])){!0===a?a=l[i]:!0!==l[i]&&(o=s[0],c.unshift(s[1]));break}if(!0!==a)if(a&&e["throws"])t=a(t);else try{t=a(t)}catch(e){return{state:"parsererror",error:a?e:"No conversion from "+u+" to "+o}}}return{state:"success",data:t}}(v,s,T,i),i?(v.ifModified&&((u=T.getResponseHeader("Last-Modified"))&&(k.lastModified[f]=u),(u=T.getResponseHeader("etag"))&&(k.etag[f]=u)),204===e||"HEAD"===v.type?l="nocontent":304===e?l="notmodified":(l=s.state,o=s.data,i=!(a=s.error))):(a=l,!e&&l||(l="error",e<0&&(e=0))),T.status=e,T.statusText=(t||l)+"",i?x.resolveWith(y,[o,l,T]):x.rejectWith(y,[T,l,a]),T.statusCode(w),w=void 0,g&&m.trigger(i?"ajaxSuccess":"ajaxError",[T,v,i?o:a]),b.fireWith(y,[T,l]),g&&(m.trigger("ajaxComplete",[T,v]),--k.active||k.event.trigger("ajaxStop")))}return T},getJSON:function(e,t,n){return k.get(e,t,n,"json")},getScript:function(e,t){return k.get(e,void 0,t,"script")}}),k.each(["get","post"],function(e,i){k[i]=function(e,t,n,r){return m(t)&&(r=r||n,n=t,t=void 0),k.ajax(k.extend({url:e,type:i,dataType:r,data:t,success:n},k.isPlainObject(e)&&e))}}),k._evalUrl=function(e,t){return k.ajax({url:e,type:"GET",dataType:"script",cache:!0,async:!1,global:!1,converters:{"text script":function(){}},dataFilter:function(e){k.globalEval(e,t)}})},k.fn.extend({wrapAll:function(e){var t;return this[0]&&(m(e)&&(e=e.call(this[0])),t=k(e,this[0].ownerDocument).eq(0).clone(!0),this[0].parentNode&&t.insertBefore(this[0]),t.map(function(){var e=this;while(e.firstElementChild)e=e.firstElementChild;return e}).append(this)),this},wrapInner:function(n){return m(n)?this.each(function(e){k(this).wrapInner(n.call(this,e))}):this.each(function(){var e=k(this),t=e.contents();t.length?t.wrapAll(n):e.append(n)})},wrap:function(t){var n=m(t);return this.each(function(e){k(this).wrapAll(n?t.call(this,e):t)})},unwrap:function(e){return this.parent(e).not("body").each(function(){k(this).replaceWith(this.childNodes)}),this}}),k.expr.pseudos.hidden=function(e){return!k.expr.pseudos.visible(e)},k.expr.pseudos.visible=function(e){return!!(e.offsetWidth||e.offsetHeight||e.getClientRects().length)},k.ajaxSettings.xhr=function(){try{return new C.XMLHttpRequest}catch(e){}};var Ut={0:200,1223:204},Xt=k.ajaxSettings.xhr();y.cors=!!Xt&&"withCredentials"in Xt,y.ajax=Xt=!!Xt,k.ajaxTransport(function(i){var o,a;if(y.cors||Xt&&!i.crossDomain)return{send:function(e,t){var n,r=i.xhr();if(r.open(i.type,i.url,i.async,i.username,i.password),i.xhrFields)for(n in i.xhrFields)r[n]=i.xhrFields[n];for(n in i.mimeType&&r.overrideMimeType&&r.overrideMimeType(i.mimeType),i.crossDomain||e["X-Requested-With"]||(e["X-Requested-With"]="XMLHttpRequest"),e)r.setRequestHeader(n,e[n]);o=function(e){return function(){o&&(o=a=r.onload=r.onerror=r.onabort=r.ontimeout=r.onreadystatechange=null,"abort"===e?r.abort():"error"===e?"number"!=typeof r.status?t(0,"error"):t(r.status,r.statusText):t(Ut[r.status]||r.status,r.statusText,"text"!==(r.responseType||"text")||"string"!=typeof r.responseText?{binary:r.response}:{text:r.responseText},r.getAllResponseHeaders()))}},r.onload=o(),a=r.onerror=r.ontimeout=o("error"),void 0!==r.onabort?r.onabort=a:r.onreadystatechange=function(){4===r.readyState&&C.setTimeout(function(){o&&a()})},o=o("abort");try{r.send(i.hasContent&&i.data||null)}catch(e){if(o)throw e}},abort:function(){o&&o()}}}),k.ajaxPrefilter(function(e){e.crossDomain&&(e.contents.script=!1)}),k.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/\b(?:java|ecma)script\b/},converters:{"text script":function(e){return k.globalEval(e),e}}}),k.ajaxPrefilter("script",function(e){void 0===e.cache&&(e.cache=!1),e.crossDomain&&(e.type="GET")}),k.ajaxTransport("script",function(n){var r,i;if(n.crossDomain||n.scriptAttrs)return{send:function(e,t){r=k("<script>").attr(n.scriptAttrs||{}).prop({charset:n.scriptCharset,src:n.url}).on("load error",i=function(e){r.remove(),i=null,e&&t("error"===e.type?404:200,e.type)}),E.head.appendChild(r[0])},abort:function(){i&&i()}}});var Vt,Gt=[],Yt=/(=)\?(?=&|$)|\?\?/;k.ajaxSetup({jsonp:"callback",jsonpCallback:function(){var e=Gt.pop()||k.expando+"_"+kt++;return this[e]=!0,e}}),k.ajaxPrefilter("json jsonp",function(e,t,n){var r,i,o,a=!1!==e.jsonp&&(Yt.test(e.url)?"url":"string"==typeof e.data&&0===(e.contentType||"").indexOf("application/x-www-form-urlencoded")&&Yt.test(e.data)&&"data");if(a||"jsonp"===e.dataTypes[0])return r=e.jsonpCallback=m(e.jsonpCallback)?e.jsonpCallback():e.jsonpCallback,a?e[a]=e[a].replace(Yt,"$1"+r):!1!==e.jsonp&&(e.url+=(St.test(e.url)?"&":"?")+e.jsonp+"="+r),e.converters["script json"]=function(){return o||k.error(r+" was not called"),o[0]},e.dataTypes[0]="json",i=C[r],C[r]=function(){o=arguments},n.always(function(){void 0===i?k(C).removeProp(r):C[r]=i,e[r]&&(e.jsonpCallback=t.jsonpCallback,Gt.push(r)),o&&m(i)&&i(o[0]),o=i=void 0}),"script"}),y.createHTMLDocument=((Vt=E.implementation.createHTMLDocument("").body).innerHTML="<form></form><form></form>",2===Vt.childNodes.length),k.parseHTML=function(e,t,n){return"string"!=typeof e?[]:("boolean"==typeof t&&(n=t,t=!1),t||(y.createHTMLDocument?((r=(t=E.implementation.createHTMLDocument("")).createElement("base")).href=E.location.href,t.head.appendChild(r)):t=E),o=!n&&[],(i=D.exec(e))?[t.createElement(i[1])]:(i=we([e],t,o),o&&o.length&&k(o).remove(),k.merge([],i.childNodes)));var r,i,o},k.fn.load=function(e,t,n){var r,i,o,a=this,s=e.indexOf(" ");return-1<s&&(r=mt(e.slice(s)),e=e.slice(0,s)),m(t)?(n=t,t=void 0):t&&"object"==typeof t&&(i="POST"),0<a.length&&k.ajax({url:e,type:i||"GET",dataType:"html",data:t}).done(function(e){o=arguments,a.html(r?k("<div>").append(k.parseHTML(e)).find(r):e)}).always(n&&function(e,t){a.each(function(){n.apply(this,o||[e.responseText,t,e])})}),this},k.each(["ajaxStart","ajaxStop","ajaxComplete","ajaxError","ajaxSuccess","ajaxSend"],function(e,t){k.fn[t]=function(e){return this.on(t,e)}}),k.expr.pseudos.animated=function(t){return k.grep(k.timers,function(e){return t===e.elem}).length},k.offset={setOffset:function(e,t,n){var r,i,o,a,s,u,l=k.css(e,"position"),c=k(e),f={};"static"===l&&(e.style.position="relative"),s=c.offset(),o=k.css(e,"top"),u=k.css(e,"left"),("absolute"===l||"fixed"===l)&&-1<(o+u).indexOf("auto")?(a=(r=c.position()).top,i=r.left):(a=parseFloat(o)||0,i=parseFloat(u)||0),m(t)&&(t=t.call(e,n,k.extend({},s))),null!=t.top&&(f.top=t.top-s.top+a),null!=t.left&&(f.left=t.left-s.left+i),"using"in t?t.using.call(e,f):c.css(f)}},k.fn.extend({offset:function(t){if(arguments.length)return void 0===t?this:this.each(function(e){k.offset.setOffset(this,t,e)});var e,n,r=this[0];return r?r.getClientRects().length?(e=r.getBoundingClientRect(),n=r.ownerDocument.defaultView,{top:e.top+n.pageYOffset,left:e.left+n.pageXOffset}):{top:0,left:0}:void 0},position:function(){if(this[0]){var e,t,n,r=this[0],i={top:0,left:0};if("fixed"===k.css(r,"position"))t=r.getBoundingClientRect();else{t=this.offset(),n=r.ownerDocument,e=r.offsetParent||n.documentElement;while(e&&(e===n.body||e===n.documentElement)&&"static"===k.css(e,"position"))e=e.parentNode;e&&e!==r&&1===e.nodeType&&((i=k(e).offset()).top+=k.css(e,"borderTopWidth",!0),i.left+=k.css(e,"borderLeftWidth",!0))}return{top:t.top-i.top-k.css(r,"marginTop",!0),left:t.left-i.left-k.css(r,"marginLeft",!0)}}},offsetParent:function(){return this.map(function(){var e=this.offsetParent;while(e&&"static"===k.css(e,"position"))e=e.offsetParent;return e||ie})}}),k.each({scrollLeft:"pageXOffset",scrollTop:"pageYOffset"},function(t,i){var o="pageYOffset"===i;k.fn[t]=function(e){return _(this,function(e,t,n){var r;if(x(e)?r=e:9===e.nodeType&&(r=e.defaultView),void 0===n)return r?r[i]:e[t];r?r.scrollTo(o?r.pageXOffset:n,o?n:r.pageYOffset):e[t]=n},t,e,arguments.length)}}),k.each(["top","left"],function(e,n){k.cssHooks[n]=ze(y.pixelPosition,function(e,t){if(t)return t=_e(e,n),$e.test(t)?k(e).position()[n]+"px":t})}),k.each({Height:"height",Width:"width"},function(a,s){k.each({padding:"inner"+a,content:s,"":"outer"+a},function(r,o){k.fn[o]=function(e,t){var n=arguments.length&&(r||"boolean"!=typeof e),i=r||(!0===e||!0===t?"margin":"border");return _(this,function(e,t,n){var r;return x(e)?0===o.indexOf("outer")?e["inner"+a]:e.document.documentElement["client"+a]:9===e.nodeType?(r=e.documentElement,Math.max(e.body["scroll"+a],r["scroll"+a],e.body["offset"+a],r["offset"+a],r["client"+a])):void 0===n?k.css(e,t,i):k.style(e,t,n,i)},s,n?e:void 0,n)}})}),k.each("blur focus focusin focusout resize scroll click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup contextmenu".split(" "),function(e,n){k.fn[n]=function(e,t){return 0<arguments.length?this.on(n,null,e,t):this.trigger(n)}}),k.fn.extend({hover:function(e,t){return this.mouseenter(e).mouseleave(t||e)}}),k.fn.extend({bind:function(e,t,n){return this.on(e,null,t,n)},unbind:function(e,t){return this.off(e,null,t)},delegate:function(e,t,n,r){return this.on(t,e,n,r)},undelegate:function(e,t,n){return 1===arguments.length?this.off(e,"**"):this.off(t,e||"**",n)}}),k.proxy=function(e,t){var n,r,i;if("string"==typeof t&&(n=e[t],t=e,e=n),m(e))return r=s.call(arguments,2),(i=function(){return e.apply(t||this,r.concat(s.call(arguments)))}).guid=e.guid=e.guid||k.guid++,i},k.holdReady=function(e){e?k.readyWait++:k.ready(!0)},k.isArray=Array.isArray,k.parseJSON=JSON.parse,k.nodeName=A,k.isFunction=m,k.isWindow=x,k.camelCase=V,k.type=w,k.now=Date.now,k.isNumeric=function(e){var t=k.type(e);return("number"===t||"string"===t)&&!isNaN(e-parseFloat(e))},"function"==typeof define&&define.amd&&define("jquery",[],function(){return k});var Qt=C.jQuery,Jt=C.$;return k.noConflict=function(e){return C.$===k&&(C.$=Jt),e&&C.jQuery===k&&(C.jQuery=Qt),k},e||(C.jQuery=C.$=k),k});

  </script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
  <script type="text/javascript">
    /*!
 * Bootstrap Colorpicker - Bootstrap Colorpicker is a modular color picker plugin for Bootstrap 4.
 * @package bootstrap-colorpicker
 * @version v3.1.2
 * @license MIT
 * @link https://farbelous.github.io/bootstrap-colorpicker/
 * @link https://github.com/farbelous/bootstrap-colorpicker.git
 */
!function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t(require("jquery")):"function"==typeof define&&define.amd?define("bootstrap-colorpicker",["jquery"],t):"object"==typeof exports?exports["bootstrap-colorpicker"]=t(require("jquery")):e["bootstrap-colorpicker"]=t(e.jQuery)}("undefined"!=typeof self?self:this,function(e){return function(e){function t(r){if(o[r])return o[r].exports;var n=o[r]={i:r,l:!1,exports:{}};return e[r].call(n.exports,n,n.exports,t),n.l=!0,n.exports}var o={};return t.m=e,t.c=o,t.d=function(e,o,r){t.o(e,o)||Object.defineProperty(e,o,{configurable:!1,enumerable:!0,get:r})},t.n=function(e){var o=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(o,"a",o),o},t.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},t.p="",t(t.s=7)}([function(t,o){t.exports=e},function(e,t,o){"use strict";function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var n=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),i=o(0),a=function(e){return e&&e.__esModule?e:{default:e}}(i),l=function(){function e(t){var o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};if(r(this,e),this.colorpicker=t,this.options=o,!this.colorpicker.element||!this.colorpicker.element.length)throw new Error("Extension: this.colorpicker.element is not valid");this.colorpicker.element.on("colorpickerCreate.colorpicker-ext",a.default.proxy(this.onCreate,this)),this.colorpicker.element.on("colorpickerDestroy.colorpicker-ext",a.default.proxy(this.onDestroy,this)),this.colorpicker.element.on("colorpickerUpdate.colorpicker-ext",a.default.proxy(this.onUpdate,this)),this.colorpicker.element.on("colorpickerChange.colorpicker-ext",a.default.proxy(this.onChange,this)),this.colorpicker.element.on("colorpickerInvalid.colorpicker-ext",a.default.proxy(this.onInvalid,this)),this.colorpicker.element.on("colorpickerShow.colorpicker-ext",a.default.proxy(this.onShow,this)),this.colorpicker.element.on("colorpickerHide.colorpicker-ext",a.default.proxy(this.onHide,this)),this.colorpicker.element.on("colorpickerEnable.colorpicker-ext",a.default.proxy(this.onEnable,this)),this.colorpicker.element.on("colorpickerDisable.colorpicker-ext",a.default.proxy(this.onDisable,this))}return n(e,[{key:"resolveColor",value:function(e){!(arguments.length>1&&void 0!==arguments[1])||arguments[1];return!1}},{key:"onCreate",value:function(e){}},{key:"onDestroy",value:function(e){this.colorpicker.element.off(".colorpicker-ext")}},{key:"onUpdate",value:function(e){}},{key:"onChange",value:function(e){}},{key:"onInvalid",value:function(e){}},{key:"onHide",value:function(e){}},{key:"onShow",value:function(e){}},{key:"onDisable",value:function(e){}},{key:"onEnable",value:function(e){}}]),e}();t.default=l},function(e,t,o){"use strict";function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0}),t.ColorItem=t.HSVAColor=void 0;var n=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),i=o(16),a=function(e){return e&&e.__esModule?e:{default:e}}(i),l=function(){function e(t,o,n,i){r(this,e),this.h=isNaN(t)?0:t,this.s=isNaN(o)?0:o,this.v=isNaN(n)?0:n,this.a=isNaN(t)?1:i}return n(e,[{key:"toString",value:function(){return this.h+", "+this.s+"%, "+this.v+"%, "+this.a}}]),e}(),s=function(){function e(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null,o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;r(this,e),this.replace(t,o)}return n(e,[{key:"api",value:function(t){for(var o=arguments.length,r=Array(o>1?o-1:0),n=1;n<o;n++)r[n-1]=arguments[n];if(0===arguments.length)return this._color;var i=this._color[t].apply(this._color,r);return i instanceof a.default?new e(i,this.format):i}},{key:"original",get:function(){return this._original}}],[{key:"HSVAColor",get:function(){return l}}]),n(e,[{key:"replace",value:function(t){var o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null;if(o=e.sanitizeFormat(o),this._original={color:t,format:o,valid:!0},this._color=e.parse(t),null===this._color)return this._color=(0,a.default)(),void(this._original.valid=!1);this._format=o||(e.isHex(t)?"hex":this._color.model)}},{key:"isValid",value:function(){return!0===this._original.valid}},{key:"setHueRatio",value:function(e){this.hue=360*(1-e)}},{key:"setSaturationRatio",value:function(e){this.saturation=100*e}},{key:"setValueRatio",value:function(e){this.value=100*(1-e)}},{key:"setAlphaRatio",value:function(e){this.alpha=1-e}},{key:"isDesaturated",value:function(){return 0===this.saturation}},{key:"isTransparent",value:function(){return 0===this.alpha}},{key:"hasTransparency",value:function(){return this.hasAlpha()&&this.alpha<1}},{key:"hasAlpha",value:function(){return!isNaN(this.alpha)}},{key:"toObject",value:function(){return new l(this.hue,this.saturation,this.value,this.alpha)}},{key:"toHsva",value:function(){return this.toObject()}},{key:"toHsvaRatio",value:function(){return new l(this.hue/360,this.saturation/100,this.value/100,this.alpha)}},{key:"toString",value:function(){return this.string()}},{key:"string",value:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null;if(!(t=e.sanitizeFormat(t||this.format)))return this._color.round().string();if(void 0===this._color[t])throw new Error("Unsupported color format: '"+t+"'");var o=this._color[t]();return o.round?o.round().string():o}},{key:"equals",value:function(t){return t=t instanceof e?t:new e(t),!(!t.isValid()||!this.isValid())&&(this.hue===t.hue&&this.saturation===t.saturation&&this.value===t.value&&this.alpha===t.alpha)}},{key:"getClone",value:function(){return new e(this._color,this.format)}},{key:"getCloneHueOnly",value:function(){return new e([this.hue,100,100,1],this.format)}},{key:"getCloneOpaque",value:function(){return new e(this._color.alpha(1),this.format)}},{key:"toRgbString",value:function(){return this.string("rgb")}},{key:"toHexString",value:function(){return this.string("hex")}},{key:"toHslString",value:function(){return this.string("hsl")}},{key:"isDark",value:function(){return this._color.isDark()}},{key:"isLight",value:function(){return this._color.isLight()}},{key:"generate",value:function(t){var o=[];if(Array.isArray(t))o=t;else{if(!e.colorFormulas.hasOwnProperty(t))throw new Error("No color formula found with the name '"+t+"'.");o=e.colorFormulas[t]}var r=[],n=this._color,i=this.format;return o.forEach(function(t){var o=[t?(n.hue()+t)%360:n.hue(),n.saturationv(),n.value(),n.alpha()];r.push(new e(o,i))}),r}},{key:"hue",get:function(){return this._color.hue()},set:function(e){this._color=this._color.hue(e)}},{key:"saturation",get:function(){return this._color.saturationv()},set:function(e){this._color=this._color.saturationv(e)}},{key:"value",get:function(){return this._color.value()},set:function(e){this._color=this._color.value(e)}},{key:"alpha",get:function(){var e=this._color.alpha();return isNaN(e)?1:e},set:function(e){this._color=this._color.alpha(Math.round(100*e)/100)}},{key:"format",get:function(){return this._format?this._format:this._color.model},set:function(t){this._format=e.sanitizeFormat(t)}}],[{key:"parse",value:function(t){if(t instanceof a.default)return t;if(t instanceof e)return t._color;var o=null;if(null===(t=t instanceof l?[t.h,t.s,t.v,isNaN(t.a)?1:t.a]:e.sanitizeString(t)))return null;Array.isArray(t)&&(o="hsv");try{return(0,a.default)(t,o)}catch(e){return null}}},{key:"sanitizeString",value:function(e){return"string"==typeof e||e instanceof String?e.match(/^[0-9a-f]{2,}$/i)?"#"+e:"transparent"===e.toLowerCase()?"#FFFFFF00":e:e}},{key:"isHex",value:function(e){return("string"==typeof e||e instanceof String)&&!!e.match(/^#?[0-9a-f]{2,}$/i)}},{key:"sanitizeFormat",value:function(e){switch(e){case"hex":case"hex3":case"hex4":case"hex6":case"hex8":return"hex";case"rgb":case"rgba":case"keyword":case"name":return"rgb";case"hsl":case"hsla":case"hsv":case"hsva":case"hwb":case"hwba":return"hsl";default:return""}}}]),e}();s.colorFormulas={complementary:[180],triad:[0,120,240],tetrad:[0,90,180,270],splitcomplement:[0,72,216]},t.default=s,t.HSVAColor=l,t.ColorItem=s},function(e,t,o){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var r={bar_size_short:16,base_margin:6,columns:6},n=r.bar_size_short*r.columns+r.base_margin*(r.columns-1);t.default={customClass:null,color:!1,fallbackColor:!1,format:"auto",horizontal:!1,inline:!1,container:!1,popover:{animation:!0,placement:"bottom",fallbackPlacement:"flip"},debug:!1,input:"input",addon:".colorpicker-input-addon",autoInputFallback:!0,useHashPrefix:!0,useAlpha:!0,template:'<div class="colorpicker">\n      <div class="colorpicker-saturation"><i class="colorpicker-guide"></i></div>\n      <div class="colorpicker-hue"><i class="colorpicker-guide"></i></div>\n      <div class="colorpicker-alpha">\n        <div class="colorpicker-alpha-color"></div>\n        <i class="colorpicker-guide"></i>\n      </div>\n    </div>',extensions:[{name:"preview",options:{showText:!0}}],sliders:{saturation:{selector:".colorpicker-saturation",maxLeft:n,maxTop:n,callLeft:"setSaturationRatio",callTop:"setValueRatio"},hue:{selector:".colorpicker-hue",maxLeft:0,maxTop:n,callLeft:!1,callTop:"setHueRatio"},alpha:{selector:".colorpicker-alpha",childSelector:".colorpicker-alpha-color",maxLeft:0,maxTop:n,callLeft:!1,callTop:"setAlphaRatio"}},slidersHorz:{saturation:{selector:".colorpicker-saturation",maxLeft:n,maxTop:n,callLeft:"setSaturationRatio",callTop:"setValueRatio"},hue:{selector:".colorpicker-hue",maxLeft:n,maxTop:0,callLeft:"setHueRatio",callTop:!1},alpha:{selector:".colorpicker-alpha",childSelector:".colorpicker-alpha-color",maxLeft:n,maxTop:0,callLeft:"setAlphaRatio",callTop:!1}}}},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function i(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var l="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},s=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),c=o(1),u=r(c),h=o(0),p=r(h),f={colors:null,namesAsValues:!0},d=function(e){function t(e){var o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};n(this,t);var r=i(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e,p.default.extend(!0,{},f,o)));return Array.isArray(r.options.colors)||"object"===l(r.options.colors)||(r.options.colors=null),r}return a(t,e),s(t,[{key:"colors",get:function(){return this.options.colors}}]),s(t,[{key:"getLength",value:function(){return this.options.colors?Array.isArray(this.options.colors)?this.options.colors.length:"object"===l(this.options.colors)?Object.keys(this.options.colors).length:0:0}},{key:"resolveColor",value:function(e){var t=!(arguments.length>1&&void 0!==arguments[1])||arguments[1];return!(this.getLength()<=0)&&(Array.isArray(this.options.colors)?this.options.colors.indexOf(e)>=0?e:this.options.colors.indexOf(e.toUpperCase())>=0?e.toUpperCase():this.options.colors.indexOf(e.toLowerCase())>=0&&e.toLowerCase():"object"===l(this.options.colors)&&(!this.options.namesAsValues||t?this.getValue(e,!1):this.getName(e,this.getName("#"+e))))}},{key:"getName",value:function(e){var t=arguments.length>1&&void 0!==arguments[1]&&arguments[1];if("string"!=typeof e||!this.options.colors)return t;for(var o in this.options.colors)if(this.options.colors.hasOwnProperty(o)&&this.options.colors[o].toLowerCase()===e.toLowerCase())return o;return t}},{key:"getValue",value:function(e){var t=arguments.length>1&&void 0!==arguments[1]&&arguments[1];return"string"==typeof e&&this.options.colors&&this.options.colors.hasOwnProperty(e)?this.options.colors[e]:t}}]),t}(u.default);t.default=d},function(e,t,o){"use strict";e.exports={aliceblue:[240,248,255],antiquewhite:[250,235,215],aqua:[0,255,255],aquamarine:[127,255,212],azure:[240,255,255],beige:[245,245,220],bisque:[255,228,196],black:[0,0,0],blanchedalmond:[255,235,205],blue:[0,0,255],blueviolet:[138,43,226],brown:[165,42,42],burlywood:[222,184,135],cadetblue:[95,158,160],chartreuse:[127,255,0],chocolate:[210,105,30],coral:[255,127,80],cornflowerblue:[100,149,237],cornsilk:[255,248,220],crimson:[220,20,60],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgoldenrod:[184,134,11],darkgray:[169,169,169],darkgreen:[0,100,0],darkgrey:[169,169,169],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkseagreen:[143,188,143],darkslateblue:[72,61,139],darkslategray:[47,79,79],darkslategrey:[47,79,79],darkturquoise:[0,206,209],darkviolet:[148,0,211],deeppink:[255,20,147],deepskyblue:[0,191,255],dimgray:[105,105,105],dimgrey:[105,105,105],dodgerblue:[30,144,255],firebrick:[178,34,34],floralwhite:[255,250,240],forestgreen:[34,139,34],fuchsia:[255,0,255],gainsboro:[220,220,220],ghostwhite:[248,248,255],gold:[255,215,0],goldenrod:[218,165,32],gray:[128,128,128],green:[0,128,0],greenyellow:[173,255,47],grey:[128,128,128],honeydew:[240,255,240],hotpink:[255,105,180],indianred:[205,92,92],indigo:[75,0,130],ivory:[255,255,240],khaki:[240,230,140],lavender:[230,230,250],lavenderblush:[255,240,245],lawngreen:[124,252,0],lemonchiffon:[255,250,205],lightblue:[173,216,230],lightcoral:[240,128,128],lightcyan:[224,255,255],lightgoldenrodyellow:[250,250,210],lightgray:[211,211,211],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightsalmon:[255,160,122],lightseagreen:[32,178,170],lightskyblue:[135,206,250],lightslategray:[119,136,153],lightslategrey:[119,136,153],lightsteelblue:[176,196,222],lightyellow:[255,255,224],lime:[0,255,0],limegreen:[50,205,50],linen:[250,240,230],magenta:[255,0,255],maroon:[128,0,0],mediumaquamarine:[102,205,170],mediumblue:[0,0,205],mediumorchid:[186,85,211],mediumpurple:[147,112,219],mediumseagreen:[60,179,113],mediumslateblue:[123,104,238],mediumspringgreen:[0,250,154],mediumturquoise:[72,209,204],mediumvioletred:[199,21,133],midnightblue:[25,25,112],mintcream:[245,255,250],mistyrose:[255,228,225],moccasin:[255,228,181],navajowhite:[255,222,173],navy:[0,0,128],oldlace:[253,245,230],olive:[128,128,0],olivedrab:[107,142,35],orange:[255,165,0],orangered:[255,69,0],orchid:[218,112,214],palegoldenrod:[238,232,170],palegreen:[152,251,152],paleturquoise:[175,238,238],palevioletred:[219,112,147],papayawhip:[255,239,213],peachpuff:[255,218,185],peru:[205,133,63],pink:[255,192,203],plum:[221,160,221],powderblue:[176,224,230],purple:[128,0,128],rebeccapurple:[102,51,153],red:[255,0,0],rosybrown:[188,143,143],royalblue:[65,105,225],saddlebrown:[139,69,19],salmon:[250,128,114],sandybrown:[244,164,96],seagreen:[46,139,87],seashell:[255,245,238],sienna:[160,82,45],silver:[192,192,192],skyblue:[135,206,235],slateblue:[106,90,205],slategray:[112,128,144],slategrey:[112,128,144],snow:[255,250,250],springgreen:[0,255,127],steelblue:[70,130,180],tan:[210,180,140],teal:[0,128,128],thistle:[216,191,216],tomato:[255,99,71],turquoise:[64,224,208],violet:[238,130,238],wheat:[245,222,179],white:[255,255,255],whitesmoke:[245,245,245],yellow:[255,255,0],yellowgreen:[154,205,50]}},function(e,t,o){function r(e,t){return Math.pow(e[0]-t[0],2)+Math.pow(e[1]-t[1],2)+Math.pow(e[2]-t[2],2)}var n=o(5),i={};for(var a in n)n.hasOwnProperty(a)&&(i[n[a]]=a);var l=e.exports={rgb:{channels:3,labels:"rgb"},hsl:{channels:3,labels:"hsl"},hsv:{channels:3,labels:"hsv"},hwb:{channels:3,labels:"hwb"},cmyk:{channels:4,labels:"cmyk"},xyz:{channels:3,labels:"xyz"},lab:{channels:3,labels:"lab"},lch:{channels:3,labels:"lch"},hex:{channels:1,labels:["hex"]},keyword:{channels:1,labels:["keyword"]},ansi16:{channels:1,labels:["ansi16"]},ansi256:{channels:1,labels:["ansi256"]},hcg:{channels:3,labels:["h","c","g"]},apple:{channels:3,labels:["r16","g16","b16"]},gray:{channels:1,labels:["gray"]}};for(var s in l)if(l.hasOwnProperty(s)){if(!("channels"in l[s]))throw new Error("missing channels property: "+s);if(!("labels"in l[s]))throw new Error("missing channel labels property: "+s);if(l[s].labels.length!==l[s].channels)throw new Error("channel and label counts mismatch: "+s);var c=l[s].channels,u=l[s].labels;delete l[s].channels,delete l[s].labels,Object.defineProperty(l[s],"channels",{value:c}),Object.defineProperty(l[s],"labels",{value:u})}l.rgb.hsl=function(e){var t,o,r,n=e[0]/255,i=e[1]/255,a=e[2]/255,l=Math.min(n,i,a),s=Math.max(n,i,a),c=s-l;return s===l?t=0:n===s?t=(i-a)/c:i===s?t=2+(a-n)/c:a===s&&(t=4+(n-i)/c),t=Math.min(60*t,360),t<0&&(t+=360),r=(l+s)/2,o=s===l?0:r<=.5?c/(s+l):c/(2-s-l),[t,100*o,100*r]},l.rgb.hsv=function(e){var t,o,r,n,i,a=e[0]/255,l=e[1]/255,s=e[2]/255,c=Math.max(a,l,s),u=c-Math.min(a,l,s),h=function(e){return(c-e)/6/u+.5};return 0===u?n=i=0:(i=u/c,t=h(a),o=h(l),r=h(s),a===c?n=r-o:l===c?n=1/3+t-r:s===c&&(n=2/3+o-t),n<0?n+=1:n>1&&(n-=1)),[360*n,100*i,100*c]},l.rgb.hwb=function(e){var t=e[0],o=e[1],r=e[2],n=l.rgb.hsl(e)[0],i=1/255*Math.min(t,Math.min(o,r));return r=1-1/255*Math.max(t,Math.max(o,r)),[n,100*i,100*r]},l.rgb.cmyk=function(e){var t,o,r,n,i=e[0]/255,a=e[1]/255,l=e[2]/255;return n=Math.min(1-i,1-a,1-l),t=(1-i-n)/(1-n)||0,o=(1-a-n)/(1-n)||0,r=(1-l-n)/(1-n)||0,[100*t,100*o,100*r,100*n]},l.rgb.keyword=function(e){var t=i[e];if(t)return t;var o,a=1/0;for(var l in n)if(n.hasOwnProperty(l)){var s=n[l],c=r(e,s);c<a&&(a=c,o=l)}return o},l.keyword.rgb=function(e){return n[e]},l.rgb.xyz=function(e){var t=e[0]/255,o=e[1]/255,r=e[2]/255;return t=t>.04045?Math.pow((t+.055)/1.055,2.4):t/12.92,o=o>.04045?Math.pow((o+.055)/1.055,2.4):o/12.92,r=r>.04045?Math.pow((r+.055)/1.055,2.4):r/12.92,[100*(.4124*t+.3576*o+.1805*r),100*(.2126*t+.7152*o+.0722*r),100*(.0193*t+.1192*o+.9505*r)]},l.rgb.lab=function(e){var t,o,r,n=l.rgb.xyz(e),i=n[0],a=n[1],s=n[2];return i/=95.047,a/=100,s/=108.883,i=i>.008856?Math.pow(i,1/3):7.787*i+16/116,a=a>.008856?Math.pow(a,1/3):7.787*a+16/116,s=s>.008856?Math.pow(s,1/3):7.787*s+16/116,t=116*a-16,o=500*(i-a),r=200*(a-s),[t,o,r]},l.hsl.rgb=function(e){var t,o,r,n,i,a=e[0]/360,l=e[1]/100,s=e[2]/100;if(0===l)return i=255*s,[i,i,i];o=s<.5?s*(1+l):s+l-s*l,t=2*s-o,n=[0,0,0];for(var c=0;c<3;c++)r=a+1/3*-(c-1),r<0&&r++,r>1&&r--,i=6*r<1?t+6*(o-t)*r:2*r<1?o:3*r<2?t+(o-t)*(2/3-r)*6:t,n[c]=255*i;return n},l.hsl.hsv=function(e){var t,o,r=e[0],n=e[1]/100,i=e[2]/100,a=n,l=Math.max(i,.01);return i*=2,n*=i<=1?i:2-i,a*=l<=1?l:2-l,o=(i+n)/2,t=0===i?2*a/(l+a):2*n/(i+n),[r,100*t,100*o]},l.hsv.rgb=function(e){var t=e[0]/60,o=e[1]/100,r=e[2]/100,n=Math.floor(t)%6,i=t-Math.floor(t),a=255*r*(1-o),l=255*r*(1-o*i),s=255*r*(1-o*(1-i));switch(r*=255,n){case 0:return[r,s,a];case 1:return[l,r,a];case 2:return[a,r,s];case 3:return[a,l,r];case 4:return[s,a,r];case 5:return[r,a,l]}},l.hsv.hsl=function(e){var t,o,r,n=e[0],i=e[1]/100,a=e[2]/100,l=Math.max(a,.01);return r=(2-i)*a,t=(2-i)*l,o=i*l,o/=t<=1?t:2-t,o=o||0,r/=2,[n,100*o,100*r]},l.hwb.rgb=function(e){var t,o,r,n,i=e[0]/360,a=e[1]/100,l=e[2]/100,s=a+l;s>1&&(a/=s,l/=s),t=Math.floor(6*i),o=1-l,r=6*i-t,0!=(1&t)&&(r=1-r),n=a+r*(o-a);var c,u,h;switch(t){default:case 6:case 0:c=o,u=n,h=a;break;case 1:c=n,u=o,h=a;break;case 2:c=a,u=o,h=n;break;case 3:c=a,u=n,h=o;break;case 4:c=n,u=a,h=o;break;case 5:c=o,u=a,h=n}return[255*c,255*u,255*h]},l.cmyk.rgb=function(e){var t,o,r,n=e[0]/100,i=e[1]/100,a=e[2]/100,l=e[3]/100;return t=1-Math.min(1,n*(1-l)+l),o=1-Math.min(1,i*(1-l)+l),r=1-Math.min(1,a*(1-l)+l),[255*t,255*o,255*r]},l.xyz.rgb=function(e){var t,o,r,n=e[0]/100,i=e[1]/100,a=e[2]/100;return t=3.2406*n+-1.5372*i+-.4986*a,o=-.9689*n+1.8758*i+.0415*a,r=.0557*n+-.204*i+1.057*a,t=t>.0031308?1.055*Math.pow(t,1/2.4)-.055:12.92*t,o=o>.0031308?1.055*Math.pow(o,1/2.4)-.055:12.92*o,r=r>.0031308?1.055*Math.pow(r,1/2.4)-.055:12.92*r,t=Math.min(Math.max(0,t),1),o=Math.min(Math.max(0,o),1),r=Math.min(Math.max(0,r),1),[255*t,255*o,255*r]},l.xyz.lab=function(e){var t,o,r,n=e[0],i=e[1],a=e[2];return n/=95.047,i/=100,a/=108.883,n=n>.008856?Math.pow(n,1/3):7.787*n+16/116,i=i>.008856?Math.pow(i,1/3):7.787*i+16/116,a=a>.008856?Math.pow(a,1/3):7.787*a+16/116,t=116*i-16,o=500*(n-i),r=200*(i-a),[t,o,r]},l.lab.xyz=function(e){var t,o,r,n=e[0],i=e[1],a=e[2];o=(n+16)/116,t=i/500+o,r=o-a/200;var l=Math.pow(o,3),s=Math.pow(t,3),c=Math.pow(r,3);return o=l>.008856?l:(o-16/116)/7.787,t=s>.008856?s:(t-16/116)/7.787,r=c>.008856?c:(r-16/116)/7.787,t*=95.047,o*=100,r*=108.883,[t,o,r]},l.lab.lch=function(e){var t,o,r,n=e[0],i=e[1],a=e[2];return t=Math.atan2(a,i),o=360*t/2/Math.PI,o<0&&(o+=360),r=Math.sqrt(i*i+a*a),[n,r,o]},l.lch.lab=function(e){var t,o,r,n=e[0],i=e[1],a=e[2];return r=a/360*2*Math.PI,t=i*Math.cos(r),o=i*Math.sin(r),[n,t,o]},l.rgb.ansi16=function(e){var t=e[0],o=e[1],r=e[2],n=1 in arguments?arguments[1]:l.rgb.hsv(e)[2];if(0===(n=Math.round(n/50)))return 30;var i=30+(Math.round(r/255)<<2|Math.round(o/255)<<1|Math.round(t/255));return 2===n&&(i+=60),i},l.hsv.ansi16=function(e){return l.rgb.ansi16(l.hsv.rgb(e),e[2])},l.rgb.ansi256=function(e){var t=e[0],o=e[1],r=e[2];return t===o&&o===r?t<8?16:t>248?231:Math.round((t-8)/247*24)+232:16+36*Math.round(t/255*5)+6*Math.round(o/255*5)+Math.round(r/255*5)},l.ansi16.rgb=function(e){var t=e%10;if(0===t||7===t)return e>50&&(t+=3.5),t=t/10.5*255,[t,t,t];var o=.5*(1+~~(e>50));return[(1&t)*o*255,(t>>1&1)*o*255,(t>>2&1)*o*255]},l.ansi256.rgb=function(e){if(e>=232){var t=10*(e-232)+8;return[t,t,t]}e-=16;var o;return[Math.floor(e/36)/5*255,Math.floor((o=e%36)/6)/5*255,o%6/5*255]},l.rgb.hex=function(e){var t=((255&Math.round(e[0]))<<16)+((255&Math.round(e[1]))<<8)+(255&Math.round(e[2])),o=t.toString(16).toUpperCase();return"000000".substring(o.length)+o},l.hex.rgb=function(e){var t=e.toString(16).match(/[a-f0-9]{6}|[a-f0-9]{3}/i);if(!t)return[0,0,0];var o=t[0];3===t[0].length&&(o=o.split("").map(function(e){return e+e}).join(""));var r=parseInt(o,16);return[r>>16&255,r>>8&255,255&r]},l.rgb.hcg=function(e){var t,o,r=e[0]/255,n=e[1]/255,i=e[2]/255,a=Math.max(Math.max(r,n),i),l=Math.min(Math.min(r,n),i),s=a-l;return t=s<1?l/(1-s):0,o=s<=0?0:a===r?(n-i)/s%6:a===n?2+(i-r)/s:4+(r-n)/s+4,o/=6,o%=1,[360*o,100*s,100*t]},l.hsl.hcg=function(e){var t=e[1]/100,o=e[2]/100,r=1,n=0;return r=o<.5?2*t*o:2*t*(1-o),r<1&&(n=(o-.5*r)/(1-r)),[e[0],100*r,100*n]},l.hsv.hcg=function(e){var t=e[1]/100,o=e[2]/100,r=t*o,n=0;return r<1&&(n=(o-r)/(1-r)),[e[0],100*r,100*n]},l.hcg.rgb=function(e){var t=e[0]/360,o=e[1]/100,r=e[2]/100;if(0===o)return[255*r,255*r,255*r];var n=[0,0,0],i=t%1*6,a=i%1,l=1-a,s=0;switch(Math.floor(i)){case 0:n[0]=1,n[1]=a,n[2]=0;break;case 1:n[0]=l,n[1]=1,n[2]=0;break;case 2:n[0]=0,n[1]=1,n[2]=a;break;case 3:n[0]=0,n[1]=l,n[2]=1;break;case 4:n[0]=a,n[1]=0,n[2]=1;break;default:n[0]=1,n[1]=0,n[2]=l}return s=(1-o)*r,[255*(o*n[0]+s),255*(o*n[1]+s),255*(o*n[2]+s)]},l.hcg.hsv=function(e){var t=e[1]/100,o=e[2]/100,r=t+o*(1-t),n=0;return r>0&&(n=t/r),[e[0],100*n,100*r]},l.hcg.hsl=function(e){var t=e[1]/100,o=e[2]/100,r=o*(1-t)+.5*t,n=0;return r>0&&r<.5?n=t/(2*r):r>=.5&&r<1&&(n=t/(2*(1-r))),[e[0],100*n,100*r]},l.hcg.hwb=function(e){var t=e[1]/100,o=e[2]/100,r=t+o*(1-t);return[e[0],100*(r-t),100*(1-r)]},l.hwb.hcg=function(e){var t=e[1]/100,o=e[2]/100,r=1-o,n=r-t,i=0;return n<1&&(i=(r-n)/(1-n)),[e[0],100*n,100*i]},l.apple.rgb=function(e){return[e[0]/65535*255,e[1]/65535*255,e[2]/65535*255]},l.rgb.apple=function(e){return[e[0]/255*65535,e[1]/255*65535,e[2]/255*65535]},l.gray.rgb=function(e){return[e[0]/100*255,e[0]/100*255,e[0]/100*255]},l.gray.hsl=l.gray.hsv=function(e){return[0,0,e[0]]},l.gray.hwb=function(e){return[0,100,e[0]]},l.gray.cmyk=function(e){return[0,0,0,e[0]]},l.gray.lab=function(e){return[e[0],0,0]},l.gray.hex=function(e){var t=255&Math.round(e[0]/100*255),o=(t<<16)+(t<<8)+t,r=o.toString(16).toUpperCase();return"000000".substring(r.length)+r},l.rgb.gray=function(e){return[(e[0]+e[1]+e[2])/3/255*100]}},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}var n="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},i=o(8),a=r(i),l=o(0),s=r(l),c="colorpicker";s.default[c]=a.default,s.default.fn[c]=function(e){var t=Array.prototype.slice.call(arguments,1),o=1===this.length,r=null,i=this.each(function(){var i=(0,s.default)(this),l=i.data(c),u="object"===(void 0===e?"undefined":n(e))?e:{};l||(l=new a.default(this,u),i.data(c,l)),o&&(r=i,"string"==typeof e&&(r="colorpicker"===e?l:s.default.isFunction(l[e])?l[e].apply(l,t):l[e]))});return o?r:i},s.default.fn[c].constructor=a.default},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var i=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),a=o(1),l=r(a),s=o(3),c=r(s),u=o(9),h=r(u),p=o(0),f=r(p),d=o(13),v=r(d),k=o(14),g=r(k),y=o(15),b=r(y),m=o(22),w=r(m),x=o(23),_=r(x),C=o(24),M=r(C),O=o(2),j=r(O),H=0,P="undefined"!=typeof self?self:void 0,E=function(){function e(t,o){n(this,e),H+=1,this.id=H,this.lastEvent={alias:null,e:null},this.element=(0,f.default)(t).addClass("colorpicker-element").attr("data-colorpicker-id",this.id),this.options=f.default.extend(!0,{},c.default,o,this.element.data()),this.disabled=!1,this.extensions=[],this.container=!0===this.options.container||!0!==this.options.container&&!0===this.options.inline?this.element:this.options.container,this.container=!1!==this.container&&(0,f.default)(this.container),this.inputHandler=new b.default(this),this.colorHandler=new w.default(this),this.sliderHandler=new v.default(this),this.popupHandler=new g.default(this,P),this.pickerHandler=new _.default(this),this.addonHandler=new M.default(this),this.init(),(0,f.default)(f.default.proxy(function(){this.trigger("colorpickerCreate")},this))}return i(e,[{key:"color",get:function(){return this.colorHandler.color}},{key:"format",get:function(){return this.colorHandler.format}},{key:"picker",get:function(){return this.pickerHandler.picker}}],[{key:"Color",get:function(){return j.default}},{key:"Extension",get:function(){return l.default}}]),i(e,[{key:"init",value:function(){this.addonHandler.bind(),this.inputHandler.bind(),this.initExtensions(),this.colorHandler.bind(),this.pickerHandler.bind(),this.sliderHandler.bind(),this.popupHandler.bind(),this.pickerHandler.attach(),this.update(),this.inputHandler.isDisabled()&&this.disable()}},{key:"initExtensions",value:function(){var t=this;Array.isArray(this.options.extensions)||(this.options.extensions=[]),this.options.debug&&this.options.extensions.push({name:"debugger"}),this.options.extensions.forEach(function(o){t.registerExtension(e.extensions[o.name.toLowerCase()],o.options||{})})}},{key:"registerExtension",value:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},o=new e(this,t);return this.extensions.push(o),o}},{key:"destroy",value:function(){var e=this.color;this.sliderHandler.unbind(),this.inputHandler.unbind(),this.popupHandler.unbind(),this.colorHandler.unbind(),this.addonHandler.unbind(),this.pickerHandler.unbind(),this.element.removeClass("colorpicker-element").removeData("colorpicker","color").off(".colorpicker"),this.trigger("colorpickerDestroy",e)}},{key:"show",value:function(e){this.popupHandler.show(e)}},{key:"hide",value:function(e){this.popupHandler.hide(e)}},{key:"toggle",value:function(e){this.popupHandler.toggle(e)}},{key:"getValue",value:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null,t=this.colorHandler.color;return t=t instanceof j.default?t:e,t instanceof j.default?t.string(this.format):t}},{key:"setValue",value:function(e){if(!this.isDisabled()){var t=this.colorHandler;t.hasColor()&&e&&t.color.equals(e)||!t.hasColor()&&!e||(t.color=e?t.createColor(e,this.options.autoInputFallback):null,this.trigger("colorpickerChange",t.color,e),this.update())}}},{key:"update",value:function(){this.colorHandler.hasColor()?this.inputHandler.update():this.colorHandler.assureColor(),this.addonHandler.update(),this.pickerHandler.update(),this.trigger("colorpickerUpdate")}},{key:"enable",value:function(){return this.inputHandler.enable(),this.disabled=!1,this.picker.removeClass("colorpicker-disabled"),this.trigger("colorpickerEnable"),!0}},{key:"disable",value:function(){return this.inputHandler.disable(),this.disabled=!0,this.picker.addClass("colorpicker-disabled"),this.trigger("colorpickerDisable"),!0}},{key:"isEnabled",value:function(){return!this.isDisabled()}},{key:"isDisabled",value:function(){return!0===this.disabled}},{key:"trigger",value:function(e){var t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:null,o=arguments.length>2&&void 0!==arguments[2]?arguments[2]:null;this.element.trigger({type:e,colorpicker:this,color:t||this.color,value:o||this.getValue()})}}]),e}();E.extensions=h.default,t.default=E},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0}),t.Palette=t.Swatches=t.Preview=t.Debugger=void 0;var n=o(10),i=r(n),a=o(11),l=r(a),s=o(12),c=r(s),u=o(4),h=r(u);t.Debugger=i.default,t.Preview=l.default,t.Swatches=c.default,t.Palette=h.default,t.default={debugger:i.default,preview:l.default,swatches:c.default,palette:h.default}},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function i(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var l=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),s=function e(t,o,r){null===t&&(t=Function.prototype);var n=Object.getOwnPropertyDescriptor(t,o);if(void 0===n){var i=Object.getPrototypeOf(t);return null===i?void 0:e(i,o,r)}if("value"in n)return n.value;var a=n.get;if(void 0!==a)return a.call(r)},c=o(1),u=r(c),h=o(0),p=r(h),f=function(e){function t(e){var o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};n(this,t);var r=i(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e,o));return r.eventCounter=0,r.colorpicker.inputHandler.hasInput()&&r.colorpicker.inputHandler.input.on("change.colorpicker-ext",p.default.proxy(r.onChangeInput,r)),r}return a(t,e),l(t,[{key:"log",value:function(e){for(var t,o=arguments.length,r=Array(o>1?o-1:0),n=1;n<o;n++)r[n-1]=arguments[n];this.eventCounter+=1;var i="#"+this.eventCounter+": Colorpicker#"+this.colorpicker.id+" ["+e+"]";(t=console).debug.apply(t,[i].concat(r)),this.colorpicker.element.trigger({type:"colorpickerDebug",colorpicker:this.colorpicker,color:this.color,value:null,debug:{debugger:this,eventName:e,logArgs:r,logMessage:i}})}},{key:"resolveColor",value:function(e){var t=!(arguments.length>1&&void 0!==arguments[1])||arguments[1];return this.log("resolveColor()",e,t),!1}},{key:"onCreate",value:function(e){return this.log("colorpickerCreate"),s(t.prototype.__proto__||Object.getPrototypeOf(t.prototype),"onCreate",this).call(this,e)}},{key:"onDestroy",value:function(e){return this.log("colorpickerDestroy"),this.eventCounter=0,this.colorpicker.inputHandler.hasInput()&&this.colorpicker.inputHandler.input.off(".colorpicker-ext"),s(t.prototype.__proto__||Object.getPrototypeOf(t.prototype),"onDestroy",this).call(this,e)}},{key:"onUpdate",value:function(e){this.log("colorpickerUpdate")}},{key:"onChangeInput",value:function(e){this.log("input:change.colorpicker",e.value,e.color)}},{key:"onChange",value:function(e){this.log("colorpickerChange",e.value,e.color)}},{key:"onInvalid",value:function(e){this.log("colorpickerInvalid",e.value,e.color)}},{key:"onHide",value:function(e){this.log("colorpickerHide"),this.eventCounter=0}},{key:"onShow",value:function(e){this.log("colorpickerShow")}},{key:"onDisable",value:function(e){this.log("colorpickerDisable")}},{key:"onEnable",value:function(e){this.log("colorpickerEnable")}}]),t}(u.default);t.default=f},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function i(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var l=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),s=function e(t,o,r){null===t&&(t=Function.prototype);var n=Object.getOwnPropertyDescriptor(t,o);if(void 0===n){var i=Object.getPrototypeOf(t);return null===i?void 0:e(i,o,r)}if("value"in n)return n.value;var a=n.get;if(void 0!==a)return a.call(r)},c=o(1),u=r(c),h=o(0),p=r(h),f=function(e){function t(e){var o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};n(this,t);var r=i(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e,p.default.extend(!0,{},{template:'<div class="colorpicker-bar colorpicker-preview"><div /></div>',showText:!0,format:e.format},o)));return r.element=(0,p.default)(r.options.template),r.elementInner=r.element.find("div"),r}return a(t,e),l(t,[{key:"onCreate",value:function(e){s(t.prototype.__proto__||Object.getPrototypeOf(t.prototype),"onCreate",this).call(this,e),this.colorpicker.picker.append(this.element)}},{key:"onUpdate",value:function(e){if(s(t.prototype.__proto__||Object.getPrototypeOf(t.prototype),"onUpdate",this).call(this,e),!e.color)return void this.elementInner.css("backgroundColor",null).css("color",null).html("");this.elementInner.css("backgroundColor",e.color.toRgbString()),this.options.showText&&(this.elementInner.html(e.color.string(this.options.format||this.colorpicker.format)),e.color.isDark()&&e.color.alpha>.5?this.elementInner.css("color","white"):this.elementInner.css("color","black"))}}]),t}(u.default);t.default=f},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function i(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function a(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}Object.defineProperty(t,"__esModule",{value:!0});var l=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),s=function e(t,o,r){null===t&&(t=Function.prototype);var n=Object.getOwnPropertyDescriptor(t,o);if(void 0===n){var i=Object.getPrototypeOf(t);return null===i?void 0:e(i,o,r)}if("value"in n)return n.value;var a=n.get;if(void 0!==a)return a.call(r)},c=o(4),u=r(c),h=o(0),p=r(h),f={barTemplate:'<div class="colorpicker-bar colorpicker-swatches">\n                    <div class="colorpicker-swatches--inner"></div>\n                </div>',swatchTemplate:'<i class="colorpicker-swatch"><i class="colorpicker-swatch--inner"></i></i>'},d=function(e){function t(e){var o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{};n(this,t);var r=i(this,(t.__proto__||Object.getPrototypeOf(t)).call(this,e,p.default.extend(!0,{},f,o)));return r.element=null,r}return a(t,e),l(t,[{key:"isEnabled",value:function(){return this.getLength()>0}},{key:"onCreate",value:function(e){s(t.prototype.__proto__||Object.getPrototypeOf(t.prototype),"onCreate",this).call(this,e),this.isEnabled()&&(this.element=(0,p.default)(this.options.barTemplate),this.load(),this.colorpicker.picker.append(this.element))}},{key:"load",value:function(){var e=this,t=this.colorpicker,o=this.element.find(".colorpicker-swatches--inner"),r=!0===this.options.namesAsValues&&!Array.isArray(this.colors);o.empty(),p.default.each(this.colors,function(n,i){var a=(0,p.default)(e.options.swatchTemplate).attr("data-name",n).attr("data-value",i).attr("title",r?n+": "+i:i).on("mousedown.colorpicker touchstart.colorpicker",function(e){var o=(0,p.default)(this);t.setValue(r?o.attr("data-name"):o.attr("data-value"))});a.find(".colorpicker-swatch--inner").css("background-color",i),o.append(a)}),o.append((0,p.default)('<i class="colorpicker-clear"></i>'))}}]),t}(u.default);t.default=d},function(e,t,o){"use strict";function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var n=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),i=o(0),a=function(e){return e&&e.__esModule?e:{default:e}}(i),l=function(){function e(t){r(this,e),this.colorpicker=t,this.currentSlider=null,this.mousePointer={left:0,top:0},this.onMove=a.default.proxy(this.defaultOnMove,this)}return n(e,[{key:"defaultOnMove",value:function(e,t){if(this.currentSlider){var o=this.currentSlider,r=this.colorpicker,n=r.colorHandler,i=n.hasColor()?n.color.getClone():n.getFallbackColor();o.guideStyle.left=t+"px",o.guideStyle.top=e+"px",o.callLeft&&i[o.callLeft](t/o.maxLeft),o.callTop&&i[o.callTop](e/o.maxTop),r.setValue(i),r.popupHandler.focus()}}},{key:"bind",value:function(){var e=this.colorpicker.options.horizontal?this.colorpicker.options.slidersHorz:this.colorpicker.options.sliders,t=[];for(var o in e)e.hasOwnProperty(o)&&t.push(e[o].selector);this.colorpicker.picker.find(t.join(", ")).on("mousedown.colorpicker touchstart.colorpicker",a.default.proxy(this.pressed,this))}},{key:"unbind",value:function(){(0,a.default)(this.colorpicker.picker).off({"mousemove.colorpicker":a.default.proxy(this.moved,this),"touchmove.colorpicker":a.default.proxy(this.moved,this),"mouseup.colorpicker":a.default.proxy(this.released,this),"touchend.colorpicker":a.default.proxy(this.released,this)})}},{key:"pressed",value:function(e){if(!this.colorpicker.isDisabled()){this.colorpicker.lastEvent.alias="pressed",this.colorpicker.lastEvent.e=e,!e.pageX&&!e.pageY&&e.originalEvent&&e.originalEvent.touches&&(e.pageX=e.originalEvent.touches[0].pageX,e.pageY=e.originalEvent.touches[0].pageY);var t=(0,a.default)(e.target),o=t.closest("div"),r=this.colorpicker.options.horizontal?this.colorpicker.options.slidersHorz:this.colorpicker.options.sliders;if(!o.is(".colorpicker")){this.currentSlider=null;for(var n in r)if(r.hasOwnProperty(n)){var i=r[n];if(o.is(i.selector)){this.currentSlider=a.default.extend({},i,{name:n});break}if(void 0!==i.childSelector&&o.is(i.childSelector)){this.currentSlider=a.default.extend({},i,{name:n}),o=o.parent();break}}var l=o.find(".colorpicker-guide").get(0);if(null!==this.currentSlider&&null!==l){var s=o.offset();this.currentSlider.guideStyle=l.style,this.currentSlider.left=e.pageX-s.left,this.currentSlider.top=e.pageY-s.top,this.mousePointer={left:e.pageX,top:e.pageY},(0,a.default)(this.colorpicker.picker).on({"mousemove.colorpicker":a.default.proxy(this.moved,this),"touchmove.colorpicker":a.default.proxy(this.moved,this),"mouseup.colorpicker":a.default.proxy(this.released,this),"touchend.colorpicker":a.default.proxy(this.released,this)}).trigger("mousemove")}}}}},{key:"moved",value:function(e){this.colorpicker.lastEvent.alias="moved",this.colorpicker.lastEvent.e=e,!e.pageX&&!e.pageY&&e.originalEvent&&e.originalEvent.touches&&(e.pageX=e.originalEvent.touches[0].pageX,e.pageY=e.originalEvent.touches[0].pageY),e.preventDefault();var t=Math.max(0,Math.min(this.currentSlider.maxLeft,this.currentSlider.left+((e.pageX||this.mousePointer.left)-this.mousePointer.left))),o=Math.max(0,Math.min(this.currentSlider.maxTop,this.currentSlider.top+((e.pageY||this.mousePointer.top)-this.mousePointer.top)));this.onMove(o,t)}},{key:"released",value:function(e){this.colorpicker.lastEvent.alias="released",this.colorpicker.lastEvent.e=e,(0,a.default)(this.colorpicker.picker).off({"mousemove.colorpicker":this.moved,"touchmove.colorpicker":this.moved,"mouseup.colorpicker":this.released,"touchend.colorpicker":this.released})}}]),e}();t.default=l},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var i=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),a=o(0),l=r(a),s=o(3),c=r(s),u=function(){function e(t,o){n(this,e),this.root=o,this.colorpicker=t,this.popoverTarget=null,this.popoverTip=null,this.clicking=!1,this.hidding=!1,this.showing=!1}return i(e,[{key:"bind",value:function(){var e=this.colorpicker;if(e.options.inline)return void e.picker.addClass("colorpicker-inline colorpicker-visible");e.picker.addClass("colorpicker-popup colorpicker-hidden"),(this.hasInput||this.hasAddon)&&(e.options.popover&&this.createPopover(),this.hasAddon&&(this.addon.attr("tabindex")||this.addon.attr("tabindex",0),this.addon.on({"mousedown.colorpicker touchstart.colorpicker":l.default.proxy(this.toggle,this)}),this.addon.on({"focus.colorpicker":l.default.proxy(this.show,this)}),this.addon.on({"focusout.colorpicker":l.default.proxy(this.hide,this)})),this.hasInput&&!this.hasAddon&&(this.input.on({"mousedown.colorpicker touchstart.colorpicker":l.default.proxy(this.show,this),"focus.colorpicker":l.default.proxy(this.show,this)}),this.input.on({"focusout.colorpicker":l.default.proxy(this.hide,this)})),(0,l.default)(this.root).on("resize.colorpicker",l.default.proxy(this.reposition,this)))}},{key:"unbind",value:function(){this.hasInput&&(this.input.off({"mousedown.colorpicker touchstart.colorpicker":l.default.proxy(this.show,this),"focus.colorpicker":l.default.proxy(this.show,this)}),this.input.off({"focusout.colorpicker":l.default.proxy(this.hide,this)})),this.hasAddon&&(this.addon.off({"mousedown.colorpicker touchstart.colorpicker":l.default.proxy(this.toggle,this)}),this.addon.off({"focus.colorpicker":l.default.proxy(this.show,this)}),this.addon.off({"focusout.colorpicker":l.default.proxy(this.hide,this)})),this.popoverTarget&&this.popoverTarget.popover("dispose"),(0,l.default)(this.root).off("resize.colorpicker",l.default.proxy(this.reposition,this)),(0,l.default)(this.root.document).off("mousedown.colorpicker touchstart.colorpicker",l.default.proxy(this.hide,this)),(0,l.default)(this.root.document).off("mousedown.colorpicker touchstart.colorpicker",l.default.proxy(this.onClickingInside,this))}},{key:"isClickingInside",value:function(e){return!!e&&(this.isOrIsInside(this.popoverTip,e.currentTarget)||this.isOrIsInside(this.popoverTip,e.target)||this.isOrIsInside(this.colorpicker.picker,e.currentTarget)||this.isOrIsInside(this.colorpicker.picker,e.target))}},{key:"isOrIsInside",value:function(e,t){return!(!e||!t)&&(t=(0,l.default)(t),t.is(e)||e.find(t).length>0)}},{key:"onClickingInside",value:function(e){this.clicking=this.isClickingInside(e)}},{key:"createPopover",value:function(){var e=this.colorpicker;this.popoverTarget=this.hasAddon?this.addon:this.input,e.picker.addClass("colorpicker-bs-popover-content"),this.popoverTarget.popover(l.default.extend(!0,{},c.default.popover,e.options.popover,{trigger:"manual",content:e.picker,html:!0})),this.popoverTip=(0,l.default)(this.popoverTarget.popover("getTipElement").data("bs.popover").tip),this.popoverTip.addClass("colorpicker-bs-popover"),this.popoverTarget.on("shown.bs.popover",l.default.proxy(this.fireShow,this)),this.popoverTarget.on("hidden.bs.popover",l.default.proxy(this.fireHide,this))}},{key:"reposition",value:function(e){this.popoverTarget&&this.isVisible()&&this.popoverTarget.popover("update")}},{key:"toggle",value:function(e){this.isVisible()?this.hide(e):this.show(e)}},{key:"show",value:function(e){if(!(this.isVisible()||this.showing||this.hidding)){this.showing=!0,this.hidding=!1,this.clicking=!1;var t=this.colorpicker;t.lastEvent.alias="show",t.lastEvent.e=e,e&&(!this.hasInput||"color"===this.input.attr("type"))&&e&&e.preventDefault&&(e.stopPropagation(),e.preventDefault()),this.isPopover&&(0,l.default)(this.root).on("resize.colorpicker",l.default.proxy(this.reposition,this)),t.picker.addClass("colorpicker-visible").removeClass("colorpicker-hidden"),this.popoverTarget?this.popoverTarget.popover("show"):this.fireShow()}}},{key:"fireShow",value:function(){this.hidding=!1,this.showing=!1,this.isPopover&&((0,l.default)(this.root.document).on("mousedown.colorpicker touchstart.colorpicker",l.default.proxy(this.hide,this)),(0,l.default)(this.root.document).on("mousedown.colorpicker touchstart.colorpicker",l.default.proxy(this.onClickingInside,this))),this.colorpicker.trigger("colorpickerShow")}},{key:"hide",value:function(e){if(!(this.isHidden()||this.showing||this.hidding)){var t=this.colorpicker,o=this.clicking||this.isClickingInside(e);if(this.hidding=!0,this.showing=!1,this.clicking=!1,t.lastEvent.alias="hide",t.lastEvent.e=e,o)return void(this.hidding=!1);this.popoverTarget?this.popoverTarget.popover("hide"):this.fireHide()}}},{key:"fireHide",value:function(){this.hidding=!1,this.showing=!1;var e=this.colorpicker;e.picker.addClass("colorpicker-hidden").removeClass("colorpicker-visible"),(0,l.default)(this.root).off("resize.colorpicker",l.default.proxy(this.reposition,this)),(0,l.default)(this.root.document).off("mousedown.colorpicker touchstart.colorpicker",l.default.proxy(this.hide,this)),(0,l.default)(this.root.document).off("mousedown.colorpicker touchstart.colorpicker",l.default.proxy(this.onClickingInside,this)),e.trigger("colorpickerHide")}},{key:"focus",value:function(){return this.hasAddon?this.addon.focus():!!this.hasInput&&this.input.focus()}},{key:"isVisible",value:function(){return this.colorpicker.picker.hasClass("colorpicker-visible")&&!this.colorpicker.picker.hasClass("colorpicker-hidden")}},{key:"isHidden",value:function(){return this.colorpicker.picker.hasClass("colorpicker-hidden")&&!this.colorpicker.picker.hasClass("colorpicker-visible")}},{key:"input",get:function(){return this.colorpicker.inputHandler.input}},{key:"hasInput",get:function(){return this.colorpicker.inputHandler.hasInput()}},{key:"addon",get:function(){return this.colorpicker.addonHandler.addon}},{key:"hasAddon",get:function(){return this.colorpicker.addonHandler.hasAddon()}},{key:"isPopover",get:function(){return!this.colorpicker.options.inline&&!!this.popoverTip}}]),e}();t.default=u},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var i=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),a=o(0),l=r(a),s=o(2),c=r(s),u=function(){function e(t){n(this,e),this.colorpicker=t,this.input=this.colorpicker.element.is("input")?this.colorpicker.element:!!this.colorpicker.options.input&&this.colorpicker.element.find(this.colorpicker.options.input),this.input&&0===this.input.length&&(this.input=!1),this._initValue()}return i(e,[{key:"bind",value:function(){this.hasInput()&&(this.input.on({"keyup.colorpicker":l.default.proxy(this.onkeyup,this)}),this.input.on({"change.colorpicker":l.default.proxy(this.onchange,this)}))}},{key:"unbind",value:function(){this.hasInput()&&this.input.off(".colorpicker")}},{key:"_initValue",value:function(){if(this.hasInput()){var e="";[this.input.val(),this.input.data("color"),this.input.attr("data-color")].map(function(t){t&&""===e&&(e=t)}),e instanceof c.default?e=this.getFormattedColor(e.string(this.colorpicker.format)):"string"==typeof e||e instanceof String||(e=""),this.input.prop("value",e)}}},{key:"getValue",value:function(){return!!this.hasInput()&&this.input.val()}},{key:"setValue",value:function(e){if(this.hasInput()){var t=this.input.prop("value");e=e||"",e!==(t||"")&&(this.input.prop("value",e),this.input.trigger({type:"change",colorpicker:this.colorpicker,color:this.colorpicker.color,value:e}))}}},{key:"getFormattedColor",value:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:null;return(e=e||this.colorpicker.colorHandler.getColorString())?(e=this.colorpicker.colorHandler.resolveColorDelegate(e,!1),!1===this.colorpicker.options.useHashPrefix&&(e=e.replace(/^#/g,"")),e):""}},{key:"hasInput",value:function(){return!1!==this.input}},{key:"isEnabled",value:function(){return this.hasInput()&&!this.isDisabled()}},{key:"isDisabled",value:function(){return this.hasInput()&&!0===this.input.prop("disabled")}},{key:"disable",value:function(){this.hasInput()&&this.input.prop("disabled",!0)}},{key:"enable",value:function(){this.hasInput()&&this.input.prop("disabled",!1)}},{key:"update",value:function(){this.hasInput()&&(!1===this.colorpicker.options.autoInputFallback&&this.colorpicker.colorHandler.isInvalidColor()||this.setValue(this.getFormattedColor()))}},{key:"onchange",value:function(e){this.colorpicker.lastEvent.alias="input.change",this.colorpicker.lastEvent.e=e;var t=this.getValue();t!==e.value&&this.colorpicker.setValue(t)}},{key:"onkeyup",value:function(e){this.colorpicker.lastEvent.alias="input.keyup",this.colorpicker.lastEvent.e=e;var t=this.getValue();t!==e.value&&this.colorpicker.setValue(t)}}]),e}();t.default=u},function(e,t,o){"use strict";function r(e,t){if(!(this instanceof r))return new r(e,t);if(t&&t in f&&(t=null),t&&!(t in h))throw new Error("Unknown model: "+t);var o,n;if(void 0===e)this.model="rgb",this.color=[0,0,0],this.valpha=1;else if(e instanceof r)this.model=e.model,this.color=e.color.slice(),this.valpha=e.valpha;else if("string"==typeof e){var i=u.get(e);if(null===i)throw new Error("Unable to parse color from string: "+e);this.model=i.model,n=h[this.model].channels,this.color=i.value.slice(0,n),this.valpha="number"==typeof i.value[n]?i.value[n]:1}else if(e.length){this.model=t||"rgb",n=h[this.model].channels;var a=p.call(e,0,n);this.color=c(a,n),this.valpha="number"==typeof e[n]?e[n]:1}else if("number"==typeof e)e&=16777215,this.model="rgb",this.color=[e>>16&255,e>>8&255,255&e],this.valpha=1;else{this.valpha=1;var l=Object.keys(e);"alpha"in e&&(l.splice(l.indexOf("alpha"),1),this.valpha="number"==typeof e.alpha?e.alpha:0);var s=l.sort().join("");if(!(s in d))throw new Error("Unable to parse color from object: "+JSON.stringify(e));this.model=d[s];var k=h[this.model].labels,g=[];for(o=0;o<k.length;o++)g.push(e[k[o]]);this.color=c(g)}if(v[this.model])for(n=h[this.model].channels,o=0;o<n;o++){var y=v[this.model][o];y&&(this.color[o]=y(this.color[o]))}this.valpha=Math.max(0,Math.min(1,this.valpha)),Object.freeze&&Object.freeze(this)}function n(e,t){return Number(e.toFixed(t))}function i(e){return function(t){return n(t,e)}}function a(e,t,o){return e=Array.isArray(e)?e:[e],e.forEach(function(e){(v[e]||(v[e]=[]))[t]=o}),e=e[0],function(r){var n;return arguments.length?(o&&(r=o(r)),n=this[e](),n.color[t]=r,n):(n=this[e]().color[t],o&&(n=o(n)),n)}}function l(e){return function(t){return Math.max(0,Math.min(e,t))}}function s(e){return Array.isArray(e)?e:[e]}function c(e,t){for(var o=0;o<t;o++)"number"!=typeof e[o]&&(e[o]=0);return e}var u=o(17),h=o(20),p=[].slice,f=["keyword","gray","hex"],d={};Object.keys(h).forEach(function(e){d[p.call(h[e].labels).sort().join("")]=e});var v={};r.prototype={toString:function(){return this.string()},toJSON:function(){return this[this.model]()},string:function(e){var t=this.model in u.to?this:this.rgb();t=t.round("number"==typeof e?e:1);var o=1===t.valpha?t.color:t.color.concat(this.valpha);return u.to[t.model](o)},percentString:function(e){var t=this.rgb().round("number"==typeof e?e:1),o=1===t.valpha?t.color:t.color.concat(this.valpha);return u.to.rgb.percent(o)},array:function(){return 1===this.valpha?this.color.slice():this.color.concat(this.valpha)},object:function(){for(var e={},t=h[this.model].channels,o=h[this.model].labels,r=0;r<t;r++)e[o[r]]=this.color[r];return 1!==this.valpha&&(e.alpha=this.valpha),e},unitArray:function(){var e=this.rgb().color;return e[0]/=255,e[1]/=255,e[2]/=255,1!==this.valpha&&e.push(this.valpha),e},unitObject:function(){var e=this.rgb().object();return e.r/=255,e.g/=255,e.b/=255,1!==this.valpha&&(e.alpha=this.valpha),e},round:function(e){return e=Math.max(e||0,0),new r(this.color.map(i(e)).concat(this.valpha),this.model)},alpha:function(e){return arguments.length?new r(this.color.concat(Math.max(0,Math.min(1,e))),this.model):this.valpha},red:a("rgb",0,l(255)),green:a("rgb",1,l(255)),blue:a("rgb",2,l(255)),hue:a(["hsl","hsv","hsl","hwb","hcg"],0,function(e){return(e%360+360)%360}),saturationl:a("hsl",1,l(100)),lightness:a("hsl",2,l(100)),saturationv:a("hsv",1,l(100)),value:a("hsv",2,l(100)),chroma:a("hcg",1,l(100)),gray:a("hcg",2,l(100)),white:a("hwb",1,l(100)),wblack:a("hwb",2,l(100)),cyan:a("cmyk",0,l(100)),magenta:a("cmyk",1,l(100)),yellow:a("cmyk",2,l(100)),black:a("cmyk",3,l(100)),x:a("xyz",0,l(100)),y:a("xyz",1,l(100)),z:a("xyz",2,l(100)),l:a("lab",0,l(100)),a:a("lab",1),b:a("lab",2),keyword:function(e){return arguments.length?new r(e):h[this.model].keyword(this.color)},hex:function(e){return arguments.length?new r(e):u.to.hex(this.rgb().round().color)},rgbNumber:function(){var e=this.rgb().color;return(255&e[0])<<16|(255&e[1])<<8|255&e[2]},luminosity:function(){for(var e=this.rgb().color,t=[],o=0;o<e.length;o++){var r=e[o]/255;t[o]=r<=.03928?r/12.92:Math.pow((r+.055)/1.055,2.4)}return.2126*t[0]+.7152*t[1]+.0722*t[2]},contrast:function(e){var t=this.luminosity(),o=e.luminosity();return t>o?(t+.05)/(o+.05):(o+.05)/(t+.05)},level:function(e){var t=this.contrast(e);return t>=7.1?"AAA":t>=4.5?"AA":""},isDark:function(){var e=this.rgb().color;return(299*e[0]+587*e[1]+114*e[2])/1e3<128},isLight:function(){return!this.isDark()},negate:function(){for(var e=this.rgb(),t=0;t<3;t++)e.color[t]=255-e.color[t];return e},lighten:function(e){var t=this.hsl();return t.color[2]+=t.color[2]*e,t},darken:function(e){var t=this.hsl();return t.color[2]-=t.color[2]*e,t},saturate:function(e){var t=this.hsl();return t.color[1]+=t.color[1]*e,t},desaturate:function(e){var t=this.hsl();return t.color[1]-=t.color[1]*e,t},whiten:function(e){var t=this.hwb();return t.color[1]+=t.color[1]*e,t},blacken:function(e){var t=this.hwb();return t.color[2]+=t.color[2]*e,t},grayscale:function(){var e=this.rgb().color,t=.3*e[0]+.59*e[1]+.11*e[2];return r.rgb(t,t,t)},fade:function(e){return this.alpha(this.valpha-this.valpha*e)},opaquer:function(e){return this.alpha(this.valpha+this.valpha*e)},rotate:function(e){var t=this.hsl(),o=t.color[0];return o=(o+e)%360,o=o<0?360+o:o,t.color[0]=o,t},mix:function(e,t){if(!e||!e.rgb)throw new Error('Argument to "mix" was not a Color instance, but rather an instance of '+typeof e);var o=e.rgb(),n=this.rgb(),i=void 0===t?.5:t,a=2*i-1,l=o.alpha()-n.alpha(),s=((a*l==-1?a:(a+l)/(1+a*l))+1)/2,c=1-s;return r.rgb(s*o.red()+c*n.red(),s*o.green()+c*n.green(),s*o.blue()+c*n.blue(),o.alpha()*i+n.alpha()*(1-i))}},Object.keys(h).forEach(function(e){if(-1===f.indexOf(e)){var t=h[e].channels;r.prototype[e]=function(){if(this.model===e)return new r(this);if(arguments.length)return new r(arguments,e);var o="number"==typeof arguments[t]?t:this.valpha;return new r(s(h[this.model][e].raw(this.color)).concat(o),e)},r[e]=function(o){return"number"==typeof o&&(o=c(p.call(arguments),t)),new r(o,e)}}}),e.exports=r},function(e,t,o){function r(e,t,o){return Math.min(Math.max(t,e),o)}function n(e){var t=e.toString(16).toUpperCase();return t.length<2?"0"+t:t}var i=o(5),a=o(18),l={};for(var s in i)i.hasOwnProperty(s)&&(l[i[s]]=s);var c=e.exports={to:{},get:{}};c.get=function(e){var t,o,r=e.substring(0,3).toLowerCase();switch(r){case"hsl":t=c.get.hsl(e),o="hsl";break;case"hwb":t=c.get.hwb(e),o="hwb";break;default:t=c.get.rgb(e),o="rgb"}return t?{model:o,value:t}:null},c.get.rgb=function(e){if(!e)return null;var t,o,n,a=/^#([a-f0-9]{3,4})$/i,l=/^#([a-f0-9]{6})([a-f0-9]{2})?$/i,s=/^rgba?\(\s*([+-]?\d+)\s*,\s*([+-]?\d+)\s*,\s*([+-]?\d+)\s*(?:,\s*([+-]?[\d\.]+)\s*)?\)$/,c=/^rgba?\(\s*([+-]?[\d\.]+)\%\s*,\s*([+-]?[\d\.]+)\%\s*,\s*([+-]?[\d\.]+)\%\s*(?:,\s*([+-]?[\d\.]+)\s*)?\)$/,u=/(\D+)/,h=[0,0,0,1];if(t=e.match(l)){for(n=t[2],t=t[1],o=0;o<3;o++){var p=2*o;h[o]=parseInt(t.slice(p,p+2),16)}n&&(h[3]=Math.round(parseInt(n,16)/255*100)/100)}else if(t=e.match(a)){for(t=t[1],n=t[3],o=0;o<3;o++)h[o]=parseInt(t[o]+t[o],16);n&&(h[3]=Math.round(parseInt(n+n,16)/255*100)/100)}else if(t=e.match(s)){for(o=0;o<3;o++)h[o]=parseInt(t[o+1],0);t[4]&&(h[3]=parseFloat(t[4]))}else{if(!(t=e.match(c)))return(t=e.match(u))?"transparent"===t[1]?[0,0,0,0]:(h=i[t[1]])?(h[3]=1,h):null:null;for(o=0;o<3;o++)h[o]=Math.round(2.55*parseFloat(t[o+1]));t[4]&&(h[3]=parseFloat(t[4]))}for(o=0;o<3;o++)h[o]=r(h[o],0,255);return h[3]=r(h[3],0,1),h},c.get.hsl=function(e){if(!e)return null;var t=/^hsla?\(\s*([+-]?(?:\d*\.)?\d+)(?:deg)?\s*,\s*([+-]?[\d\.]+)%\s*,\s*([+-]?[\d\.]+)%\s*(?:,\s*([+-]?[\d\.]+)\s*)?\)$/,o=e.match(t);if(o){var n=parseFloat(o[4]);return[(parseFloat(o[1])+360)%360,r(parseFloat(o[2]),0,100),r(parseFloat(o[3]),0,100),r(isNaN(n)?1:n,0,1)]}return null},c.get.hwb=function(e){if(!e)return null;var t=/^hwb\(\s*([+-]?\d*[\.]?\d+)(?:deg)?\s*,\s*([+-]?[\d\.]+)%\s*,\s*([+-]?[\d\.]+)%\s*(?:,\s*([+-]?[\d\.]+)\s*)?\)$/,o=e.match(t);if(o){var n=parseFloat(o[4]);return[(parseFloat(o[1])%360+360)%360,r(parseFloat(o[2]),0,100),r(parseFloat(o[3]),0,100),r(isNaN(n)?1:n,0,1)]}return null},c.to.hex=function(){var e=a(arguments);return"#"+n(e[0])+n(e[1])+n(e[2])+(e[3]<1?n(Math.round(255*e[3])):"")},c.to.rgb=function(){var e=a(arguments);return e.length<4||1===e[3]?"rgb("+Math.round(e[0])+", "+Math.round(e[1])+", "+Math.round(e[2])+")":"rgba("+Math.round(e[0])+", "+Math.round(e[1])+", "+Math.round(e[2])+", "+e[3]+")"},c.to.rgb.percent=function(){var e=a(arguments),t=Math.round(e[0]/255*100),o=Math.round(e[1]/255*100),r=Math.round(e[2]/255*100);return e.length<4||1===e[3]?"rgb("+t+"%, "+o+"%, "+r+"%)":"rgba("+t+"%, "+o+"%, "+r+"%, "+e[3]+")"},c.to.hsl=function(){var e=a(arguments);return e.length<4||1===e[3]?"hsl("+e[0]+", "+e[1]+"%, "+e[2]+"%)":"hsla("+e[0]+", "+e[1]+"%, "+e[2]+"%, "+e[3]+")"},c.to.hwb=function(){var e=a(arguments),t="";return e.length>=4&&1!==e[3]&&(t=", "+e[3]),"hwb("+e[0]+", "+e[1]+"%, "+e[2]+"%"+t+")"},c.to.keyword=function(e){return l[e.slice(0,3)]}},function(e,t,o){"use strict";var r=o(19),n=Array.prototype.concat,i=Array.prototype.slice,a=e.exports=function(e){for(var t=[],o=0,a=e.length;o<a;o++){var l=e[o];r(l)?t=n.call(t,i.call(l)):t.push(l)}return t};a.wrap=function(e){return function(){return e(a(arguments))}}},function(e,t,o){"use strict";e.exports=function(e){return!!e&&(e instanceof Array||Array.isArray(e)||e.length>=0&&e.splice instanceof Function)}},function(e,t,o){function r(e){var t=function(t){return void 0===t||null===t?t:(arguments.length>1&&(t=Array.prototype.slice.call(arguments)),e(t))};return"conversion"in e&&(t.conversion=e.conversion),t}function n(e){var t=function(t){if(void 0===t||null===t)return t;arguments.length>1&&(t=Array.prototype.slice.call(arguments));var o=e(t);if("object"==typeof o)for(var r=o.length,n=0;n<r;n++)o[n]=Math.round(o[n]);return o};return"conversion"in e&&(t.conversion=e.conversion),t}var i=o(6),a=o(21),l={};Object.keys(i).forEach(function(e){l[e]={},Object.defineProperty(l[e],"channels",{value:i[e].channels}),Object.defineProperty(l[e],"labels",{value:i[e].labels});var t=a(e);Object.keys(t).forEach(function(o){var i=t[o];l[e][o]=n(i),l[e][o].raw=r(i)})}),e.exports=l},function(e,t,o){function r(){for(var e={},t=Object.keys(l),o=t.length,r=0;r<o;r++)e[t[r]]={distance:-1,parent:null};return e}function n(e){var t=r(),o=[e];for(t[e].distance=0;o.length;)for(var n=o.pop(),i=Object.keys(l[n]),a=i.length,s=0;s<a;s++){var c=i[s],u=t[c];-1===u.distance&&(u.distance=t[n].distance+1,u.parent=n,o.unshift(c))}return t}function i(e,t){return function(o){return t(e(o))}}function a(e,t){for(var o=[t[e].parent,e],r=l[t[e].parent][e],n=t[e].parent;t[n].parent;)o.unshift(t[n].parent),r=i(l[t[n].parent][n],r),n=t[n].parent;return r.conversion=o,r}var l=o(6);e.exports=function(e){for(var t=n(e),o={},r=Object.keys(t),i=r.length,l=0;l<i;l++){var s=r[l];null!==t[s].parent&&(o[s]=a(s,t))}return o}},function(e,t,o){"use strict";function r(e){return e&&e.__esModule?e:{default:e}}function n(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var i=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),a=o(0),l=r(a),s=o(2),c=r(s),u=function(){function e(t){n(this,e),this.colorpicker=t}return i(e,[{key:"bind",value:function(){if(this.colorpicker.options.color)return void(this.color=this.createColor(this.colorpicker.options.color));!this.color&&this.colorpicker.inputHandler.getValue()&&(this.color=this.createColor(this.colorpicker.inputHandler.getValue(),this.colorpicker.options.autoInputFallback))}},{key:"unbind",value:function(){this.colorpicker.element.removeData("color")}},{key:"getColorString",value:function(){return this.hasColor()?this.color.string(this.format):""}},{key:"setColorString",value:function(e){var t=e?this.createColor(e):null;this.color=t||null}},{key:"createColor",value:function(e){var t=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],o=new c.default(this.resolveColorDelegate(e),this.format);return o.isValid()||(t&&(o=this.getFallbackColor()),this.colorpicker.trigger("colorpickerInvalid",o,e)),this.isAlphaEnabled()||(o.alpha=1),o}},{key:"getFallbackColor",value:function(){if(this.fallback&&this.fallback===this.color)return this.color;var e=this.resolveColorDelegate(this.fallback),t=new c.default(e,this.format);return t.isValid()?t:(console.warn("The fallback color is invalid. Falling back to the previous color or black if any."),this.color?this.color:new c.default("#000000",this.format))}},{key:"assureColor",value:function(){return this.hasColor()||(this.color=this.getFallbackColor()),this.color}},{key:"resolveColorDelegate",value:function(e){var t=!(arguments.length>1&&void 0!==arguments[1])||arguments[1],o=!1;return l.default.each(this.colorpicker.extensions,function(r,n){!1===o&&(o=n.resolveColor(e,t))}),o||e}},{key:"isInvalidColor",value:function(){return!this.hasColor()||!this.color.isValid()}},{key:"isAlphaEnabled",value:function(){return!1!==this.colorpicker.options.useAlpha}},{key:"hasColor",value:function(){return this.color instanceof c.default}},{key:"fallback",get:function(){return this.colorpicker.options.fallbackColor?this.colorpicker.options.fallbackColor:this.hasColor()?this.color:null}},{key:"format",get:function(){return this.colorpicker.options.format?this.colorpicker.options.format:this.hasColor()&&this.color.hasTransparency()&&this.color.format.match(/^hex/)?this.isAlphaEnabled()?"rgba":"hex":this.hasColor()?this.color.format:"rgb"}},{key:"color",get:function(){return this.colorpicker.element.data("color")},set:function(e){this.colorpicker.element.data("color",e),e instanceof c.default&&"auto"===this.colorpicker.options.format&&(this.colorpicker.options.format=this.color.format)}}]),e}();t.default=u},function(e,t,o){"use strict";function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var n=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),i=o(0),a=function(e){return e&&e.__esModule?e:{default:e}}(i),l=function(){function e(t){r(this,e),this.colorpicker=t,this.picker=null}return n(e,[{key:"bind",value:function(){var e=this.picker=(0,a.default)(this.options.template);this.options.customClass&&e.addClass(this.options.customClass),this.options.horizontal&&e.addClass("colorpicker-horizontal"),this._supportsAlphaBar()?(this.options.useAlpha=!0,e.addClass("colorpicker-with-alpha")):this.options.useAlpha=!1}},{key:"attach",value:function(){var e=this.colorpicker.container?this.colorpicker.container:null;e&&this.picker.appendTo(e)}},{key:"unbind",value:function(){this.picker.remove()}},{key:"_supportsAlphaBar",value:function(){return(this.options.useAlpha||this.colorpicker.colorHandler.hasColor()&&this.color.hasTransparency())&&!1!==this.options.useAlpha&&(!this.options.format||this.options.format&&!this.options.format.match(/^hex([36])?$/i))}},{key:"update",value:function(){if(this.colorpicker.colorHandler.hasColor()){var e=!0!==this.options.horizontal,t=e?this.options.sliders:this.options.slidersHorz,o=this.picker.find(".colorpicker-saturation .colorpicker-guide"),r=this.picker.find(".colorpicker-hue .colorpicker-guide"),n=this.picker.find(".colorpicker-alpha .colorpicker-guide"),i=this.color.toHsvaRatio();r.length&&r.css(e?"top":"left",(e?t.hue.maxTop:t.hue.maxLeft)*(1-i.h)),n.length&&n.css(e?"top":"left",(e?t.alpha.maxTop:t.alpha.maxLeft)*(1-i.a)),o.length&&o.css({top:t.saturation.maxTop-i.v*t.saturation.maxTop,left:i.s*t.saturation.maxLeft}),this.picker.find(".colorpicker-saturation").css("backgroundColor",this.color.getCloneHueOnly().toHexString());var a=this.color.toHexString(),l="";l=this.options.horizontal?"linear-gradient(to right, "+a+" 0%, transparent 100%)":"linear-gradient(to bottom, "+a+" 0%, transparent 100%)",this.picker.find(".colorpicker-alpha-color").css("background",l)}}},{key:"options",get:function(){return this.colorpicker.options}},{key:"color",get:function(){return this.colorpicker.colorHandler.color}}]),e}();t.default=l},function(e,t,o){"use strict";function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var n=function(){function e(e,t){for(var o=0;o<t.length;o++){var r=t[o];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(t,o,r){return o&&e(t.prototype,o),r&&e(t,r),t}}(),i=function(){function e(t){r(this,e),this.colorpicker=t,this.addon=null}return n(e,[{key:"hasAddon",value:function(){return!!this.addon}},{key:"bind",value:function(){this.addon=this.colorpicker.options.addon?this.colorpicker.element.find(this.colorpicker.options.addon):null,this.addon&&0===this.addon.length&&(this.addon=null)}},{key:"unbind",value:function(){this.hasAddon()&&this.addon.off(".colorpicker")}},{key:"update",value:function(){if(this.colorpicker.colorHandler.hasColor()&&this.hasAddon()){var e=this.colorpicker.colorHandler.getColorString(),t={background:e},o=this.addon.find("i").eq(0);o.length>0?o.css(t):this.addon.css(t)}}}]),e}();t.default=i}])});
//# sourceMappingURL=bootstrap-colorpicker.min.js.map
    var SERVER_URL="index.php";

class BootstrapIconWrapper {
  constructor() {
    // Top navi buttons
    this.book = '<svg class="bi bi-book" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M5.214 3.072c1.599-.32 3.702-.363 5.14 1.074a.5.5 0 01.146.354v11a.5.5 0 01-.854.354c-.843-.844-2.115-1.059-3.47-.92-1.344.14-2.66.617-3.452 1.013A.5.5 0 012 15.5v-11a.5.5 0 01.276-.447L2.5 4.5l-.224-.447.002-.001.004-.002.013-.006a5.116 5.116 0 01.22-.103 12.958 12.958 0 012.7-.869zM3 4.82v9.908c.846-.343 1.944-.672 3.074-.788 1.143-.118 2.387-.023 3.426.56V4.718c-1.063-.929-2.631-.956-4.09-.664A11.958 11.958 0 003 4.82z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M14.786 3.072c-1.598-.32-3.702-.363-5.14 1.074A.5.5 0 009.5 4.5v11a.5.5 0 00.854.354c.844-.844 2.115-1.059 3.47-.92 1.344.14 2.66.617 3.452 1.013A.5.5 0 0018 15.5v-11a.5.5 0 00-.276-.447L17.5 4.5l.224-.447-.002-.001-.004-.002-.013-.006-.047-.023a12.582 12.582 0 00-.799-.34 12.96 12.96 0 00-2.073-.609zM17 4.82v9.908c-.846-.343-1.944-.672-3.074-.788-1.143-.118-2.386-.023-3.426.56V4.718c1.063-.929 2.631-.956 4.09-.664A11.956 11.956 0 0117 4.82z" clip-rule="evenodd"/> \
</svg>';
    this.pencil = '<svg class="bi bi-pencil" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M13.293 3.293a1 1 0 011.414 0l2 2a1 1 0 010 1.414l-9 9a1 1 0 01-.39.242l-3 1a1 1 0 01-1.266-1.265l1-3a1 1 0 01.242-.391l9-9zM14 4l2 2-9 9-3 1 1-3 9-9z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M14.146 8.354l-2.5-2.5.708-.708 2.5 2.5-.708.708zM5 12v.5a.5.5 0 00.5.5H6v.5a.5.5 0 00.5.5H7v.5a.5.5 0 00.5.5H8v-1.5a.5.5 0 00-.5-.5H7v-.5a.5.5 0 00-.5-.5H5z" clip-rule="evenodd"/> \
</svg>';
    this.folder = '<svg class="bi bi-folder" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path d="M11.828 6a3 3 0 01-2.12-.879l-.83-.828A1 1 0 008.173 4H4.5a1 1 0 00-1 .981L3.546 6h-1L2.5 5a2 2 0 012-2h3.672a2 2 0 011.414.586l.828.828A2 2 0 0011.828 5v1z"/> \
  <path fill-rule="evenodd" d="M15.81 6H4.19a1 1 0 00-.996 1.09l.637 7a1 1 0 00.995.91h10.348a1 1 0 00.995-.91l.637-7A1 1 0 0015.81 6zM4.19 5a2 2 0 00-1.992 2.181l.637 7A2 2 0 004.826 16h10.348a2 2 0 001.991-1.819l.637-7A2 2 0 0015.81 5H4.19z" clip-rule="evenodd"/> \
</svg>';
    this.cloud_upload = '<svg class="bi bi-cloud-upload" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path d="M6.887 8.2l-.964-.165A2.5 2.5 0 105.5 13H8v1H5.5a3.5 3.5 0 11.59-6.95 5.002 5.002 0 119.804 1.98A2.501 2.501 0 0115.5 14H12v-1h3.5a1.5 1.5 0 00.237-2.982L14.7 9.854l.216-1.028a4 4 0 10-7.843-1.587l-.185.96z"/> \
  <path fill-rule="evenodd" d="M7 10.854a.5.5 0 00.707 0L10 8.56l2.293 2.293a.5.5 0 00.707-.707L10.354 7.5a.5.5 0 00-.708 0L7 10.146a.5.5 0 000 .708z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M10 8a.5.5 0 01.5.5v8a.5.5 0 01-1 0v-8A.5.5 0 0110 8z" clip-rule="evenodd"/> \
</svg>';
    this.x_circle = '<svg class="bi bi-x-circle" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M10 17a7 7 0 100-14 7 7 0 000 14zm0 1a8 8 0 100-16 8 8 0 000 16z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M12.646 13.354l-6-6 .708-.708 6 6-.708.708z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M7.354 13.354l6-6-.708-.708-6 6 .708.708z" clip-rule="evenodd"/> \
</svg>';
    this.x_circle_fill = '<svg class="bi bi-x-circle-fill" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7.354 6.646L10 9.293l2.646-2.647a.5.5 0 01.708.708L10.707 10l2.647 2.646a.5.5 0 01-.708.708L10 10.707l-2.646 2.647a.5.5 0 01-.708-.708L9.293 10 6.646 7.354a.5.5 0 11.708-.708z" clip-rule="evenodd"/> \
</svg>';

    // Parts edit buttons
    this.plus = '<svg class="bi bi-plus" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M10 5.5a.5.5 0 01.5.5v4a.5.5 0 01-.5.5H6a.5.5 0 010-1h3.5V6a.5.5 0 01.5-.5z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M9.5 10a.5.5 0 01.5-.5h4a.5.5 0 010 1h-3.5V14a.5.5 0 01-1 0v-4z" clip-rule="evenodd"/> \
</svg>';
    this.chevron_up = '<svg class="bi bi-chevron-up" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M9.646 6.646a.5.5 0 01.708 0l6 6a.5.5 0 01-.708.708L10 7.707l-5.646 5.647a.5.5 0 01-.708-.708l6-6z" clip-rule="evenodd"/> \
</svg>';
    this.chevron_down = '<svg class="bi bi-chevron-down" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M3.646 6.646a.5.5 0 01.708 0L10 12.293l5.646-5.647a.5.5 0 01.708.708l-6 6a.5.5 0 01-.708 0l-6-6a.5.5 0 010-.708z" clip-rule="evenodd"/> \
</svg>';
    this.arrow_up = '<svg class="bi bi-arrow-up" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M10 5.5a.5.5 0 01.5.5v9a.5.5 0 01-1 0V6a.5.5 0 01.5-.5z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M9.646 4.646a.5.5 0 01.708 0l3 3a.5.5 0 01-.708.708L10 5.707 7.354 8.354a.5.5 0 11-.708-.708l3-3z" clip-rule="evenodd"/> \
</svg>';
    this.arrow_down = '<svg class="bi bi-arrow-down" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path fill-rule="evenodd" d="M6.646 11.646a.5.5 0 01.708 0L10 14.293l2.646-2.647a.5.5 0 01.708.708l-3 3a.5.5 0 01-.708 0l-3-3a.5.5 0 010-.708z" clip-rule="evenodd"/> \
  <path fill-rule="evenodd" d="M10 4.5a.5.5 0 01.5.5v9a.5.5 0 01-1 0V5a.5.5 0 01.5-.5z" clip-rule="evenodd"/> \
</svg>';
    this.trash = '<svg class="bi bi-trash" width="1em" height="1em" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> \
  <path d="M7.5 7.5A.5.5 0 018 8v6a.5.5 0 01-1 0V8a.5.5 0 01.5-.5zm2.5 0a.5.5 0 01.5.5v6a.5.5 0 01-1 0V8a.5.5 0 01.5-.5zm3 .5a.5.5 0 00-1 0v6a.5.5 0 001 0V8z"/> \
  <path fill-rule="evenodd" d="M16.5 5a1 1 0 01-1 1H15v9a2 2 0 01-2 2H7a2 2 0 01-2-2V6h-.5a1 1 0 01-1-1V4a1 1 0 011-1H8a1 1 0 011-1h2a1 1 0 011 1h3.5a1 1 0 011 1v1zM6.118 6L6 6.059V15a1 1 0 001 1h6a1 1 0 001-1V6.059L13.882 6H6.118zM4.5 5V4h11v1h-11z" clip-rule="evenodd"/> \
</svg>';
  }
}


class PageContent {
  constructor(page_content_id) {
    this.TEXTAREA_MAX_HEIGHT = 400;
    this.page_data = null;
    this.page_content_id = page_content_id;
    this.on_change_func = null;
    this.page_data_has_changed = false;

    if ($(this.page_content_id).length != 1) {
      console.error("PageContent: Given "+this.page_content_id+" points to "+$(this.page_content_id).length+" objects");
    }

    console.log("Executed PageContent constructor");

    this.fields = {};

    this.fields.page_values = {
      'title': 'Title',
      'favicon-ico': 'Favicon (URL)',
      'description': 'Description',
      'image': 'Sharing Image',
      'keywords': 'Keywords',
      'style-css': 'Custom CSS'
    };

    this.fields.section_values = {
      'margin': "Margin",
      'padding': "Padding",
      'height': "Height",
      'color': "Font color",
      'background-image': "Background image (URL)",
      'font-family-google': "Google font name"
    }
  }

  get_parts_count() {
    if (this.page_data == null) {
      return 0;
    }

    if (this.page_data.parts == null) {
      return 0;
    }

    return this.page_data.parts.length;
  }

  render_editor_input(field, name) {
    if (field == "color") {
      return '<input class="page_field color_field form-control" type="text" name="'+name+'" id="'+name+'">';
    }

    if (field == "text") {
      return '<textarea class="section_field form-control" name="'+name+'" id="'+name+'" rows="5"></textarea>';
    }

    // Fallback: single-line text
    return '<input class="page_field form-control" type="text" name="'+name+'" id="'+name+'">';
  }

  render_editor_fields_page() {
    var html = [];

    // Fields for page attributes
    html.push('<div class="section_page">');
    for (var field in this.fields.page_values) {
      var name = 'page_'+field;

      html.push('<div class="row">'
        +'<div class="col-4">'+this.fields.page_values[field]+'</div>'
        +'<div class="col-8">'+this.render_editor_input(field, name)+'</div>'
        +'</div>');
    }
    html.push('</div>');

    return html.join("\n");
  }

  render_editor_fields_section(n) {
    var html = [];
    var biw = new BootstrapIconWrapper();

    var name_advanced = "section_advanced section_advanced_"+n;

    html.push('<div class="section_group">');

    var name='section_'+n+'_text';
    html.push('<div class="row"><div class="col-12"><label for="'+name+'" class="label_text">Text</label>'+this.render_editor_input('text', name)+'</div></div>');
    html.push('<div class="row"><div class="col-12">');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_advanced" data-partnumber="'+n+'">'+biw.chevron_down+'</button>');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_move_down" data-partnumber="'+n+'">'+biw.arrow_down+'</button>');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_move_up" data-partnumber="'+n+'">'+biw.arrow_up+'</button>');
    html.push('<button type="button" class="btn btn-secondary btn-sm button_part button_add_part" data-partnumber="'+n+'">'+biw.plus+'</button>');
    html.push('<button type="button" class="btn btn-danger btn-sm button_part button_delete_part" data-partnumber="'+n+'">'+biw.trash+'</button>');
    html.push('</div></div>');

    for (var field in this.fields.section_values) {
      var name = 'section_'+n+'_'+field;

      html.push('<div class="row '+name_advanced+'">'
        +'<div class="col-4">'+this.fields.section_values[field]+'</div>'
        +'<div class="col-8">'+this.render_editor_input(field, name)+'</div>'
        +'</div>');
    }

    html.push('</div>');

    return html.join("\n");
  }

  render_editor_sectionborder() {
    // Currently there is no constantly visible section border
    return '';
  }

  render_editor() {
    var html = [];

    // Page-level fields
    html.push(this.render_editor_fields_page());
    html.push(this.render_editor_sectionborder());

    // Fields for each existing section
    var html_section = [];
    for (var n=0; n < this.get_parts_count(); n++) {
      html_section.push(this.render_editor_fields_section(n))
    }

    html.push(html_section.join(this.render_editor_sectionborder()));

    $(".page_field").off();
    $(".section_field").off();
    $(".color_field").off();

    $(this.page_content_id).html(html.join("\n"));

    this.advanced_hide();

    $(".page_field").on('keyup', {obj: this}, this.update_object_value);
    $(".section_field").on('keyup', {obj: this}, this.update_object_value);

    $(".button_advanced").on("click", {obj: this}, this.button_advanced_toggle);
    $(".button_move_down").on("click", {obj: this}, this.button_move_down);
    $(".button_move_up").on("click", {obj: this}, this.button_move_up);
    $(".button_add_part").on("click", {obj: this}, this.button_add_part);
    $(".button_delete_part").on("click", {obj: this}, this.button_delete_part);


    $(".button_move_up[data-partnumber=0]").attr("disabled", true);
    $(".button_move_down[data-partnumber="+(this.get_parts_count()-1)+"]").attr("disabled", true);
  }

  advanced_hide() {
    var biw = new BootstrapIconWrapper();
    $(".section_advanced").css('display', 'none');
    $(".button_advanced").html(biw.chevron_down);
  }

  button_advanced_toggle(event) {
    var biw = new BootstrapIconWrapper();
    var part = $(this).attr('data-partnumber');

    if ($(".section_advanced_"+part).css('display') == 'none') {
      event.data.obj.advanced_hide();
      $(".section_advanced_"+part).css('display', 'flex');
      $(this).html(biw.chevron_up);
    }
    else {
      $(".section_advanced_"+part).css('display', 'none');
      $(this).html(biw.chevron_down);
    }
  }

  button_add_part(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_insert(part+1);
  }

  part_insert(part) {
    var new_page_data = this.page_data;
    new_page_data.parts.splice(part, 0, {});
    this.set_data_internal(new_page_data, true);
  }

  button_move_up(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_move(part, part-1);
  }

  button_move_down(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_move(part, part+1);
  }

  part_move(part_from, part_to) {
    console.log("move from "+part_from+" to "+part_to);
    var tmp = this.page_data.parts[part_to];
    this.page_data.parts[part_to] = this.page_data.parts[part_from];
    this.page_data.parts[part_from] = tmp;
    var new_page_data = this.page_data;
    this.set_data_internal(new_page_data, true);
  }

  button_delete_part(event) {
    var part = parseInt($(this).attr('data-partnumber'));
    event.data.obj.part_delete(part);
  }

  part_delete(part) {
    var new_page_data = this.page_data;
    new_page_data.parts.splice(part, 1);
    this.set_data_internal(new_page_data, true);
  }

  get_luma(hex_color) {
    if (typeof hex_color != "string") {
      return 0;
    }

    var c = hex_color.substring(1);      // strip #
    var rgb = parseInt(c, 16);   // convert rrggbb to decimal
    var r = (rgb >> 16) & 0xff;  // extract red
    var g = (rgb >>  8) & 0xff;  // extract green
    var b = (rgb >>  0) & 0xff;  // extract blue

    return 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709
  }

  update_color_field(event) {
    var selector = "#"+event.target.id;
    $(selector).css('background-color', $(selector).val());

    var luma = this.get_luma($(selector).val());
    if (luma > 230) {
      $(selector).css("color", "#495057");
    }
    else if (luma > 128) {
      $(selector).css("color", "black")
    }
    else {
      $(selector).css("color", "white");
    }
  }

  update_object_value(event) {
    var target_attrs = event.target.id.split("_");
    var changed = false;
    var new_value = event.target.value;

    if (target_attrs[0] == "page") {
      var old_value = event.data.obj.page_data.page_values[target_attrs[1]];

      if (old_value != new_value) {
        if (old_value != undefined || (new_value != "")) {
          changed = true;
          event.data.obj.page_data.page_values[target_attrs[1]] = new_value;
        }
      }
    }

    if (target_attrs[0] == "section") {
      var old_value = event.data.obj.page_data.parts[target_attrs[1]][target_attrs[2]];

      if (old_value != new_value) {
        if (old_value != undefined || (new_value != "")) {
          changed = true;
          event.data.obj.page_data.parts[target_attrs[1]][target_attrs[2]] = new_value;
        }
      }
    }

    if (target_attrs[2] == "color") {
      event.data.obj.update_color_field(event);
    }

    if (changed) {
      event.data.obj.page_data_has_changed = true;
      event.data.obj.on_change_call();
    }
  }

  update_editor_values() {
    // Update page attributes
    for (var field in this.fields.page_values) {
      var name = '#page_'+field;
      if (this.page_data.page_values[field] != null) {
        $(name).val(this.page_data.page_values[field]);
      }
    }

    // Update sections
    for (var n=0; n < this.get_parts_count(); n++) {
      for (var field in this.fields.section_values) {
        var name = '#section_'+n+'_'+field;

        if (this.page_data.parts[n][field] != null) {
          $(name).val(this.page_data.parts[n][field]);
        }
      }

      if (this.page_data.parts[n].text != null) {
        $("#section_"+n+"_text").val(this.page_data.parts[n].text);
      }
    }
  }

  activate_colorpicker() {
    $(".color_field").colorpicker({
      useAlpha:false,
      fallbackColor:"#ffffff"
    });

    $(".color_field").on("colorpickerChange", {obj: this}, this.update_object_value);

    // Reset color backgrounds
    $(".color_field").each(function () {
      $(this).trigger("keyup", {obj: this});
    });
  }

  activate_textarea_autoheight() {
    $('textarea').off('input');

    var obj = this;

    $('textarea').each(function () {
      var new_height = this.scrollHeight;
      if (new_height > obj.TEXTAREA_MAX_HEIGHT) {
        new_height = obj.TEXTAREA_MAX_HEIGHT;
      }
      this.setAttribute('style', 'height:' + (new_height) + 'px;overflow-y:hidden;');
    }).on('input', function () {
      this.style.height = 'auto';
      this.style.height = (this.scrollHeight) + 'px';
    });
  }

  on_change(func) {
    this.on_change_func = func;
  }

  on_change_call() {
    if (this.on_change_func != null) {
      this.on_change_func();
    }
  }

  set_data_internal(data, data_has_changed) {
    this.page_data = data;

    this.render_editor();
    this.update_editor_values();
    this.activate_colorpicker();
    this.activate_textarea_autoheight();

    this.page_data_has_changed = data_has_changed;
    this.on_change_call();
  }

  set_data(data) {
    this.set_data_internal(data, false);
  }

  get_data() {
    this.page_data_has_changed = false;
    return this.page_data;
  }
}

class FileContent {
  constructor(file_content_id) {
    this.file_data = null;
    this.file_content_id = file_content_id;

    if ($(this.file_content_id).length != 1) {
      console.error("FileContent: Given "+this.file_content_id+" points to "+$(this.file_content_id).length+" objects");
    }
  }

  get_files_count() {
    if (this.file_data == null) {
      return 0;
    }

    return this.file_data.length;
  }

  get_files_table_html() {
    var body_rows = [];
    var biw = new BootstrapIconWrapper();

    if (this.get_files_count() == 0) {
      return "<p>No files. Upload one:</p>";
    }

    for (var n=0; n < this.get_files_count(); n++) {
      var html = "<tr>";
      html += "<td>"+this.file_data[n].name+"</td>";
      html += "<td>"+this.file_data[n].size+"</td>";
      html += "<td><button type='button' class='btn btn-danger btn-sm button_file_delete' data-filename='"+this.file_data[n].name+"'>"+biw.x_circle_fill+"</button>";
      html += "</tr>";
      body_rows.push(html);
    }

    return "<table class='table'><thead><tr><th scope='col'>Filename</th><th scope='col'>Size</th><th scope='col'>Delete</th></tr></thead><tbody>"+body_rows.join('')+"</tbody></table>";
  }

  set_data(data) {
    this.file_data = data;
    $(this.file_content_id).html(this.get_files_table_html());
  }
}


var page_content = null;
var file_content = null;

function update_edit() {
  var post_data = {
    type:"POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: "get"
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $("#login_content").hide();
        $("#header_publish").show();
        mode_set('edit');
        page_content.set_data(data_obj.data);
      }
      else {
        $("#message_loginfailed").show();
        $("#password").val('');
        setTimeout(function () { $("#password").focus(); }, 1);
        console.error("update_edit() failed. Retrieved data:", data_obj);
        if (data_obj.message != "") {
          alert(data_obj.message);
        }
      }
    })
    .fail(function(data) {
      alert("update_edit() failed. See console");
      console.error("update_edit() failed. Retrieved data:", data);
    });
}

function set_data() {
  var post_data = {
    type: "POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: "set",
      data: JSON.stringify(page_content.get_data())
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $("#button_publish").addClass("btn-success");
        update_header_publish();
        setTimeout(function() { $("#button_publish").removeClass("btn-success"); mode_set('edit'); }, 1000);
      }
      else {
        alert("set_data() failed. See console");
        console.error("set_data() failed. Data:", data_obj);
      }
    })
    .fail(function(data) {
      alert("set_data() failed. See console");
      console.error("set_data() failed. Data:", data);
    });
}

function update_preview() {
  var post_data = {
    type: "POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: "preview",
      data: JSON.stringify(page_content.get_data())
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $("#page_content_preview").html(data_obj.data.html);

        // Handle Google font CSS links
        $("[href*='https://fonts.googleapis.com/css?family='][rel='stylesheet']").remove();
        $("head").append(data_obj.data.head);

        mode_set('preview');
      }
      else {
        alert("update_preview() failed. See console");
        console.error("update_preview() failed. Data:", data_obj);
      }
    })
    .fail(function(data) {
      alert("update_preview() failed. See console");
      console.error("update_preview() failed. Data:", data);
    });
}

function update_file(backend_function, filename) {
  if (backend_function == undefined) {
    backend_function = "file_list";
  }

  var post_data = {
    type: "POST",
    url: SERVER_URL,
    data: {
      password: $("#password").val(),
      function: backend_function,
      data: filename
    }
  };

  var jqxhr = $.post(post_data)
    .done(function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $(".button_file_delete").off();

        mode_set('file');
        file_content.set_data(data_obj.data);

        activate_file_delete_buttons();
      }
      else {
        alert("update_file() failed. See console.");
        console.error("update_file() failed. Data:", data_obj);
      }
    })
    .fail(function(data) {
      alert("update_file() failed. See console");
      console.error("update_file() failed. Data:", data);
    });
}

function upload_file() {
  if ($("#file_upload").val() == "") {
    return;
  }

  var data = new FormData();
  data.append('file_upload', $('#file_upload')[0].files[0]);
  data.append('password', $("#password").val());
  data.append('function', 'file_upload');

  $.ajax({
    url: SERVER_URL,
    data: data,
    cache: false,
    contentType: false,
    processData: false,
    method: 'POST',
    success: function(data) {
      var data_obj = JSON.parse(data);

      if (data_obj.success) {
        $(".button_file_delete").off();

        mode_set('file');
        file_content.set_data(data_obj.data);

        activate_file_delete_buttons();

        $("#file_upload").val("");
        update_upload_filename();
      }
      else {
        if (data_obj.message != "") {
          alert("File upload failed: "+data_obj.message);
        }
        else {
          alert("upload_file() failed. See console.");
          console.error("upload_file() failed. Data:", data_obj);
        }
      }
    },
    error: function(data, error) {
      alert("upload_file() failed. See console.");
      console.error("upload_file() failes. Data:", data, error);
    }
  });
}

function update_upload_filename() {
  var filename = $("#file_upload").val();
  filename = filename.split(/(\\|\/)/g).pop();

  if (filename === "") {
    filename = "Choose file";
  }

  $("#file_upload_label").text(filename);
}

function mode_set(mode) {
  $(".button_mode").prop("disabled", false);
  $("#button_"+mode+"_mode").prop("disabled", true);

  $(".page_content").hide();
  $("#page_content_"+mode).show();

  $(".container").css('padding-top',$("#header_publish_inner").height()+50);
}

function update_header_publish() {
  if (page_content.page_data_has_changed) {
    $("#button_publish").prop("disabled", false);
    $("#button_cancel").prop("disabled", false);
  }
  else {
    $("#button_publish").prop("disabled", true);
    $("#button_cancel").prop("disabled", true);
  }
}

function update_header_buttons() {
  var biw = new BootstrapIconWrapper();

  $("#button_preview_mode").html(biw.book);
  $("#button_edit_mode").html(biw.pencil);
  $("#button_file_mode").html(biw.folder);
  $("#button_publish").html(biw.cloud_upload);
  $("#button_cancel").html(biw.x_circle);
}

function activate_file_delete_buttons() {
  $(".button_file_delete").click(function () {
    update_file("file_delete", $(this).attr('data-filename'));
  });
}

$(document).ready(function () {
  console.log("AdminUI.js is ready!");

  // Header is show after successful login
  $("#header_publish").hide();
  update_header_buttons();

  mode_set('edit');

  page_content = new PageContent("#page_content_edit");
  file_content = new FileContent("#page_content_file_inner");

  $("#button_login").click(function () {
    update_edit();
  });

  $("#button_edit_mode").click(function() {
    mode_set('edit');
  });

  $("#button_preview_mode").click(function () {
    update_preview();
  });

  $("#button_file_mode").click(function () {
    update_file();
  });

  $("#button_cancel").click(function () {
    if (confirm("Are you sure you want to discard your changes?")) {
      update_edit();
    }
  });

  $("#button_publish").click(function () {
    set_data();
  });

  $("#button_file_upload").click(function () {
    upload_file();
  });

  // Login when enter pressed
  $("#password").on("keypress", function (e) {
    if (e.which == 13) {
      update_edit();
    }
    $("#message_loginfailed").hide();
  });

  page_content.on_change(function () {
    update_header_publish();
  });

  $("#file_upload").change(function () {
    update_upload_filename();
  });

  setTimeout(function () { $("#password").focus(); }, 1);
});

  </script>
</head>
<body>
  <header id="header_publish">
    <nav id="header_publish_inner" class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
      <div class="navbar-nav navbar-nav-center">
        <div class="btn-group button_single" role="group">
          <button type="button" id="button_edit_mode" class="button_header button_mode btn btn-primary"></button>
          <button type="button" id="button_preview_mode" class="button_header button_mode btn btn-primary"></button>
          <button type="button" id="button_file_mode" class="button_header button_mode btn btn-primary"></button>
        </div>
        <button type="button" id="button_publish" class="button_header button_single btn btn-primary"></button>
        <button type="button" id="button_cancel" class="button_header button_single btn btn-primary"></button>
      </div>
    </nav>
  </header>

  <div class="container">
    <form onsubmit="return false">
      <div id="login_content">
        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" class="form-control" />
        </div>

        <div class="form-group">
          <input type="button" id="button_login" value="Log In" class="btn btn-primary">
        </div>

        <div class="form-group">
          <div id="message_loginfailed" class="alert alert-warning" role="alert">Please check your password</div>
        </div>
      </div>
    </form>

    <div id="page_content_edit" class="page_content">
    </div>

    <div id="page_content_preview" class="page_content">
    </div>

    <div id="page_content_file" class="page_content">
      <div id="page_content_file_inner">
      </div>

      <form onsubmit="return false">
        <div class="input-group">
          <div class="custom-file">
            <input type="file" class="custom-file-input" id="file_upload">
            <label class="custom-file-label" for="button_file_upload" id="file_upload_label">Choose file</label>
          </div>
          <div class="input-group-append">
            <span class="input-group-text" id="button_file_upload">Upload</span>
          </div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

    <?php
  }
}

?>

<?php

include_once("global_functions.php");

class ShowPage {
  private $version = "";
  private $page_content = null;

  function __construct($version, $datapath, $page_json=null) {
    $this->version = $version;

    if (is_null($page_json)) {
      $datafile = $datapath."/content.json";

      if (is_readable($datafile)) {
        $this->page_content = new PageContent($datafile, $datapath);
      } else {
        log_message("Data file $datafile is not readable", 1, 0);
      }
    }
    else {
      $this->page_content = new PageContent($page_json, $datapath);
    }
  }

  function get_html_page() {
    return $this->render_header().$this->render_content().$this->render_footer();
  }

  function get_html_preview() {
    return $this->get_body_style_tag().$this->render_content();
  }

  function get_html_googlefonts() {
    return $this->get_html_tag('<link href="https://fonts.googleapis.com/css?family=###&display=swap" rel="stylesheet" />', $this->page_content->get_page_google_fonts_value());
  }

  private function get_html_tag($html, $value) {
    if (!is_null($value) and $value != "") {
      return preg_replace('/###/', $value, $html);
    }

    return null;
  }

  private function array_push_if_set(&$array, $element) {
    if (!is_null($element) and $element != "") {
      array_push($array, $element);
    }
  }

  private function get_body_style_tag() {
    return "<style>#page table { margin: 0 auto; } #page img { max-width: 100%; } #page { font-family: Arial,Helvetica,sans-serif; }</style>";
  }

  function render_header() {
    $head_tags = Array();

    $this->array_push_if_set($head_tags, $this->get_html_tag('<!-- This landing page has been created with ### -->', $this->version));

    $this->array_push_if_set($head_tags, '<meta charset="UTF-8"><meta http-equiv="content-type" content="text/html; charset=utf-8" />');
    $this->array_push_if_set($head_tags, '<meta name="viewport" content="width=device-width, initial-scale=1.0" />');
    $this->array_push_if_set($head_tags, $this->get_html_tag('<title>###</title>', $this->page_content->get_page_value('title')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<link href="https://fonts.googleapis.com/css?family=###&display=swap" rel="stylesheet" />', $this->page_content->get_page_google_fonts_value()));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<link rel="icon" href="###" type="image/x-icon" />', $this->page_content->get_page_value('favicon-ico')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<link rel="shortcut icon" href="###" type="image/x-icon" />', $this->page_content->get_page_value('favicon-ico')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta name="description" content="###" />', $this->page_content->get_page_value('description')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta name="keywords" content="###" />', $this->page_content->get_page_value('keywords')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<style>###</style>', $this->page_content->get_page_value('style-css')));

    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:site_name" content="###" />', $this->page_content->get_page_value('title')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:title" content="###" />', $this->page_content->get_page_value('title')));
    $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:description" content="###" />', $this->page_content->get_page_value('description')));

    if (strlen($this->page_content->get_page_value('image')) > 0) {
      $this->array_push_if_set($head_tags, $this->get_html_tag('<meta property="og:image" content="###" />', get_my_url().$this->page_content->get_page_value('image')));
    }

    $html = "<!DOCTYPE html>\n<html>\n<head>";
    $html .= join("\n", $head_tags)."\n";
    $html .= $this->get_body_style_tag()."</head>";
    $html .= "<body style='margin:0; padding:0;'>";

    return $html;
  }

  function render_footer() {
    return "</body></html>\n";
  }

  function render_content() {
    $part_count = $this->page_content->get_parts_count();

    $html = "";

    if (is_null($part_count)) {
      log_message("Page does not contain any parts", null, 1);
    } else {
      for ($n=0; $n < $part_count; $n++) {
        $html .= $this->render_part($n);
      }
    }

    return "<div id='page'>".$html."</div>";
  }

  function render_part($index) {
    $parsedown = new Parsedown();

    $style_tags = Array();

    $this->array_push_if_set($style_tags, $this->get_html_tag(
      "background-image:url('###'); background-position: center; background-repeat: no-repeat; background-size: cover; ",
      $this->page_content->get_part($index, 'background-image')
    ));
    $this->array_push_if_set($style_tags, $this->get_html_tag("height:###;", $this->page_content->get_part($index, 'height')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("font-family:'###', cursive;", $this->page_content->get_part($index, 'font-family-google')));

    $this->array_push_if_set($style_tags, $this->get_html_tag("margin:###;", $this->page_content->get_part($index, 'margin', '10px')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("padding:###;", $this->page_content->get_part($index, 'padding', '0')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("color:###;", $this->page_content->get_part($index, 'color', '#000000')));
    $this->array_push_if_set($style_tags, $this->get_html_tag("text-align:###;", $this->page_content->get_part($index, 'text-align', 'center')));

    $html = "<section id=\"sec".$index."\" style=\"".join(" ", $style_tags)."\">";

    $html .= $parsedown->text($this->page_content->get_part($index, 'text'));

    $html .= '</section>';

    return $html;
  }

}

?>

