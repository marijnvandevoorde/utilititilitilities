<?php

namespace Sevenedge\View\Helper;

use Cake\View\Helper;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;

class SEMinifierHelper extends Helper {
	
	public $helpers = ['Html'];
	
	
	public function afterRenderFile($event, $viewFile, $content) {
		if (!Configure::read('debug')) {
			// delete all unncessery newlines
			$search = array(
				'/\>[^\S ]+/s',  // strip whitespaces after tags, except space
				'/[^\S ]+\</s',  // strip whitespaces before tags, except space
				'/(\s)+/s'       // shorten multiple whitespace sequences
			);

			$replace = array(
				'>',
				'<',
				'\\1'
			);

			$content = preg_replace($search, $replace, $content);
		}
		
		return $content;
	}
	
	/**
     * Minifies css from a file or an array of files, using default HtmlHelper to output HTML
     * @param String / Array $path
     * @return String
     **/
	public function css($path, array $options = []) {
		if (!Configure::read('debug')) {
			if (gettype($path) === 'string') {
				$path = [$path];
			}
			// define new filename for all paths in the array
			$filename = '';
			foreach ($path as $p) {
				$filename .= ($filename === '' ? '' : '--') . str_replace('=', '-', str_replace('?', '-', $p));
			}
			$filename = 'semin.' . $filename . '.css';
			$full_filename =  WWW_ROOT . 'css' . DS . $filename;
			
			$filename = str_replace(DS, '/', $filename);
			
			if (file_exists($full_filename)) {
				// just return cached file
				return $this->Html->css([$filename], $options);
			} else {
				// here we go, find and replace the content in each files and put them togheter in a string
				$output = '';
				foreach ($path as $p) {
					$filepath = WWW_ROOT . 'css' . DS . $p;
					$arr = explode('?', $filepath);
					$contents = file_get_contents($arr[0]);
					
					// remove all comments
					$contents = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $contents);
					
					// remove all whitespace
					$contents = preg_replace("/\s{2,}/", " ", $contents);
					
					// remove all newlines
					$contents = str_replace("\n", '', str_replace("\r", '', $contents));
					
					// remove all spaces before and after the { sign
					$contents = str_replace(' {', '{', str_replace('{ ', '{', $contents));
					
					// remove all spaces before and after the , sign
					$contents = str_replace(', ', ',', str_replace(' ,', ',', $contents));
					
					$output .= $contents;
				}
				
				// create file
				$file = fopen($full_filename, 'w') or die('Unable to open file "' . $full_filename . '", make sure your CSS directory is present and writable by PHP!');
				fwrite($file, $output);
				fclose($file);
				
				return $this->Html->css([$filename], $options);
			}
		} else {
			return $this->Html->css($path, $options);
		}
    }
	
	/**
     * Minifies js from a file or an array of files, using default HtmlHelper to output HTML
     * @param String / Array $url
     * @return String
     **/
	public function script($url, array $options = [], $already_minified = false) {
		if (!Configure::read('debug')) {
			if (gettype($url) === 'string') {
				$url = [$url];
			}
			// define new filename for all paths in the array
			$filename = '';
			foreach ($url as $p) {
				$filename .= ($filename === '' ? '' : '_') . str_replace('vendor', 'vn', str_replace('.pkgd', '', str_replace('.min', '', str_replace('.js', '', str_replace('/', '-', str_replace('=', '-', str_replace('jquery', 'jq', str_replace('?', '-', $p))))))));
			}
			$filename = 'semin.' . $filename . '.js';
			$full_filename =  WWW_ROOT . 'js' . DS . $filename;
			
			$filename = str_replace(DS, '/', $filename);
			
			if (file_exists($full_filename)) {
				// just return cached file
				return $this->Html->script([$filename], $options);
			} else {
				// here we go, find and replace the content in each files and put them togheter in a string
				$output = '';
				// concatenate all files (in a string)
				foreach ($url as $p) {
					$filepath = WWW_ROOT . 'js' . DS . $p;
					$arr = explode('?', $filepath);
					$contents = file_get_contents($arr[0]);
					$output .= ';' . $contents;
				}
				if (!$already_minified) {
					// use the class below to minify
					$output = JSMin::minify($output);
				}
				
				// create file
				$file = fopen($full_filename, 'w') or die('Unable to open file "' . $full_filename . '" (strlen: ' . strlen($full_filename) . '), make sure your JS directory is present and writable by PHP, and the combined lenght of the filenames are not to long!');
				fwrite($file, $output);
				fclose($file);
				
				return $this->Html->script([$filename], $options);
			}
		} else {
			return $this->Html->script($url, $options);
		}
	}
	
}


// FOUND AT https://github.com/rgrove/jsmin-php
/**
 * jsmin.php - PHP implementation of Douglas Crockford's JSMin.
 *
 * This is pretty much a direct port of jsmin.c to PHP with just a few
 * PHP-specific performance tweaks. Also, whereas jsmin.c reads from stdin and
 * outputs to stdout, this library accepts a string as input and returns another
 * string as output.
 *
 * PHP 5 or higher is required.
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.c, which has the following license:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * --
 *
 * @package JSMin
 * @author Ryan Grove <ryan@wonko.com>
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * @copyright 2012 Adam Goforth <aag@adamgoforth.com> (Updates)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @version 1.1.2 (2012-05-01)
 * @link https://github.com/rgrove/jsmin-php
 */
class JSMin {
  const ORD_LF            = 10;
  const ORD_SPACE         = 32;
  const ACTION_KEEP_A     = 1;
  const ACTION_DELETE_A   = 2;
  const ACTION_DELETE_A_B = 3;
  protected $a           = '';
  protected $b           = '';
  protected $input       = '';
  protected $inputIndex  = 0;
  protected $inputLength = 0;
  protected $lookAhead   = null;
  protected $output      = '';
  // -- Public Static Methods --------------------------------------------------
  /**
   * Minify Javascript
   *
   * @uses __construct()
   * @uses min()
   * @param string $js Javascript to be minified
   * @return string
   */
  public static function minify($js) {
    $jsmin = new JSMin($js);
    return $jsmin->min();
  }
  // -- Public Instance Methods ------------------------------------------------
  /**
   * Constructor
   *
   * @param string $input Javascript to be minified
   */
  public function __construct($input) {
    $this->input       = str_replace("\r\n", "\n", $input);
    $this->inputLength = strlen($this->input);
  }
  // -- Protected Instance Methods ---------------------------------------------
  /**
   * Action -- do something! What to do is determined by the $command argument.
   *
   * action treats a string as a single character. Wow!
   * action recognizes a regular expression if it is preceded by ( or , or =.
   *
   * @uses next()
   * @uses get()
   * @throws JSMinException If parser errors are found:
   *         - Unterminated string literal
   *         - Unterminated regular expression set in regex literal
   *         - Unterminated regular expression literal
   * @param int $command One of class constants:
   *      ACTION_KEEP_A      Output A. Copy B to A. Get the next B.
   *      ACTION_DELETE_A    Copy B to A. Get the next B. (Delete A).
   *      ACTION_DELETE_A_B  Get the next B. (Delete B).
  */
  protected function action($command) {
    switch($command) {
      case self::ACTION_KEEP_A:
        $this->output .= $this->a;
      case self::ACTION_DELETE_A:
        $this->a = $this->b;
        if ($this->a === "'" || $this->a === '"') {
          for (;;) {
            $this->output .= $this->a;
            $this->a       = $this->get();
            if ($this->a === $this->b) {
              break;
            }
            if (ord($this->a) <= self::ORD_LF) {
              throw new JSMinException('Unterminated string literal.');
            }
            if ($this->a === '\\') {
              $this->output .= $this->a;
              $this->a       = $this->get();
            }
          }
        }
      case self::ACTION_DELETE_A_B:
        $this->b = $this->next();
        if ($this->b === '/' && (
            $this->a === '(' || $this->a === ',' || $this->a === '=' ||
            $this->a === ':' || $this->a === '[' || $this->a === '!' ||
            $this->a === '&' || $this->a === '|' || $this->a === '?' ||
            $this->a === '{' || $this->a === '}' || $this->a === ';' ||
            $this->a === "\n" )) {
          $this->output .= $this->a . $this->b;
          for (;;) {
            $this->a = $this->get();
            if ($this->a === '[') {
              /*
                inside a regex [...] set, which MAY contain a '/' itself. Example: mootools Form.Validator near line 460:
                  return Form.Validator.getValidator('IsEmpty').test(element) || (/^(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]\.?){0,63}[a-z0-9!#$%&'*+/=?^_`{|}~-]@(?:(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\])$/i).test(element.get('value'));
              */
              for (;;) {
                $this->output .= $this->a;
                $this->a = $this->get();
                if ($this->a === ']') {
                    break;
                } elseif ($this->a === '\\') {
                  $this->output .= $this->a;
                  $this->a       = $this->get();
                } elseif (ord($this->a) <= self::ORD_LF) {
                  throw new JSMinException('Unterminated regular expression set in regex literal.');
                }
              }
            } elseif ($this->a === '/') {
              break;
            } elseif ($this->a === '\\') {
              $this->output .= $this->a;
              $this->a       = $this->get();
            } elseif (ord($this->a) <= self::ORD_LF) {
              throw new JSMinException('Unterminated regular expression literal.');
            }
            $this->output .= $this->a;
          }
          $this->b = $this->next();
        }
    }
  }
  /**
   * Get next char. Convert ctrl char to space.
   *
   * @return string|null
   */
  protected function get() {
    $c = $this->lookAhead;
    $this->lookAhead = null;
    if ($c === null) {
      if ($this->inputIndex < $this->inputLength) {
        $c = substr($this->input, $this->inputIndex, 1);
        $this->inputIndex += 1;
      } else {
        $c = null;
      }
    }
    if ($c === "\r") {
      return "\n";
    }
    if ($c === null || $c === "\n" || ord($c) >= self::ORD_SPACE) {
      return $c;
    }
    return ' ';
  }
  /**
   * Is $c a letter, digit, underscore, dollar sign, or non-ASCII character.
   *
   * @return bool
   */
  protected function isAlphaNum($c) {
    return ord($c) > 126 || $c === '\\' || preg_match('/^[\w\$]$/', $c) === 1;
  }
  /**
   * Perform minification, return result
   *
   * @uses action()
   * @uses isAlphaNum()
   * @uses get()
   * @uses peek()
   * @return string
   */
  protected function min() {
    if (0 == strncmp($this->peek(), "\xef", 1)) {
        $this->get();
        $this->get();
        $this->get();
    } 
    $this->a = "\n";
    $this->action(self::ACTION_DELETE_A_B);
    while ($this->a !== null) {
      switch ($this->a) {
        case ' ':
          if ($this->isAlphaNum($this->b)) {
            $this->action(self::ACTION_KEEP_A);
          } else {
            $this->action(self::ACTION_DELETE_A);
          }
          break;
        case "\n":
          switch ($this->b) {
            case '{':
            case '[':
            case '(':
            case '+':
            case '-':
            case '!':
            case '~':
              $this->action(self::ACTION_KEEP_A);
              break;
            case ' ':
              $this->action(self::ACTION_DELETE_A_B);
              break;
            default:
              if ($this->isAlphaNum($this->b)) {
                $this->action(self::ACTION_KEEP_A);
              }
              else {
                $this->action(self::ACTION_DELETE_A);
              }
          }
          break;
        default:
          switch ($this->b) {
            case ' ':
              if ($this->isAlphaNum($this->a)) {
                $this->action(self::ACTION_KEEP_A);
                break;
              }
              $this->action(self::ACTION_DELETE_A_B);
              break;
            case "\n":
              switch ($this->a) {
                case '}':
                case ']':
                case ')':
                case '+':
                case '-':
                case '"':
                case "'":
                  $this->action(self::ACTION_KEEP_A);
                  break;
                default:
                  if ($this->isAlphaNum($this->a)) {
                    $this->action(self::ACTION_KEEP_A);
                  }
                  else {
                    $this->action(self::ACTION_DELETE_A_B);
                  }
              }
              break;
            default:
              $this->action(self::ACTION_KEEP_A);
              break;
          }
      }
    }
    return $this->output;
  }
  /**
   * Get the next character, skipping over comments. peek() is used to see
   *  if a '/' is followed by a '/' or '*'.
   *
   * @uses get()
   * @uses peek()
   * @throws JSMinException On unterminated comment.
   * @return string
   */
  protected function next() {
    $c = $this->get();
    if ($c === '/') {
      switch($this->peek()) {
        case '/':
          for (;;) {
            $c = $this->get();
            if (ord($c) <= self::ORD_LF) {
              return $c;
            }
          }
        case '*':
          $this->get();
          for (;;) {
            switch($this->get()) {
              case '*':
                if ($this->peek() === '/') {
                  $this->get();
                  return ' ';
                }
                break;
              case null:
                throw new JSMinException('Unterminated comment.');
            }
          }
        default:
          return $c;
      }
    }
    return $c;
  }
  /**
   * Get next char. If is ctrl character, translate to a space or newline.
   *
   * @uses get()
   * @return string|null
   */
  protected function peek() {
    $this->lookAhead = $this->get();
    return $this->lookAhead;
  }
}
// -- Exceptions ---------------------------------------------------------------
class JSMinException extends Exception {}