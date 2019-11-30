<?php

$VERSION = "Dummylander 0.1";
$DATAFILE = "data/content.json";
$ADMIN_PASSWORD = "secret";

log_message(@$_SERVER['QUERY_STRING']);
if (@$_SERVER['QUERY_STRING'] == "admin") {
  $admin_ui = new ShowAdminUI();
}
elseif (@$_POST['password'] != "" and $_POST['password'] == $ADMIN_PASSWORD) {
  $admin_api = new AdminAPI($DATAFILE, @$_POST['function'], @$_POST['data']);
  echo($admin_api->execute());
}
else {
  $show_page = new ShowPage($VERSION, $DATAFILE);
}

// Normal termination
exit(0);


function log_message ($message, $exit_level = null) {
  // Write log message to server log
  error_log($message, 4);

  if (!is_null($exit_level)) {
    exit($exit_level);
  }
}



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

class AdminAPI {
  private $page_storage = null;
  private $function = null;
  private $data = null;

  function __construct($data_file, $function, $data) {
    $this->page_storage = new PageStorage($data_file);
    $this->function = $function;
    $this->data = $data;
  }

  private function get_return_data($success, $data = null) {
    $return_data = Array(
      'success' => $success
    );

    if (!is_null($data)) {
      $return_data['data'] = $data;
    }

    return json_encode($return_data);
  }

  function execute() {
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
  }
}

?>

<?php

class PageContent {
  public $page_data = null;

  public function __construct($data_file) {
    $json = file_get_contents($data_file);
    $this->page_data = json_decode($json, true);
  }

  public function get_page_value($field, $default=null) {
    if (is_null($this->page_data) or
      !array_key_exists('page_values', $this->page_data) or
      !array_key_exists($field, $this->page_data['page_values'])) {
        return $default;
    }

    return $this->page_data['page_values'][$field];
  }

  // Returns value you can give to Google Fonts CSS tag, e.g. "Playfair+Display|Tomorrow"
  // <link href="https://fonts.googleapis.com/css?family=Playfair+Display|Tomorrow&display=swap" rel="stylesheet" />
  // In case no Google Fonts are used returns null

  public function get_page_google_fonts_value() {
    $fonts_used = Array();

    for ($n=0; $n < $this->get_parts_count(); $n++) {
      if (!is_null($this->get_part($n, 'font-family-google'))) {
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
      log_message("The page content has no field 'parts'");
      return $default;
    }

    if (!array_key_exists($index, $this->page_data['parts'])) {
      log_message("The page content has not field 'parts'->$index");
      return $default;
    }

    if (!array_key_exists($field, $this->page_data['parts'][$index])) {
      log_message("The page content has no field 'parts'->$index->$field");
      return $default;
    }

    return $this->page_data['parts'][$index][$field];
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

class ShowAdminUI {
  function __construct() {
    ?>
    <!DOCTYPE html>
<html>
<head>
  <title>DummyLander AdminUI</title>
  <meta charset="UTF-8">
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
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

class PageContent {
  constructor(page_content_id) {
    this.page_data = null;
    this.page_content_id = page_content_id;

    if ($(this.page_content_id).length != 1) {
      console.error("PageContent: Given "+this.page_content_id+" points to "+$(this.page_content_id).length+" objects");
    }

    console.log("Executed PageContent constructor");

    this.fields = {};

    this.fields.page_values = {
      'title': 'Title',
      'favicon-ico': 'Favicon (URL)',
      'description': 'Description'
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

    html.push('<div class="section_group">');
    for (var field in this.fields.section_values) {
      var name = 'section_'+n+'_'+field;

      html.push('<div class="row">'
        +'<div class="col-4">'+this.fields.section_values[field]+'</div>'
        +'<div class="col-8">'+this.render_editor_input(field, name)+'</div>'
        +'</div>');
    }

    var name='section_'+n+'_text';

    html.push('<div class="row"><div class="col-12"><label for="'+name+'">Text</label>'+this.render_editor_input('text', name)+'</div></div>');
    html.push('</div>');

    return html.join("\n");
  }

  render_editor_sectionborder() {
    return '<div class="row"><div class="col-12"><hr></div></div>';
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

    $(".page_field").on('keyup change', {obj: this}, this.update_object_value);
    $(".section_field").on("keyup change", {obj: this}, this.update_object_value);
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

    if (target_attrs[0] == "page") {
      event.data.obj.page_data.page_values[target_attrs[1]] = event.target.value;
    }

    if (target_attrs[0] == "section") {
      event.data.obj.page_data.parts[target_attrs[1]][target_attrs[2]] = event.target.value;
    }

    if (target_attrs[2] == "color") {
      event.data.obj.update_color_field(event);
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

    // Reset color backgrounds
    //var obj = this;
    $(".color_field").each(function () {
      $(this).trigger("change", {obj: this});
    });
  }

  set_data(data) {
    this.page_data = data;

    this.render_editor();
    this.update_editor_values();
    this.activate_colorpicker();
  }

  get_data() {
    return this.page_data;
  }
}


var page_content = new PageContent("#page_content");

function get_data() {
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
        page_content.set_data(data_obj.data);
      }
      else {
        alert("get_data() failed. See console");
        console.error("get_data() failed. Retrieved data:", data_obj);
      }
    })
    .fail(function(data) {
      alert("get_data() failed. See console");
      console.error("get_data() failed. Retrieved data:", data);
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
        alert("Page data was saved");
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

$(document).ready(function () {
  console.log("AdminUI.js is ready!");

  $("#button_get").click(function () {
    get_data();
  });

  $("#button_set").click(function () {
    set_data();
  });
});

  </script>
</head>
<body>
  <div class="container">
    <h1>AdminUI</h1>

    <form>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="form-control" />
      </div>

      <div class="form-group">
        <input type="button" id="button_get" value="Load Data" class="btn btn-primary">
        <input type="button" id="button_set" value="Save Data" class="btn btn-primary">
      </div>

      <div id="page_content">
      </div>
    </form>
  </div>
</body>
</html>

    <?php
  }
}

?>

<?php

class ShowPage {
  private $version = "";

  function __construct($version, $datafile) {
    $this->version = $version;

    if (is_readable($datafile)) {
      $page = new PageContent($datafile);

      $this->render_header($page);
      $this->render_content($page);
      $this->render_footer($page);
    } else {
      log_message("Data file $datafile is not readable", 1);
    }
  }

  private function get_html_tag($html, $value) {
    $tag = "";

    if (!is_null($value)) {
      $tag = preg_replace('/###/', $value, $html);
      log_message('Adding tag: '.$tag);
    }

    return $tag;
  }

  function render_header($page) {
    $head_tags = Array();

    array_push($head_tags, $this->get_html_tag('<!-- This landing page has been created with ### -->', $this->version));

    array_push($head_tags, '<meta charset="UTF-8"><meta http-equiv="content-type" content="text/html; charset=utf-8" />');
    array_push($head_tags, '<meta name="viewport" content="width=device-width, initial-scale=1.0" />');
    array_push($head_tags, $this->get_html_tag('<title>###</title>', $page->get_page_value('title')));
    array_push($head_tags, $this->get_html_tag('<link href="https://fonts.googleapis.com/css?family=###&display=swap" rel="stylesheet" />', $page->get_page_google_fonts_value()));
    array_push($head_tags, $this->get_html_tag('<link rel="icon" href="###" type="image/x-icon" />', $page->get_page_value('favicon-ico')));
    array_push($head_tags, $this->get_html_tag('<link rel="shortcut icon" href="###" type="image/x-icon" />', $page->get_page_value('favicon-ico')));
    array_push($head_tags, $this->get_html_tag('<meta name="description" content="###" />', $page->get_page_value('description')));

    array_push($head_tags, $this->get_html_tag('<meta property="og:site_name" content="###" />', $page->get_page_value('title')));
    array_push($head_tags, $this->get_html_tag('<meta property="og:title" content="###" />', $page->get_page_value('title')));
    array_push($head_tags, $this->get_html_tag('<meta property="og:description" content="###" />', $page->get_page_value('description')));

    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <?php echo(join("\n", $head_tags)."\n"); ?>
      <style>
        table { margin: 0 auto; }
      </style>
    </head>
    <body style="margin:0; padding: 0;
      font-family: <?php echo($page->get_page_value('font-family', 'Arial,Helvetica,sans-serif')); ?>
    ">
    <?php
  }

  function render_footer($page) {
    ?>
    </body>
    </html>
    <?php
  }

  function render_content($page) {
    $part_count = $page->get_parts_count();

    if (is_null($part_count)) {
      log_message("Page does not contain any parts");
    } else {
      for ($n=0; $n < $part_count; $n++) {
        $this->render_part($page, $n);
      }
    }
  }

  function render_part($page, $index) {
    $parsedown = new Parsedown();

    $style_tags = Array();

    array_push($style_tags, $this->get_html_tag(
      "background-image:url('###'); background-position: center center;",
      $page->get_part($index, 'background-image')
    ));
    array_push($style_tags, $this->get_html_tag("height:###;", $page->get_part($index, 'height')));
    array_push($style_tags, $this->get_html_tag("font-family:###, cursive;", $page->get_part($index, 'font-family-google')));

    array_push($style_tags, $this->get_html_tag("margin:###;", $page->get_part($index, 'margin', '10px')));
    array_push($style_tags, $this->get_html_tag("padding:###;", $page->get_part($index, 'padding', '0')));
    array_push($style_tags, $this->get_html_tag("color:###;", $page->get_part($index, 'color', '#000000')));
    array_push($style_tags, $this->get_html_tag("text-align:###;", $page->get_part($index, 'text-align', 'center')));

    ?>
      <section
        id="sec<?php echo($index); ?>"
        style="
          <?php echo(join("\n", $style_tags)."\n"); ?>
          "
      >

      <?php echo($parsedown->text($page->get_part($index, 'text'))); ?>

      </section>
    <?php
    log_message($page->get_part($index, 'text'));
  }

}

?>

