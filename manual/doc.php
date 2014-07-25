<?php
/** 
 *           File:  doc.php
 *         Author:  Alvan
 *       Modifier:  Alvan
 *       Modified:  2014-07-25
 *    Description:  Generate PHP manual for Vim
 *          Usage:  1. Download PHP manual from http://php.net/get/php_manual_en.tar.gz/from/a/mirror
 *                  2. Extract all files into an directory src/
 *                  3. Run in terminal: php doc.php
 *                  4. Use Vim to open doc/tags, do :%sort and :wq
 */
error_reporting(E_ALL);
ini_set('display_errors', 'on');

define('DIR_SRC', __DIR__ . '/src/');
define('DIR_TMP', __DIR__ . '/tmp/');
define('DIR_DOC', __DIR__ . '/doc/');
define('NUM_COL', 78);
define('STR_TAB', "    ");

$dir = dir(DIR_SRC);
$dir OR exit('Failed to open src directory');

file_exists(DIR_TMP) OR mkdir(DIR_TMP, 0777, true);
file_exists(DIR_DOC) OR mkdir(DIR_DOC, 0777, true);
file_exists(DIR_DOC . 'tags') AND unlink(DIR_DOC . 'tags');

while (false !== ($src = $dir->read()))
{
	if (preg_match('/^function\./', $src))
	{
		printf("[%s] %s" . PHP_EOL, date('c'), 'Processing ' . $src);

		$tmp = DIR_TMP . $src;
		$doc = DIR_DOC . preg_replace('/^function\./', '', preg_replace('/html$/', 'txt', $src));

		$htm = file_get_contents($dir->path . $src);
		if (preg_match('#<h1[^>]*>([^<]+)</h1>#', $htm, $mas))
			$tag = $mas[1];
		else
			continue;

		$htm = preg_replace_callback('#(<div class="methodsynopsis dc-description">)(.+?)(</div>)#s', function($mas) {
			return $mas[1] . "<br>" . wordwrap(
				preg_replace('#(?:\s+){2,}#', ' ', trim(str_replace(array("\r", "\n"), '', strip_tags($mas[2])))),
				NUM_COL - 1 - strlen(STR_TAB),
				'~<br>'
			) . '~' .  $mas[3];
		}, $htm);
		$htm = preg_replace_callback('#(<h3[^>]*>)(.+?)(</h3>)#', function($mas) {
			return $mas[1] . str_repeat('=', NUM_COL) . '<br>*' . implode('* *', explode(' ', $mas[2])) . '*' . $mas[3];
		}, $htm);
		$htm = preg_replace_callback('#(<strong[^>]+class="command"[^>]*>)(.+?)(</strong>)#', function($mas) {
			return $mas[1] . '`' . implode('` `', explode(' ', $mas[2])) . '`' . $mas[3];
		}, $htm);
		$htm = preg_replace('#(<code class="parameter">)([^$]+?)(</code>)#', '\1{\2}\3', $htm);
		$htm = preg_replace('#(<a[^>]*href="function\.[^.]+\.html"[^>]*>)([^\(]+?)(?:\(\))?(</a>)#', '\1|\2|\3', $htm);
		$htm = preg_replace('#<div[^>]+class="manualnavbar".*?</div><hr(?: /)?>#s', '', $htm);
		$htm = preg_replace('#<hr(?: /)?><div[^>]+class="manualnavbar".*?</div></body>#s', '</body>', $htm);
		
		file_put_contents($tmp, $htm);
		system(sprintf("w3m -cols %d -t %d -o indent_incr=%d -s -no-graph %s > %s", NUM_COL + 1, strlen(STR_TAB), strlen(STR_TAB), $tmp, $doc));

		$txt = file_get_contents($doc);
		$txt = preg_replace('#^\s?([^\s].+~)$#m', STR_TAB . '\1', $txt);
		$txt = preg_replace('#^(\s*?<\?php\s*?)$#m', '\1 >', $txt);
		$txt = preg_replace_callback('#^(<\?php\s+>)(.+?)([\r\n]+\?>)$#ms', function($mas) {
			return $mas[1] . preg_replace('#([\r\n]+)#', '\1' . STR_TAB, $mas[2]) . $mas[3];
		}, $txt);
		$txt = preg_replace('#^([\t ]*\?>\s*?)$#m', '<\1', $txt);

		$txt .= PHP_EOL . "vim:ft=help:";
		file_put_contents($doc, $txt);

		file_put_contents(DIR_DOC . 'tags', "${tag}\t" . basename($doc) . "\t/^${tag}\n", FILE_APPEND | LOCK_EX);
	}
}
