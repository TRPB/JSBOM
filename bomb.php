<?php 
opcache_reset();


$file = $_GET['file'];

$bombJs = 'jsbomb.js';

header('content-type: text/javascript');

function matchBraces($str, $braces = "{}") {
	$stack  = array();
	$result = array();
	$pos = -1;
	$end = strlen($str) + 1;

	while(TRUE) {
		$p1 = strpos($str, $braces{0}, $pos + 1);
		$p2 = strpos($str, $braces{1}, $pos + 1);
		$pos = min(
				($p1 === FALSE) ? $end : $p1,
				($p2 === FALSE) ? $end : $p2);
		if($pos == $end)
			break;
		if($str{$pos} == $braces{0})
			array_push($stack, $pos);
		else if($str{$pos} == $braces{1}){
			if(!count($stack))
				user_error("odd closebrace at offset $pos");
			else
				$result[array_pop($stack)] = $pos;
		}
	};
	if(count($stack))
		user_error("odd openbrace at offset ".array_pop($stack));
	ksort($result);
	return $result;
}


class JSBCompile {
	private $class;
	
	public function __construct(JSBClass $class) {
		$this->class = $class;
	}
	
	private $visibility = [
				'public' => 'BaseObject.VISIBILITY_PUBLIC',
				'private' => 'BaseObject.VISIBILITY_PRIVATE',
				'protected' => 'BaseObject.VISIBILITY_PROTECTED'
			];
	
	public function output() {
		$str = 'function ' . $this->class->class . '() {' . "\n";
		$str .= "\t" . 'var baseObject = new BaseObject(\'' . $this->class->class . '\', null, null);' . "\n\n";
		foreach ($this->class->properties as $property) {
			$str .= "\t" . 'baseObject.addProperty(\'' . $property->name . '\', \'' .  $property->type . '\', ' . $this->visibility[$property->visibility] . ');' . "\n";
			$str .= "\t" . 'Object.defineProperty(this, \'' . $property->name .  '\' , baseObject.getGetSet(\'' . $property->name .  '\'));' . "\n\n";
		}

		foreach ($this->class->methods as $method) {
			$str .=  "\t" . 'this.' . $method->name . ' = baseObject.addMethod(\'' . $method->name . '\', '
				 . $this->visibility[$method->visibility] . ', \'' 
				 . $method->returnType . '\', function ' . $this->class->class . '(' . implode(', ', array_keys($method->args)) . ') {';
			
			$str .= $method->body . "\n";
			
			$str .= "\t" . '}, ' . json_encode($method->args) . ');' . "\n\n";
			//this.setName = baseObject.addMethod('setName', BaseObject.VISIBILITY_PUBLIC, 'void', function Person(name) {
			//	this.name = name;
			//}, {'name': 'string'});
			
			
		}

		
		$str .= '}' . "\n\n";
		return $str;
	}
}

class JSBFile {
	private $contents;
	private $allBraces;
	
	public function __construct($name) {
		$this->contents = '/*JSBOM*/' . file_get_contents($name);		
		$this->getClasses();
	}
	
	public function getClasses() {
		$classes = [];
		$pos = 0;
		
		while ($pos = strpos($this->contents, 'class ', $pos+1)) {
			$braces = matchBraces($this->contents);
			
			$start = $pos;
			$brace = strpos($this->contents, '{', $start);
			$endBrace = $braces[$brace];
			$classHeader = substr($this->contents, $start, $brace-$start);
			
			$parts = preg_split('/\s+/', trim($classHeader));
			
			$keywords = ['class' => null, 'extends' => '', 'implements' => []];
			
			$class = new JSBClass;
			$class->startPosition = $pos;
			$class->endPosition = $endBrace;
			 
			$currentPart = '';
			
			foreach ($parts as $part) {
				if ($part != '' && property_exists($class, strtolower($part))) {
					$currentPart = strtolower($part);
					continue;
				}			
				if (is_array($class->$currentPart)) {
					$arr = &$class->currentPart;
					$arr[] = $part;
				}
				else $class->$currentPart = $part;
			}			
			
			
			$this->getBody(substr($this->contents, $brace, $endBrace-$brace+1), $class);

			$classes[] = $class;
		}
		
		$output = $this->contents;
		
		
		
		foreach ($classes as $class) {
			$c = new JSBCompile($class);
			$replacement = substr($this->contents, $class->startPosition, $class->endPosition-$class->startPosition+1);
			$output = str_replace($replacement, $c->output(), $output);			
		}
		
		echo $output;
	}
	
	public function getBody($classBody, JSBClass $class) {
		$parts = [];		
		$keywords = ['public', 'private', 'protected'];		
		$braces = matchBraces($classBody);
		
		//echo $classBody; die;
		
		foreach ($keywords as $keyword) {			
			$pos = 0;
			
			while ($pos = strpos($classBody, $keyword, $pos+1)) {				
				$brace = strpos($classBody, '{', $pos);
				$semi = strpos($classBody, ';', $pos);

				$header = substr($classBody, $pos, min($brace,$semi)-$pos);
				
				$parts = preg_split('/\s+/', trim($header));
				
				if (strpos($header, '(') !== false) {					
					$method = new JSBMethod;
					$method->visibility = $keyword;
					
					foreach ($parts as $part) {
						if (trim($part) === $keyword) continue;
						if (empty($method->returnType)) $method->returnType = $part;
						else if (empty($method->name)) {
							$ps = explode('(', $part);
							$method->name = $ps[0];
							$method->body = substr($classBody, $brace+1, $braces[$brace]-$brace-1);
							
							$args = substr($header, strpos($header, '(')+1, strpos($header, ')')-strpos($header, '(')-1);
							
							$args = explode(',', $args);
							foreach ($args as $arg) {
								$as = array_filter(array_map('trim', preg_split('/\s+/', trim($arg))));
								if (count($as) === 0) continue;

								if (count($as) == 2) {
									$method->args[$as[1]] = $as[0];
								}
								else if (count($as) == 1) {
									$method->args[$as[0]] = 'mixed';
								}
								
							}
							
						}						
					}					
					$class->methods[] = $method;
				}
				else {
					$property = new JSBProperty;
					foreach ($parts as $part) {
						if (trim($part) !== '') {
							if ($property->visibility == '') $property->visibility = $part;
							else if (empty($property->type)) $property->type = $part;
							else $property->name = $part;
						}
					}					
					$class->properties[] = $property;
				}			
			}			
		}		
	}	
}

class JSBClass {
	public $methods = [];
	public $properties = [];
	public $class;
	public $extends;
	public $implements = [];
	public $startPosition;
	public $endPosition;
}


class JSBMethod {
	public $body;
	public $visibility;
	public $name;
	public $returnType;
	public $args = [];
}

class JSBProperty {
	public $name;
	public $type;
	public $visibility;
}


$file = new JSBFile($_GET['file']);

echo file_get_contents($bombJs);

