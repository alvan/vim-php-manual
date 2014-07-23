<?php
/*=============================================================================
#     FileName: doc.php
#         Desc: generate vim php doc from php docs source.
#       Author: Jörn Horstmann<http://blog.planetxml.de/archives/16-PHP-Manual-in-VIM-einbinden.html>
#
#       Modified: Alvan
#   LastChange: 2014-07-23
#         Desc: Add reference and default value support of function parameters.
#
#       Modified:荒野无灯,Gasol
#        Email: admin@ihacklog.com
#     HomePage: http://ihacklog.com/?p=5298
#      URL: http://blog.planetxml.de/uploads/source/php/phpdoc/parser2.php.txt
#   LastChange: 2012-01-07 14:23:40
#
#      Usage:
#		sudo pear install XML_Parser
#		svn co http://svn.php.net/repository/phpdoc/modules/doc-en phpdoc
#		php doc.php
#
#		sort the tags file, use vim to open doc/tags and run :
#		:%sort
#		:wq
#		
=============================================================================*/

require_once('XML/Parser.php');
error_reporting(E_ALL);

define('OUT_PATH', 'doc/');
define('TAGS_PATH', OUT_PATH . 'tags');

mkdir(OUT_PATH, 0777, true);

$entities = array(
    '&lt;'    => '<',
    '&gt;'    => '>',
    '&amp;'   => '&',
    '&quot;'  => '"',
    '&apos;'  => '"',
);
$tags     = array();

function load_entities($path)
{
    global $entities;
    $data = file_get_contents($path);
    preg_match_all('/<!ENTITY\s+(\S+)\s+(["\'])(.*?)\\2>/sm', $data, $regs, PREG_SET_ORDER);
    //print_r($regs);
    foreach ($regs as $r) {
        $entities['&' . $r[1] . ';'] = $r[3];
    }
}

function array_top($arr)
{
    $c = count($arr);
    if ($c == 0)
        return null;
    return $arr[$c-1];
}

class Parser extends XML_Parser
{
    var $_elStack = array();
    var $_cdata   = '';

    var $manual = array();
    var $paratext = '';

    var $methodname = '';
    var $methodtype = '';

    function Parser()
    {
        $this->folding = false;
        $this->XML_Parser();
    }

    function format_function_ref($func)
    {
        return '|' . $func . '|';
    }

    function format_param_ref($param)
    {
        return '{' . $param . '}';
    }

    function format_text($text)
    {
        return wordwrap(
            preg_replace('/\s+/', ' ', trim($text)),
            78
        );
    }

    function format_tag($tag)
    {
        $tmp = sprintf("%78s", '*php:' . $tag . '*');
        $this->tag = '/^' . $tmp . '$/';
        return $tmp;
    }

    function format_title($title, $desc)
    {
        return $this->format_text(preg_replace('/\s+/', ' ', trim($title . ' -- ' . $desc)));
    }

    function format_help_tag($title)
    {
        global $tags;
        if (isset($tags[$title])) {
            $i = '#' . ++$tags[$title];
        }
        else {
            $tags[$title] = 1;
            $i = '';
        }
        return sprintf("%78s", '*' . $title . $i . '*');
    }

    /*
    function format_description($name, $desc)
    {
        $name = '*php:' . $name . '()*';
        return sprintf("%78s\n%s", $name, wordwrap('' . trim(ucwords($desc)), 78));
    }
    */

    function format_proto($type, $name, $param)
    {
        $text = '  ' . $type . ' ' . $name .'(';
        $opt  = 0;
        $c    = count($param);
        for ($i=0; $i<$c; $i++) {
            $p = $param[$i];
            if ($p[2]) {
                if ($i > 0) {
                    $text .= ' ';
                }
                $text .= '[';
                $opt++;
            }
            if ($i > 0) {
                $text .= ', ';
            }
            $text .= $p[0] . ' ' . $p[1];

			if (isset($p[3])) {
				$text .= ' = ' . $p[3];
			}
        }
        for ($i=0; $i<$opt; $i++) {
            $text .= ']';
        }
        $text .= ')~';

        return $text;
    }

    function format_code($text)
    {
        /*
        return '  ' . str_repeat(
            '..', 34) . "\n : " . 
            preg_replace('/\r?\n/', "\n : ", trim($text));
            */
        return preg_replace(
            '/^\s*<\?php\s*$/m',
            '<?php >', 
            preg_replace(
                '/^\s+\?>/m',
                '?>',
                '  ' . trim(
                    preg_replace(
                        '/\r?\n/',
                        "\n  ",
                        $text
                    )
                )
            )
        );
    }

    function startHandler($xp, $elem, &$attribs)
    {
        if ($this->_cdata) {
            $top  = array_top($this->_elStack);
            $el   = $top[0];
            $attr = $top[1];
            $this->handleElement($el, $attr, $this->_cdata, false);
        }
        array_push($this->_elStack, array($elem, $attribs));
        $this->_cdata = '';
    }

    function endHandler($xp, $elem)
    {
        $top  = array_top($this->_elStack);
        $el   = $top[0];
        $attr = $top[1];
        $this->handleElement($el, $attr, $this->_cdata, true);
        array_pop($this->_elStack);
        $this->_cdata = '';
    }

    function cdataHandler($xp, $data)
    {
        $this->_cdata .= $data;
    }

    function inElement($name)
    {
        $c = count($this->_elStack);
        //print_r($this->_elStack);

        for ($i=$c-1; $i>=0; $i--) {
            if ($this->_elStack[$i][0] == $name) {
                return true;
            }
        }
        return false;
    }

    function handleElement($name, $attr, $data, $final)
    {
        if ($this->inElement('refnamediv')) {
            if ($name == 'refname') {
                $this->refname = trim($data);
            }
            elseif ($this->inElement('refpurpose')) {
                $this->refpurpose = isset($this->refpurpose) ? $this->refpurpose . $data : $data;
            }
            if (($name == 'refnamediv') && $final) {
                //$this->paragraphlist[] = $this->format_help_tag($this->refname);
                $this->paragraphlist[] = $this->format_title($this->refname, $this->refpurpose);
            }
        }
        elseif ($this->inElement('methodsynopsis')) {
            if ($this->inElement('methodparam')) {
                if ($name == 'type') {
                    $this->paramtype = trim($data);
                }
                elseif ($name == 'parameter') {
                    $this->paramname = ((isset($attr['role']) && $attr['role'] == 'reference') ? '&' : '') . trim($data);
                }
                elseif ($name == 'initializer') {
                    $this->paraminit = trim($data);
                }
                elseif (($name == 'methodparam') && $final) {
                    $this->paramlist[] = array(
                        isset($this->paramtype) ? trim($this->paramtype) : '',
                        trim($this->paramname),
						isset($attr['choice']) && ($attr['choice'] == 'opt'),
                        isset($this->paraminit) ? trim($this->paraminit) : null,
                    );
                }
            }
            elseif ($name == 'type') {
                $this->methodtype = trim($data);
            }
            elseif ($name == 'methodname') {
                $this->methodname = trim($data);
            }
            elseif (($name == 'methodsynopsis') && $final) {
                $this->proto = $this->format_proto(
                    $this->methodtype,
                    $this->methodname,
                    isset($this->paramlist) ? $this->paramlist : array());
                $this->paragraphlist[] = $this->proto;
            }
        }
        elseif ($this->inElement('programlisting') || $this->inElement('screen') || $this->inElement('literallayout')) {
            if (!ctype_space($this->paratext)) {
                $this->paragraphlist[] = $this->format_text($this->paratext);
                $this->paratext = '';
            }
            $this->paragraphlist[] = $this->format_code($data);
        }
        elseif ($this->inElement('para') || $this->inElement('simpara') || $this->inElement('example')) {
            if ($name == 'function') {
                $this->paratext .= $this->format_function_ref($data);
            }
            elseif ($name == 'parameter') {
                $this->paratext .= $this->format_param_ref($data);
            }
            elseif (($name == 'para' || $name == 'simpara' || $name == 'example') && $final) {
                if (!ctype_space($data)) {
                    $this->paratext .= $data;
                }
                if (!ctype_space($this->paratext))
                    $this->paragraphlist[] = $this->format_text($this->paratext);
                $this->paratext = '';
            }
            else {
                $this->paratext .= $data;
            }
        }
    }
}

function process_all($path)
{
    if ($dir = opendir($path)) {
        while ($fname = readdir($dir)) {
            $fname2 = $path . '/' . $fname;
            if (is_dir($fname2) && ($fname != '.') && ($fname != '..') && ($fname != 'CVS')) {
                process_all($fname2);
                echo "recursing into $fname2\n";
            }
            elseif (preg_match('#[\/\\\\]\w+[\/\\\\]functions[\/\\\\]([a-zA-Z0-9\._-]+)\.xml$#', $fname2, $regs)) {
                process_file($fname2, OUT_PATH . $regs[1] . '.txt');
            }
        }
        closedir($dir);
    }
}

function process_file($in, $out)
{
    global $entities;

    echo "processing $in\n";
    $p =& new Parser();
    $data = file_get_contents($in);
    $data = strtr($data, $entities);
    $data = strtr($data, $entities);
    /*
    $data = strtr($data, array(
            '&true;'  => 'TRUE',
            '&false;' => 'FALSE',
            '&null;'  => 'NULL',
            '&lt;'    => '<',
            '&gt;'    => '>',
            '&amp;'   => '&',
            '&quot;'  => '"',
            '&apos;'  => '"',
            '&deg;'   => '°',
            '&return.success;' => 'Returns TRUE on success or FALSE on failure.',
            '&warn.undocumented.func;' => '',
            '&note.gd.2;' => 'This function requires GD 2.0.1 or later.',
            '&php.ini;' => 'php.ini',
            '&url.mysql.docs.error;' => 'http://dev.mysql.com/doc/mysql/en/Error-returns.html',
            '&example.outputs;' => 'The above example will output something similar to:'
    ));
    */

    $data = preg_replace('/\&([^;]+;)/', '&amp;\\1', $data);

    $res = $p->setInputString($data);
    $res = $p->parse();

    $fp = fopen($out, 'wb') or die("Could not open $out!");

    fwrite($fp, implode("\n\n", $p->paragraphlist));

    fwrite($fp, "\n\nvim:ft=help:\n");
    fclose($fp);

    if ($p->refname) {
        /*
        if (!empty($p->proto))
            $tag = '/^' . $p->proto . '$/';
        else
            $tag = '1';
        */
        $tag = '/^' . $p->refname;
        $fp = fopen(TAGS_PATH, 'ab') or die("Could not open tags!");
        fwrite($fp, $p->refname . "\t" . basename($out) . "\t" . $tag . "\n");
        fclose($fp);
    }
}


$fp = fopen(TAGS_PATH, 'wb') or die("Could not open tags!");
//fwrite($fp, "!_TAG_FILE_FORMAT	2\n");
//fwrite($fp, "!_TAG_FILE_SORTED	1\n");
fclose($fp);

load_entities('phpdoc/doc-base/entities/global.ent');
//load_entities('phpdoc/doc-base/entities/version.ent');
//load_entities('phpdoc/doc-base/entities/file-entities.ent');
load_entities('phpdoc/en/language-defs.ent');
load_entities('phpdoc/en/language-snippets.ent');
load_entities('phpdoc/en/extensions.ent');
load_entities('phpdoc/en/contributors.ent');
//print_r($entities['&example.outputs;']);
//print_r($entities);
process_all('phpdoc/en/reference');
//process_all('d:/htdocs/livedocs/phpdoc/en/reference');

echo "sort tags\n";
system('sort ' . TAGS_PATH. ' -o ' . TAGS_PATH);
